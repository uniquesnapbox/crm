const path = require("path");

function parseSessions(raw) {
  if (!raw) {
    return ["default"];
  }

  try {
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed) && parsed.length > 0) {
      return parsed.map((session) => String(session).trim()).filter(Boolean);
    }
  } catch (_) {
    // no-op
  }

  return String(raw)
    .split(",")
    .map((session) => session.trim())
    .filter(Boolean);
}

function uniqueSessions(sessions) {
  return Array.from(new Set((sessions || []).map((session) => String(session).trim()).filter(Boolean)));
}

function parseBoolean(rawValue, defaultValue = false) {
  if (rawValue === undefined || rawValue === null || rawValue === "") {
    return defaultValue;
  }

  const value = String(rawValue).trim().toLowerCase();
  if (["1", "true", "yes", "on"].includes(value)) {
    return true;
  }
  if (["0", "false", "no", "off"].includes(value)) {
    return false;
  }

  return defaultValue;
}

function parseNumber(rawValue, defaultValue) {
  const parsed = Number.parseInt(String(rawValue ?? ""), 10);
  if (!Number.isFinite(parsed)) {
    return defaultValue;
  }

  return parsed;
}

function normalizeOrigin(origin) {
  return String(origin || "")
    .trim()
    .replace(/\/+$/, "")
    .toLowerCase();
}

function parseSocketCorsOrigins(raw) {
  const fallback = "*";
  if (!raw) {
    return fallback;
  }

  const trimmed = String(raw).trim();
  if (!trimmed || trimmed === "*") {
    return fallback;
  }

  try {
    const parsed = JSON.parse(trimmed);
    if (Array.isArray(parsed)) {
      const origins = parsed.map(normalizeOrigin).filter(Boolean);
      return origins.length > 0 ? origins : fallback;
    }
  } catch (_error) {
    // no-op
  }

  const origins = trimmed
    .split(",")
    .map(normalizeOrigin)
    .filter(Boolean);

  return origins.length > 0 ? origins : fallback;
}

module.exports = {
  port: Number(process.env.PORT || 3100),
  apiKey: process.env.WHATSAPP_API_KEY || "",
  inboundWebhookUrl: process.env.WHATSAPP_INBOUND_WEBHOOK_URL || "",
  inboundWebhookToken: process.env.WHATSAPP_INBOUND_WEBHOOK_TOKEN || "",
  nodeEnv: process.env.NODE_ENV || "development",
  socketCorsOrigins: parseSocketCorsOrigins(process.env.WHATSAPP_SOCKET_CORS_ORIGIN || "*"),
  socketAllowNoOrigin: parseBoolean(process.env.WHATSAPP_SOCKET_ALLOW_NO_ORIGIN, true),
  socketPath: process.env.WHATSAPP_SOCKET_PATH || "/socket.io",
  socketPingIntervalMs: parseNumber(process.env.WHATSAPP_SOCKET_PING_INTERVAL_MS, 25000),
  socketPingTimeoutMs: parseNumber(process.env.WHATSAPP_SOCKET_PING_TIMEOUT_MS, 60000),
  socketConnectTimeoutMs: parseNumber(process.env.WHATSAPP_SOCKET_CONNECT_TIMEOUT_MS, 45000),
  socketMaxHttpBufferSize: parseNumber(process.env.WHATSAPP_SOCKET_MAX_HTTP_BUFFER_SIZE, 1_000_000),
  socketRecoveryDurationMs: parseNumber(process.env.WHATSAPP_SOCKET_RECOVERY_DURATION_MS, 120000),
  httpJsonLimitMb: Math.max(1, parseNumber(process.env.WHATSAPP_HTTP_JSON_LIMIT_MB, 15)),
  reconnectBaseDelayMs: parseNumber(process.env.WHATSAPP_RECONNECT_BASE_DELAY_MS, 5000),
  reconnectMaxDelayMs: parseNumber(process.env.WHATSAPP_RECONNECT_MAX_DELAY_MS, 60000),
  dataPath: path.resolve(process.cwd(), process.env.WHATSAPP_DATA_PATH || "./.wwebjs_auth"),
  browserExecutablePath:
    process.env.PUPPETEER_EXECUTABLE_PATH ||
    process.env.CHROME_EXECUTABLE_PATH ||
    process.env.WHATSAPP_BROWSER_EXECUTABLE_PATH ||
    "",
  headless: String(process.env.WHATSAPP_HEADLESS || "true").toLowerCase() === "true",
  browserUserAgent: process.env.WHATSAPP_BROWSER_USER_AGENT ||
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36",
  deviceName: process.env.WHATSAPP_DEVICE_NAME || "USB CRM Server",
  browserName: process.env.WHATSAPP_BROWSER_NAME || "Chrome",
  webVersion: process.env.WHATSAPP_WEB_VERSION || "2.3000.1045732124",
  webVersionCache: {
    type: "local",
    path: process.env.WHATSAPP_WEB_CACHE_PATH || "./.wwebjs_cache",
    strict: parseBoolean(process.env.WHATSAPP_WEB_VERSION_CACHE_STRICT, false)
  },
  defaultSession:
    process.env.WHATSAPP_DEFAULT_SESSION ||
    process.env.WHATSAPP_SERVICE_SESSION ||
    "default",
  sessions: uniqueSessions([
    ...parseSessions(
      process.env.WHATSAPP_SESSIONS ||
      process.env.WHATSAPP_SESSION_KEYS ||
      process.env.WHATSAPP_DEFAULT_SESSION ||
      process.env.WHATSAPP_SERVICE_SESSION ||
      "default"
    ),
    process.env.WHATSAPP_DEFAULT_SESSION ||
    process.env.WHATSAPP_SERVICE_SESSION ||
    "default"
  ])
};
