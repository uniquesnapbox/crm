require("dotenv").config();

const crypto = require("crypto");
const fs = require("fs");
const path = require("path");
const http = require("http");
const express = require("express");
const { Server } = require("socket.io");

const config = require("./config");
const logger = require("./logger");
const WhatsAppManager = require("./whatsappManager");

const app = express();
const server = http.createServer(app);
const manager = new WhatsAppManager(config);

server.keepAliveTimeout = 75 * 1000;
server.headersTimeout = 80 * 1000;

const normalizeOrigin = (origin) =>
  String(origin || "")
    .trim()
    .replace(/\/+$/, "")
    .toLowerCase();

const configuredSocketOrigins =
  config.socketCorsOrigins === "*"
    ? "*"
    : new Set((config.socketCorsOrigins || []).map(normalizeOrigin).filter(Boolean));

function allowSocketOrigin(origin, callback) {
  if (configuredSocketOrigins === "*") {
    callback(null, true);
    return;
  }

  if (!origin) {
    callback(null, Boolean(config.socketAllowNoOrigin));
    return;
  }

  const allowed = configuredSocketOrigins.has(normalizeOrigin(origin));
  if (allowed) {
    callback(null, true);
    return;
  }

  logger.warn("Socket connection rejected by CORS origin policy", { origin });
  callback(new Error("Socket origin not allowed"), false);
}

const io = new Server(server, {
  path: config.socketPath,
  transports: ["websocket", "polling"],
  pingInterval: config.socketPingIntervalMs,
  pingTimeout: config.socketPingTimeoutMs,
  connectTimeout: config.socketConnectTimeoutMs,
  maxHttpBufferSize: config.socketMaxHttpBufferSize,
  connectionStateRecovery: {
    maxDisconnectionDuration: config.socketRecoveryDurationMs,
    skipMiddlewares: false
  },
  cors: {
    origin: allowSocketOrigin,
    methods: ["GET", "POST"],
    credentials: false
  }
});

const idempotencyFile = path.join(config.dataPath, "idempotency-store.json");
const idempotencyStore = new Map();
const IDEMPOTENCY_TTL_MS = 24 * 60 * 60 * 1000;

function safeReadJson(filePath) {
  try {
    if (!fs.existsSync(filePath)) {
      return {};
    }
    const raw = fs.readFileSync(filePath, "utf8");
    return raw ? JSON.parse(raw) : {};
  } catch (error) {
    logger.warn("Failed to read idempotency file", {
      filePath,
      error: error.message
    });
    return {};
  }
}

function safeWriteJson(filePath, payload) {
  try {
    fs.mkdirSync(path.dirname(filePath), { recursive: true });
    fs.writeFileSync(filePath, JSON.stringify(payload), "utf8");
  } catch (error) {
    logger.error("Failed to write idempotency file", {
      filePath,
      error: error.message
    });
  }
}

function loadIdempotencyStore() {
  const fromDisk = safeReadJson(idempotencyFile);
  const now = Date.now();

  Object.entries(fromDisk).forEach(([key, value]) => {
    if (!value || typeof value !== "object") {
      return;
    }

    const createdAt = Number(value.createdAt || 0);
    if (!createdAt || now - createdAt > IDEMPOTENCY_TTL_MS) {
      return;
    }

    idempotencyStore.set(key, value);
  });
}

function persistIdempotencyStore() {
  const now = Date.now();
  const data = {};

  idempotencyStore.forEach((value, key) => {
    const createdAt = Number(value.createdAt || 0);
    if (!createdAt || now - createdAt > IDEMPOTENCY_TTL_MS) {
      return;
    }
    data[key] = value;
  });

  safeWriteJson(idempotencyFile, data);
}

function pruneIdempotencyStore() {
  const now = Date.now();
  let changed = false;

  for (const [key, value] of idempotencyStore.entries()) {
    const createdAt = Number(value.createdAt || 0);
    if (!createdAt || now - createdAt > IDEMPOTENCY_TTL_MS) {
      idempotencyStore.delete(key);
      changed = true;
    }
  }

  if (changed) {
    persistIdempotencyStore();
  }
}

function buildIdempotencyKey(body) {
  if (typeof body.idempotencyKey === "string" && body.idempotencyKey.trim() !== "") {
    return body.idempotencyKey.trim();
  }

  if (
    body.metadata &&
    typeof body.metadata === "object" &&
    body.metadata.log_id !== undefined &&
    body.metadata.log_id !== null
  ) {
    return `wa-log-${body.metadata.log_id}`;
  }

  const fallback = JSON.stringify({
    to: body.to || "",
    message: body.message || "",
    channelKey: body.channelKey || config.defaultSession,
    hasAttachment: hasAttachmentPayload(body)
  });
  return `hash-${crypto.createHash("sha1").update(fallback).digest("hex")}`;
}

function isReadyStatus(status) {
  return status === "ready";
}

function hasAttachmentPayload(body) {
  const attachment = body?.attachment;
  if (!attachment || typeof attachment !== "object") {
    return false;
  }

  const hasData = typeof attachment.data === "string" && attachment.data.trim() !== "";
  const hasUrl = typeof attachment.url === "string" && attachment.url.trim() !== "";

  return hasData || hasUrl;
}

function isApiKeyAllowed(incomingApiKey) {
  if (!config.apiKey) {
    return true;
  }

  const token = String(incomingApiKey || "");
  return token !== "" && token === config.apiKey;
}

function requireApiKey(req, res, next) {
  if (isApiKeyAllowed(req.header("x-api-key"))) {
    return next();
  }

  return res.status(401).json({
    success: false,
    error: "Unauthorized"
  });
}

app.use(express.json({ limit: `${config.httpJsonLimitMb}mb` }));

io.use((socket, next) => {
  const token =
    socket.handshake.auth?.apiKey ||
    socket.handshake.headers?.["x-api-key"] ||
    socket.handshake.query?.apiKey;

  if (isApiKeyAllowed(token)) {
    return next();
  }

  return next(new Error("Unauthorized socket connection"));
});

io.engine.on("connection_error", (error) => {
  logger.warn("Socket engine connection error", {
    code: error?.code,
    message: error?.message,
    context: error?.context || null
  });
});

function emitSessionSnapshot(target, sessionKey) {
  const status = manager.getStatus(sessionKey);
  const qr = manager.getQr(sessionKey);

  target.emit("whatsapp-status", {
    sessionKey,
    status
  });

  if (qr) {
    target.emit("whatsapp-qr", {
      sessionKey,
      qr,
      status: "qr_required"
    });
  }

  if (status === "ready") {
    target.emit("whatsapp-ready", {
      sessionKey,
      status: "ready"
    });
  }
}

function attachManagerEventBridge() {
  manager.on("whatsapp-qr", (payload) => {
    io.emit("whatsapp-qr", payload);
  });

  manager.on("whatsapp-ready", (payload) => {
    io.emit("whatsapp-ready", payload);
    io.emit("whatsapp-status", payload);
  });

  manager.on("whatsapp-authenticated", (payload) => {
    io.emit("whatsapp-status", payload);
  });

  manager.on("whatsapp-disconnected", (payload) => {
    io.emit("whatsapp-disconnected", payload);
    io.emit("whatsapp-status", payload);
  });

  manager.on("whatsapp-auth-failure", (payload) => {
    io.emit("whatsapp-auth-failure", payload);
    io.emit("whatsapp-status", payload);
  });

  manager.on("whatsapp-status", (payload) => {
    io.emit("whatsapp-status", payload);
  });
}

io.on("connection", async (socket) => {
  logger.info("Socket client connected", {
    socketId: socket.id,
    origin: socket.handshake.headers?.origin || null
  });

  const summaries = manager.sessionsSummary();
  socket.emit("whatsapp-status", {
    sessions: summaries
  });

  for (const summary of summaries) {
    emitSessionSnapshot(socket, summary.sessionKey);
  }

  socket.on("whatsapp-request-sync", async (payload, ack) => {
    const sessionKey = String(payload?.sessionKey || config.defaultSession);
    const ensureClient = Boolean(payload?.ensureClient);

    try {
      if (ensureClient) {
        await manager.ensureClient(sessionKey);
      }
      emitSessionSnapshot(socket, sessionKey);

      if (typeof ack === "function") {
        ack({
          success: true,
          data: {
            sessionKey,
            status: manager.getStatus(sessionKey)
          }
        });
      }
    } catch (error) {
      logger.error("Failed to sync WhatsApp session snapshot", {
        sessionKey,
        ensureClient,
        error: error.message
      });

      socket.emit("whatsapp-status", {
        sessionKey,
        status: "disconnected"
      });

      if (typeof ack === "function") {
        ack({
          success: false,
          error: error.message || "Unable to sync WhatsApp session"
        });
      }
    }
  });

  socket.on("whatsapp-disconnect-session", async (payload, ack) => {
    const sessionKey = String(payload?.sessionKey || config.defaultSession);

    try {
      await manager.disconnectSession(sessionKey, { logout: true });

      if (typeof ack === "function") {
        ack({
          success: true,
          data: {
            sessionKey,
            status: "disconnected"
          }
        });
      }
    } catch (error) {
      logger.error("Manual WhatsApp disconnect failed", {
        sessionKey,
        error: error.message
      });

      if (typeof ack === "function") {
        ack({
          success: false,
          error: error.message || "Failed to disconnect session"
        });
      }
    }
  });

  socket.on("disconnect", (reason) => {
    logger.info("Socket client disconnected", {
      socketId: socket.id,
      reason
    });
  });
});

app.get("/health", (_req, res) => {
  const sessions = manager.sessionsSummary();
  const ready = sessions.some((session) => isReadyStatus(session.status));

  res.status(200).json({
    success: true,
    data: {
      service: "balancexe-whatsapp-service",
      env: config.nodeEnv,
      uptimeSeconds: Math.floor(process.uptime()),
      ready,
      sessions
    }
  });
});

app.get("/qr", requireApiKey, (req, res) => {
  const sessionKey = String(req.query.sessionKey || req.query.channelKey || config.defaultSession);

  const status = manager.getStatus(sessionKey);
  const qr = manager.getQr(sessionKey);

  res.status(200).json({
    success: true,
    data: {
      sessionKey,
      status,
      qr
    }
  });
});

app.post("/messages/send", requireApiKey, async (req, res) => {
  const body = req.body || {};
  const to = String(body.to || "").trim();
  const message = String(body.message || "").trim();
  const hasAttachment = hasAttachmentPayload(body);
  const channelKey = String(body.channelKey || config.defaultSession).trim() || config.defaultSession;
  const idempotencyKey = buildIdempotencyKey(body);

  if (!to || (!message && !hasAttachment)) {
    return res.status(422).json({
      success: false,
      error: "Validation failed",
      details: {
        to: !to ? "Recipient is required" : undefined,
        message: !message && !hasAttachment ? "Message or attachment is required" : undefined
      }
    });
  }

  const existing = idempotencyStore.get(idempotencyKey);
  if (existing && existing.result) {
    logger.info("Duplicate send request resolved by idempotency key", {
      idempotencyKey,
      channelKey,
      to
    });

    return res.status(200).json({
      success: true,
      duplicate: true,
      data: existing.result
    });
  }

  logger.info("Incoming WhatsApp send request", {
    idempotencyKey,
    channelKey,
    to,
    messageLength: message.length,
    hasAttachment,
    metadata: body.metadata || null
  });

  try {
    await manager.ensureClient(channelKey);
    const sessionStatus = manager.getStatus(channelKey);
    if (!isReadyStatus(sessionStatus)) {
      return res.status(503).json({
        success: false,
        error: `Session ${channelKey} not ready`,
        data: {
          sessionKey: channelKey,
          status: sessionStatus
        }
      });
    }

    const result = await manager.sendMessage({
      to,
      message,
      channelKey,
      attachment: hasAttachment ? body.attachment : null
    });

    idempotencyStore.set(idempotencyKey, {
      createdAt: Date.now(),
      result
    });
    persistIdempotencyStore();

    logger.info("WhatsApp send success", {
      idempotencyKey,
      channelKey,
      to: result.to,
      messageId: result.id
    });

    return res.status(200).json({
      success: true,
      data: result
    });
  } catch (error) {
    logger.error("WhatsApp send failed", {
      idempotencyKey,
      channelKey,
      to,
      error: error.message
    });

    const msg = String(error.message || "Unknown error");
    const lower = msg.toLowerCase();
    const statusCode =
      lower.includes("invalid recipient") || lower.includes("invalid recipient number")
        ? 422
        : lower.includes("not ready")
        ? 503
        : 500;

    return res.status(statusCode).json({
      success: false,
      error: msg
    });
  }
});

app.use((error, _req, res, _next) => {
  if (error instanceof SyntaxError && error.status === 400 && "body" in error) {
    logger.warn("Invalid JSON payload received", { error: error.message });

    return res.status(400).json({
      success: false,
      error: "Invalid JSON payload"
    });
  }

  logger.error("Unhandled express error", { error: error.message, stack: error.stack });

  res.status(500).json({
    success: false,
    error: "Internal server error"
  });
});

async function bootstrap() {
  fs.mkdirSync(config.dataPath, { recursive: true });
  attachManagerEventBridge();

  loadIdempotencyStore();
  pruneIdempotencyStore();
  setInterval(pruneIdempotencyStore, 10 * 60 * 1000).unref();

  try {
    await manager.initAll();
  } catch (error) {
    logger.error("Failed to initialize one or more sessions", {
      error: error.message
    });
  }

  server.listen(config.port, () => {
    logger.info("WhatsApp service started", {
      port: config.port,
      nodeEnv: config.nodeEnv,
      sessions: config.sessions,
      socketPath: config.socketPath
    });
  });
}

bootstrap().catch((error) => {
  logger.error("Fatal bootstrap error", {
    error: error.message,
    stack: error.stack
  });
  process.exit(1);
});
