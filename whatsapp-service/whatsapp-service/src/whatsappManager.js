const fs = require("fs");
const path = require("path");
const { EventEmitter } = require("events");
const { Client, LocalAuth, MessageMedia } = require("./whatsappWebPatch");
const logger = require("./logger");

class WhatsAppManager extends EventEmitter {
  constructor(config) {
    super();
    this.config = config;
    this.shuttingDown = false;
    this.clients = new Map();
    this.initializingClients = new Map();
    this.recoveringClients = new Map();
    this.readyReconcileTimers = new Map();
    this.status = new Map();
    this.qrCode = new Map();
    this.qrGeneratedAt = new Map();
    this.reconnectTimers = new Map();
    this.reconnectAttempts = new Map();
    this.manualDisconnectedSessions = new Set();
  }

  async initAll() {
    if (this.shuttingDown) {
      throw new Error("WhatsApp manager is shutting down");
    }

    for (const sessionKey of this.config.sessions) {
      await this.initClient(sessionKey);
    }
  }

  async initClient(sessionKey) {
    if (this.shuttingDown) {
      throw new Error("WhatsApp manager is shutting down");
    }

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
      if (this.shuttingDown) {
        throw new Error("WhatsApp manager is shutting down");
      }

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

        this.scheduleReadyReconciliation(key, client, "bootstrap");

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
      webVersion: this.config.webVersion,
      webVersionCache: this.config.webVersionCache,
      userAgent: this.config.browserUserAgent,
      deviceName: this.config.deviceName,
      browserName: this.config.browserName,
      puppeteer: {
        headless: this.config.headless ? "new" : false,
        executablePath: this.config.browserExecutablePath || undefined,
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
      if (this.shuttingDown) {
        return;
      }

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
      this.scheduleReadyReconciliation(key, client, "qr");
    });

    client.on("authenticated", () => {
      if (this.shuttingDown) {
        return;
      }

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
      this.scheduleReadyReconciliation(key, client, "authenticated");
    });

    client.on("ready", () => {
      if (this.shuttingDown) {
        return;
      }

      this.status.set(key, "ready");
      this.qrCode.delete(key);
      this.qrGeneratedAt.delete(key);
      this.clearReconnectState(key);
      this.clearReadyReconciliation(key);
      logger.info("WhatsApp client ready", { sessionKey: key });

      const payload = {
        sessionKey: key,
        status: "ready"
      };

      this.emit("whatsapp-ready", payload);
    });

    client.on("auth_failure", (message) => {
      if (this.shuttingDown) {
        return;
      }

      this.status.set(key, "failed");
      this.clearReadyReconciliation(key);
      logger.error("WhatsApp auth failure", { sessionKey: key, message });
      this.emit("whatsapp-auth-failure", {
        sessionKey: key,
        status: "failed",
        error: message || "Authentication failed"
      });
      this.scheduleReconnect(key, "auth_failure");
    });

    client.on("disconnected", (reason) => {
      if (this.shuttingDown) {
        return;
      }

      this.qrCode.delete(key);
      this.qrGeneratedAt.delete(key);
      this.clearReadyReconciliation(key);

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
      if (this.shuttingDown) {
        return;
      }

      if (state && typeof state === "string") {
        logger.info("WhatsApp client state changed", {
          sessionKey: key,
          state
        });

        if (String(state).toUpperCase() === "CONNECTED") {
          this.scheduleReadyReconciliation(key, client, "state_connected");
        }
      }
    });

    client.on("message", async (message) => {
      if (this.shuttingDown) {
        return;
      }

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
      if (this.shuttingDown) {
        return;
      }

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
    const lockNames = new Set([
      "devtoolsactiveport",
      "singletoncookie",
      "singletonlock",
      "singletonsocket",
      "lock"
    ]);

    const removeLockFiles = async (currentDir) => {
      let entries = [];

      try {
        entries = await fs.promises.readdir(currentDir, { withFileTypes: true });
      } catch (_) {
        return;
      }

      for (const entry of entries) {
        const filePath = path.join(currentDir, entry.name);

        if (entry.isDirectory()) {
          await removeLockFiles(filePath);
          continue;
        }

        const normalizedName = String(entry.name || "").toLowerCase();
        if (!lockNames.has(normalizedName)) {
          continue;
        }

        try {
          await fs.promises.rm(filePath, { force: true });
        } catch (_) {
          // no-op
        }
      }
    };

    await removeLockFiles(sessionDir);
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
      "session closed",
      "browser is already running",
      "already running for profile",
      "singletonlock",
      "singletoncookie",
      "singletonsocket",
      "devtoolsactiveport",
      "profile in use",
      "failed to launch the browser process",
      "cannot acquire lock",
      "chrome not reachable"
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
      if (this.shuttingDown) {
        throw new Error("WhatsApp manager is shutting down");
      }

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
    if (this.shuttingDown) {
      return;
    }

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

  clearReadyReconciliation(sessionKey) {
    const key = sessionKey || this.config.defaultSession;

    if (this.readyReconcileTimers.has(key)) {
      clearTimeout(this.readyReconcileTimers.get(key));
      this.readyReconcileTimers.delete(key);
    }
  }

  hasPersistedSessionArtifacts(sessionKey) {
    const key = sessionKey || this.config.defaultSession;
    const sessionDir = path.join(this.config.dataPath, `session-${key}`, "Default");
    const candidateFiles = [
      "Cookies",
      "Cookies-journal",
      "Login Data",
      "Login Data-journal",
      "Network Persistent State",
      "Preferences",
      "Secure Preferences"
    ];

    try {
      return candidateFiles.some((fileName) => {
        const filePath = path.join(sessionDir, fileName);
        if (!fs.existsSync(filePath)) {
          return false;
        }
        const stats = fs.statSync(filePath);
        return stats.isFile() && stats.size > 0;
      });
    } catch (_) {
      return false;
    }
  }

  async inspectClientReadiness(client) {
    const result = {
      waState: null,
      page: null,
      ready: false
    };

    if (!client || !client.pupPage) {
      return result;
    }

    try {
      if (typeof client.pupPage.isClosed === "function" && client.pupPage.isClosed()) {
        return result;
      }
    } catch (_) {
      return result;
    }

    try {
      const state = await client.getState();
      result.waState = String(state || "").toUpperCase();
    } catch (error) {
      result.waState = `ERROR:${String(error?.message || error || "")}`;
    }

    try {
      result.page = await client.pupPage.evaluate(() => ({
        url: String(location.href || ""),
        title: String(document.title || ""),
        readyState: String(document.readyState || ""),
        hasWWebJS: Boolean(window.WWebJS),
        hasStore: Boolean(window.Store),
        bodyText: String(document.body?.innerText || "").slice(0, 1000)
      }));
    } catch (error) {
      result.page = {
        error: String(error?.message || error || "")
      };
    }

    const connectedStates = new Set(["CONNECTED", "OPEN", "PAIRING"]);
    const stateLooksConnected = connectedStates.has(result.waState);
    const page = result.page || {};
    const pageLooksLoaded = page.readyState === "complete" && (page.hasWWebJS || page.hasStore);
    const pageText = `${page.title || ""}\n${page.bodyText || ""}`.toLowerCase();
    const pageLooksLoggedOut = /scan|qr code|link with phone|log in|sign in/.test(pageText);

    result.ready = Boolean(stateLooksConnected && pageLooksLoaded && !pageLooksLoggedOut);
    return result;
  }

  markSessionReady(sessionKey, source, inspection = null) {
    const key = sessionKey || this.config.defaultSession;
    const previousStatus = this.getStatus(key);

    this.status.set(key, "ready");
    this.qrCode.delete(key);
    this.qrGeneratedAt.delete(key);
    this.clearReconnectState(key);
    this.clearReadyReconciliation(key);

    logger.info("WhatsApp session marked ready", {
      sessionKey: key,
      source,
      previousStatus,
      waState: inspection?.waState || null
    });

    const payload = {
      sessionKey: key,
      status: "ready",
      source,
      waState: inspection?.waState || null
    };

    this.emit("whatsapp-ready", payload);
    this.emit("whatsapp-status", payload);
  }

  scheduleReadyReconciliation(sessionKey, client, reason = "unknown") {
    if (this.shuttingDown) {
      return;
    }

    const key = sessionKey || this.config.defaultSession;

    if (this.readyReconcileTimers.has(key) || this.manualDisconnectedSessions.has(key)) {
      return;
    }

    let attempt = 0;
    const maxAttempts = 24;
    let detachedFrameHits = 0;

    const probe = async () => {
      this.readyReconcileTimers.delete(key);

      if (this.manualDisconnectedSessions.has(key) || this.getStatus(key) === "ready") {
        return;
      }

      const activeClient = client || this.clients.get(key);
      if (!activeClient) {
        return;
      }

      try {
        const inspection = await this.inspectClientReadiness(activeClient);
        logger.info("WhatsApp readiness probe", {
          sessionKey: key,
          reason,
          attempt: attempt + 1,
          waState: inspection.waState,
          ready: inspection.ready
        });

        if (inspection.ready) {
          this.markSessionReady(key, `probe:${reason}`, inspection);
          return;
        }

        const inspectionText = `${inspection.waState || ""}\n${inspection?.page?.error || ""}`.toLowerCase();
        if (inspectionText.includes("detached frame")) {
          detachedFrameHits += 1;
        }

        if (
          detachedFrameHits >= 2 &&
          this.hasPersistedSessionArtifacts(key) &&
          !this.manualDisconnectedSessions.has(key) &&
          this.getStatus(key) !== "ready"
        ) {
          logger.warn("WhatsApp readiness probe falling back to persisted auth artifacts", {
            sessionKey: key,
            reason,
            attempt: attempt + 1,
            detachedFrameHits
          });
          this.markSessionReady(key, `probe:${reason}:persisted_auth`, inspection);
          return;
        }
      } catch (error) {
        logger.debug("WhatsApp readiness probe failed", {
          sessionKey: key,
          reason,
          attempt: attempt + 1,
          error: error.message
        });
      }

      attempt += 1;
      if (attempt >= maxAttempts || this.manualDisconnectedSessions.has(key) || this.getStatus(key) === "ready") {
        return;
      }

      const nextDelay = Math.min(15000, 2000 * Math.pow(1.4, attempt));
      const timer = setTimeout(probe, nextDelay);
      timer.unref?.();
      this.readyReconcileTimers.set(key, timer);
    };

    const initialDelay = reason === "qr" ? 4000 : 2000;
    const timer = setTimeout(probe, initialDelay);
    timer.unref?.();
    this.readyReconcileTimers.set(key, timer);
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
    this.clearReadyReconciliation(key);
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
    if (this.shuttingDown) {
      return null;
    }

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
    if (this.shuttingDown) {
      throw new Error("WhatsApp manager is shutting down");
    }

    const key = sessionKey || this.config.defaultSession;
    const currentStatus = this.getStatus(key);
    const currentQr = this.getQr(key);
    const generatedAt = this.qrGeneratedAt.get(key);
    const qrAgeMs = generatedAt ? Date.now() - Date.parse(generatedAt) : Number.POSITIVE_INFINITY;

    if (["ready", "authenticated"].includes(currentStatus)) {
      return this.getQrInfo(key);
    }

    const existingClient = this.getClient(key);
    if (existingClient) {
      try {
        const inspection = await this.inspectClientReadiness(existingClient);
        if (inspection.ready) {
          this.markSessionReady(key, "refresh_qr_probe", inspection);
          return this.getQrInfo(key);
        }
      } catch (error) {
        logger.debug("WhatsApp QR refresh readiness probe failed", {
          sessionKey: key,
          error: error.message
        });
      }
    }

    // Old dashboard tabs may still poll with refresh=1. Never recycle a fresh QR mid-scan.
    if (currentStatus === "qr_required" && currentQr && qrAgeMs < 60000) {
      return this.getQrInfo(key);
    }

    await this.destroyClient(key, { emitDestroyedStatus: false });
    await this.cleanupSessionArtifacts(key);

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
      if (this.shuttingDown) {
        throw new Error("WhatsApp manager is shutting down");
      }

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

    this.clearReadyReconciliation(key);

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
    if (this.shuttingDown) {
      throw new Error("WhatsApp manager is shutting down");
    }

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

      const sentMessage = await this.sendMessageWithLidFallback(
        client,
        chatId,
        hasMedia ? media : text,
        hasMedia
          ? {
              caption: text || undefined,
              sendMediaAsDocument: attachment?.sendAsDocument !== false,
            }
          : undefined,
        hasMedia
      );

      return {
        id: this.serializeWhatsAppId(sentMessage?.id),
        to: chatId,
        contentType: hasMedia ? "media" : "text",
        timestamp: sentMessage?.timestamp || Date.now()
      };
    };

    try {
      const client = await this.ensureClient(key);
      return await sendWithClient(client);
    } catch (error) {
      if (!this.isTransientBrowserError(error)) {
        throw error;
      }

      logger.warn("WhatsApp send hit a transient browser error; retrying once", {
        sessionKey: key,
        error: error.message
      });

      const refreshedClient = await this.recoverClient(key, "send_transient_error");
      return await sendWithClient(refreshedClient);
    }
  }

  async sendMessageWithLidFallback(client, chatId, content, options = undefined, hasMedia = false) {
    if (!hasMedia) {
      try {
        return await this.sendMessageViaResolvedChat(client, chatId, content, options);
      } catch (error) {
        const errorMessage = String(error?.message || error || "");
        const isLidRelated = /getChat|No LID for user|LID/i.test(errorMessage);

        if (!isLidRelated) {
          throw error;
        }
      }
    }

    try {
      return hasMedia
        ? await client.sendMessage(chatId, content, options)
        : await client.sendMessage(chatId, content);
    } catch (error) {
      const errorMessage = String(error?.message || error || "");
      const isLidRelated = /getChat|No LID for user|LID/i.test(errorMessage);

      if (!isLidRelated || typeof client.getContactLidAndPhone !== "function") {
        throw error;
      }

      let resolvedChatId = null;
      try {
        const resolved = await client.getContactLidAndPhone([chatId]);
        const first = Array.isArray(resolved) ? resolved[0] : null;
        resolvedChatId = String(first?.lid || first?.pn || "").trim() || null;
      } catch (lookupError) {
        logger.warn("Unable to resolve WhatsApp LID for send fallback", {
          sessionKey: client.info?.wid?._serialized || null,
          chatId,
          error: lookupError.message
        });
      }

      if (!resolvedChatId || resolvedChatId === chatId) {
        throw error;
      }

      logger.info("Retrying WhatsApp send with resolved LID", {
        originalChatId: chatId,
        resolvedChatId
      });

      return hasMedia
        ? await client.sendMessage(resolvedChatId, content, options)
        : await client.sendMessage(resolvedChatId, content);
    }
  }

  async sendMessageViaResolvedChat(client, chatId, content, options = undefined) {
    if (!client?.pupPage) {
      throw new Error("WhatsApp client page is unavailable");
    }

    return client.pupPage.evaluate(
      async (targetChatId, payload, sendOptions) => {
        const widFactory = window.require("WAWebWidFactory");
        const collections = window.require("WAWebCollections");

        const chatWid = widFactory.createWid(targetChatId);
        let chat =
          collections.Chat.get(chatWid) ||
          (await window.require("WAWebFindChatAction").findOrCreateLatestChat(chatWid))
            ?.chat ||
          null;

        if (!chat && typeof window.WWebJS?.getChat === "function") {
          chat = await window.WWebJS.getChat(targetChatId, { getAsModel: false });
        }

        if (!chat) {
          throw new Error(`Unable to resolve chat for ${targetChatId}`);
        }

        if (sendOptions?.sendSeen) {
          await window.WWebJS.sendSeen(targetChatId);
        }

        const sent = await window.WWebJS.sendMessage(chat, payload, sendOptions || {});
        return sent ? window.WWebJS.getMessageModel(sent) : undefined;
      },
      chatId,
      content,
      options || {}
    );
  }

  async getMessageHistory({ to, channelKey, limit = 100 }) {
    const key = channelKey || this.config.defaultSession;
    try {
      return await this.readMessageHistory({ to, channelKey: key, limit });
    } catch (error) {
      if (!this.isTransientBrowserError(error)) {
        throw error;
      }

      logger.warn("WhatsApp history hit a transient browser error; reconnecting", {
        sessionKey: key,
        error: error.message
      });
      await this.recoverClient(key, "history_transient_error");
      return this.readMessageHistory({ to, channelKey: key, limit });
    }
  }

  async readMessageHistory({ to, channelKey, limit = 100 }) {
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
        if (typeof storedChat.syncHistory === "function") {
          await storedChat.syncHistory();
        }
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

  isTransientBrowserError(error) {
    const message = String(error?.message || error || "");
    return /detached Frame|Execution context was destroyed|Target closed|Session closed/i.test(message);
  }

  async recoverClient(sessionKey, reason) {
    if (this.shuttingDown) {
      throw new Error("WhatsApp manager is shutting down");
    }

    const key = sessionKey || this.config.defaultSession;
    if (this.recoveringClients.has(key)) {
      return this.recoveringClients.get(key);
    }

    const recovery = (async () => {
      this.status.set(key, "initializing");
      await this.destroyClient(key, { emitDestroyedStatus: false });
      const client = await this.ensureClient(key);
      await this.waitForStatus(key, "ready", 60000);
      logger.info("WhatsApp browser session recovered", { sessionKey: key, reason });
      return client;
    })();

    this.recoveringClients.set(key, recovery);
    try {
      return await recovery;
    } finally {
      this.recoveringClients.delete(key);
    }
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
        const contentType = String(model?.type || model?.__x_type || "text");
        let body = String(model?.body || model?.__x_body || "");
        const looksLikeInlineMedia = body.startsWith("/9j/")
          || body.startsWith("iVBOR")
          || body.startsWith("UklGR");
        if (["image", "video", "sticker"].includes(contentType) && looksLikeInlineMedia) {
          body = "";
        }
        results.push({
          sessionKey: session,
          messageId,
          from: serializedId(model?.from || model?.__x_from || (!fromMe ? remote : "")),
          to: serializedId(model?.to || model?.__x_to || (fromMe ? remote : "")),
          body,
          contentType,
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
    const contentType = String(read(() => message.type, () => raw.type) || "text");
    let body = String(read(() => message.body, () => raw.caption, () => raw.body) || "");
    const looksLikeInlineMedia = body.startsWith("/9j/")
      || body.startsWith("iVBOR")
      || body.startsWith("UklGR");
    if (["image", "video", "sticker"].includes(contentType) && (body.length > 10000 || looksLikeInlineMedia)) {
      body = "";
    }

    const payload = {
      sessionKey,
      messageId: String(messageId),
      from: String(from),
      to: String(to),
      body,
      contentType,
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

  serializeWhatsAppId(id) {
    if (!id) {
      return null;
    }
    if (typeof id === "string") {
      return id;
    }
    if (id._serialized) {
      return String(id._serialized);
    }

    const remote = id.remote?._serialized || id.remote || "";
    const token = id.id || "";
    if (remote && token) {
      return [id.fromMe ? "true" : "false", String(remote), String(token)].join("_");
    }

    return null;
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

  async shutdown() {
    if (this.shuttingDown) {
      return;
    }

    this.shuttingDown = true;

    for (const timer of this.reconnectTimers.values()) {
      clearTimeout(timer);
    }
    this.reconnectTimers.clear();

    for (const timer of this.readyReconcileTimers.values()) {
      clearTimeout(timer);
    }
    this.readyReconcileTimers.clear();

    const clients = Array.from(this.clients.keys());
    for (const sessionKey of clients) {
      try {
        await this.destroyClient(sessionKey, { emitDestroyedStatus: false });
      } catch (_) {
        // no-op
      }
    }

    this.initializingClients.clear();
    this.recoveringClients.clear();
    this.manualDisconnectedSessions.clear();
    this.qrCode.clear();
    this.qrGeneratedAt.clear();
    this.status.clear();
  }
}

module.exports = WhatsAppManager;
