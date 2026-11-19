import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import { createServer } from 'node:http';
import { AsyncLocalStorage } from 'node:async_hooks';
import { createCipheriv, createDecipheriv, createHash, randomBytes, randomUUID } from 'node:crypto';
import { parse as parseQueryString } from 'node:querystring';
import { z } from 'zod';

const apiBaseUrl = (process.env.CRM_API_URL || 'http://127.0.0.1:8081/api/mcp/v1').replace(/\/$/, '');
const apiToken = process.env.CRM_API_TOKEN || '';
const timeoutMs = Number(process.env.CRM_API_TIMEOUT_MS || 30000);
const mcpPath = process.env.MCP_PATH || '/mcp';
const publicBaseUrl = (process.env.MCP_PUBLIC_URL || apiBaseUrl.replace(/\/api\/mcp\/v1$/, '')).replace(/\/$/, '');
const oauthIssuer = (process.env.MCP_OAUTH_ISSUER || publicBaseUrl).replace(/\/$/, '');
const oauthResource = process.env.MCP_OAUTH_RESOURCE || `${publicBaseUrl}${mcpPath}`;
const crmLoginUrl = process.env.CRM_LOGIN_URL || `${publicBaseUrl}/api/login`;
const oauthEncryptionKey = createHash('sha256')
  .update(process.env.MCP_OAUTH_ENCRYPTION_KEY || apiToken)
  .digest();
const requestContext = new AsyncLocalStorage();
const oauthCodes = new Map();
const oauthSessions = new Map();
const oauthClients = new Map();

if (!apiToken) {
  throw new Error('CRM_API_TOKEN is required. Store a CRM Sanctum token in the local plugin environment.');
}

const server = new McpServer({ name: 'usb-crm-chatgpt', version: '0.2.0' });

const text = (value) => ({ content: [{ type: 'text', text: JSON.stringify(value, null, 2) }] });

async function crm(path, options = {}) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const response = await fetch(`${apiBaseUrl}${path}`, {
      ...options,
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${requestContext.getStore()?.crmToken || apiToken}`,
        ...(options.headers || {}),
      },
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
      const error = new Error(body.message || `CRM API request failed (${response.status})`);
      error.status = response.status;
      error.details = body;
      throw error;
    }
    return body;
  } finally {
    clearTimeout(timer);
  }
}

const jsonBody = (data) => ({ method: 'POST', body: JSON.stringify(data) });
const patchBody = (data) => ({ method: 'PATCH', body: JSON.stringify(data) });
const requireConfirm = (confirm) => {
  if (!confirm) throw new Error('This action requires confirm=true.');
};

const base64Url = (value) => Buffer.from(value).toString('base64')
  .replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_');

const encryptOAuthPayload = (payload) => {
  const iv = randomBytes(12);
  const cipher = createCipheriv('aes-256-gcm', oauthEncryptionKey, iv);
  const encrypted = Buffer.concat([cipher.update(JSON.stringify(payload), 'utf8'), cipher.final()]);
  return [iv, cipher.getAuthTag(), encrypted].map(base64Url).join('.');
};

const decryptOAuthPayload = (value) => {
  try {
    const [ivText, tagText, dataText] = String(value || '').split('.');
    if (!ivText || !tagText || !dataText) return null;
    const decipher = createDecipheriv('aes-256-gcm', oauthEncryptionKey, Buffer.from(ivText, 'base64url'));
    decipher.setAuthTag(Buffer.from(tagText, 'base64url'));
    const data = Buffer.concat([decipher.update(Buffer.from(dataText, 'base64url')), decipher.final()]);
    const payload = JSON.parse(data.toString('utf8'));
    return payload.exp && payload.exp < Math.floor(Date.now() / 1000) ? null : payload;
  } catch {
    return null;
  }
};

const issueOAuthToken = (kind, data, ttlSeconds) => encryptOAuthPayload({
  ...data,
  kind,
  exp: Math.floor(Date.now() / 1000) + ttlSeconds,
});

const parseCookies = (header = '') => Object.fromEntries(header.split(';').map((part) => {
  const index = part.indexOf('=');
  return index < 0 ? [part.trim(), ''] : [part.slice(0, index).trim(), decodeURIComponent(part.slice(index + 1).trim())];
}).filter(([key]) => key));

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[character]));

const allowedRedirectUri = (redirectUri) => {
  try {
    const parsed = new URL(redirectUri);
    return parsed.protocol === 'https:' && parsed.hostname === 'chatgpt.com'
      && (parsed.pathname === '/connector_platform_oauth_redirect' || parsed.pathname.startsWith('/connector/oauth/'));
  } catch {
    return false;
  }
};

const validOAuthRequest = (params) => {
  if (params.response_type !== 'code' || !params.client_id || !params.redirect_uri || !params.code_challenge) {
    return 'Missing or invalid OAuth parameters.';
  }
  if (!allowedRedirectUri(params.redirect_uri)) return 'The OAuth redirect URI is not allowed.';
  if (!['S256'].includes(params.code_challenge_method || 'S256')) return 'Only PKCE S256 is supported.';
  if (params.resource && params.resource !== oauthResource && params.resource !== publicBaseUrl) return 'Invalid resource.';
  const registeredClient = oauthClients.get(params.client_id);
  if (!registeredClient && !String(params.client_id).startsWith('https://chatgpt.com/')) return 'Unknown OAuth client.';
  if (registeredClient && !registeredClient.redirect_uris.includes(params.redirect_uri)) return 'Redirect URI is not registered.';
  return null;
};

const oauthHiddenFields = (params) => Object.entries(params).map(([key, value]) => `<input type="hidden" name="${escapeHtml(key)}" value="${escapeHtml(value)}">`).join('');

const oauthLoginPage = (params, error = '') => `<!doctype html><html><head><meta charset="utf-8"><title>USB CRM authorization</title><style>body{font-family:Arial,sans-serif;background:#f3f6fb;margin:0;padding:40px;color:#172033}.card{max-width:420px;margin:auto;background:#fff;border-radius:14px;padding:28px;box-shadow:0 8px 30px #15254a1c}label{display:block;margin:14px 0 6px}input{width:100%;box-sizing:border-box;padding:11px;border:1px solid #ccd4e2;border-radius:7px}button{margin-top:20px;width:100%;padding:12px;border:0;border-radius:7px;background:#0d2384;color:#fff;font-weight:600}.error{color:#b42318;background:#fef3f2;padding:10px;border-radius:7px}</style></head><body><div class="card"><h2>Connect USB CRM</h2><p>Sign in with your CRM account to authorize ChatGPT.</p>${error ? `<div class="error">${escapeHtml(error)}</div>` : ''}<form method="post" action="/oauth/login">${oauthHiddenFields(params)}<label>Email</label><input name="email" type="email" required autocomplete="username"><label>Password</label><input name="password" type="password" required autocomplete="current-password"><button>Continue</button></form></div></body></html>`;

const oauthConsentPage = (params, user) => `<!doctype html><html><head><meta charset="utf-8"><title>Authorize USB CRM</title><style>body{font-family:Arial,sans-serif;background:#f3f6fb;margin:0;padding:40px;color:#172033}.card{max-width:470px;margin:auto;background:#fff;border-radius:14px;padding:28px;box-shadow:0 8px 30px #15254a1c}button{padding:11px 18px;border:0;border-radius:7px;background:#0d2384;color:#fff;font-weight:600;margin-right:8px}button[name=decision][value=deny{background:#667085}</style></head><body><div class="card"><h2>Authorize USB CRM</h2><p>ChatGPT wants to use USB CRM as <strong>${escapeHtml(user?.name || user?.email || 'your CRM account')}</strong>.</p><p>This allows searching and managing CRM leads, tasks, follow-ups and reports.</p><form method="post" action="/oauth/consent">${oauthHiddenFields(params)}<button name="decision" value="approve">Allow</button><button name="decision" value="deny">Cancel</button></form></div></body></html>`;

const sendJson = (res, status, body, headers = {}) => {
  res.writeHead(status, { 'content-type': 'application/json', ...headers });
  res.end(JSON.stringify(body));
};

const sendHtml = (res, html, headers = {}) => {
  res.writeHead(200, { 'content-type': 'text/html; charset=utf-8', ...headers });
  res.end(html);
};

const redirect = (res, location, headers = {}) => {
  res.writeHead(302, { location, ...headers });
  res.end();
};

const readRawBody = async (req) => {
  let raw = '';
  for await (const chunk of req) raw += chunk;
  return raw;
};

const parseFormBody = async (req) => parseQueryString(await readRawBody(req));

const scalarParams = (params) => Object.fromEntries(Object.entries(params).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]));

const oauthMetadata = () => ({
  issuer: oauthIssuer,
  authorization_endpoint: `${oauthIssuer}/oauth/authorize`,
  token_endpoint: `${oauthIssuer}/oauth/token`,
  registration_endpoint: `${oauthIssuer}/oauth/register`,
  response_types_supported: ['code'],
  grant_types_supported: ['authorization_code', 'refresh_token'],
  code_challenge_methods_supported: ['S256'],
  token_endpoint_auth_methods_supported: ['none'],
  client_id_metadata_document_supported: true,
  authorization_response_iss_parameter_supported: true,
  scopes_supported: ['crm:read', 'crm:write', 'offline_access'],
});

const protectedResourceMetadata = () => ({
  resource: oauthResource,
  authorization_servers: [oauthIssuer],
  scopes_supported: ['crm:read', 'crm:write', 'offline_access'],
});

const handleOAuth = async (req, res, requestPath, requestUrl) => {
  if (requestPath === '/.well-known/oauth-protected-resource' && req.method === 'GET') {
    sendJson(res, 200, protectedResourceMetadata());
    return true;
  }
  if ((requestPath === '/.well-known/oauth-authorization-server' || requestPath === '/.well-known/openid-configuration') && req.method === 'GET') {
    sendJson(res, 200, oauthMetadata());
    return true;
  }
  if (requestPath === '/oauth/register' && req.method === 'POST') {
    const raw = await readRawBody(req);
    let registration = req.headers['content-type']?.includes('application/json')
      ? JSON.parse(raw)
      : scalarParams(parseQueryString(raw));
    const clientId = `crm-client-${randomUUID()}`;
    const redirectUris = Array.isArray(registration.redirect_uris) ? registration.redirect_uris : [];
    if (!redirectUris.length || redirectUris.some((uri) => !allowedRedirectUri(uri))) {
      sendJson(res, 400, { error: 'invalid_client_metadata', error_description: 'A valid ChatGPT redirect URI is required.' });
      return true;
    }
    oauthClients.set(clientId, { redirect_uris: redirectUris });
    sendJson(res, 201, { client_id: clientId, client_id_issued_at: Math.floor(Date.now() / 1000), token_endpoint_auth_method: 'none' });
    return true;
  }
  if (requestPath === '/oauth/authorize' && req.method === 'GET') {
    const params = scalarParams(Object.fromEntries(requestUrl.searchParams.entries()));
    const error = validOAuthRequest(params);
    if (error) { sendJson(res, 400, { error: 'invalid_request', error_description: error }); return true; }
    const session = oauthSessions.get(parseCookies(req.headers.cookie).mcp_oauth_session);
    if (!session) { sendHtml(res, oauthLoginPage(params)); return true; }
    sendHtml(res, oauthConsentPage(params, session.user));
    return true;
  }
  if (requestPath === '/oauth/login' && req.method === 'POST') {
    const params = scalarParams(await parseFormBody(req));
    const { email, password, ...oauthParams } = params;
    const error = validOAuthRequest(oauthParams);
    if (error) { sendJson(res, 400, { error: 'invalid_request', error_description: error }); return true; }
    try {
      const response = await fetch(crmLoginUrl, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password, device_name: 'chatgpt-mcp-oauth' }) });
      const body = await response.json().catch(() => ({}));
      if (!response.ok || !body.token) { sendHtml(res, oauthLoginPage(oauthParams, body.message || 'CRM login failed.')); return true; }
      const sessionId = randomUUID();
      oauthSessions.set(sessionId, { crmToken: body.token, user: body.user, createdAt: Date.now() });
      redirect(res, `/oauth/authorize?${new URLSearchParams(oauthParams)}`, { 'set-cookie': `mcp_oauth_session=${encodeURIComponent(sessionId)}; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=1800` });
    } catch (loginError) {
      sendHtml(res, oauthLoginPage(oauthParams, 'Unable to contact CRM login. Please try again.'));
    }
    return true;
  }
  if (requestPath === '/oauth/consent' && req.method === 'POST') {
    const params = scalarParams(await parseFormBody(req));
    const session = oauthSessions.get(parseCookies(req.headers.cookie).mcp_oauth_session);
    const error = validOAuthRequest(params);
    if (error || !session) { sendJson(res, 400, { error: 'invalid_request', error_description: error || 'CRM session expired.' }); return true; }
    if (params.decision !== 'approve') {
      const denied = new URL(params.redirect_uri);
      denied.searchParams.set('error', 'access_denied');
      if (params.state) denied.searchParams.set('state', params.state);
      denied.searchParams.set('iss', oauthIssuer);
      redirect(res, denied.toString());
      return true;
    }
    const code = randomUUID();
    oauthCodes.set(code, { ...params, crmToken: session.crmToken, user: session.user, expiresAt: Date.now() + 300000 });
    const callback = new URL(params.redirect_uri);
    callback.searchParams.set('code', code);
    if (params.state) callback.searchParams.set('state', params.state);
    callback.searchParams.set('iss', oauthIssuer);
    redirect(res, callback.toString());
    return true;
  }
  if (requestPath === '/oauth/token' && req.method === 'POST') {
    const params = scalarParams(await parseFormBody(req));
    if (params.grant_type === 'authorization_code') {
      const record = oauthCodes.get(params.code);
      oauthCodes.delete(params.code);
      if (!record || record.expiresAt < Date.now() || record.client_id !== params.client_id || record.redirect_uri !== params.redirect_uri) {
        sendJson(res, 400, { error: 'invalid_grant' }); return true;
      }
      const expectedChallenge = base64Url(createHash('sha256').update(params.code_verifier || '').digest());
      if (expectedChallenge !== record.code_challenge) { sendJson(res, 400, { error: 'invalid_grant', error_description: 'PKCE verification failed.' }); return true; }
      const tokenData = { crmToken: record.crmToken, user: record.user, scope: record.scope || 'crm:read crm:write offline_access' };
      sendJson(res, 200, { token_type: 'Bearer', access_token: issueOAuthToken('access', tokenData, 3600), expires_in: 3600, refresh_token: issueOAuthToken('refresh', tokenData, 2592000), scope: tokenData.scope });
      return true;
    }
    if (params.grant_type === 'refresh_token') {
      const token = decryptOAuthPayload(params.refresh_token);
      if (!token || token.kind !== 'refresh') { sendJson(res, 400, { error: 'invalid_grant' }); return true; }
      const tokenData = { crmToken: token.crmToken, user: token.user, scope: token.scope || 'crm:read crm:write offline_access' };
      sendJson(res, 200, { token_type: 'Bearer', access_token: issueOAuthToken('access', tokenData, 3600), expires_in: 3600, refresh_token: issueOAuthToken('refresh', tokenData, 2592000), scope: tokenData.scope });
      return true;
    }
    sendJson(res, 400, { error: 'unsupported_grant_type' });
    return true;
  }
  return false;
};

server.tool('list_leads', 'Search leads visible to the authenticated CRM user.', {
  search: z.string().optional(), status_id: z.number().int().optional(), per_page: z.number().int().min(1).max(50).optional(),
}, async ({ search, status_id, per_page }) => text(await crm(`/leads?${new URLSearchParams(Object.entries({ search, status_id, per_page }).filter(([, v]) => v !== undefined))}`)));

server.tool('create_lead', 'Create a lead after checking for duplicate mobile/email. Returns duplicates instead of creating when a match exists.', {
  client_name: z.string().min(1), mobile: z.string().optional(), client_email: z.string().email().optional(), company_name: z.string().optional(), assigned_to: z.number().int().optional(), status_id: z.number().int().optional(), interest_level: z.enum(['low', 'medium', 'high', 'very_high']).optional(), deal_size: z.number().min(0).optional(),
}, async (data) => text(await crm('/leads', jsonBody(data))));

server.tool('update_lead', 'Update an authorized lead.', { lead_id: z.number().int(), client_name: z.string().optional(), mobile: z.string().optional(), client_email: z.string().email().optional(), company_name: z.string().optional(), assigned_to: z.number().int().nullable().optional(), status_id: z.number().int().nullable().optional(), interest_level: z.enum(['low', 'medium', 'high', 'very_high']).nullable().optional(), deal_size: z.number().min(0).nullable().optional() }, async ({ lead_id, ...data }) => text(await crm(`/leads/${lead_id}`, patchBody(data))));

server.tool('delete_lead', 'Soft-delete an authorized lead. Requires explicit confirmation.', { lead_id: z.number().int(), confirm: z.boolean() }, async ({ lead_id, confirm }) => { requireConfirm(confirm); return text(await crm(`/leads/${lead_id}?confirm=true`, { method: 'DELETE' })); });

server.tool('change_lead_status', 'Change an authorized lead status.', { lead_id: z.number().int(), status_id: z.number().int() }, async ({ lead_id, status_id }) => text(await crm(`/leads/${lead_id}/status`, patchBody({ status_id }))));

server.tool('list_follow_ups', 'List pending or overdue follow-ups visible to the authenticated CRM user.', { overdue: z.boolean().optional(), lead_id: z.number().int().optional() }, async ({ overdue, lead_id }) => text(await crm(lead_id ? `/leads/${lead_id}/follow-ups` : `/follow-ups?${new URLSearchParams(overdue ? { overdue: 'true' } : {})}`)));

server.tool('create_follow_up', 'Schedule a follow-up for an authorized lead.', { lead_id: z.number().int(), note: z.string().min(1), scheduled_at: z.string().datetime().optional() }, async ({ lead_id, note, scheduled_at }) => text(await crm(`/leads/${lead_id}/follow-ups`, jsonBody({ note, scheduled_at }))));

server.tool('update_follow_up', 'Reschedule, edit or complete a follow-up.', { lead_id: z.number().int(), follow_up_id: z.number().int(), note: z.string().optional(), scheduled_at: z.string().datetime().nullable().optional(), status: z.enum(['pending', 'completed', 'canceled']).optional() }, async ({ lead_id, follow_up_id, ...data }) => text(await crm(`/leads/${lead_id}/follow-ups/${follow_up_id}`, patchBody(data))));

server.tool('list_tasks', 'List tasks visible to the authenticated CRM user.', { search: z.string().optional(), overdue: z.boolean().optional(), per_page: z.number().int().min(1).max(50).optional() }, async ({ search, overdue, per_page }) => text(await crm(`/tasks?${new URLSearchParams(Object.entries({ search, overdue: overdue ? 'true' : undefined, per_page }).filter(([, v]) => v !== undefined))}`)));

server.tool('create_task', 'Create a task and optionally assign active employees.', { heading: z.string().min(1), description: z.string().optional(), priority: z.enum(['low', 'medium', 'high']), start_date: z.string().optional(), due_date: z.string().optional(), project_id: z.number().int().optional(), user_ids: z.array(z.number().int()).optional() }, async (data) => text(await crm('/tasks', jsonBody(data))));

server.tool('update_task', 'Update an authorized task.', { task_id: z.number().int(), heading: z.string().optional(), description: z.string().nullable().optional(), priority: z.enum(['low', 'medium', 'high']).optional(), start_date: z.string().nullable().optional(), due_date: z.string().nullable().optional() }, async ({ task_id, ...data }) => text(await crm(`/tasks/${task_id}`, patchBody(data))));

server.tool('change_task_status', 'Change a task status using a CRM task-board column slug.', { task_id: z.number().int(), status: z.string().min(1) }, async ({ task_id, status }) => text(await crm(`/tasks/${task_id}/status`, patchBody({ status }))));

server.tool('complete_task', 'Mark a task completed.', { task_id: z.number().int() }, async ({ task_id }) => text(await crm(`/tasks/${task_id}/status`, patchBody({ status: 'completed' }))));

server.tool('assign_task', 'Assign a task to active employee IDs.', { task_id: z.number().int(), user_ids: z.array(z.number().int()) }, async ({ task_id, user_ids }) => text(await crm(`/tasks/${task_id}/assignees`, patchBody({ user_ids }))));

server.tool('delete_task', 'Soft-delete an authorized task. Requires explicit confirmation.', { task_id: z.number().int(), confirm: z.boolean() }, async ({ task_id, confirm }) => { requireConfirm(confirm); return text(await crm(`/tasks/${task_id}?confirm=true`, { method: 'DELETE' })); });

server.tool('performance_report', 'Generate daily, weekly, monthly or custom CRM performance totals from database records.', { period: z.enum(['daily', 'weekly', 'monthly', 'custom']).optional(), from: z.string().optional(), to: z.string().optional() }, async ({ period, from, to }) => text(await crm(`/reports/performance?${new URLSearchParams(Object.entries({ period, from, to }).filter(([, v]) => v !== undefined))}`)));

if ((process.env.MCP_TRANSPORT || 'stdio').toLowerCase() !== 'http') {
  const transport = new StdioServerTransport();
  await server.connect(transport);
} else {
  const port = Number(process.env.MCP_PORT || 3015);
  const path = mcpPath;
  const accessToken = process.env.MCP_ACCESS_TOKEN || apiToken;
  if (!accessToken) throw new Error('MCP_ACCESS_TOKEN is required for HTTP mode.');
  const transports = new Map();
  const readBody = async (req) => {
    if (req.method !== 'POST') return undefined;
    const raw = await readRawBody(req);
    return raw ? JSON.parse(raw) : undefined;
  };
  const httpServer = createServer(async (req, res) => {
    try {
      const requestUrl = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
      const requestPath = requestUrl.pathname;
      if (await handleOAuth(req, res, requestPath, requestUrl)) return;
      if (requestPath === `${path}/health`) {
        res.writeHead(200, { 'content-type': 'application/json' });
        res.end(JSON.stringify({ ok: true, service: 'usb-crm-mcp' }));
        return;
      }
      if (requestPath !== path) { res.writeHead(404); res.end(); return; }
      if (req.method === 'OPTIONS') {
        res.writeHead(204, { 'access-control-allow-origin': '*', 'access-control-allow-headers': 'Authorization, Content-Type, MCP-Session-Id', 'access-control-allow-methods': 'GET, POST, DELETE, OPTIONS' });
        res.end();
        return;
      }
      const authorization = req.headers.authorization || '';
      const bearerMatch = /^Bearer\\s+(.+)$/i.exec(authorization);
      const bearer = bearerMatch ? bearerMatch[1].trim() : '';
      const oauthToken = decryptOAuthPayload(bearer);
      const identity = bearer === accessToken
        ? { crmToken: apiToken }
        : (oauthToken?.kind === 'access' && oauthToken.crmToken ? oauthToken : null);
      if (!identity) {
        console.warn(`MCP authorization rejected: header=${authorization ? 'present' : 'missing'}, tokenParts=${bearer ? bearer.split('.').length : 0}, decrypted=${oauthToken ? 'yes' : 'no'}, kind=${oauthToken?.kind || 'none'}`);
        res.writeHead(401, {
          'content-type': 'application/json',
          'www-authenticate': `Bearer resource_metadata="${oauthIssuer}/.well-known/oauth-protected-resource"`,
        });
        res.end(JSON.stringify({ error: 'Unauthorized' })); return;
      }
      const sessionId = req.headers['mcp-session-id'];
      let transport = sessionId ? transports.get(sessionId) : undefined;
      if (!transport) {
        transport = new StreamableHTTPServerTransport({ sessionIdGenerator: () => randomUUID() });
        transport.onclose = () => { if (transport.sessionId) transports.delete(transport.sessionId); };
        await server.connect(transport);
      }
      await requestContext.run(identity, async () => transport.handleRequest(req, res, await readBody(req)));
      if (transport.sessionId) transports.set(transport.sessionId, transport);
    } catch (error) {
      if (!res.headersSent) res.writeHead(500, { 'content-type': 'application/json' });
      if (!res.writableEnded) res.end(JSON.stringify({ error: error.message }));
    }
  });
  httpServer.listen(port, '127.0.0.1', () => console.error(`USB CRM MCP listening on 127.0.0.1:${port}${path}`));
}
