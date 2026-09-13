require("dotenv").config();

const crypto = require("crypto");
const fs = require("fs");
const path = require("path");
const http = require("http");
const express = require("express");
const QRCode = require("qrcode");
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

  manager.on("whatsapp-message", (payload) => {
    io.emit("whatsapp-message", payload);
    forwardIncomingMessage(payload);
  });
}

async function forwardIncomingMessage(payload) {
  if (!config.inboundWebhookUrl || !config.inboundWebhookToken) {
    logger.warn("Incoming WhatsApp webhook is not configured", {
      sessionKey: payload?.sessionKey || null
    });
    return;
  }

  try {
    const response = await fetch(config.inboundWebhookUrl, {
      method: "POST",
      headers: {
        "content-type": "application/json",
        "x-whatsapp-webhook-token": config.inboundWebhookToken
      },
      body: JSON.stringify(payload),
      signal: AbortSignal.timeout(15000)
    });

    if (!response.ok) {
      logger.warn("CRM rejected incoming WhatsApp message", {
        sessionKey: payload?.sessionKey || null,
        messageId: payload?.messageId || null,
        status: response.status,
        response: (await response.text()).slice(0, 500)
      });
    }
  } catch (error) {
    logger.warn("Unable to forward incoming WhatsApp message", {
      sessionKey: payload?.sessionKey || null,
      messageId: payload?.messageId || null,
      error: error.message
    });
  }
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

// Browser-friendly QR viewer. The page itself is public, but the QR remains
// protected: the user must enter the service API key and the browser sends it
// in the x-api-key header rather than putting it in the URL.
app.get("/qr/view", (req, res) => {
  const sessionKey = String(req.query.sessionKey || config.defaultSession).trim() || config.defaultSession;
  const safeSessionKey = JSON.stringify(sessionKey).replace(/</g, "\\u003c");

  res.type("html").send(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>WhatsApp QR — ${sessionKey.replace(/[<>&\"']/g, "")}</title>
  <style>
    :root { color-scheme: light; font-family: system-ui, -apple-system, sans-serif; }
    body { display: grid; min-height: 100vh; place-items: center; margin: 0; background: #f4f7f6; color: #17221e; }
    main { width: min(92vw, 520px); box-sizing: border-box; padding: 28px; border-radius: 18px; background: #fff; box-shadow: 0 12px 36px #17352b1f; text-align: center; }
    h1 { margin: 0 0 8px; font-size: 1.45rem; }
    p { color: #53645d; }
    input { width: 100%; box-sizing: border-box; padding: 12px; margin: 10px 0; border: 1px solid #ccd8d2; border-radius: 8px; font-size: 1rem; }
    button { padding: 12px 18px; border: 0; border-radius: 8px; background: #16805c; color: #fff; font-weight: 700; cursor: pointer; }
    button:disabled { opacity: .6; cursor: wait; }
    #qr { display: block; width: min(100%, 440px); margin: 22px auto 12px; image-rendering: pixelated; }
    #qr[hidden] { display: none; }
    #status { min-height: 1.4em; font-size: .95rem; }
    .hint { font-size: .82rem; }
  </style>
</head>
<body>
  <main>
    <h1>WhatsApp Bridge QR</h1>
    <p>Session: <strong>${sessionKey.replace(/[<>&\"']/g, "")}</strong></p>
    <input id="apiKey" type="password" autocomplete="off" placeholder="WHATSAPP_API_KEY">
    <button id="load" type="button">Show current QR</button>
    <p id="status" role="status">Enter the service API key to load the protected QR.</p>
    <img id="qr" alt="WhatsApp QR code" hidden>
    <p class="hint">QR refreshes automatically while this page is open. Do not share this page or API key.</p>
  </main>
  <script>
    const sessionKey = ${safeSessionKey};
    const apiKeyInput = document.getElementById("apiKey");
    const loadButton = document.getElementById("load");
    const status = document.getElementById("status");
    const qrImage = document.getElementById("qr");
    let objectUrl = null;

    async function loadQr() {
      const apiKey = apiKeyInput.value.trim();
      if (!apiKey) {
        status.textContent = "API key is required.";
        return;
      }

      loadButton.disabled = true;
      status.textContent = "Loading current QR…";
      try {
        const headers = { "x-api-key": apiKey };
        const infoResponse = await fetch("/qr?sessionKey=" + encodeURIComponent(sessionKey), { headers, cache: "no-store" });
        const info = await infoResponse.json();
        if (!infoResponse.ok) throw new Error(info.error || "Unable to load QR status");
        if (!info.data || !info.data.qr) throw new Error("QR is not available yet. Try again in a few seconds.");

        const imageResponse = await fetch("/qr/image?sessionKey=" + encodeURIComponent(sessionKey), { headers, cache: "no-store" });
        if (!imageResponse.ok) throw new Error("Unable to render QR image");
        const blob = await imageResponse.blob();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(blob);
        qrImage.src = objectUrl;
        qrImage.hidden = false;
        status.textContent = "QR ready — scan it from WhatsApp → Linked devices.";
      } catch (error) {
        qrImage.hidden = true;
        status.textContent = error.message;
      } finally {
        loadButton.disabled = false;
      }
    }

    loadButton.addEventListener("click", loadQr);
    apiKeyInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter") loadQr();
    });
    setInterval(() => {
      if (apiKeyInput.value.trim() && !loadButton.disabled) loadQr();
    }, 15000);
  </script>
</body>
</html>`);
});

app.get("/qr/image", requireApiKey, async (req, res) => {
  const sessionKey = String(req.query.sessionKey || req.query.channelKey || config.defaultSession);
  const qrInfo = manager.getQrInfo(sessionKey);

  if (!qrInfo.qr) {
    return res.status(404).json({
      success: false,
      error: "QR is not available",
      data: qrInfo
    });
  }

  try {
    const image = await QRCode.toBuffer(qrInfo.qr, {
      type: "png",
      width: 480,
      margin: 2,
      errorCorrectionLevel: "M"
    });

    res.set("Cache-Control", "no-store");
    res.type("png").send(image);
  } catch (error) {
    logger.error("WhatsApp QR image rendering failed", {
      sessionKey,
      error: error.message
    });

    res.status(500).json({
      success: false,
      error: "Unable to render QR image"
    });
  }
});

app.get("/qr", requireApiKey, async (req, res) => {
  const sessionKey = String(req.query.sessionKey || req.query.channelKey || config.defaultSession);
  const forceRefresh = ["1", "true", "yes"].includes(
    String(req.query.refresh || "").toLowerCase()
  );

  try {
    let qrInfo;
    if (forceRefresh) {
      qrInfo = await manager.refreshQr(sessionKey);
    } else {
      manager.ensureClient(sessionKey).catch((error) => {
        logger.warn("WhatsApp QR background initialization failed", {
          sessionKey,
          error: error.message
        });
      });
      qrInfo = manager.getQrInfo(sessionKey);
    }

    return res.status(200).json({
      success: true,
      data: qrInfo
    });
  } catch (error) {
    logger.error("WhatsApp QR refresh failed", {
      sessionKey,
      error: error.message
    });

    return res.status(500).json({
      success: false,
      error: error.message || "Unable to refresh WhatsApp QR"
    });
  }
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

app.get("/messages/history", requireApiKey, async (req, res) => {
  const to = String(req.query.to || "").trim();
  const channelKey = String(req.query.channelKey || config.defaultSession).trim() || config.defaultSession;
  const limit = Math.max(1, Math.min(200, Number(req.query.limit) || 100));

  if (!to) {
    return res.status(422).json({ success: false, error: "Recipient is required" });
  }

  try {
    const messages = await manager.getMessageHistory({ to, channelKey, limit });

    return res.status(200).json({
      success: true,
      data: { messages }
    });
  } catch (error) {
    logger.warn("Unable to read WhatsApp message history", {
      channelKey,
      to,
      error: error.message
    });

    return res.status(500).json({
      success: false,
      error: error.message || "Unable to read WhatsApp message history"
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

  await new Promise((resolve, reject) => {
    const onError = (error) => {
      server.off("error", onError);
      reject(error);
    };

    server.once("error", onError);
    server.listen(config.port, () => {
      server.off("error", onError);
      resolve();
    });
  });

  logger.info("WhatsApp service started", {
    port: config.port,
    nodeEnv: config.nodeEnv,
    sessions: config.sessions,
    socketPath: config.socketPath
  });

  manager.initAll().catch((error) => {
    logger.error("WhatsApp background bootstrap failed", {
      error: error.message,
      stack: error.stack
    });
  });
}

let stopping = false;
async function stopService() {
  if (stopping) return;
  stopping = true;
  server.close();
  try {
    await manager.shutdown();
  } finally {
    process.exit(0);
  }
}
process.on("SIGINT", stopService);
process.on("SIGTERM", stopService);
process.on("message", (message) => {
  if (message === "shutdown") stopService();
});

bootstrap().catch(async (error) => {
  logger.error("Fatal bootstrap error", {
    error: error.message,
    stack: error.stack
  });
  try {
    await manager.shutdown();
  } catch (shutdownError) {
    logger.warn("WhatsApp manager shutdown during bootstrap failure failed", {
      error: shutdownError.message
    });
  }
  process.exit(1);
});
