# USB CRM — ChatGPT Integration Implementation Plan

Status: design baseline on `feature/chatgpt-crm-integration`. This document does not enable production AI access.

## 1. Existing API surface verified in `routes/api.php`

### Shared integration-token group (read-only)
- GET /api/integrations/employees and /employees/{employee}
- GET /api/integrations/clients and /clients/{client}
- GET /api/integrations/leads and /leads/{lead}
- GET /api/integrations/projects and /projects/{project}
- GET /api/integrations/attendance and /attendance/{attendance}

### Sanctum-authenticated mobile API
- Employees: GET /api/employees
- Tasks: GET /api/tasks, GET /api/tasks/{task}, PATCH /api/tasks/{task},
  POST /api/tasks/{task}/complete, PATCH /api/tasks/{task}/assignees,
  DELETE /api/tasks/{task}, POST /api/tasks/bulk-action
- Reports: GET /api/reports/summary
- Clients: GET /api/clients, GET /api/clients/{client}, PATCH /api/clients/{client}
- Leads/follow-ups: lead-contact list/create/update/quick-update and follow-up list/create/status routes
- Attendance: clock-in, clock-out, location update/alert, admin list

Important: this route inventory is not proof that each endpoint is suitable for an AI principal. Existing mobile endpoints must not be exposed to an AI integration without separate authorization, validation, and audit review.

## 2. Proposed AI tool catalog

Initial tool surface (15 logical tools):
1. list_staff
2. create_staff
3. update_staff
4. deactivate_staff
5. list_tasks
6. create_task
7. update_task
8. assign_task
9. delete_task
10. get_attendance
11. manage_leave
12. manage_leads
13. manage_clients
14. manage_followups
15. get_performance_reports

Some logical tools may call existing CRM services/routes; others require new API operations. The final mapping must be confirmed against controller validation, policies, database relationships, and business rules before implementation.

## 3. Security requirements — mandatory before enabling writes

- Replace the shared static integration token with individually revocable credentials (or a dedicated service principal) stored hashed at rest.
- Enforce token expiry, rotation, scopes, rate limits, and last-used metadata.
- Separate read and write scopes; default to read-only.
- Apply Laravel Policies/Gates and existing task ownership/assignment rules on every request. Never trust a user/employee ID supplied by the model.
- Validate all inputs with Form Requests; use allowlisted fields and explicit status transitions.
- Require explicit confirmation for destructive or high-impact actions (staff deactivation, task deletion, bulk changes).
- Record actor, credential ID, action, target, timestamp, request/correlation ID, and before/after diff in an append-only audit log. Never log secrets.
- Minimize personal data. Attendance responses must omit GPS coordinates and live-tracking details unless a separately authorized use case explicitly requires them.
- Use pagination, bounded date ranges, response field allowlists, and safe error messages.
- Protect against mass assignment, IDOR, replay, and cross-user data access.

## 4. Implementation sequence

### Phase A — security foundation
1. Inspect the actual integration-token middleware and current authorization policies.
2. Add dedicated AI integration credentials/scopes and revocation/expiry.
3. Add audit logging, throttling, safe response resources, and feature tests.
4. Keep all AI write scopes disabled until tests pass.

### Phase B — API/service layer
1. Refactor existing business logic into reusable services where controllers currently own the logic.
2. Implement missing staff and task creation operations only after checking existing web CRUD and validation.
3. Add scoped endpoints for leave, lead/client/follow-up workflows only where no safe existing endpoint exists.
4. Keep mobile Sanctum routes separate from AI credentials.

### Phase C — ChatGPT adapter
1. Expose a narrow, documented tool schema (MCP or supported Actions adapter).
2. Map each tool to one server-side operation; do not give the model arbitrary SQL, shell, or generic HTTP access.
3. Add idempotency keys for create/update operations where retries could duplicate records.
4. Add confirmation gates for destructive actions.

### Phase D — verification and release
- Feature tests for each scope, role, ownership boundary, validation failure, expiry, revocation, throttling, and audit record.
- Run full PHPUnit suite and static analysis.
- Deploy to staging, verify logs and rollback, then enable read-only pilot.
- Enable write tools gradually after owner approval.

## 5. Release guardrails

- No production credentials or .env values in Git.
- No deployment or database migration is performed by this plan.
- Do not connect ChatGPT to production until the security and authorization tests pass.
- Laravel framework upgrade is a separate compatibility project; do not combine a major framework upgrade with the first AI API release.

## 6. Next code-review targets

- `app/Http/Middleware` implementation for `integration.token`
- `App\\Http\\Controllers\\Api\\Integration\\*` controllers and response resources
- Task policies/permission helpers in `MobileCrmApiController`
- Employee CRUD controller, Form Requests, model fillable fields, and routes
- Leave controllers/routes and report query authorization
- Existing feature tests and CI workflow

This is an implementation baseline, not a claim that the 15 tools have been built or tested.
