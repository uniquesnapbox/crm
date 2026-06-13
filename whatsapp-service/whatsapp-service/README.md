# BalanceXe WhatsApp Service (Socket.io + whatsapp-web.js)

## Overview
- Real-time QR login over Socket.io (no terminal QR).
- Session restore with `LocalAuth` in `.wwebjs_auth`.
- REST API for sending messages.
- Socket events for connection status.

## Events
- `whatsapp-qr` -> `{ sessionKey, qr, status }`
- `whatsapp-ready` -> `{ sessionKey, status }`
- `whatsapp-disconnected` -> `{ sessionKey, status, reason }`
- `whatsapp-status` -> snapshot/status updates
- `whatsapp-auth-failure` -> auth failure details

## API
- `GET /health`
- `GET /qr?sessionKey=default` (requires `x-api-key` if set)
- `POST /messages/send` (requires `x-api-key` if set)

## Socket Auth
- Socket clients pass API key in handshake auth:
  - `auth: { apiKey: "<WHATSAPP_API_KEY>" }`

## Local Run
```bash
cd whatsapp-service
cp .env.example .env
npm install
npm run start
```

## PM2 Production Run
```bash
cd whatsapp-service
npm install --omit=dev
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

## Persistence
- Keep `whatsapp-service/.wwebjs_auth` persistent (volume/shared disk).
- If this folder is deleted, QR scan will be required again.
