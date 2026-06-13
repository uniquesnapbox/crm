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
    this.qrCodeUpdatedAt = new Map();
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

    const initializePromise = (async () => {
      this.manualDisconnectedSessions.delete(key);
      this.status.set(key, "initializing");
      this.emit("whatsapp-status", {
        sessionKey: key,
        status: "initializing"
      });

      const client = new Client({
        authStrategy: new LocalAuth({
          clientId: key,
          dataPath: this.config.dataPath
        }),
        puppeteer: {
          headless: this.config.headless,
          executablePath: this.config.chromePath || undefined,
          args: ["--no-sandbox", "--disable-setuid-sandbox"]
        }
      });

      client.on("qr", (qr) => {
        this.status.set(key, "qr_required");
        this.qrCode.set(key, qr);
        this.qrCodeUpdatedAt.set(key, Date.now());
        logger.info("WhatsApp QR generated", { sessionKey: key });

        const payload = {
          sessionKey: key,
          qr,
          status: "qr_required"
        };
        this.emit("whatsapp-qr", payload);
        this.emit("whatsapp-status", payload);
      });

      client.on("authenticated", () => {
        this.status.set(key, "authenticated");
        logger.info("WhatsApp authenticated", { sessionKey: key });

        const payload = {
          sessionKey: key,
          status: "authenticated"
        };
        this.emit("whatsapp-authenticated", payload);
        this.emit("whatsapp-status", payload);
      });

      client.on("ready", () => {
        this.status.set(key, "ready");
        this.qrCode.delete(key);
        this.clearReconnectState(key);
        logger.info("WhatsApp client ready", { sessionKey: key });

        const payload = {
          sessionKey: key,
          status: "ready"
        };
        this.emit("whatsapp-ready", payload);
        this.emit("whatsapp-status", payload);
      });

      client.on("auth_failure", (message) => {
        this.status.set(key, "auth_failure");
        logger.error("WhatsApp auth failure", { sessionKey: key, message });

        const payload = {
          sessionKey: key,
          status: "auth_failure",
          message
        };
        this.emit("whatsapp-auth-failure", payload);
        this.emit("whatsapp-status", payload);
      });

      client.on("disconnected", (reason) => {
        this.status.set(key, "disconnected");
        logger.warn("WhatsApp disconnected", { sessionKey: key, reason });

        const payload = {
          sessionKey: key,
          status: "disconnected",
          reason
        };
        this.emit("whatsapp-disconnected", payload);
        this.emit("whatsapp-status", payload);

        if (this.manualDisconnectedSessions.has(key)) {
          this.clearReconnectState(key);
          logger.info("Skipping auto-reconnect due to manual disconnect", {
            sessionKey: key
          });
          return;
        }

        this.scheduleReconnect(key, reason);
      });

      this.clients.set(key, client);
      await client.initialize();

      this.emit("whatsapp-status", {
        sessionKey: key,
        status: this.getStatus(key)
      });

      return client;
    })();

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
    this.qrCodeUpdatedAt.delete(key);

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
    this.qrCodeUpdatedAt.delete(key);

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

  getQrUpdatedAt(sessionKey) {
    const key = sessionKey || this.config.defaultSession;
    return this.qrCodeUpdatedAt.get(key) || null;
  }

  async sendMessage({ to, message, channelKey, attachment = null }) {
    const key = channelKey || this.config.defaultSession;
    const client = await this.ensureClient(key);

    if (!client) {
      throw new Error(`No client initialized for session: ${key}`);
    }

    const status = this.getStatus(key);
    if (status !== "ready") {
      throw new Error(`Session ${key} not ready. Current status: ${status}`);
    }

    const chatId = this.toChatId(to);
    const media = await this.buildMediaFromAttachment(attachment);
    const text = String(message || "").trim();
    const hasMedia = Boolean(media);

    if (!text && !hasMedia) {
      throw new Error("Message or attachment is required");
    }

    const sentMessage = hasMedia
      ? await client.sendMessage(chatId, media, {
          caption: text || undefined,
          sendMediaAsDocument: attachment?.sendAsDocument !== false
        })
      : await client.sendMessage(chatId, text);

    return {
      id: sentMessage.id?._serialized || null,
      to: chatId,
      contentType: hasMedia ? "media" : "text",
      timestamp: sentMessage.timestamp || Date.now()
    };
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
      qrAvailable: Boolean(this.getQr(sessionKey))
    }));
  }
}

module.exports = WhatsAppManager;
