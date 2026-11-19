import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';

const apiBaseUrl = (process.env.CRM_API_URL || 'http://127.0.0.1:8081/api/mcp/v1').replace(/\/$/, '');
const apiToken = process.env.CRM_API_TOKEN || '';
const timeoutMs = Number(process.env.CRM_API_TIMEOUT_MS || 30000);

if (!apiToken) {
  throw new Error('CRM_API_TOKEN is required. Store a CRM Sanctum token in the local plugin environment.');
}

const server = new McpServer({ name: 'usb-crm-chatgpt', version: '0.1.0' });

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
        Authorization: `Bearer ${apiToken}`,
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

const transport = new StdioServerTransport();
await server.connect(transport);
