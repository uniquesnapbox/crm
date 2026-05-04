const { createLogger, format, transports } = require("winston");
const path = require("path");

const logger = createLogger({
  level: process.env.LOG_LEVEL || "info",
  format: format.combine(
    format.timestamp(),
    format.errors({ stack: true }),
    format.splat(),
    format.json()
  ),
  transports: [
    new transports.File({
      filename: path.resolve(process.cwd(), "logs", "whatsapp-service.log"),
      maxsize: 5 * 1024 * 1024,
      maxFiles: 3
    }),
    new transports.Console({
      format: format.combine(format.colorize(), format.simple())
    })
  ]
});

module.exports = logger;
