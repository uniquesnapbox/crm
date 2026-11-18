# ChatGPT Tool Contract — USB CRM

This file defines the first tool contract. These are application-level tool names, not a promise that all endpoints already exist.

## Common rules

- All dates use ISO 8601; timestamps include timezone.
- All list operations are paginated. Default page size 20; hard maximum 50.
- Every operation is company-scoped and permission-checked server-side.
- The model never supplies the acting user identity; derive it from the authenticated integration principal.
- Never return passwords, tokens, private notes, or attendance GPS coordinates.
- Write operations return the affected record ID and a concise result, not unrestricted model serialization.
- Require an explicit confirmation step before staff deactivation, task deletion, leave approval/rejection, or bulk mutations.
- Use idempotency keys for create operations and audit every mutation.

## Tool definitions

### 1. list_staff
Inputs: `search?`, `status?`, `role?`, `page?`, `per_page?`
Read scope: `staff.read`
Existing candidate: GET /api/integrations/employees (must be migrated from shared token auth before AI use).

### 2. create_staff
Inputs: `name`, `email`, `mobile?`, `employee_id?`, `department_id?`, `designation_id?`, `role_ids?`
Write scope: `staff.create`
New AI endpoint/service required. Reuse existing employee creation validation and invitation workflow; never create a user with an arbitrary privileged role.

### 3. update_staff
Inputs: `staff_id`, allowlisted profile fields only.
Write scope: `staff.update`
New AI endpoint/service required unless existing safe service can be reused.

### 4. deactivate_staff
Inputs: `staff_id`, `reason`, `confirm=true`
Write scope: `staff.deactivate`
New AI endpoint/service required. Soft-deactivate only; no hard delete. Require confirmation and block self-deactivation/last-admin removal.

### 5. list_tasks
Inputs: `search?`, `status?`, `assigned_to?`, `due_from?`, `due_to?`, `page?`, `per_page?`
Read scope: `tasks.read`
Existing candidate: GET /api/tasks. Reuse the existing visibility/ownership rules.

### 6. create_task
Inputs: `title`, `description?`, `project_id?`, `assignee_ids?`, `priority?`, `start_date?`, `due_date?`
Write scope: `tasks.create`
New AI endpoint/service required. Validate project access and assignee eligibility.

### 7. update_task
Inputs: `task_id`, allowlisted task fields.
Write scope: `tasks.update`
Existing candidate: PATCH /api/tasks/{task}; review status transitions and partial-update semantics before reuse.

### 8. assign_task
Inputs: `task_id`, `assignee_ids`
Write scope: `tasks.assign`
Existing candidate: PATCH /api/tasks/{task}/assignees. Preserve existing assignable-employee checks.

### 9. delete_task
Inputs: `task_id`, `reason`, `confirm=true`
Write scope: `tasks.delete`
Existing candidate: DELETE /api/tasks/{task}. Require confirmation and audit before/after metadata.

### 10. get_attendance
Inputs: `employee_id?`, `date?`, `date_from?`, `date_to?`, `page?`, `per_page?`
Read scope: `attendance.read`
Existing candidate: GET /api/integrations/attendance. Current resource query selects latitude/longitude; create a privacy-safe AI response resource that excludes GPS and live location.

### 11. manage_leave
Inputs: `action=list|apply|approve|reject`, `leave_id?`, `employee_id?`, `from_date?`, `to_date?`, `leave_type_id?`, `reason?`
Scopes: `leave.read`, `leave.apply`, `leave.approve`
Map to existing leave workflows only after confirming routes, role rules, quota checks, and approval chain.

### 12. manage_leads
Inputs: `action=list|create|update`, `lead_id?`, `name?`, `email?`, `phone?`, `status?`, `source_id?`, `assigned_to?`
Scopes: `leads.read`, `leads.write`
Existing lead-contact API is a candidate; verify lead-vs-contact data model and ownership rules before mapping.

### 13. manage_clients
Inputs: `action=list|update`, `client_id?`, allowlisted profile fields.
Scopes: `clients.read`, `clients.update`
Existing candidates: GET /api/clients and PATCH /api/clients/{client}. The mobile endpoint is user-permission based; do not call it using a broad service identity without acting-user authorization.

### 14. manage_followups
Inputs: `action=list|create|update_status`, `lead_id`, `followup_id?`, `due_at?`, `notes?`, `status?`
Scopes: `followups.read`, `followups.write`
Existing candidates: lead-contact follow-up routes. Check whether notes are sensitive and apply ownership checks.

### 15. get_performance_reports
Inputs: `report_type`, `date_from`, `date_to`, `employee_id?`, `project_id?`
Read scope: `reports.read`
Existing candidate: GET /api/reports/summary. Restrict date range and employee/project visibility.

## Endpoint mapping status

| Tool | Current status |
|---|---|
| list_staff | Existing read endpoint; auth hardening required |
| create_staff | New AI-safe operation needed |
| update_staff | New AI-safe operation needed |
| deactivate_staff | New AI-safe operation needed |
| list_tasks | Existing Sanctum endpoint; identity mapping required |
| create_task | New API operation needed |
| update_task | Existing Sanctum endpoint; review validation |
| assign_task | Existing Sanctum endpoint; review authorization |
| delete_task | Existing Sanctum endpoint; confirmation/audit required |
| get_attendance | Existing read endpoint; GPS must be excluded |
| manage_leave | Existing UI workflows need route/service mapping |
| manage_leads | Existing lead-contact routes; domain mapping needed |
| manage_clients | Existing endpoints; permission-safe adapter needed |
| manage_followups | Existing lead-contact routes; permission-safe adapter needed |
| get_performance_reports | Existing summary endpoint; scope/date filters needed |

The current shared integration-token middleware authenticates one configured token and has no per-token scopes, expiry, rotation, or principal-level revocation. It must not be reused as the final ChatGPT authorization model.
