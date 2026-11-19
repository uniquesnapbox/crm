# ChatGPT Web CRM MCP: HTTPS and OAuth Handoff

Status: prepared only. No public endpoint, tunnel, production deployment, or OAuth client has been created.

## Current local integration

- Reuse the existing private plugin `usb-crm-chatgpt` and its local stdio MCP server in `chatgpt-plugin/`.
- The MCP server calls Laravel only through `/api/mcp/v1`; it never connects directly to MySQL.
- Local API URL: `http://127.0.0.1:8081/api/mcp/v1`.
- Authentication is a per-CRM-user Laravel Sanctum token supplied through the process environment as `CRM_API_TOKEN`. It must not be committed, put in plugin files, or pasted into chat.
- Delete tools require explicit confirmation and Laravel writes mutation records to `crm_audit_logs`.

## Required web architecture

ChatGPT web cannot reach `127.0.0.1`. The approved production shape should be:

```text
ChatGPT web
  -> HTTPS remote MCP endpoint (streamable HTTP)
  -> OAuth 2.1 Authorization Code + PKCE
  -> CRM OAuth bridge / token broker
  -> Laravel Sanctum user-scoped API token
  -> /api/mcp/v1
```

The remote MCP endpoint must reuse the existing tool definitions and CRM API contract; it must not become a second database integration or bypass Laravel authorization.

## OAuth/security requirements before exposure

1. Obtain an approved HTTPS domain and certificate. Do not use a public tunnel or port-forwarding without explicit owner approval.
2. Register the exact ChatGPT callback URL supplied by the ChatGPT connector. Allow-list only that URI; do not use wildcard redirects.
3. Use OAuth 2.1 authorization-code flow with PKCE (S256), short-lived access tokens, rotating refresh tokens, state/nonce validation, and secure, encrypted refresh-token storage.
4. Map the authenticated CRM user and company/tenant into every request. Never accept company or user IDs from the model as authority.
5. Use least-privilege scopes, for example: `crm.read`, `crm.read.reports`, `crm.write.leads`, `crm.write.tasks`, and `crm.write.followups`.
6. Keep the Sanctum token server-side in the bridge. Never return it to ChatGPT or expose it in logs, URLs, error messages, or plugin metadata.
7. Enforce TLS, request size limits, rate limits, origin/redirect validation, structured audit IDs, and redacted error logging at the reverse proxy and MCP service.
8. Keep delete operations confirmation-gated and soft-delete based. Preserve Laravel permission checks and audit logging for every mutation.
9. Add health checks that do not reveal CRM data, and verify that debug mode is disabled on the remote deployment.

## Deployment gate

The following require explicit approval before execution: choosing the public hostname/provider, creating the OAuth client, opening firewall/DNS access, deploying the remote MCP process, creating a tunnel, or connecting ChatGPT web to the remote endpoint.

## Owner handoff steps

1. Approve a domain/hosting target and the OAuth provider/bridge strategy.
2. Provide the exact ChatGPT callback URL and approved scopes to the operator; register only those values.
3. Deploy the reused MCP service behind HTTPS with secrets injected by the host secret manager.
4. Configure the bridge to issue a user-scoped Laravel Sanctum token after successful CRM login/consent.
5. Register the remote MCP connector in ChatGPT web and complete OAuth consent.
6. Run read-only smoke tests first, then approve a test lead/task/follow-up in a non-production tenant before enabling writes.
7. Confirm audit entries, tenant isolation, token revocation, rate limiting, and delete confirmation before production use.

## Local verification commands

```powershell
# From crm-main; keep this bound to loopback for local work only.
php -S 127.0.0.1:8081 server.php

# The existing plugin process reads CRM_API_URL and CRM_API_TOKEN from the
# Windows user environment; do not put either secret in this file.
```

The local test database used for validation was `uniqu700_crm_mcp_test`; the main CRM database was not used for write tests.
