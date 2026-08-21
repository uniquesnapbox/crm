const fs = require("fs");
const path = require("path");
const { EventEmitter } = require("events");
const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");
const logger = require("./logger");

class WhatsAppManager extends EventEmitter {
  constructor(config) {
    super();
    this.config = config;
    this.clients = new Map();
    this.initializingClients = new Map();
    this.status = new Map();
    this.qrCode = new Map();
    this.qrGeneratedAt = new Map();
    this.reconnectTimers = new Map();
    this.reconnectAttempts = new Map();
    this.manualDisconnectedSessions = new Set();
  }

  async initAll() {
    for (const sessionKey of this.config.sessions) {
      await this.initClient(sessionKey);
    }
  }

  async initClient(sessionKey) {
    const key = sessionKey || this.config.defaultSession;

    if (this.clients.has(key)) {
      return this.clients.get(key);
    }

    if (this.initializingClients.has(key)) {
      return this.initializingClients.get(key);
    }

    const initializePromise = this.initializeClientWithRetry(key);

    this.initializingClients.set(key, initializePromise);
    try {
      return await initializePromise;
    } catch (error) {
      this.clients.delete(key);
      this.status.set(key, "failed");
      this.emit("whatsapp-status", {
        sessionKey: key,
        status: "failed"
      });
      throw error;
    } finally {
      this.initializingClients.delete(key);
    }
  }

  async initializeClientWithRetry(key) {
    const attempts = 3;
    let lastError = null;

    for (let attempt = 1; attempt <= attempts; attempt++) {
      this.manualDisconnectedSessions.delete(key);
      this.status.set(key, "initializing");
      this.emit("whatsapp-status", {
        sessionKey: key,
        status: "initializing"
      });

      const client = this.createClient(key);
      this.clients.set(key, client);

      try {
        const initialization = client.initialize();
        await Promise.race([
          initialization,
          this.waitForBootstrapState(key, 90000)
        ]);

        initialization.catch((error) => {
          if (this.clients.get(key) !== client) {
            return;
          }

          logger.error("WhatsApp client failed after bootstrap", {
            sessionKey: key,
            error: error.message
          });
          this.status.set(key, "failed");
          this.scheduleReconnect(key, "post_bootstrap_failure");
        });

        return client;
      } catch (error) {
        lastError = error;
        logger.warn("WhatsApp client initialization attempt failed", {
          sessionKey: key,
          attempt,
          error: error.message
        });

        await this.destroyClient(key, { emitDestroyedStatus: false });
        await this.cleanupSessionArtifacts(key);

        if (!this.isRetryableBootstrapError(error) || attempt === attempts) {
          break;
        }

        await this.sleep(this.computeReconnectDelay(attempt));
      }
    }

    throw lastError || new Error(`Failed to initialize WhatsApp session ${key}`);
  }

  createClient(key) {
    const client = new Client({
      authStrategy: new LocalAuth({
        clientId: key,
        dataPath: this.config.dataPath
      }),
      puppeteer: {
        headless: "new",
        args: [
          "--no-sandbox",
          "--disable-setuid-sandbox",
          "--disable-dev-shm-usage",
          "--disable-gpu",
          "--no-zygote",
          "--no-first-run",
          "--disable-site-isolation-trials",
          "--disable-features=IsolateOrigins,site-per-process"
        ]
      }
    });

    client.on("qr", (qr) => {
      this.qrCode.set(key, qr);
      this.qrGeneratedAt.set(key, new Date().toISOString());
      this.status.set(key, "qr_required");
      logger.info("WhatsApp QR generated", { sessionKey: key });

      const payload = {
        sessionKey: key,
        status: "qr_required"
      };

      this.emit("whatsapp-qr", {
        ...payload,
        qr
      });
      this.emit("whatsapp-status", payload);
    });

    client.on("authenticated", () => {
      this.status.set(key, "authenticated");
      logger.info("WhatsApp authenticated", { sessionKey: key });
      this.emit("whatsapp-authenticated", {
        sessionKey: key,
        status: "authenticated"
      });
      this.emit("whatsapp-status", {
        sessionKey: key,
        status: "authenticated"
      });
    });

    client.on("ready", () => {
      this.status.set(key, "ready");
      this.qrCode.delete(key);
      this.qrGeneratedAt.delete(key);
      this.clearReconnectState(key);
      logger.info("WhatsApp client ready", { sessionKey: key });

      const payload = {
        sessionKey: key,
        status: "ready"
      };

      this.emit("whatsapp-ready", payload);
    });

    client.on("auth_failure", (message) => {
      this.status.set(key, "failed");
      logger.error("WhatsApp auth failure", { sessionKey: key, message });
      this.emit("whatsapp-auth-failure", {
        sessionKey: key,
        status: "failed",
        error: message || "Authentication failed"
      });
      this.scheduleReconnect(key, "auth_failure");
    });

    client.on("disconnected", (reason) => {
      this.qrCode.delete(key);
      this.qrGeneratedAt.delete(key);

      if (this.manualDisconnectedSessions.has(key)) {
        this.status.set(key, "disconnected");
        return;
      }

      this.status.set(key, "disconnected");
      logger.warn("WhatsApp disconnected", { sessionKey: key, reason });
      this.emit("whatsapp-disconnected", {
        sessionKey: key,
        status: "disconnected",
        reason: reason || "unknown"
      });
      this.scheduleReconnect(key, reason || "disconnected");
    });

    client.on("change_state", (state) => {
      if (state && typeof state === "string") {
        logger.info("WhatsApp client state changed", {
          sessionKey: key,
          state
        });
      }
    });

    client.on("message", async (message) => {
      try {
        this.emit("whatsapp-message", await this.serializeMessage(message, key, true));
      } catch (error) {
        logger.warn("Unable to process incoming WhatsApp message", {
          sessionKey: key,
          error: error.message
        });
      }
    });

    // `message` only covers incoming traffic. Capture messages sent from another
    // linked WhatsApp device as well so CRM stays in sync with the real chat.
    client.on("message_create", async (message) => {
      try {
        const payload = await this.serializeMessage(message, key, true);
        if (payload.fromMe) {
          this.emit("whatsapp-message", payload);
        }
      } catch (error) {
        logger.warn("Unable to process outgoing WhatsApp message", {
          sessionKey: key,
          error: error.message
        });
      }
    });

    return client;
  }

  async cleanupSessionArtifacts(sessionKey) {
    const sessionDir = path.join(this.config.dataPath, `session-${sessionKey}`);
    const targets = [
      path.join(sessionDir, "DevToolsActivePort"),
      path.join(sessionDir, "SingletonCookie"),
      path.join(sessionDir, "SingletonLock"),
      path.join(sessionDir, "SingletonSocket"),
      path.join(sessionDir, "LOCK"),
      path.join(sessionDir, "Default", "LOCK"),
      path.join(sessionDir, "Default", "DevToolsActivePort")
    ];

    for (const filePath of targets) {
      try {
        if (fs.existsSync(filePath)) {
          fs.rmSync(filePath, { force: true, recursive: false });
        }
      } catch (_) {
        // no-op
      }
    }
  }

  isRetryableBootstrapError(error) {
    const message = String(error?.message || error || "").toLowerCase();

    return [
      "execution context was destroyed",
      "cannot find context with specified id",
      "target closed",
      "detached frame",
      "protocol error",
      "navigation",
      "session closed"
    ].some((needle) => message.includes(needle));
  }

  sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  withTimeout(promise, timeoutMs, message) {
    let timer;

    const timeoutPromise = new Promise((_, reject) => {
      timer = setTimeout(() => reject(new Error(message)), timeoutMs);
    });

    return Promise.race([promise, timeoutPromise]).finally(() => {
      if (timer) {
        clearTimeout(timer);
      }
    });
  }

  async waitForBootstrapState(sessionKey, timeoutMs = 90000) {
    const key = sessionKey || this.config.defaultSession;
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeoutMs) {
      const status = this.getStatus(key);
      if (["qr_required", "authenticated", "ready"].includes(status)) {
        return true;
      }
      if (status === "failed") {
        throw new Error(`WhatsApp client bootstrap failed for session ${key}`);
      }

      await this.sleep(250);
    }

    throw new Error(`WhatsApp client bootstrap timed out for session ${key}`);
  }

  scheduleReconnect(sessionKey, reason = "unknown") {
    const key = sessionKey || this.config.defaultSession;

    if (this.reconnectTimers.has(key)) {
      return;
    }

    const nextAttempt = (this.reconnectAttempts.get(key) || 0) + 1;
    this.reconnectAttempts.set(key, nextAttempt);
    const delayMs = this.computeReconnectDelay(nextAttempt);

    logger.info("Scheduling WhatsApp reconnect", {
      sessionKey: key,
      reason,
      attempt: nextAttempt,
      delayMs
    });

    const timer = setTimeout(async () => {
      this.reconnectTimers.delete(key);

      try {
        await this.reconnect(key, reason);
      } catch (error) {
        logger.error("WhatsApp reconnect failed", {
          sessionKey: key,
          reason,
          attempt: nextAttempt,
          error: error.message
        });

        this.scheduleReconnect(key, reason);
      }
    }, delayMs);

    this.reconnectTimers.set(key, timer);
  }

  clearReconnectState(sessionKey) {
    const key = sessionKey || this.config.defaultSession;

    if (this.reconnectTimers.has(key)) {
      clearTimeout(this.reconnectTimers.get(key));
      this.reconnectTimers.delete(key);
    }

    this.reconnectAttempts.set(key, 0);
  }

  computeReconnectDelay(attemptNumber) {
    const baseDelay = Math.max(1000, Number(this.config.reconnectBaseDelayMs || 5000));
    const maxDelay = Math.max(baseDelay, Number(this.config.reconnectMaxDelayMs || 60000));
    const exponent = Math.max(0, attemptNumber - 1);

    return Math.min(maxDelay, baseDelay * Math.pow(2, exponent));
  }

  async disconnectSession(sessionKey, options = {}) {
    const key = sessionKey || this.config.defaultSession;
    const shouldLogout = options.logout !== false;
    const client = this.clients.get(key);

    this.manualDisconnectedSessions.add(key);
    this.clearReconnectState(key);
    this.qrCode.delete(key);
    this.qrGeneratedAt.delete(key);

    if (!client) {
      const payload = {
        sessionKey: key,
        status: "disconnected",
        reason: "manual_disconnect"
      };
      this.status.set(key, "disconnected");
      this.emit("whatsapp-disconnected", payload);
      this.emit("whatsapp-status", payload);
      return;
    }

    if (shouldLogout && typeof client.logout === "function") {
      try {
        await client.logout();
      } catch (error) {
        logger.warn("WhatsApp logout failed during manual disconnect", {
          sessionKey: key,
          error: error.message
        });
      }
    }

    try {
      await client.destroy();
    } catch (error) {
      logger.warn("WhatsApp destroy failed during manual disconnect", {
        sessionKey: key,
        error: error.message
      });
    }

    this.clients.delete(key);
    this.initializingClients.delete(key);

    const payload = {
      sessionKey: key,
      status: "disconnected",
      reason: "manual_disconnect"
    };
    this.status.set(key, "disconnected");
    this.emit("whatsapp-disconnected", payload);
    this.emit("whatsapp-status", payload);
  }

  async reconnect(sessionKey, reason = "unknown") {
    const key = sessionKey || this.config.defaultSession;
    const currentStatus = this.getStatus(key);

    if (currentStatus === "ready") {
      return this.clients.get(key) || null;
    }

    this.manualDisconnectedSessions.delete(key);
    await this.destroyClient(key, { emitDestroyedStatus: false });
    logger.info("Reinitializing WhatsApp client", {
      sessionKey: key,
      reason
    });
    return this.initClient(key);
  }

  async refreshQr(sessionKey) {
    const key = sessionKey || this.config.defaultSession;
    const currentStatus = this.getStatus(key);
    const currentQr = this.getQr(key);
    const generatedAt = this.qrGeneratedAt.get(key);
    const qrAgeMs = generatedAt ? Date.now() - Date.parse(generatedAt) : Number.POSITIVE_INFINITY;

    if (["ready", "authenticated"].includes(currentStatus)) {
      return this.getQrInfo(key);
    }

    // Old dashboard tabs may still poll with refresh=1. Never recycle a fresh QR mid-scan.
    if (currentStatus === "qr_required" && currentQr && qrAgeMs < 60000) {
      return this.getQrInfo(key);
    }

    await this.destroyClient(key, { emitDestroyedStatus: false });
    await this.removeUnauthenticatedSession(key);
    this.manualDisconnectedSessions.delete(key);
    this.status.set(key, "initializing");

    this.initClient(key).catch((error) => {
      logger.error("WhatsApp QR client initialization failed", {
        sessionKey: key,
        error: error.message
      });
    });
    await this.waitForQrOrReady(key, 30000);
    return this.getQrInfo(key);
  }

  async waitForQrOrReady(sessionKey, timeoutMs = 30000) {
    const key = sessionKey || this.config.defaultSession;
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeoutMs) {
      const status = this.getStatus(key);
      if (status === "ready" || status === "authenticated" || this.getQr(key)) {
        return true;
      }
      if (status === "failed") {
        throw new Error(`Unable to generate QR for session ${key}`);
      }

      await this.sleep(250);
    }

    throw new Error(`Timed out waiting for a fresh QR for session ${key}`);
  }

  async removeUnauthenticatedSession(sessionKey) {
    const sessionDir = path.resolve(this.config.dataPath, `session-${sessionKey}`);
    const dataRoot = `${path.resolve(this.config.dataPath)}${path.sep}`;

    if (!sessionDir.startsWith(dataRoot)) {
      throw new Error("Invalid WhatsApp session path");
    }

    try {
      await fs.promises.rm(sessionDir, { recursive: true, force: true });
    } catch (error) {
      logger.warn("Unable to clear unauthenticated WhatsApp session", {
        sessionKey,
        error: error.message
      });
      throw error;
    }
  }

  async destroyClient(sessionKey, options = {}) {
    const key = sessionKey || this.config.defaultSession;
    const emitDestroyedStatus = options.emitDestroyedStatus !== false;

    if (this.reconnectTimers.has(key)) {
      clearTimeout(this.reconnectTimers.get(key));
      this.reconnectTimers.delete(key);
    }

    const client = this.clients.get(key);
    if (!client) {
      return;
    }

    try {
      await client.destroy();
    } catch (_) {
      // no-op
    }

    this.clients.delete(key);
    this.qrCode.delete(key);
    this.qrGeneratedAt.delete(key);

    if (emitDestroyedStatus) {
      this.status.set(key, "destroyed");
      this.emit("whatsapp-status", {
        sessionKey: key,
        status: "destroyed"
      });
    }
  }

  getClient(sessionKey) {
    const key = sessionKey || this.config.defaultSession;
    return this.clients.get(key);
  }

  async ensureClient(sessionKey) {
    const key = sessionKey || this.config.defaultSession;
    this.manualDisconnectedSessions.delete(key);

    if (!this.clients.has(key) && !this.initializingClients.has(key)) {
      await this.initClient(key);
    }

    if (this.initializingClients.has(key)) {
      return this.initializingClients.get(key);
    }

    return this.getClient(key);
  }

  getStatus(sessionKey) {
    const key = sessionKey || this.config.defaultSession;
    return this.status.get(key) || "unknown";
  }

  getQr(sessionKey) {
    const key = sessionKey || this.config.defaultSession;
    return this.qrCode.get(key) || null;
  }

  getQrInfo(sessionKey) {
    const key = sessionKey || this.config.defaultSession;

    return {
      sessionKey: key,
      status: this.getStatus(key),
      qr: this.getQr(key),
      generatedAt: this.qrGeneratedAt.get(key) || null
    };
  }

  async sendMessage({ to, message, channelKey, attachment = null }) {
    const key = channelKey || this.config.defaultSession;
    const chatId = this.toChatId(to);
    const media = await this.buildMediaFromAttachment(attachment);
    const text = String(message || "").trim();
    const hasMedia = Boolean(media);

    if (!text && !hasMedia) {
      throw new Error("Message or attachment is required");
    }

    const sendWithClient = async (client) => {
      if (!client) {
        throw new Error(`No client initialized for session: ${key}`);
      }

      const status = this.getStatus(key);
      if (status !== "ready") {
        throw new Error(`Session ${key} not ready. Current status: ${status}`);
      }

      const sentMessage = hasMedia
        ? await client.sendMessage(chatId, media, {
            caption: text || undefined,
            sendMediaAsDocument: attachment?.sendAsDocument !== false
          })
        : await client.sendMessage(chatId, text);

      return {
        id: sentMessage?.id?._serialized || sentMessage?.id || null,
        to: chatId,
        contentType: hasMedia ? "media" : "text",
        timestamp: sentMessage?.timestamp || Date.now()
      };
    };

    try {
      const client = await this.ensureClient(key);
      return await sendWithClient(client);
    } catch (error) {
      const transientSendError =
        /detached Frame/i.test(error.message || "") ||
        /Execution context was destroyed/i.test(error.message || "") ||
        /Target closed/i.test(error.message || "");

      if (!transientSendError) {
        throw error;
      }

      logger.warn("WhatsApp send hit a transient browser error; retrying once", {
        sessionKey: key,
        error: error.message
      });

      await this.destroyClient(key, { emitDestroyedStatus: false });
      const refreshedClient = await this.ensureClient(key);
      await this.waitForStatus(key, "ready", 60000);
      return await sendWithClient(refreshedClient);
    }
  }

  async getMessageHistory({ to, channelKey, limit = 100 }) {
    const key = channelKey || this.config.defaultSession;
    const client = await this.ensureClient(key);

    if (!client || this.getStatus(key) !== "ready") {
      throw new Error(`Session ${key} not ready`);
    }

    const chat = await this.findChatByPhone(client, to);
    if (chat) {
      const messages = await chat.fetchMessages({
        limit: Math.max(1, Math.min(200, Number(limit) || 100))
      });
      const serialized = await Promise.all(
        messages.map((message) => this.serializeMessage(message, key, false))
      );

      if (serialized.length) {
        return serialized;
      }
    }

    const storedMessages = await this.getStoredMessagesByPhone(client, to, key, limit);
    const storedChatId = storedMessages.find((message) => message.chatId)?.chatId;

    if (storedChatId) {
      try {
        const storedChat = await client.getChatById(storedChatId);
        const fetchedMessages = await storedChat.fetchMessages({
          limit: Math.max(1, Math.min(200, Number(limit) || 100))
        });
        const serialized = await Promise.all(
          fetchedMessages.map((message) => this.serializeMessage(message, key, false))
        );

        if (serialized.length) {
          return serialized;
        }
      } catch (error) {
        logger.debug("Unable to expand WhatsApp history from matched LID chat", {
          sessionKey: key,
          chatId: storedChatId,
          error: error.message
        });
      }
    }

    return storedMessages;
  }

  async getStoredMessagesByPhone(client, rawNumber, sessionKey, limit) {
    const digits = String(rawNumber || "").replace(/\D/g, "");
    const suffix = digits.slice(-10);

    return client.pupPage.evaluate(({ phoneSuffix, session, maxMessages }) => {
      const collections = window.require("WAWebCollections");
      const apiContact = window.require("WAWebApiContact");
      const normalize = (value) => String(value || "").replace(/\D/g, "");
      const serializedId = (value) => value?._serialized || String(value || "");
      const models = collections.Msg?.getModelsArray?.()
        || window.Store?.Msg?.getModelsArray?.()
        || [];
      const results = [];

      const phoneValues = (wid) => {
        const values = [serializedId(wid), wid?.user, wid?.phoneNumber?.user];
        try {
          const mapped = apiContact.getPhoneNumber(wid);
          values.push(serializedId(mapped), mapped?.user);
        } catch (_) {
          // A phone-number WID does not need LID conversion.
        }
        return values;
      };

      for (const model of models) {
        const id = model?.id || model?.__x_id || {};
        const fromMe = Boolean(id?.fromMe ?? model?.fromMe ?? model?.__x_fromMe);
        const remote = id?.remote || model?.from || model?.to || model?.__x_from || model?.__x_to;
        const matches = phoneValues(remote).some((value) => {
          const normalized = normalize(value);
          return normalized && normalized.slice(-10) === phoneSuffix;
        });

        if (!matches) {
          continue;
        }

        const remoteId = serializedId(remote);
        const messageId = serializedId(id)
          || [fromMe ? "true" : "false", remoteId, id?.id || model?.t || Date.now()].join("_");
        results.push({
          sessionKey: session,
          messageId,
          from: serializedId(model?.from || model?.__x_from || (!fromMe ? remote : "")),
          to: serializedId(model?.to || model?.__x_to || (fromMe ? remote : "")),
          body: String(model?.body || model?.__x_body || ""),
          contentType: String(model?.type || model?.__x_type || "text"),
          timestamp: Number(model?.t || model?.timestamp || model?.__x_t || Math.floor(Date.now() / 1000)),
          fromMe,
          chatId: remoteId,
          hasMedia: Boolean(model?.isMedia || model?.__x_isMedia),
          media: null
        });
      }

      return results
        .sort((a, b) => a.timestamp - b.timestamp)
        .slice(-Math.max(1, Math.min(200, Number(maxMessages) || 100)));
    }, { phoneSuffix: suffix, session: sessionKey, maxMessages: limit });
  }

  async findChatByPhone(client, rawNumber) {
    const digits = String(rawNumber || "").replace(/\D/g, "");
    const comparable = digits.slice(-10);
    const candidateIds = [this.toChatId(digits)];

    try {
      const resolvedIds = await client.pupPage.evaluate(async (phone) => {
        const result = await window.WWebJS.enforceLidAndPnRetrieval(`${phone}@c.us`);
        return [result?.lid?._serialized, result?.phone?._serialized].filter(Boolean);
      }, digits);

      for (const resolvedId of resolvedIds) {
        if (!candidateIds.includes(resolvedId)) {
          candidateIds.unshift(resolvedId);
        }
      }
    } catch (error) {
      logger.debug("Unable to resolve WhatsApp LID", {
        phone: digits,
        error: error.message
      });
    }

    try {
      const registeredId = await client.getNumberId(digits);
      const serialized = registeredId?._serialized || String(registeredId || "");
      if (serialized && !candidateIds.includes(serialized)) {
        candidateIds.unshift(serialized);
      }
    } catch (error) {
      logger.debug("Unable to resolve registered WhatsApp number", {
        phone: digits,
        error: error.message
      });
    }

    for (const candidateId of candidateIds) {
      try {
        const chat = await client.getChatById(candidateId);
        if (chat) {
          return chat;
        }
      } catch (_) {
        // Fall back to loaded chat/contact matching for WhatsApp LID accounts.
      }
    }

    const matchedChatId = await client.pupPage.evaluate((phoneSuffix) => {
      const collections = window.require("WAWebCollections");
      const apiContact = window.require("WAWebApiContact");
      const chats = collections.Chat.getModelsArray();
      const normalize = (value) => String(value || "").replace(/\D/g, "");
      const matches = (value) => {
        const normalized = normalize(value);
        return normalized && normalized.slice(-10) === phoneSuffix;
      };
      const idValues = (value) => [
        value?._serialized,
        value?.user,
        value?.phoneNumber?._serialized,
        value?.phoneNumber?.user,
        value?.number
      ];

      for (const chat of chats) {
        if (chat?.isGroup) {
          continue;
        }

        let mappedPhone = null;
        try {
          mappedPhone = apiContact.getPhoneNumber(chat?.id);
        } catch (_) {
          // The chat may already use a phone-number WID.
        }

        const candidates = [
          ...idValues(chat?.id),
          ...idValues(chat?.contact),
          ...idValues(chat?.contact?.id),
          ...idValues(chat?.contact?.phoneNumber),
          ...idValues(mappedPhone)
        ];

        if (candidates.some(matches)) {
          return chat.id?._serialized || null;
        }
      }

      return null;
    }, comparable);

    if (matchedChatId) {
      try {
        return await client.getChatById(matchedChatId);
      } catch (_) {
        return null;
      }
    }

    return null;
  }

  async serializeMessage(message, sessionKey, includeMedia) {
    const raw = message?._data || {};
    const read = (...readers) => {
      for (const reader of readers) {
        try {
          const value = reader();
          if (value !== undefined && value !== null) {
            return value;
          }
        } catch (_) {
          // WhatsApp occasionally throws from lazy model getters; use raw data.
        }
      }
      return null;
    };
    const id = read(() => message.id, () => raw.id) || {};
    const fromMe = Boolean(read(() => message.fromMe, () => id.fromMe, () => raw.fromMe));
    const remote = read(() => id.remote?._serialized, () => id.remote, () => raw.from, () => raw.to);
    const messageId = read(() => id._serialized, () => raw.id?._serialized)
      || [fromMe ? "true" : "false", String(remote || ""), String(id.id || raw.t || Date.now())].join("_");
    const from = read(() => message.from, () => raw.from, () => !fromMe ? remote : null) || "";
    const to = read(() => message.to, () => raw.to, () => fromMe ? remote : null) || "";
    const payload = {
      sessionKey,
      messageId: String(messageId),
      from: String(from),
      to: String(to),
      body: String(read(() => message.body, () => raw.body) || ""),
      contentType: String(read(() => message.type, () => raw.type) || "text"),
      timestamp: Number(read(() => message.timestamp, () => raw.t) || Math.floor(Date.now() / 1000)),
      fromMe,
      chatId: String(remote || (fromMe ? to : from)),
      hasMedia: Boolean(read(() => message.hasMedia, () => raw.isMedia)),
      media: null
    };

    if (!includeMedia || !payload.hasMedia) {
      return payload;
    }

    let media = null;
    try {
      media = await message.downloadMedia();
    } catch (error) {
      logger.warn("Unable to download incoming WhatsApp media", {
        sessionKey,
        messageId: payload.messageId,
        error: error.message
      });
      return payload;
    }
    const mimeType = String(media?.mimetype || "").toLowerCase();
    const size = media?.data ? Buffer.byteLength(media.data, "base64") : 0;

    if (media?.data && mimeType.startsWith("image/") && size <= 5 * 1024 * 1024) {
      payload.media = {
        data: media.data,
        mimeType,
        fileName: media.filename || `whatsapp-${payload.messageId || Date.now()}`
      };
    }

    return payload;
  }

  async waitForStatus(sessionKey, desiredStatus, timeoutMs = 30000) {
    const key = sessionKey || this.config.defaultSession;
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeoutMs) {
      if (this.getStatus(key) === desiredStatus) {
        return true;
      }

      await new Promise((resolve) => setTimeout(resolve, 1000));
    }

    throw new Error(`Session ${key} not ready. Current status: ${this.getStatus(key)}`);
  }

  async buildMediaFromAttachment(attachment) {
    if (!attachment || typeof attachment !== "object") {
      return null;
    }

    const inlineData = String(attachment.data || "").trim();
    const attachmentUrl = String(attachment.url || "").trim();
    const mimeType = String(attachment.mimeType || "application/octet-stream").trim();
    const fileName = String(attachment.fileName || "receipt.pdf").trim();

    if (inlineData) {
      const normalizedData = this.stripDataUrlPrefix(inlineData);
      return new MessageMedia(mimeType, normalizedData, fileName);
    }

    if (attachmentUrl) {
      return MessageMedia.fromUrl(attachmentUrl, {
        unsafeMime: true,
        filename: fileName || undefined
      });
    }

    return null;
  }

  stripDataUrlPrefix(value) {
    const raw = String(value || "").trim();
    const match = raw.match(/^data:.*?;base64,(.*)$/i);
    if (match && match[1]) {
      return match[1];
    }

    return raw;
  }

  toChatId(rawNumber) {
    const digitsOnly = String(rawNumber || "").replace(/\D/g, "");
    if (!digitsOnly || digitsOnly.length < 8) {
      throw new Error("Invalid recipient number");
    }
    return `${digitsOnly}@c.us`;
  }

  sessionsSummary() {
    const uniqueSessionKeys = Array.from(
      new Set([...this.config.sessions, ...Array.from(this.clients.keys())])
    );

    return uniqueSessionKeys.map((sessionKey) => ({
      sessionKey,
      status: this.getStatus(sessionKey),
      qrAvailable: Boolean(this.getQr(sessionKey)),
      qrGeneratedAt: this.qrGeneratedAt.get(sessionKey) || null
    }));
  }
}

module.exports = WhatsAppManager;
