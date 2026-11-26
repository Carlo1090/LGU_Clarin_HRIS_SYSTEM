# Clarin Municipal HRIS — Production Implementation & Governance Guide

> **Canonical project specification and development rules** | Version **1.0.0** | Updated **2026-11-17** | Status: **Proposed for municipal/project approval**
>
> Scope: Municipal Human Resource Information System (HRIS), including Laravel backend/API, user interface, database, integrations, administration, and operations.
>
> **Important:** This guide defines the required approach. It does **not** certify that the application, a particular database schema, a role, or any module has already been implemented, tested, approved, or deployed.

## Read this first: non-negotiable rules

1. **Documentation is part of the feature.** Every **major change MUST update this Markdown file in the same pull request (PR) or change set**. No undocumented major change may be merged, released, or marked done.
2. **Security is server-side.** UI menu visibility is not authorization. Every sensitive API endpoint, query, record, download, and action MUST enforce authenticated, resource-scoped permissions.
3. **No self-approval.** Critical employee changes, salary changes, attendance corrections, leave decisions, payroll approvals, and privileged-role changes MUST follow documented authorization and segregation-of-duties rules. Exceptional cases require a separately approved, logged workflow.
4. **Production data is not disposable.** NEVER reset, truncate, seed demo data into, or destructively migrate a production database. Back up and test restoration before risky changes.
5. **No secrets or real personnel data in Git, logs, issue tickets, AI prompts, screenshots, or test fixtures.** Use synthetic data unless a formally approved, controlled exception is necessary.
6. **The first HRMO Head account is created manually through an approved, audited bootstrap procedure,** not through a public registration page, permanently enabled bootstrap endpoint, or reusable default credentials.
7. **Financial and personnel records require traceability.** Every approval and privileged change needs an actor, time, subject, decision, and appropriate before/after evidence; protect the audit trail against ordinary edits/deletion.
8. **Changes require proof.** Validation, authorization tests, negative-path tests, and deployment/rollback evidence are required before production release.
9. **Requirements are not implementation evidence.** Mark any uncertain table name, route, integration, form, law-dependent rule, or workflow as **proposed** until verified against the actual repository and municipal approvals.
10. **If this guide conflicts with a binding law or formally approved municipal policy, stop and obtain a recorded decision** from the relevant office(s); do not silently assume this draft overrides them.

## Part A — Mandatory production engineering rules

### A1. Document authority, roles, and status

- **Single source of truth:** Maintain this document as `docs/Clarin_HRIS_Production_Guide.md` in the application repository (the file provided here can be copied to that path). Avoid parallel, diverging master specifications.
- **Change authority:** HRMO Head/business process owner approves HR workflows; payroll/accounting/treasury authorities approve their respective financial controls; MIS/technical lead approves infrastructure and access design; DPO/privacy lead reviews personal-data processing; designated project/release owner authorizes deployment. Record the actual names/positions and approved decision, not guesses.
- **Enforcement terms:** **MUST** = release requirement; **SHOULD** = strong recommendation requiring a documented reason if skipped; **MAY** = optional choice.
- **Status tags:** `PROPOSED`, `APPROVED`, `IMPLEMENTED`, `TESTED`, `DEPLOYED`, `DEPRECATED`, `BLOCKED`. A feature changes status only with verifiable evidence; keep these distinct.
- **The original functional overview in Part B is a preserved planning baseline.** Proposed URLs and dashboards are not confirmed routes and must not be described as live.

### A2. Major-change definition and mandatory Markdown update

A change is **MAJOR** if it affects any of the following, even when the code diff is small:

| Category                           | Examples                                                                                         | Required documentation                                                                    |
| ---------------------------------- | ------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------- |
| Authentication and authorization   | Roles, permissions, MFA, registration, password/reset flows, impersonation                       | Permission matrix, threat/risk impact, tests, recovery procedure                          |
| Database/model architecture        | New or changed tables, keys, nullable/unique rules, FK relationships, data migrations, retention | Root/child relationships, ERD or table map, migration + rollback/backout, data validation |
| Employee and 201-file data         | Personal fields, employee lifecycle, attachments, PDS/SALN access                                | Data classification, ownership, access and retention, form/workflow changes               |
| HR workflow/business rule          | Employee registration, approval chains, leave credits, attendance correction, appointments       | Actors, state machine, authorization, exceptions, audit requirements                      |
| Payroll and disbursement           | Formulas, salary schedule, deductions, finalization, payslip access, exports                     | Approved rule/version and effective date, reconciliation, approvers, test cases           |
| Public or internal API/integration | Route contracts, device import, notifications, external systems, file formats                    | API contract, validation, authentication, failure handling, data flows                    |
| Infrastructure/security/privacy    | Deployment, secrets, network, logging, encryption, backups, privacy handling                     | Security impact, operations runbook, restore/rollback test, approvals                     |
| Major UI/navigation/reporting      | Dashboards, privileged exports, visibility, sensitive summaries                                  | Role-to-screen mapping, data exposure review, accessibility/test evidence                 |
| Policy/dependency/legal update     | New rule, supported-version change, policy exception, vendor change                              | Reason, affected requirements, approval, transition/migration plan                        |

**Documentation-first and same-PR workflow:**

1. Write a **Change Record** (template A15) describing the current and proposed behavior, impact, risk, owner, and approval needed; add it to the **Change Register** (A14).
2. Before or alongside code, update the relevant section of this document: architecture, schema, permissions, workflows, API, security, tests, migration, operations, or status.
3. Implement in a reviewed branch. Include migration/compatibility notes and tests, and link the related ticket/PR (plain identifiers are acceptable until assigned).
4. In the **same PR**, update the Change Register with the specific documentation sections modified. Reviewer checks code vs document consistency; `docs-only` changes may be reviewed separately.
5. Before release, record approvals, test evidence, deployment version/date, monitored outcome, and rollback result or readiness. If these are pending, state `PENDING`, not `PASSED`.
6. An urgent security hotfix may follow an approved emergency path, but MUST update this guide within the hotfix change set or before final incident closure; document any temporary exception and expiry. Emergency handling never permits secret exposure or skipping required incident containment.

**PR acceptance rule:** For every PR labeled `major-change`, the canonical Markdown file MUST be changed. Technical review MUST also verify the correctness of the update; a cosmetic or unrelated edit does not satisfy this rule. Configure repository branch protection/CI for mandatory reviews and documentation checks; do not rely only on developer memory.

### A3. Architecture and Laravel code quality

- **Choose and pin a supported Laravel/PHP combination.** Confirm the versions from `composer.lock`, build/runtime and official Laravel deployment requirements; do not claim Laravel 12 or 13 is already installed.
- **Boundaries:** Controllers handle HTTP only; Form Requests validate/authorize inputs; Policies/Gates enforce permissions; Actions/Services implement business workflows; Eloquent models define relationships; queued Jobs handle suitable asynchronous work; Events are used deliberately and documented.
- **Separate `User` (login identity) from `Employee` (personnel record).** Their relationship, if needed, is optional until a verified business decision; a user may exist before an employee record (first HRMO Head), and employee records may exist without self-service accounts. No payroll logic should assume `users.id === employees.id`.
- **Use named domain operations** such as `ApproveLeave`, `CorrectAttendance`, `FinalizePayroll`, and `AssignRole` rather than duplicating authorization/business rules in controllers.
- **Use transactions** for atomic multi-table operations and lock/concurrency control for balances, approvals, payroll finalization, and one-time bootstrap. Ensure external side effects do not occur before transaction commit; use after-commit jobs or equivalent patterns.
- **Prevent duplicate processing:** use unique business keys, idempotency tokens where appropriate, and state checks for retried requests/imports/jobs.
- **Do not trust client fields** such as `user_id`, `role`, `status`, `salary`, `approved_by`, `department_id` or `is_admin`. Derive privileged values from authorized server-side context or approved workflows.
- **Use explicit allowlists** for input fields; no uncontrolled mass assignment; validate foreign keys against both existence and allowed organizational scope.
- **API behavior:** predictable status codes, stable validation errors, pagination for large records, filtering scoped to the current role, versioned contracts when consumers depend on them, and no sensitive exception traces in production responses.
- **Quality:** formatter/linter, static analysis where feasible, dependency vulnerability review, unit + feature tests, code review, and clear naming. Avoid hidden magic permissions or undocumented fallback access.

### A4. Proposed root/child data model and integrity rules

> **Proposed logical model, NOT an assertion that the uploaded files contain these actual tables.** Confirm and document real table names, columns, relationships, and cardinalities by inspecting the live project's migrations/schema before coding. No SQL schema dump was included in the two Markdown uploads.

| Logical root / aggregate        | Related child records (illustrative)                                                                  | Integrity rule                                                                         |
| ------------------------------- | ----------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| `users` (authentication)        | Role assignments, MFA factors, session/recovery events                                                | Unique login identity; controlled activation; secure role changes                      |
| `employees` (personnel)         | Addresses/contact, family, education, work history, documents, appointments, salary history, training | Stable employee identifier; sensitive details controlled by policy                     |
| `departments` / `org_units`     | Positions, reporting relationships, employee assignment history                                       | Valid organizational hierarchy; maintain effective dates                               |
| `plantilla_items` / `positions` | Approved assignment or occupancy history                                                              | Enforce approved item number uniqueness and non-overlapping occupancy where applicable |
| `attendance_imports`            | Raw device rows, normalized logs, correction requests and decisions                                   | Preserve source evidence; corrections should not silently overwrite raw rows           |
| `leave_requests`                | Approvals, attachments, leave-credit ledger entries                                                   | Enforce legal state transitions; ledger reconciles with balances                       |
| `payroll_periods`               | Payroll runs, line items, deductions, approvals, payslip artifacts                                    | No edits to finalized run; new adjustment/reversal with audit linkage                  |
| `vacancies`                     | Applicants, assessments, recommendations                                                              | PII scoped; documented recruitment review criteria                                     |
| `evaluation_cycles`             | Employee evaluations, review and sign-off history                                                     | Evaluator must be authorized and within scope                                          |
| `audit_events` (append-only)    | Referential subject/action details                                                                    | Independent access, immutability/tamper detection, retention policy                    |

**Every actual database change MUST document**: owning module, parent and child table, relationship (`1:1`, `1:N`, or `N:M`), primary/foreign keys, `ON DELETE` policy, nullability, defaults, unique/check constraints, indexes, effective-date rules, data sensitivity, backfill, test plan, and rollback/backout method.

- Apply **foreign keys and database constraints** in addition to Laravel validation. Prohibit orphan rows and silent cascade deletion of employee/payroll/audit records.
- Distinguish employment and appointment **history** from current assignment; historical data must remain reconstructable for a date/period.
- Treat employee codes, usernames, plantilla numbers, and other identifiers as **business identifiers**, not permission grants. Decide uniqueness scope in the approved schema.
- Use `DECIMAL` / integer minor units for monetary amounts as appropriate; never binary floating point for payroll calculation. Specify rounding, effective-date and versioned rules only after approval.
- Store timestamps consistently (UTC recommended), display official local date/time with `Asia/Manila` where required, and define cutoff/holiday handling for attendance and payroll.
- Soft deletes are **not** a substitute for records retention, confidentiality or audit; document whether deletion is permitted at all. Never delete approved payroll/audit evidence as a routine cleanup.
- Prefer **expand → backfill → validate → switch → contract** for incompatible production schema changes. Avoid destructive `migrate:fresh`, `db:wipe`, force seeders, unreviewed `DROP`, or one-step column removal in production.

### A5. Authentication, 2FA and role-based authorization

- Require **MFA/2FA for every human account**, and stronger controls for privileged accounts (e.g., authenticator app or security key where supported), in line with the HRIS baseline. Protect enrollment and recovery with identity verification and rate limits.
- Use Laravel authentication with secure, rotating sessions/tokens, secure/HttpOnly/SameSite cookie settings where applicable, login throttling, logout and session invalidation after credential/privilege changes. Document idle timeout and device/session policy before launch.
- Deny by default. **No direct API bypass** for role checks, even if a menu item is hidden. A global `super-admin` authorization bypass is forbidden for HR/payroll/financial approvals.
- **Scope permissions by action AND resource**: an Office Head can approve only assigned employees' requests; an employee can view only their own permitted payslips; an Auditor normally has read-only and time/scoped access; a Mayor normally views aggregated reports.
- Keep MIS system administration separate from HRMO business authority, salary approval, payroll release and DPO investigation authority.
- Explicitly log role assignment/removal, permission changes, MFA reset, lockout override, record export, and privileged view of sensitive files. Audit logs themselves must be access controlled.

**Minimum permission matrix (proposed; requires sign-off):**

| Role                      | Core access                                                    | Explicit restriction                                                         |
| ------------------------- | -------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| Employee                  | Own permitted profile, DTR, leave requests, payslips           | No other employee's PII, payroll administration or approval                  |
| Office/Department Head    | Scoped employees and assigned review queues                    | No cross-department access or unauthorized final approval                    |
| HR Officer                | Authorized employee/201-file maintenance; workflow preparation | Cannot independently approve own sensitive changes                           |
| **HRMO Head (`HR HEAD`)** | HR supervision and explicitly delegated HR approvals           | Not automatically payroll release, unrestricted privacy access, or MIS admin |
| Payroll Officer           | Prepare/check draft payroll and reports                        | Cannot independently release own run                                         |
| Municipal Accountant      | Approved payroll review/reconciliation                         | No implicit HR record edit or treasury authority                             |
| Municipal Treasurer       | Authorized approved disbursement outputs                       | No change to salary inputs or payroll calculations                           |
| Mayor/Management          | Aggregated/approved management analytics                       | No automatic individual PII/salary detail access                             |
| MIS/System Administrator  | Technical accounts, infrastructure, monitoring                 | No default 201-file reads or HR/payroll approvals                            |
| DPO                       | Authorized privacy requests, access/incident oversight         | Scope limited to privacy responsibilities                                    |
| Auditor                   | Auditable, generally read-only evidence                        | No editing source transactions without separate approval                     |

### A6. First HRMO Head account — secure _manual_ bootstrap

> The project plans to create the **first `HR HEAD` / HRMO Head** account manually. This is a **one-time controlled operation**, not an open registration flow. The exact role slug is a **proposed application identifier** and must match the codebase's verified role schema.

1. Obtain written approval from the proper municipal authority identifying the account holder and business role; independently verify their identity. Record approver, ticket/change ID, date, and operator.
2. Inspect actual tables/migrations to confirm `users`, `roles`, the assignment pivot/mapping, account status, MFA fields and audit mechanism. Document the real foreign keys in A4/change record **before** creating data.
3. Create or verify the **role/permission definitions** via version-controlled migrations/seed data, reviewed to avoid a broad administrator or payroll privilege. Production seeding must never contain real account credentials.
4. Create **exactly one intended named human account** through a restricted, temporary Artisan console command or a reviewed, one-time administrative process available only to authorized operators. Require an exact identifier, explicit approval reference, and dry-run/preflight mode; use a database transaction; safely fail if already provisioned. Never expose a publicly reachable setup route.
5. Use a cryptographically strong one-time enrollment/reset mechanism with short expiry delivered via a verified channel. Do not hard-code a password, reuse a default password, paste credentials in logs, or put plaintext secrets in `.env`, migrations, tickets or this guide.
6. Keep access **inactive/pending activation** until the user confirms identity, establishes their own credentials, and enrolls in MFA. Force secure completion; no privileged dashboard before enrollment.
7. Verify the assigned role and **negative permissions** (no payroll release, no MIS admin, no unrestricted disciplinary/SALN access). Test authorization as the account, not only by reading the sidebar.
8. Audit creation, operator, approval, role assignment, MFA activation and first successful sign-in **without writing passwords, MFA secrets or recovery codes to logs**. Securely disable/remove bootstrap tooling or the invocation path after completion.
9. Require independent review and a tested recovery procedure for loss of the first account; do not create secret backdoor accounts. Any second privileged account follows normal approved onboarding, not the first-user exception.

**Completion evidence:** verified user ID/account status; role/permission audit; MFA enrolled; denial tests; signed bootstrap approval; disabled bootstrap path; incident/recovery contact recorded. No real account credentials belong in this document.

### A7. HR domain approval and data-handling rules

- **Employee registration/201 files:** prepare → verify supporting documents → approve authorized activation; maintain change reasons and appointment history. Protect PDS, SALN, disciplinary, dependent, identification and medical/other sensitive fields with separate access checks where relevant.
- **Organization/plantilla:** only approved positions/salary grades may feed payroll. Reassignment must use effective dates and prevent conflicting assignment/occupancy. Approval authority and local forms require validation with HRMO.
- **Attendance/DTR:** Preserve device import provenance, import batches, source rows, timestamps, and timezone. Deduplicate imports and keep raw punches immutable. Employees or HR Officers file slot corrections. The HRMO Head decides, and approvals apply an override in the derived daily calculation.
- **Leave:** draft → submitted → department review → HRMO credit/rule validation → authorized decision → ledger posting. Prevent double deductions, overlapping approved requests, unauthorized negative credit balances and duplicated approvals; define exceptions per approved CSC/municipal rules.
- **Payroll:** prepare → validate approved HR/attendance/leave inputs → calculate with rule version/effective date → independent review/reconciliation → required approval → finalize/lock → authorized disbursement output. **Preparers cannot independently approve/release their own payroll.** Closed payroll changes become authorized adjustments or a versioned rerun, never in-place edits.
- **Recruitment/performance:** limit applicant/evaluation access to the approved review panel and role; record scoring criteria, reviewer, status changes and decisions; document appointment transition to employee/201 file.
- **Exports/reports:** report-level filters must not bypass row-level permission checks. Audit sensitive exports, apply least-necessary fields, and protect stored/generated files, including temporary exports and printer workflows.
- Define status values, allowed transitions, approvers, time limits, exception paths, notification recipients, and audit evidence for each module **before implementation**; confirm them with the responsible offices.

### A8. Personal data privacy, file storage and security controls

- Apply Philippine **Republic Act No. 10173 (Data Privacy Act of 2012)** and its IRR, with DPO/legal review of the municipality's specific obligations. Appropriate organizational, physical and technical safeguards are required; this guide alone does not establish compliance.
- Maintain an approved inventory of personal data and processing purposes, lawful basis, collection notices, controller/processor responsibilities, third-party transfers, access review, retention and secure disposal, incident response, and privacy-request handling. Avoid assuming consent is always the proper lawful basis for public employment processing.
- Assess privacy/security impact **before** collecting new sensitive fields, integrating biometric devices, or sharing data externally. Keep an incident plan with owner, escalation, evidence preservation, notification decision process and statutory deadlines determined by DPO/legal review.
- Store 201-file uploads and sensitive exports on **private** storage, not publicly browseable directories. Enforce signed/short-lived authorized access, per-file authorization, strict extension/MIME/content validation, size limits, safe filenames, malware screening where feasible, and encryption where risk-appropriate.
- Encrypt traffic with HTTPS/TLS; use secure secrets management, least-privilege database/service credentials, secure hashing for passwords, key rotation procedures, and separation of production vs development data.
- Use output escaping, CSRF protections for cookie-authenticated state changes, SQL parameterization/ORM, strict CORS allowlists, security headers and documented rate limits. Threat-model high-risk paths using the current **OWASP ASVS** requirements as a testing baseline.
- **Log metadata, not content:** do not log passwords, tokens, MFA secrets, salary detail snapshots, medical details, document bodies, or unnecessary personal identifiers. Encrypt/protect centralized logs and audit access.
- Set data retention and legal holds only after DPO, HRMO and the required records-management authority approve; do not invent a blanket deletion period.

### A9. Production infrastructure, release and operational safety

- **Environment:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` generated and protected, production secrets outside version control, secure `.env`/secrets store; ensure only the application `public/` directory is web-served. Do not run the PHP development server as the production web server.
- **Infrastructure:** supported and patched PHP/Laravel/dependencies; TLS termination; least-privilege DB and storage access; production/non-production isolation; secure session/cache/queue service; firewall/network controls; tested time synchronization.
- **Build/deploy:** pin dependencies and use repeatable CI/CD builds; review package vulnerabilities; run tests/static checks; stage and smoke-test before production; approval and change window required for high-risk releases. Use Laravel's documented optimization commands where suitable for the installed version.
- **Migrations:** back up, review data volume/locks, stage-test, use backward-compatible steps and monitor. Apply production migrations only as part of an approved release. **Never run `migrate:fresh`, destructive seeders, or unreviewed rollback commands on production.** Rollback may require app compatibility or restore; do not assume every migration is safely reversible.
- **Asynchronous work:** restart/reload queue workers after release where applicable; retry rules must be idempotent; failures must be observable and alertable; use after-commit dispatch for transactional data.
- **Observability:** health checks, centralized error logging (with PII redaction), latency/error/queue/DB monitoring, audit-log integrity, alert recipients, security event review, and scheduled capacity checks. Define ownership and runbooks for incidents.
- **Backup and disaster recovery:** encrypted restricted backups with scheduled integrity checks and **documented restore drills**; verify restore of DB and document storage together. HRMO/MIS must approve RPO/RTO targets and recovery priority before launch; this draft intentionally does not invent numbers.
- **Recovery/rollback:** every major deployment has an owner, backward-compatibility note, feature disable option where possible, rollback/backout steps, and post-deploy acceptance checks. Do not claim zero-downtime guarantees without testing.
- **No unapproved direct production hotfix.** Emergency changes require recorded approver, incident ID, independent review as soon as practical, and the documentation rule in A2.

### A10. Minimum test and verification matrix

| Area                 | Required evidence before release                                                                                                        |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Accounts/MFA         | Unauthorized enrollment/reset blocked; login rate limiting; session invalidation; first HRMO Head 2FA verified                          |
| Roles/scopes         | Authenticated-but-unauthorized endpoint returns 403; cross-department access denied; own-record-only checks; negative permission checks |
| DB/migrations        | FK/unique constraints; correct history; migration tested on representative anonymized copy; data/backout verified                       |
| 201 files/privacy    | Private file cannot be downloaded by URL without permission; restricted PII redaction; audit captures access without leaking content    |
| Attendance/leave     | Duplicate imports rejected; correction approval chain; ledger reconciliation; overlapping leave handling                                |
| Payroll              | Approved effective-date rules; independent approver; manual payroll reconciliation; rounding/boundary tests; finalization lock          |
| Workflow concurrency | Double-approval, concurrent request, retries, repeated import, and duplicate finalization cannot corrupt records                        |
| Exports/reports      | Per-role/department row filtering; forbidden report access; exported artifacts protected and audited                                    |
| Operations           | CI green; backups/restore evidenced; monitoring/alerts tested; smoke tests and rollback/backout plan recorded                           |

**Acceptance definition of done:** requirements and approval captured; implementation reviewed; role/negative tests pass; security/privacy impact assessed; DB migration/backfill reviewed; runbook/rollback checked; this Markdown's affected sections and Change Register updated; version/status and release approvals recorded. If any mandatory item is unmet, status is `BLOCKED` or `PROPOSED`, never `DEPLOYED`.

### A11. Deployment checklist (copy and complete per release)

- [ ] Release ID, owner, approver(s), scope, and deployment window recorded.
- [ ] All applicable major-change entries updated in A14 and relevant technical sections revised.
- [ ] Production configuration and secrets verified; `APP_DEBUG=false`; no test credentials or public setup routes.
- [ ] Least-privilege roles, API policies and MFA verified with negative tests.
- [ ] Automated tests, dependency/security scans and peer review passed.
- [ ] Schema migration/backfill safe, reviewed, and exercised in staging; data snapshots/backups available.
- [ ] Protected employee files and audit/event logs confirmed in scope and access controlled.
- [ ] Payroll rules and reconciliation signed off by the relevant offices, if payroll is affected.
- [ ] Disaster recovery/restore and backout plan are current and assigned to a named owner.
- [ ] CI/CD approved; monitoring and alert contacts prepared; queues/workers handled.
- [ ] Post-release smoke checks, audit evidence, and stakeholder sign-off completed.
- [ ] Release and change entries marked `DEPLOYED` **only after verification**.

### A12. CI and reviewer guardrail for documentation changes

**Repository setup to implement (not present or verified in the uploads):**

1. Add this file to source control at `docs/Clarin_HRIS_Production_Guide.md`.
2. Require PR review and passing tests on the protected main/release branch.
3. In the PR template, require: `Major change? Yes/No`, affected modules, security/privacy impact, DB changes, evidence, approvals, and a link to this document's change entry.
4. Add a CI check: **if a PR is marked `major-change`, fail unless the canonical Markdown file is modified**. A reviewer must reject empty/cosmetic-only updates.
5. For **all** PRs, require a human reviewer to decide whether the change is major; do not let a developer evade the check by omitting the label.
6. Ensure the release checklist checks the change record's documentation status. Keep branch-protection rules version-controlled or documented for operators.

**Suggested PR checklist:**

```text
[ ] Major change: YES / NO (explain)
[ ] Updated docs/Clarin_HRIS_Production_Guide.md or justified minor-only scope
[ ] Change Register ID and sections affected:
[ ] Affected tables/foreign keys/migration and backout:
[ ] Role matrix/authorization and privacy impact:
[ ] Test results including unauthorized paths:
[ ] Business owner, MIS, DPO and financial approval as applicable:
[ ] Rollback/operational impact:
```

### A13. Open decisions / production blockers — do not guess

| Decision required                                                         | Owner to confirm                            | Current status           |
| ------------------------------------------------------------------------- | ------------------------------------------- | ------------------------ |
| Existing repository, framework version, database engine and actual schema | MIS / technical lead                        | **UNVERIFIED**           |
| Verified `HR HEAD` role identifier and exact permissions                  | HRMO + MIS                                  | **PROPOSED**             |
| Manual first-account authority, operator, recovery process                | HRMO + MIS                                  | **PENDING APPROVAL**     |
| Current municipal departments, positions, plantilla and appointment rules | HRMO                                        | **PENDING CONFIRMATION** |
| Exact approval delegation/segregation of duties by transaction            | HRMO / Accountant / Treasurer / Management  | **PENDING CONFIRMATION** |
| Salary schedule, deductions, effective dates and contribution rules       | Payroll / Accountant / applicable authority | **PENDING CONFIRMATION** |
| Leave rules, CSC forms, biometric formats and DTR exceptions              | HRMO / responsible office                   | **PENDING CONFIRMATION** |
| Data classification, retention, privacy notices and incident procedures   | DPO / records authority                     | **PENDING CONFIRMATION** |
| Hosting, backup frequency, RPO/RTO, disaster recovery owner, monitoring   | MIS / project owner                         | **PENDING CONFIRMATION** |
| Actual dashboard/endpoint names and availability                          | Developers / MIS                            | **PROPOSED**             |

### A14. Change Register — MUST be maintained in THIS file

> Add **one row per major change** (not one row per release). **Never delete prior entries.** Links/IDs are placeholders until a real ticket/PR/deployment exists. Keep a dated history so the document and system do not drift apart.

| Change ID | Date (PHT) | Change and reason                                                                                                                                                           | Areas affected                                               | Required approval                                              | Documented sections                                 | Status / evidence                                                                          |
| --------- | ---------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ | -------------------------------------------------------------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| CHG-0001  | 2026-11-17 | Consolidated the two near-identical HRIS overview uploads; introduced mandatory production controls, manual first-HRMO-Head bootstrap process and change-documentation rule | Project governance, auth, DB, workflows, privacy, deployment | HRMO/MIS/DPO sign-off to be obtained                           | Part A (A1–A15); preserved original scope in Part B | **PROPOSED** — document created; no application code or production deployment claimed      |
| CHG-0002  | 2026-11-18 | Introduce fixed plantilla offices, role dashboard office context, and HRMO Head-managed account setup                                                                       | Organization, UI, authentication                             | Municipal approval and release verification pending            | A4-A6, A14                                          | **TESTED** - see change record below                                                       |
| CHG-0003  | 2026-11-18 | Add HRMO Head employee spreadsheet preview/import and show existing departments alongside fixed offices                                                                     | Employee records, organization directory, UI                 | Municipal approval and production release verification pending | A3-A4, A7, A14                                      | **IMPLEMENTED** - validation evidence pending                                              |
| CHG-0004  | 2026-11-18 | Allow HRMO Head to manage employee import workbook columns and show added fields on employee profiles                                                                       | Employee records, schema, import workflow, UI                | Municipal approval and production release verification pending | A3-A4, A7, A14                                      | **IMPLEMENTED** - validation pending                                                       |
| CHG-0005  | 2026-11-18 | Limit employee account imports to core identity/status fields and create single-employee accounts with one-time credentials                                                 | Employee records, account setup, import workflow, UI         | Municipal approval and production release verification pending | A3-A4, A7, A14                                      | **TESTED** - 23 focused feature tests, TypeScript, formatting, and targeted PHPStan passed |

#### CHG-0002 - Fixed offices and account setup

- The project owner selected the 14 plantilla office headings and the existing 11 application roles. A user's office comes from their linked employee record. The 11 initial dashboards share the existing dashboard component.
- `DatabaseSeeder` upserts the 14 approved department codes and 11 web roles. It creates no accounts or demo records. The directory exposes the approved offices and generic role and department writes are denied. Legacy department records and linked personnel data are preserved. Review and reconcile legacy records before inviting affected employees.
- Public registration is disabled. The verified, password-confirmed, MFA-enrolled HRMO Head provisioned through bootstrap can access `/hris/account-invitations`. Invitations require an approved role, an existing unlinked employee in an approved office, a work email, and an approval reference. The extra HRMO Head role is outside the web invitation allowlist.
- The invitation transaction creates the user, links the existing user/employee foreign keys in both directions, and writes an audit record containing the actor, role, and approval reference. The password broker emails a short-lived setup link after commit. The recipient sets a password, verifies email, and confirms MFA before entering the HRIS. Use Forgot password to recover an expired or undelivered setup email.
- The `users.requires_mfa` boolean defaults to false for existing accounts and is true for newly provisioned accounts. Existing account enrollment and legacy sample account disposition require rollout review. Inactive accounts are denied access to the HRIS.
- Bootstrap requires written approval and a verified recipient. An authorized shell operator runs `php artisan hris:bootstrap-head <email> --name=<name> --approval=<reference> --operator=<operator>` for preflight, then repeats it with `--execute` after approval. The command refuses a second governed HRMO Head. Limit production shell access to approved operators. Record recovery ownership and verify MFA and audit evidence before release.
- Deploy the additive migration and idempotent reference seeder in a reviewed environment. Verify mail delivery and test the account flow before inviting personnel. App rollback should retain the additive column, employee links, and audit records until the release owner approves a backout. Prior registration must remain disabled for an invite-only deployment.
- The full suite passed 56 tests using synthetic records. The frontend production build passed. Municipal sign-off, real mail delivery and deployment verification are pending.

#### CHG-0003 - Employee spreadsheet import and department directory

- HRMO Head can download and upload the `EMPLOYEES` workbook at `/hris/employees/import`. It supports up to 5,000 rows / 10 MB. Employee Number, First Name, Last Name, and Employment Status are required; Department, Position, and other Employee profile fields are optional and resolved against existing HRIS records. Email is optional.
- Import matches employees by employee number, previews row errors and create/update actions, validates all configured spreadsheet values except optional email and checks department/position relationships, and commits employee profiles and new accounts transactionally with an audit record. New accounts with email receive a setup link; without email, HRMO receives a one-time temporary password that must be shared privately. New users must change it and enroll in MFA.
- Employee import operates on `employees` and does not create or modify Plantilla positions. The Employees directory summarizes and sorts by employment status, followed by employee name. HRMO can add, rename, and remove employee workbook columns; the four required identity/status columns cannot be removed. Added column values are stored in `employees.custom_fields` and shown on employee profiles. Existing columns can be renamed without changing their mapped field. Imports only update fields present in the workbook, preserving fields omitted after removal. HRMO can also edit Employment Status dropdown choices. The Plantilla page displays existing position records and linked employee numbers in a read-only table.
- The department page now shows persisted department records (including legacy HRMO/MTO records) plus any missing offices from the fixed 14-office directory. Existing database rows are read-only there; general department editing remains disabled.
- HRMO approval, privacy review, real mail delivery, and deployment verification remain pending.

#### CHG-0004 - Employee import workbook columns

- Status: IMPLEMENTED; validation and production rollout pending.
- HRMO Head manages employee import spreadsheet columns from the employee import page. Required columns are Employee Number, First Name, Last Name, and Employment Status. Optional standard columns retain their existing mapping when renamed; custom columns are stored with employee data and rendered on the profile.
- An additive JSON custom_fields column was added to employees. Backout should retain this column and stored employee information until a reviewed data migration is approved.
- Imports only update fields present in the uploaded workbook, preventing omitted columns from clearing existing employee data. Removing a custom column hides it from the template/profile but leaves its prior values stored.
- Review authorization, privacy classification, focused import tests, TypeScript checks, and deployment/rollback evidence before production release.

#### CHG-0005 - Employee account creation workflow

- The editable employee workbook requires Employee Number, First Name, Last Name, Department, and Employment Status. Middle Name, Suffix, Position, Email, and custom columns are optional. Appointment classification and other Plantilla profile data are entered later through Plantilla; appointment status remains separate from Employment Status.
- Import preview validates only these account setup fields and shows the username and generated temporary password before applying the import. The same password is assigned on commit. Created credentials are returned once after import; email setup links are attempted when an email is supplied, but email remains optional.
- The single Employee Add Record form creates the employee and Employee-role login account in one transaction. Employee Number is the username. It shows the temporary password once after save, requires a password change and MFA, and accepts an optional email for the account setup link.
- Focused validation passed: 23 Employee import and HRIS resource feature tests, TypeScript check, frontend formatting, targeted PHPStan, and PHP formatting. Review authorization, privacy classification, mail delivery, and deployment/rollback evidence before production release.

**Versioning:** `MAJOR.MINOR.PATCH` for this guide. MAJOR = changed business/security/data contract; MINOR = added approved functionality or policy; PATCH = clarification/formatting. Align each deployed release to the corresponding document Git commit and change IDs. The version does not imply the application uses that version.

### A15. Mandatory major-change record template

Copy the section below for each major change, place the completed record under the register or in an explicitly referenced subsection **inside this same Markdown file**, and link it from A14.

```md
#### CHG-XXXX — <clear title>

- Date/time (Asia/Manila):
- Author / owner:
- Business approver(s) / technical reviewer(s):
- Ticket / PR / release / Git commit:
- Status: PROPOSED | APPROVED | IMPLEMENTED | TESTED | DEPLOYED | BLOCKED
- Why change is needed / baseline before:
- Scope / behavior after:
- Affected roles and permitted/denied actions:
- Affected modules and routes/API contracts:
- Affected actual DB tables, root/child relations, FKs, indexes, data migration:
- Personal-data and security impact / risks / mitigations:
- Payroll/attendance/leave impact (or N/A):
- Rollout, backfill, downtime and rollback/backout plan:
- Test cases, negative-path evidence and results:
- Operations/backup/monitoring changes:
- Sections of this guide updated:
- Approvals and deployment verification:
- Known limitations / follow-up owner:
```

### A16. External references (for validation, not a substitute for municipal sign-off)

- [Laravel deployment (current version)](https://laravel.com/docs/deployment) — production configuration, health checks, optimization and safe deployment approach.
- [Laravel authorization](https://laravel.com/docs/authorization) — policies, gates, and controller/route authorization.
- [Laravel authentication](https://laravel.com/docs/authentication) — session authentication and login protections.
- [OWASP ASVS](https://asvs.owasp.org/) — security verification requirements (stable version shown by OWASP as 5.0.0 when this guide was created).
- [Philippine Data Privacy Act of 2012, RA 10173](https://privacy.gov.ph/data-privacy-act/) — statutory privacy/security baseline.
- [Implementing Rules and Regulations of RA 10173](https://privacy.gov.ph/implementing-rules-regulations-data-privacy-act-%202012/) — implementation requirements including personal-data security.

---

## Part B — Functional HRIS overview (preserved project scope)

> The following functional overview is derived from the uploaded HRIS planning Markdown documents. It documents **planned** modules and suggested routes only. The production policies in Part A govern design, changes and deployment. Formatting/headings have been normalized; the functional intent is retained.

The Clarin Municipal HRIS is a system that helps the Municipal Government manage employee information, attendance, leave, payroll, recruitment, and employee performance in one place.

Instead of keeping information in separate spreadsheets, folders, and paper documents, the HRIS provides one organized system where authorized employees can access the information and functions needed for their work.

This overview is based on the Clarin Municipal HRIS Strategic Plan. It explains the proposed system in simple terms, including its modules, user roles, dashboard contents, and how the different offices work together.

Important: The dashboard links below are suggested page addresses for planning. They are not confirmed working links in the application yet. Each user's access must be limited according to their assigned role.

### 1. Main purpose of the system

Keep employee records in one central database.

Make employee information easier to find and update.

Reduce manual encoding of attendance and payroll.

Allow employees to submit leave and other requests online.

Make approval processes easier to track.

Help HRMO monitor employees and personnel records.

Help payroll staff prepare accurate payroll information.

Generate reports for authorized municipal offices.

Keep a record of who changed or approved information.

Protect private employee information.

### 2. Main system modules

Each module handles one important part of employee management.

#### A. Employee Management / 201 Files

Purpose: Stores the complete employment record of every employee.

Register new employees.

Store personal information and contact details.

Store employment information and appointment details.

Record department, position, and plantilla item number.

Store educational background and qualifications.

Record work experience and employment history.

Store dependents and other relevant employee information.

Upload and manage employee documents.

Track Personal Data Sheet (PDS) and Statement of Assets, Liabilities and Net Worth (SALN) records.

Record training, seminars, and certificates.

Track salary history and appointment changes.

Maintain restricted disciplinary records.

View an employee's complete 201 file.

#### B. Organization and Plantilla Management

Purpose: Manages the municipality's offices, positions, and approved plantilla information.

Add and manage municipal departments.

Register offices and organizational units.

Add position titles and job descriptions.

Record plantilla item numbers.

Record salary grades and approved salary schedules.

Identify occupied and vacant positions.

Assign employees to their respective offices and positions.

Track changes in organizational structure.

View the municipality's staffing structure.

#### C. Attendance and Daily Time Record (DTR)

Purpose: Records and monitors employee attendance.

Import attendance records from supported biometric devices or other approved sources.

Record time-in and time-out.

Generate Daily Time Records.

Monitor late arrivals and undertime.

Identify absences and missing time logs.

Review attendance exceptions.

Allow employees to request attendance corrections.

Allow authorized officers to review and approve corrections.

Generate monthly attendance summaries.

Provide attendance data for payroll preparation.

#### D. Leave Management

Purpose: Handles employee leave applications and leave balances.

View available leave credits.

Submit leave applications.

Select the type of leave.

Enter the requested leave dates.

Upload supporting documents when required.

Track the status of leave requests.

Allow department heads to review requests.

Allow HRMO to validate leave information.

Record approved and rejected requests.

Update leave balances after approval.

Generate leave reports and leave history.

#### E. Payroll Management

Purpose: Helps authorized payroll staff calculate and prepare employee salaries.

Set up payroll periods.

Maintain approved salary information.

Get attendance and leave information from the system.

Calculate salary based on approved payroll rules.

Compute applicable deductions and contributions.

Prepare payroll summaries.

Identify missing or incorrect payroll information.

Review payroll calculations before approval.

Prepare payslips.

Generate payroll reports.

Prepare statutory contribution and remittance outputs.

Keep payroll processing and approval history.

Payroll must follow the approved salary schedule, applicable government rules, and authorized municipal procedures. The system should not automatically release payroll without the required review and approval.

#### F. Recruitment and Applicant Management

Purpose: Helps HRMO manage applications for vacant positions.

Create and publish job vacancies.

Record applicant information.

Upload applicant documents.

Track application status.

Review applicant qualifications.

Record examination and interview results.

Compare applicants based on approved criteria.

Record evaluation results.

Track selected applicants.

Prepare recruitment reports.

#### G. Performance Management

Purpose: Helps the municipality monitor employee performance and development.

Create performance evaluation periods.

Record employee performance commitments.

Allow authorized supervisors to review performance.

Record performance ratings.

Monitor evaluation status.

Keep previous performance records.

Identify employee training needs.

Record training and development activities.

Generate performance reports.

#### H. Reports and Analytics

Purpose: Turns system information into useful reports for management.

Employee headcount reports.

Employee lists by department.

Employment status reports.

Plantilla and vacancy reports.

Attendance and DTR reports.

Late and absence reports.

Leave utilization reports.

Leave balance reports.

Payroll summaries.

Salary and deduction reports.

Recruitment reports.

Performance reports.

Audit and system activity reports.

Access to reports must depend on the user's role. Sensitive information, especially salary and personal records, must not be visible to everyone.

#### I. User and System Administration

Purpose: Controls who can access the system and what they can do.

Create user accounts.

Assign user roles.

Assign department access.

Activate or deactivate accounts.

Reset accounts using an approved identity-verification process.

Configure system settings.

Manage access permissions.

Review login and security activity.

View audit logs.

Monitor backups and system health.

#### J. Security and Privacy

Purpose: Protects employee information and system activities.

Require two-factor authentication (2FA) for every account.

Apply stronger protection to privileged accounts.

Restrict access based on user roles.

Keep records of important changes.

Record who performed an action and when.

Protect sensitive employee documents.

Monitor suspicious login activity.

Support privacy requests and incident handling.

Follow applicable Data Privacy Act requirements.

### 3. User roles and their functions

The system should show different dashboards and menus depending on the user's job. For example, an employee should not see payroll administration tools, and a system administrator should not automatically have permission to approve payroll.

#### 1. Employee

Main responsibility: View personal information and submit requests.

View personal profile.

View permitted employment information.

View attendance and DTR.

View available leave credits.

Submit leave applications.

Submit attendance correction requests.

View request status.

View personal payslips.

View personal performance records, when available.

Receive system announcements and notifications.

Update permitted personal information, subject to HRMO verification.

Suggested dashboard:

My Profile

My Attendance

My Leave Credits

My Leave Requests

My Payslips

My Performance

Notifications

#### 2. Department Head / Office Head

Main responsibility: Monitor employees in their own department and review requests.

View employees assigned to their department.

Monitor department attendance.

Review employee leave requests.

Review department attendance summaries.

Monitor employee performance evaluations.

Submit or review required department information.

View approval history.

Receive notifications for pending approvals.

Suggested dashboard:

Department Overview

Department Employees

Attendance Monitoring

Leave Approvals

Performance Reviews

Approval History

#### 3. HR Officer

Main responsibility: Manage employee records and daily HR transactions.

Register and update employee records.

Maintain employee 201 files.

Upload and organize employee documents.

Manage employment and appointment information.

Maintain employee work history.

Manage department and position assignments.

Review employee information submitted for correction.

Process leave records.

Review attendance records and exceptions.

File attendance correction requests on an employee’s behalf with a required reason.

Maintain training and performance information.

Prepare HR reports.

Monitor incomplete employee records.

Suggested dashboard:

HR Overview

Employee Records

201 File Management

Employee Documents

Organization and Positions

Attendance Management

Leave Management

Performance Records

HR Reports

#### 4. HRMO Head

Main responsibility: Supervise HR operations and approve authorized HR transactions.

Monitor the overall employee population.

Review employee record changes.

Oversee 201 file completeness.

Supervise attendance and leave administration.

Review and approve assigned HR requests.

Approve, return with a comment, or reject attendance correction requests.

Monitor recruitment activities.

Monitor performance evaluation activities.

Review HR reports.

Monitor pending transactions.

Check HR staff activities and approval history.

Ensure that HR processes follow approved municipal policies.

Suggested dashboard:

HRMO Dashboard

Employee Statistics

Employee Records

Pending HR Approvals

Attendance Summary

Leave Summary

Recruitment Monitoring

Performance Monitoring

HR Reports

Audit History

#### 5. Payroll Officer

Main responsibility: Prepare and check payroll computations.

Manage payroll periods.

Review employee salary information.

Retrieve approved attendance information.

Retrieve approved leave information.

Prepare payroll calculations.

Compute approved deductions and contributions.

Identify payroll errors and missing information.

Generate draft payroll summaries.

Prepare payslips.

Prepare payroll reports and required outputs.

Submit payroll for review and approval.

Maintain payroll processing history.

Suggested dashboard:

Payroll Overview

Payroll Periods

Payroll Preparation

Attendance and Leave Inputs

Payroll Computation

Payroll Exceptions

Payroll Review

Payslips

Payroll Reports

Processing History

#### 6. Municipal Accountant

Main responsibility: Review payroll information and accounting-related outputs.

View approved payroll summaries.

Review payroll totals and deductions.

Check accounting-related payroll information.

Review payroll reports and supporting records.

Assist in payroll reconciliation.

Prepare or review accounting outputs according to assigned duties.

Access authorized payroll history.

Report inconsistencies for correction.

Suggested dashboard:

Accounting Dashboard

Approved Payroll Summaries

Payroll Review

Deductions and Contributions

Payroll Reconciliation

Accounting Reports

Payroll History

#### 7. Municipal Treasurer

Main responsibility: Handle authorized payroll disbursement-related activities.

View approved payroll summaries.

Review authorized disbursement information.

Access payroll outputs needed for payment processing.

Monitor payroll disbursement status.

Review authorized payroll reports.

Maintain records related to disbursement.

Coordinate with the Accountant and Payroll Officer.

Suggested dashboard:

Treasury Dashboard

Approved Payroll

Disbursement Summary

Payroll Payment Status

Payroll Reports

Disbursement History

#### 8. Municipal Mayor / Authorized Management

Main responsibility: Monitor the overall municipal workforce through summary information.

View total employee count.

View employee distribution by department.

Monitor occupied and vacant plantilla positions.

View staffing summaries.

Monitor general attendance trends.

View leave and overtime summaries, where authorized.

Review high-level HR reports.

Monitor overall HR performance and activities.

Access approved management reports.

Suggested dashboard:

Executive Dashboard

Total Employees

Employees by Department

Plantilla Overview

Vacant Positions

Attendance Summary

Leave Summary

HR Reports

Management access should generally show summary information. Access to individual employee records or detailed payroll information requires separate authorization.

#### 9. System Administrator / MIS

Main responsibility: Maintain the technical operation and security of the system.

Create accounts based on approved requests.

Assign approved system roles and permissions.

Activate and deactivate accounts.

Manage system configurations.

Maintain technical settings.

Monitor system availability.

Monitor backup status.

Review technical errors.

Manage deployment and updates.

Maintain system logs.

Support users with technical problems.

Suggested dashboard:

System Overview

User Accounts

Roles and Permissions

System Settings

Login Activity

Technical Logs

Backup Monitoring

System Health

Important: The System Administrator should not automatically have permission to view private HR records or approve HR and payroll transactions.

#### 10. Data Protection Officer (DPO)

Main responsibility: Help protect employee information and monitor privacy-related activities.

Review privacy-related system activities.

Monitor access logs within the authorized scope.

Manage or monitor privacy requests.

Maintain privacy incident records.

Review reported data privacy concerns.

Support privacy compliance activities.

Coordinate with HRMO and MIS when necessary.

Suggested dashboard:

Privacy Overview

Privacy Requests

Access Logs

Privacy Incidents

Data Protection Reports

#### 11. Auditor / Authorized Audit User

Main responsibility: Review records and transaction history for audit purposes.

View authorized employee and transaction records.

Review payroll evidence within the approved scope.

Check approval history.

Review changes made to records.

View audit logs.

Generate authorized audit reports.

Verify who performed a particular transaction.

Use read-only access unless additional permission is formally approved.

Suggested dashboard:

Audit Overview

Audit Logs

Payroll Audit Records

HR Transaction History

Approval History

Audit Reports

### 4. Suggested dashboard and sidebar structure

The sidebar is the main navigation menu. It should only display modules that the logged-in user is allowed to access.

Here is a suggested structure for the system:

CLARIN MUNICIPAL HRIS

MAIN SIDEBAR MENU

Dashboard — /dashboard

Employee Management

Employee List — /employees

Add Employee — /employees/create

201 Files — /employees/201-files

Employee Documents — /employees/documents

Organization

Departments — /organization/departments

Positions — /organization/positions

Plantilla — /organization/plantilla

Attendance

Attendance Overview — /attendance

Import Attendance — /attendance/imports

Daily Time Records — /attendance/dtr

Attendance Corrections — /attendance/corrections

Overtime and CTO — /attendance/overtime

Travel Orders — /attendance/travel

Leave Management

Leave Overview — /leave

Leave Applications — /leave/requests

Leave Credits — /leave/credits

Leave Reports — /leave/reports

Payroll

Payroll Overview — /payroll

Payroll Periods — /payroll/periods

Prepare Payroll — /payroll/prepare

Payroll Review — /payroll/review

Payslips — /payroll/payslips

Payroll Reports — /payroll/reports

Contributions and Deductions — /payroll/deductions

Recruitment

Vacancies — /recruitment/vacancies

Applicants — /recruitment/applicants

Applicant Evaluation — /recruitment/evaluations

Selection Process — /recruitment/selections

Performance

Evaluation Periods — /performance/cycles

Employee Performance — /performance/reviews

Training Records — /performance/training

Reports

Employee Reports — /reports/employees

Attendance Reports — /reports/attendance

Leave Reports — /reports/leave

Payroll Reports — /reports/payroll

Audit Reports — /reports/audit

Administration

User Accounts — /admin/users

Roles and Permissions — /admin/roles

Audit Logs — /admin/audit-logs

System Settings — /admin/settings

Backup Monitoring — /admin/backups

These are proposed route names for planning. Actual routes may change during development.

### 5. What should appear on each dashboard?

A dashboard should show the most important information for the user's work. It should not display every system feature at once.

| User              | Important dashboard information                                           |
| ----------------- | ------------------------------------------------------------------------- |
| Employee          | Personal profile, attendance, leave credits, payslips, requests           |
| Department Head   | Department employees, attendance, pending approvals, performance          |
| HR Officer        | Employee records, incomplete 201 files, attendance issues, leave requests |
| HRMO Head         | Total employees, department statistics, pending HR transactions, reports  |
| Payroll Officer   | Payroll period, payroll preparation, errors, deductions, payslips         |
| Accountant        | Approved payroll, payroll totals, deductions, reconciliation              |
| Treasurer         | Approved payroll, disbursement information, payment status                |
| Mayor             | Employee totals, department staffing, vacancies, HR summaries             |
| MIS Administrator | User accounts, permissions, system health, backups, technical logs        |
| DPO               | Privacy requests, access logs, privacy incidents                          |
| Auditor           | Audit records, transaction history, payroll evidence                      |

#### Example: HRMO dashboard

HRMO Dashboard

Sample layout

Total Employees

—

Active Employees

—

Pending Leave Requests

—

Incomplete 201 Files

—

Quick Actions

Register New Employee

Review Employee Records

Review Leave Requests

Check Attendance Issues

Generate HR Reports

Recent Activities

New employee registered

Employee information updated

Leave application submitted

Attendance correction requested

Illustrative dashboard only. Actual statistics will come from the database.

### 6. How the modules work together

This is one of the most important parts of the HRIS. The modules should not work independently. They must share the correct information with each other.

EMPLOYEE MANAGEMENT / 201 FILE

Employee profile, position, salary information, documents

ATTENDANCE

DTR, time-in, time-out, overtime

LEAVE

Applications, credits, approvals

PAYROLL MANAGEMENT

Uses approved employee, attendance, leave, and salary information

ACCOUNTING AND TREASURY OUTPUTS

Payroll summaries, supporting documents, disbursement information

REPORTS AND MANAGEMENT DASHBOARDS

HR statistics, payroll summaries, attendance and management reports

RECRUITMENT AND PERFORMANCE

Connects recruitment, employment history, evaluations and training to employee records

Simplified view of the proposed information flow.

Simple example: How payroll works

HRMO registers an employee and records the approved employment information.

The Attendance Module records the employee's time-in and time-out.

The Leave Module records approved leave applications.

The Payroll Module collects the necessary attendance, leave, and salary information.

The Payroll Officer prepares the payroll.

The authorized reviewer checks the calculations.

The Accountant and Treasurer access the approved outputs for their assigned responsibilities.

The system generates payslips and payroll reports.

The system keeps a history of the transactions and approvals.

### 7. Common workflows in the system

These are examples of how users will complete their tasks.

#### A. Registering a new employee

HR Officer opens Employee Management.

Clicks Add Employee.

Enters employee information.

Uploads required documents.

Saves the employee record.

HRMO reviews and verifies the information.

The employee record becomes active after the required approval.

#### B. Filing a leave application

Employee opens the Leave Module.

Selects the leave type.

Enters the requested dates.

Submits the application.

Department Head reviews the request.

HRMO checks the leave information and credits.

The authorized approver makes the final decision.

The system updates the leave record and status.

#### C. Correcting attendance

The employee can request a slot correction for incomplete punches, no punches on a scheduled workday, or no schedule. Schedule setup is handled separately. Each request includes a proposed date and time and a required reason. An evidence reference is required when the slot has no raw punch on record. An HR Officer can file for the employee with a required reason.

The HRMO Head sees the raw punches beside the proposal and chooses Approve, Return with comment, or Reject. The system blocks self-approval and logs filing, decisions, and withdrawals. Each slot can have one pending request per day. The filer can withdraw it while pending. Employees see outcomes in the app.

Approval applies a slot override in the daily calculation. Raw punches remain immutable. The DTR identifies corrected slots, their approvers, and their approval dates. Employees awaiting initial profile approval can use attendance self-service after satisfying account security checks.

The Employees attendance screen shows flagged dates, their request status, and the pending request count. Daily punches is read-only.

#### D. Preparing payroll

Payroll Officer opens the payroll period.

The system collects approved attendance and leave records.

The system calculates salary and applicable deductions.

Payroll Officer checks errors and missing information.

Authorized reviewers examine the payroll.

Accountant and Treasurer receive the required approved outputs.

Payroll reports and payslips are generated.

The payroll period is finalized and protected from unauthorized changes.

#### E. Recruitment

HRMO creates a vacancy.

Applicants submit their information and documents.

HRMO checks applicant qualifications.

Authorized evaluators record interview and assessment results.

HRMPSB processes the selection workflow.

The selected applicant proceeds through the appropriate appointment process.

### 8. Important system features

#### Plantilla and employee profile data

The HRMO Plantilla form follows `TEMPLATE_HRMO-AND-PLANTILLA.xlsx`: each position record stores the office and position details, and the employee-specific columns are saved on the matched `employees` record by employee number. Leave employee number blank for a vacant position. Employee email remains optional. The self-service My HR Profile page reads the signed-in user's linked employee record, so employees see their HRMO-maintained Plantilla details without editing them.

The Employees page provides an HRMO-only download and preview/apply flow for the editable `EMPLOYEES` workbook. Employee Number, First Name, Last Name, Department, and Employment Status are required; middle name, suffix, position, email, and custom columns are optional. The preview shows the employee number as the username and the generated temporary password; commit uses that same password. Email setup links are attempted when an email is provided. Manual employee creation also creates an Employee login account and shows its temporary credentials once. Both account creation flows require a password change and MFA. Plantilla and other profile fields are filled later through Plantilla and are not required for account import; Appointment Status remains distinct from Employment Status. Employee import matches by employee number and does not create or modify Plantilla positions. The employee directory shows status counts and sorts by employment status, then employee name. The Plantilla page displays the full saved position records with linked employee numbers. Keep identifier columns as text to preserve leading zeroes. The Plantilla source workbook remains privately stored at `storage/app/private/TEMPLATE_HRMO-AND-PLANTILLA-IMPORT.xlsx`; employee import columns and status choices are managed in the HRIS UI.

Aside from the main modules, the HRIS should include these supporting functions:

Search: Find employees, documents, applications, and records quickly.

Notifications: Inform users about pending approvals and important updates.

Approval tracking: Show who needs to review or approve a request.

File upload: Store employee documents and supporting files securely.

Export and printing: Generate PDF, Excel, and CSV reports where appropriate.

Audit trail: Record who created, updated, approved, or changed a record.

Role permissions: Restrict pages and actions based on assigned responsibilities.

Two-factor authentication: Require a second verification step when signing in.

Mobile-friendly pages: Allow employees to use the system on their phones.

Backup and recovery: Protect system records against data loss.

System configuration: Allow authorized staff to update rules and settings without changing application code.

### 9. Recommended development order

The system should be built in stages rather than developing all modules at the same time.

| Phase | Modules                               | Main goal                                                |
| ----- | ------------------------------------- | -------------------------------------------------------- |
| 0     | Planning and requirements             | Confirm rules, offices, forms, responsibilities and data |
| 1     | Login, roles, organization, 201 files | Establish the system foundation                          |
| 2     | Attendance and leave                  | Make attendance and leave processing digital             |
| 3     | Payroll                               | Prepare and validate payroll processing                  |
| 4     | Recruitment and performance           | Complete the wider HR functions                          |
| 5     | Testing and deployment                | Train users, test security and launch the system         |

The strategic plan estimates a pilot after the foundation phase and a complete rollout over approximately 12–14 months. The actual timeline depends on approval, available resources, data preparation, and testing.

### 10. Important rules before development

Confirm the actual municipal departments, employee categories, and authorized positions.

Obtain the current employee list and existing 201 file requirements.

Confirm the official salary schedule and approving ordinance.

Confirm current leave rules and approval responsibilities.

Confirm the biometric device and attendance data format.

Verify current contribution rates and statutory requirements with the responsible offices.

Confirm the current CSC forms and document retention rules.

Decide who can view, edit, approve, and export sensitive information.

Require two-factor authentication for every user.

Do not allow one person to prepare and independently release payroll.

Do not allow MIS administrators to approve HR transactions.

Test payroll against the existing manual process before using it for actual payroll.

### 11. The complete system in one simple view

| Office / User     | What they mainly do in the HRIS                                        |
| ----------------- | ---------------------------------------------------------------------- |
| Employee          | View personal records, attendance, leave, payslips and submit requests |
| Department Head   | Monitor department employees and approve assigned requests             |
| HR Officer        | Manage employee records, attendance and leave                          |
| HRMO Head         | Supervise HR operations and approve authorized HR transactions         |
| Payroll Officer   | Prepare and check payroll                                              |
| Accountant        | Review payroll and accounting outputs                                  |
| Treasurer         | Handle authorized payroll disbursement information                     |
| Mayor             | Monitor overall workforce and management reports                       |
| MIS Administrator | Maintain accounts, security, backups and technical operations          |
| DPO               | Monitor privacy-related matters                                        |
| Auditor           | Review authorized records and audit trails                             |

The main idea: The HRIS serves as the central employee-management system of the municipality. HRMO manages personnel information, employees access their own permitted records, department heads supervise their staff, payroll processes approved information, management views summaries, and authorized offices review transactions.

This creates one organized process instead of separate, disconnected manual records.
