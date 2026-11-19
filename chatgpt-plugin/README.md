# USB CRM ChatGPT MCP Plugin

This plugin connects to the USB CRM MCP server over HTTPS. It calls the Laravel
CRM API with a Sanctum token belonging to the CRM user who should be
represented in ChatGPT.

The packaged ChatGPT configuration uses:

```text
https://crm.uniquzsnapbox.com/mcp
```

For local development only, the server can still run in stdio mode. Set these
environment variables in the local plugin process:

```text
CRM_API_URL=http://127.0.0.1:8081/api/mcp/v1
CRM_API_TOKEN=<CRM Sanctum token>
```

For local testing, start the Laravel API from `crm-main` with:

```powershell
php -S 127.0.0.1:8081 server.php
```

The token is intentionally not stored in this directory. Use a per-user token,
never the legacy shared `INTEGRATION_API_TOKEN`. The server does not access
MySQL directly. Delete tools require `confirm=true`, and the Laravel API writes
to `crm_audit_logs` for every mutation.
