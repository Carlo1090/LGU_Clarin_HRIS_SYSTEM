# Graph Report - lgu-deployment-merge-68f75db594af43b9a332dc73f46bc4d4  (2026-11-25)

## Corpus Check
- 384 files · ~481,056 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 48 file(s) not represented in the graph (top: (none) 31, .css 10, .conf 2)

## Summary
- 2774 nodes · 6663 edges · 165 communities (105 shown, 60 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 85 edges (avg confidence: 0.86)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `a059b5da`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- EmployeeActivationController.php
- Input
- DailyPunchController.php
- dependencies
- Button
- Illuminate\Http\Request
- Inertia\Testing\AssertableInertia
- sidebar.tsx
- Illuminate\Support\Facades\DB
- Role
- dialog.tsx
- Illuminate\Database\Eloquent\Factories\Factory
- HrisResourceController.php
- use-appearance.tsx
- Illuminate\Http\RedirectResponse
- DashboardController.php
- cn
- TestCase
- EmployeeProfileReviewController.php
- layout.tsx
- User
- Illuminate\Database\Eloquent\Model
- Illuminate\Database\Schema\Blueprint
- EmployeeImportTemplate
- app-sidebar-header.tsx
- AttendanceRecomputer.php
- web.php
- MonthlyAttendanceReportController.php
- devDependencies
- dashboard.tsx
- InputError
- HrisResourceController
- index.ts
- HrisResourceTest.php
- components.json
- with
- two-factor-recovery-codes.tsx
- leave-form-preview-dialog.tsx
- Illuminate\Database\Migrations\Migration
- two-factor-setup-modal.tsx
- FortifyServiceProvider
- package.json
- Part A — Mandatory production engineering rules
- compilerOptions
- Illuminate\Foundation\Http\FormRequest
- optionalDependencies
- HandleInertiaRequests.php
- welcome.tsx
- @inertiajs/react
- PunchClassifier
- Part B — Functional HRIS overview (preserved project scope)
- ProfileUpdateTest
- PortalTest.php
- scripts
- Architecture Decision Log
- EmployeeDirectory
- react
- SecurityTest.php
- AttendanceRecomputer
- EmployeeSpreadsheetImportTest.php
- AuthenticationTest
- composer.json
- updates
- 3. User roles and their functions
- Laravel\Fortify\Features
- AttendanceRecord
- Payslip
- services
- require-dev
- 2025_01_01_000004_create_hris_recruitment_performance_tables.php
- 2. Main system modules
- ProfileCard.jsx
- Inertia\Inertia
- LeaveApplication
- EmployeeIdentifier
- 2026_11_18_000002_expand_plantilla_items_for_hrmo_form.php
- PasswordResetTest.php
- DailyPunchController
- auth.ts
- ScheduleController
- AttendanceImport
- volumes
- db
- rustfs
- AuditLog
- resource-directory.tsx
- AttendancePunch
- require
- 2026_11_18_000005_add_biometric_attendance_imports.php
- Illuminate\Database\Seeder
- AccountInvitationTest.php
- config
- 2025_01_01_000001_create_hris_organization_tables.php
- AnnouncementTest.php
- scripts
- DashboardInsights
- profile.tsx
- ImportAttendanceLogJob.php
- healthcheck
- nginx
- 0001_01_01_000000_create_users_table.php
- 0001_01_01_000002_create_jobs_table.php
- 7. Common workflows in the system
- collapsible.tsx
- Department
- depends_on
- redis
- psr-4
- laravel
- 0001_01_01_000001_create_cache_table.php
- 2025_01_01_000002_create_hris_employee_tables.php
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- 2026_11_18_000001_add_required_mfa_to_users.php
- 2026_11_18_000002_allow_missing_attendance_records_in_corrections.php
- 2026_11_18_000003_allow_optional_email_and_password_setup.php
- Illuminate\Support\Facades\Schema
- 2026_11_20_000001_add_avatar_to_users.php
- 2026_11_23_000001_add_commutation_to_leave_applications.php
- 2026_11_24_000001_add_profile_details_to_users.php
- A14. Change Register — MUST be maintained in THIS file
- AGENTS.md
- HrisEmployeeDirectory.php
- pnpm-workspace.yaml
- EmployeeAccountDeactivationTest.php
- 2026_11_24_000005_finalize_attendance_schedule_indexes.php
- AttendanceCorrection
- EvaluationPeriod
- bootstrap/app.php
- employee-profile-review.tsx
- BiometricLinkVerifier
- DemoEmployeeImportDirectorySeederTest.php
- vite.config.ts
- dashboard-announcements.tsx
- 2026_11_23_000001_expand_attendance_punches_for_zkteco.php
- 2026_11_25_000003_widen_employee_identifiers_for_encryption.php
- UserFactory
- 2026_11_18_000001_add_workflow_to_attendance_corrections.php
- 2026_11_24_000002_add_attendance_import_error_status.php
- 2026_11_24_000003_add_reviewed_slots_to_attendance_records.php
- 2026_11_24_000006_add_attendance_import_recompute_summary.php
- 2026_11_24_000007_allow_daily_attendance_without_schedule_source.php
- 2026_11_24_000008_add_published_at_to_announcements.php
- 2026_11_24_000011_allow_pending_employee_passwords.php
- 2026_11_24_000010_create_attendance_department_assignments_table.php
- 2026_11_25_000004_create_employee_profile_revisions_table.php
- attendance-log-import.md
- employee-identifier-encryption.md
- overrides

## God Nodes (most connected - your core abstractions)
1. `cn()` - 152 edges
2. `User` - 121 edges
3. `Employee` - 111 edges
4. `Button()` - 107 edges
5. `react` - 88 edges
6. `@inertiajs/react` - 67 edges
7. `TestCase` - 56 edges
8. `Input()` - 55 edges
9. `lucide-react` - 51 edges
10. `Department` - 43 edges

## Surprising Connections (you probably didn't know these)
- `A3. Architecture and Laravel code quality` --references--> `Employee`  [INFERRED]
  HRIS_PROJECT_SCAN.md → app/Models/Employee.php
- `CHG-0002 - Fixed offices and account setup` --references--> `DatabaseSeeder`  [INFERRED]
  HRIS_PROJECT_SCAN.md → database/seeders/DatabaseSeeder.php
- `{closure#5}()` --calls--> `PunchClassifier`  [EXTRACTED]
  tests/Unit/PunchClassifierTest.php → app/Attendance/Services/PunchClassifier.php
- `{closure#7}()` --references--> `AttendancePunch`  [EXTRACTED]
  app/Attendance/Services/AttendanceRecomputer.php → app/Models/AttendancePunch.php
- `{closure#1}()` --calls--> `PunchClassifier`  [EXTRACTED]
  app/Providers/AppServiceProvider.php → app/Attendance/Services/PunchClassifier.php

## Import Cycles
- None detected.

## Communities (165 total, 60 thin omitted)

### Community 0 - "EmployeeActivationController.php"
Cohesion: 0.10
Nodes (9): {closure#1}(), EmployeeActivationController, EmployeeActivationCode, EmployeeAccountCreator, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}() (+1 more)

### Community 1 - "Input"
Cohesion: 0.10
Nodes (14): lucide-react, BiometricIdField(), PasswordInput(), navigation, Props, TextLink(), IconProps, Input() (+6 more)

### Community 2 - "DailyPunchController.php"
Cohesion: 0.08
Nodes (15): {closure#17}(), {closure#18}(), {closure#19}(), {closure#2}(), {closure#20}(), {closure#21}(), {closure#22}(), {closure#23}() (+7 more)

### Community 3 - "dependencies"
Cohesion: 0.05
Nodes (42): dependencies, aos, class-variance-authority, clsx, cmdk, concurrently, gsap, @hugeicons/core-free-icons (+34 more)

### Community 4 - "Button"
Cohesion: 0.07
Nodes (49): AttendanceDirectory(), AttendanceDirectoryProps, EmployeeAttendance, statuses, statusFor(), AttendanceWorkspaceHeader(), viewLink(), views (+41 more)

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (12): GoogleLoginController, AccountInvitationController, {closure#1}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#2}(), {closure#3}() (+4 more)

### Community 6 - "Inertia\Testing\AssertableInertia"
Cohesion: 0.05
Nodes (28): {closure#10}(), {closure#12}(), {closure#14}(), {closure#15}(), {closure#19}(), {closure#3}(), {closure#5}(), {closure#7}() (+20 more)

### Community 7 - "sidebar.tsx"
Cohesion: 0.12
Nodes (37): AppLogo(), AppSidebar(), mainNavItems, NavFooter(), NavMain(), NavUser(), Sheet(), SheetContent() (+29 more)

### Community 8 - "Illuminate\Support\Facades\DB"
Cohesion: 0.09
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 9 - "Role"
Cohesion: 0.08
Nodes (3): Role, RoleFactory, {closure#2}()

### Community 10 - "dialog.tsx"
Cohesion: 0.20
Nodes (28): DeleteUser(), EmployeeAccountStatusAction(), EmployeeCodeReissue(), EmployeeIdentifierReveal(), Confirm, Context, CredentialsAcknowledgment(), FeedbackProvider() (+20 more)

### Community 11 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.06
Nodes (12): {closure#7}(), Applicant, PayrollPeriod, Position, Vacancy, ApplicantFactory, DepartmentFactory, EmployeeFactory (+4 more)

### Community 12 - "HrisResourceController.php"
Cohesion: 0.04
Nodes (9): AttendanceDaily, {closure#13}(), {closure#26}(), {closure#27}(), {closure#35}(), {closure#39}(), {closure#6}(), {closure#7}() (+1 more)

### Community 13 - "use-appearance.tsx"
Cohesion: 0.10
Nodes (28): sonner, withApp(), AppearanceToggleTab(), Toaster(), TooltipProvider(), Appearance, applyTheme(), getStoredAppearance() (+20 more)

### Community 14 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.06
Nodes (14): Controller, AttendanceCorrectionController, AttendanceLogImportController, {closure#1}(), {closure#2}(), LeaveApplicationController, LeaveReviewController, PortalController (+6 more)

### Community 15 - "DashboardController.php"
Cohesion: 0.07
Nodes (14): {closure#4}(), {closure#5}(), {closure#7}(), LoginActivity, Payroll, PayrollDeduction, PrivacyIncident, PrivacyRequest (+6 more)

### Community 16 - "cn"
Cohesion: 0.07
Nodes (44): class-variance-authority, @radix-ui/react-navigation-menu, @radix-ui/react-slot, @radix-ui/react-toggle, @radix-ui/react-toggle-group, Breadcrumbs(), DirectoryResults(), Badge() (+36 more)

### Community 17 - "TestCase"
Cohesion: 0.07
Nodes (12): AttendanceScheduleSchemaTest, {closure#1}(), PasswordConfirmationTest, RegistrationTest, {closure#1}(), TwoFactorChallengeTest, DashboardTest, {closure#2}() (+4 more)

### Community 18 - "EmployeeProfileReviewController.php"
Cohesion: 0.17
Nodes (5): {closure#1}(), {closure#3}(), {closure#7}(), EmployeeProfileReviewController, {closure#5}()

### Community 19 - "layout.tsx"
Cohesion: 0.17
Nodes (10): Separator(), SidebarSeparator(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn, WhenCurrentUrlFn, SettingsLayout() (+2 more)

### Community 20 - "User"
Cohesion: 0.06
Nodes (8): {closure#1}(), BiometricLinkController, {closure#1}(), {closure#1}(), Employee, User, {closure#1}(), {closure#2}()

### Community 21 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.04
Nodes (24): DepartmentAssignment, {closure#2}(), {closure#9}(), ApplicantDocument, ApplicantEvaluation, AttendanceClockInRule, DisciplinaryRecord, EmployeeAppointmentChange (+16 more)

### Community 22 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.08
Nodes (30): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+22 more)

### Community 23 - "EmployeeImportTemplate"
Cohesion: 0.22
Nodes (3): EmployeeImportController, EmployeeSpreadsheetImport, EmployeeImportTemplate

### Community 24 - "app-sidebar-header.tsx"
Cohesion: 0.10
Nodes (41): AppHeader(), Props, AppSidebarHeader(), Destination, AppearanceToggle(), options, Avatar(), AvatarFallback() (+33 more)

### Community 25 - "AttendanceRecomputer.php"
Cohesion: 0.10
Nodes (8): BiometricLink, {closure#1}(), {closure#2}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}()

### Community 26 - "web.php"
Cohesion: 0.16
Nodes (3): DashboardController, DailyTimeRecordController, EmployeeIdentifierRevealController

### Community 27 - "MonthlyAttendanceReportController.php"
Cohesion: 0.12
Nodes (11): {closure#1}(), {closure#1}(), {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+3 more)

### Community 28 - "devDependencies"
Cohesion: 0.25
Nodes (8): devDependencies, @babel/core, babel-plugin-react-compiler, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @types/aos, @types/node, vite-plus

### Community 29 - "dashboard.tsx"
Cohesion: 0.08
Nodes (39): recharts, ChartConfig, ChartContainer(), ChartContext, ChartContextProps, ChartLegendContent(), ChartStyle(), ChartTooltipContent() (+31 more)

### Community 30 - "InputError"
Cohesion: 0.11
Nodes (20): @laravel/passkeys, Heading(), InputError(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props (+12 more)

### Community 31 - "HrisResourceController"
Cohesion: 0.26
Nodes (4): {closure#32}(), {closure#33}(), {closure#34}(), HrisResourceController

### Community 32 - "index.ts"
Cohesion: 0.22
Nodes (11): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+3 more)

### Community 34 - "components.json"
Cohesion: 0.10
Nodes (20): aliases, components, hooks, lib, ui, utils, iconLibrary, registries (+12 more)

### Community 35 - "with"
Cohesion: 0.10
Nodes (20): runs-on, steps, jobs, ci, name, on, pull_request, push (+12 more)

### Community 36 - "two-factor-recovery-codes.tsx"
Cohesion: 0.18
Nodes (19): AlertError(), AppLogoIcon(), Props, TwoFactorRecoveryCodes(), Alert(), AlertDescription(), AlertTitle(), alertVariants (+11 more)

### Community 37 - "leave-form-preview-dialog.tsx"
Cohesion: 0.17
Nodes (18): Choice(), creditRows, FormLine(), LeaveApplicant, LeaveDraft, LeaveFormPreview(), LeaveFormPreviewDialog(), leaveReasons (+10 more)

### Community 38 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.09
Nodes (7): {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}()

### Community 39 - "two-factor-setup-modal.tsx"
Cohesion: 0.12
Nodes (17): input-otp, GridScanIcon(), Props, TwoFactorSetupStep(), TwoFactorVerificationStep(), InputOTP, InputOTPGroup, InputOTPSeparator (+9 more)

### Community 41 - "package.json"
Cohesion: 0.06
Nodes (34): private, $schema, type, @babel/core, babel-plugin-react-compiler, clsx, cmdk, concurrently (+26 more)

### Community 42 - "Part A — Mandatory production engineering rules"
Cohesion: 0.11
Nodes (16): A10. Minimum test and verification matrix, A11. Deployment checklist (copy and complete per release), A12. CI and reviewer guardrail for documentation changes, A13. Open decisions / production blockers — do not guess, A15. Mandatory major-change record template, A16. External references (for validation, not a substitute for municipal sign-off), A1. Document authority, roles, and status, A2. Major-change definition and mandatory Markdown update (+8 more)

### Community 43 - "compilerOptions"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

### Community 44 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.08
Nodes (9): ResetUserPassword, PasswordValidationRules, ProfileValidationRules, ImportAttendanceLogRequest, UpdateBiometricLinkRequest, PasswordUpdateRequest, ProfileDeleteRequest, ProfileUpdateRequest (+1 more)

### Community 45 - "optionalDependencies"
Cohesion: 0.25
Nodes (8): optionalDependencies, @laravel/multiplex, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu, @tailwindcss/oxide-win32-x64-msvc

### Community 46 - "HandleInertiaRequests.php"
Cohesion: 0.12
Nodes (7): {closure#29}(), {closure#2}(), {closure#6}(), {closure#7}(), {closure#8}(), HandleInertiaRequests, Announcement

### Community 47 - "welcome.tsx"
Cohesion: 0.09
Nodes (32): aos, gsap, buildAtlas(), clamp(), CLICK_EFFECTS, compile(), createTarget(), DEFAULT_COLORS (+24 more)

### Community 48 - "@inertiajs/react"
Cohesion: 0.11
Nodes (20): @inertiajs/react, PreviewContent(), columns, DailyTimeRecordData, DailyTimeRecordPreview(), Column, SearchForm(), SearchProps (+12 more)

### Community 49 - "PunchClassifier"
Cohesion: 0.22
Nodes (8): PunchClassifier, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), schedule()

### Community 50 - "Part B — Functional HRIS overview (preserved project scope)"
Cohesion: 0.14
Nodes (13): 10. Important rules before development, 11. The complete system in one simple view, 1. Main purpose of the system, 4. Suggested dashboard and sidebar structure, 5. What should appear on each dashboard?, 6. How the modules work together, 8. Important system features, 9. Recommended development order (+5 more)

### Community 52 - "PortalTest.php"
Cohesion: 0.11
Nodes (8): {closure#10}(), {closure#11}(), {closure#13}(), {closure#15}(), {closure#2}(), {closure#4}(), {closure#6}(), {closure#8}()

### Community 53 - "scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 54 - "Architecture Decision Log"
Cohesion: 0.15
Nodes (12): Architecture Decision Log, D-001: Application stack (Laravel + React + Inertia), D-002: PHP 8.4 is the minimum version, D-003: MySQL 8.4 instead of PostgreSQL 16, D-004: RustFS replaces MinIO for local file storage, D-005: The app container does not use `env_file`, D-006: Tests run on in-memory SQLite with forced settings, D-007: Frontend is built on the host, not in Docker (+4 more)

### Community 55 - "EmployeeDirectory"
Cohesion: 0.19
Nodes (4): EmployeeDirectory, DepartmentSchedule, EmployeeScheduleOverride, ScheduleResolver

### Community 56 - "react"
Cohesion: 0.08
Nodes (23): react, EmployeeProfileDraft(), fields, ReviewFields, PlaceholderPatternProps, WorkspaceHeader(), downloadEmployeeImportErrors(), EmployeeOnboardingResult() (+15 more)

### Community 57 - "SecurityTest.php"
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#2}(), SecurityTest

### Community 61 - "composer.json"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 62 - "updates"
Cohesion: 0.17
Nodes (11): default-days, patterns, github-actions, interval, updates, cooldown, directory, groups (+3 more)

### Community 63 - "3. User roles and their functions"
Cohesion: 0.17
Nodes (12): 10. Data Protection Officer (DPO), 11. Auditor / Authorized Audit User, 1. Employee, 2. Department Head / Office Head, 3. HR Officer, 3. User roles and their functions, 4. HRMO Head, 5. Payroll Officer (+4 more)

### Community 65 - "AttendanceRecord"
Cohesion: 0.25
Nodes (4): {closure#2}(), {closure#3}(), AttendanceRecord, AttendanceRecordFactory

### Community 67 - "services"
Cohesion: 0.18
Nodes (11): build, networks, volumes, context, dockerfile, image, networks, ports (+3 more)

### Community 68 - "require-dev"
Cohesion: 0.18
Nodes (11): require-dev, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+3 more)

### Community 69 - "2025_01_01_000004_create_hris_recruitment_performance_tables.php"
Cohesion: 0.18
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 70 - "2. Main system modules"
Cohesion: 0.18
Nodes (11): 2. Main system modules, A. Employee Management / 201 Files, B. Organization and Plantilla Management, C. Attendance and Daily Time Record (DTR), D. Leave Management, E. Payroll Management, F. Recruitment and Applicant Management, G. Performance Management (+3 more)

### Community 71 - "ProfileCard.jsx"
Cohesion: 0.28
Nodes (7): @hugeicons/core-free-icons, @hugeicons/react, clamp(), FOILS, isDark(), ProfileCard(), THEMES

### Community 72 - "Inertia\Inertia"
Cohesion: 0.10
Nodes (7): {closure#1}(), EmployeeCredentialController, EmployeeProfileDraftController, EnsureHrisAccountReady, HandleAppearance, EmployeeActivationSlips, HrisPasswordConfirmation

### Community 73 - "LeaveApplication"
Cohesion: 0.14
Nodes (5): {closure#1}(), LeaveApplication, LeaveType, LeaveApplicationFactory, LeaveTypeFactory

### Community 74 - "EmployeeIdentifier"
Cohesion: 0.18
Nodes (6): EmployeeIdentifier, {closure#1}(), {closure#2}(), EncryptEmployeeIdentifiers, {closure#2}(), {closure#5}()

### Community 75 - "2026_11_18_000002_expand_plantilla_items_for_hrmo_form.php"
Cohesion: 0.20
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}()

### Community 76 - "PasswordResetTest.php"
Cohesion: 0.13
Nodes (3): {closure#1}(), HrisAccountSetup, PasswordResetTest

### Community 77 - "DailyPunchController"
Cohesion: 0.19
Nodes (4): {closure#13}(), {closure#25}(), {closure#8}(), DailyPunchController

### Community 78 - "auth.ts"
Cohesion: 0.24
Nodes (8): Auth, TwoFactorSecretKey, TwoFactorSetupData, User, InertiaConfig, @inertiajs/core, InputHTMLAttributes, react

### Community 80 - "AttendanceImport"
Cohesion: 0.19
Nodes (5): {closure#1}(), AttendanceImport, AttendanceLogParser, {closure#2}(), {closure#3}()

### Community 81 - "volumes"
Cohesion: 0.22
Nodes (8): driver, networks, hris, volumes, mysql_data, redis_data, rustfs_data, vendor_data

### Community 82 - "db"
Cohesion: 0.22
Nodes (9): environment, image, networks, volumes, MYSQL_DATABASE, MYSQL_PASSWORD, MYSQL_ROOT_PASSWORD, MYSQL_USER (+1 more)

### Community 83 - "rustfs"
Cohesion: 0.22
Nodes (9): RUSTFS_ACCESS_KEY, RUSTFS_CONSOLE_ENABLE, RUSTFS_SECRET_KEY, environment, image, networks, ports, volumes (+1 more)

### Community 84 - "AuditLog"
Cohesion: 0.06
Nodes (6): AttendanceRecomputeCommand, BootstrapHrmoHead, {closure#1}(), {closure#7}(), {closure#5}(), AuditLog

### Community 85 - "resource-directory.tsx"
Cohesion: 0.13
Nodes (28): DirectoryLayout, directoryLayouts, DirectoryOverview(), Field, HrisRow, HrisValue, ResourceDirectory(), clamp() (+20 more)

### Community 86 - "AttendancePunch"
Cohesion: 0.24
Nodes (5): {closure#24}(), {closure#26}(), {closure#6}(), {closure#7}(), AttendancePunch

### Community 87 - "require"
Cohesion: 0.25
Nodes (8): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php

### Community 88 - "2026_11_18_000005_add_biometric_attendance_imports.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 89 - "Illuminate\Database\Seeder"
Cohesion: 0.16
Nodes (5): DatabaseSeeder, DemoDashboardSeeder, DemoPortalAccountSeeder, PlantillaDepartmentSeeder, CHG-0002 - Fixed offices and account setup

### Community 91 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 92 - "2025_01_01_000001_create_hris_organization_tables.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 94 - "scripts"
Cohesion: 0.29
Nodes (7): scripts, build, build:ssr, check, check:fix, dev, types:check

### Community 96 - "profile.tsx"
Cohesion: 0.17
Nodes (10): autocomplete, Details, Field, labels, lengths, Profile(), ProfileCard(), ProfileValues (+2 more)

### Community 98 - "healthcheck"
Cohesion: 0.53
Nodes (6): healthcheck, interval, retries, test, timeout, healthcheck

### Community 99 - "nginx"
Cohesion: 0.33
Nodes (6): depends_on, image, networks, ports, volumes, nginx

### Community 100 - "0001_01_01_000000_create_users_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 101 - "0001_01_01_000002_create_jobs_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 102 - "7. Common workflows in the system"
Cohesion: 0.33
Nodes (6): 7. Common workflows in the system, A. Registering a new employee, B. Filing a leave application, C. Correcting attendance, D. Preparing payroll, E. Recruitment

### Community 104 - "Department"
Cohesion: 0.12
Nodes (5): {closure#15}(), {closure#3}(), {closure#6}(), {closure#6}(), Department

### Community 105 - "depends_on"
Cohesion: 0.40
Nodes (5): depends_on, condition, db, redis, condition

### Community 106 - "redis"
Cohesion: 0.40
Nodes (5): command, image, networks, volumes, redis

### Community 107 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 108 - "laravel"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 111 - "2025_01_01_000002_create_hris_employee_tables.php"
Cohesion: 0.17
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+1 more)

### Community 116 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.09
Nodes (7): {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}()

### Community 120 - "A14. Change Register — MUST be maintained in THIS file"
Cohesion: 0.50
Nodes (4): A14. Change Register — MUST be maintained in THIS file, CHG-0003 - Employee spreadsheet import and department directory, CHG-0004 - Employee import workbook columns, CHG-0005 - Employee account creation workflow

### Community 137 - "2026_11_24_000005_finalize_attendance_schedule_indexes.php"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 138 - "AttendanceCorrection"
Cohesion: 0.33
Nodes (3): {closure#3}(), {closure#5}(), AttendanceCorrection

### Community 141 - "bootstrap/app.php"
Cohesion: 0.28
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 142 - "employee-profile-review.tsx"
Cohesion: 0.31
Nodes (8): AuditEntry, EmployeeProfileReview(), Page, Pagination(), ProfileSettings(), ReviewField(), ReviewRecord, Settings

### Community 145 - "vite.config.ts"
Cohesion: 0.25
Nodes (7): @inertiajs/vite, laravel-vite-plugin, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @tailwindcss/vite, vite-plus, @vitejs/plugin-react

### Community 147 - "dashboard-announcements.tsx"
Cohesion: 0.33
Nodes (6): AnnouncementEntry(), DashboardAnnouncement, DashboardAnnouncements(), manilaClock, manilaDateTime, manilaDateTimeOptions

### Community 148 - "2026_11_23_000001_expand_attendance_punches_for_zkteco.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

## Knowledge Gaps
- **20 isolated node(s):** `@types/react`, `@types/react-dom`, `concurrently`, `motion`, `react-dom` (+15 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1038 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **60 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Employee` connect `User` to `EmployeeActivationController.php`, `DailyPunchController.php`, `Inertia\Testing\AssertableInertia`, `Illuminate\Support\Facades\DB`, `Role`, `Illuminate\Database\Eloquent\Factories\Factory`, `HrisResourceController.php`, `AttendanceDailyPunchTest.php`, `Illuminate\Http\RedirectResponse`, `DashboardController.php`, `BiometricLinkVerifier`, `TestCase`, `EmployeeProfileReviewController.php`, `EmployeeOnboardingSchemaTest.php`, `Illuminate\Database\Eloquent\Model`, `AttendanceRecomputer.php`, `web.php`, `MonthlyAttendanceReportController.php`, `HrisResourceController`, `HrisResourceTest.php`, `Part A — Mandatory production engineering rules`, `PortalTest.php`, `EmployeeDirectory`, `AttendanceRecomputer`, `EmployeeSpreadsheetImportTest.php`, `AttendanceRecord`, `Inertia\Inertia`, `LeaveApplication`, `AttendanceImport`, `AuditLog`, `AttendancePunch`, `AccountInvitationTest.php`, `Department`, `HrisEmployeeDirectory.php`, `EmployeeAccountDeactivationTest.php`?**
  _High betweenness centrality (0.081) - this node is a cross-community bridge._
- **What connects `@types/react`, `@types/react-dom`, `concurrently` to the rest of the system?**
  _20 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `EmployeeActivationController.php` be split into smaller, more focused modules?**
  _Cohesion score 0.09803921568627451 - nodes in this community are weakly interconnected._
- **Why does `User` connect `User` to `EmployeeActivationController.php`, `Illuminate\Http\Request`, `Inertia\Testing\AssertableInertia`, `Illuminate\Support\Facades\DB`, `Role`, `Illuminate\Database\Eloquent\Factories\Factory`, `HrisResourceController.php`, `AttendanceDailyPunchTest.php`, `Illuminate\Http\RedirectResponse`, `DashboardController.php`, `BiometricLinkVerifier`, `TestCase`, `EmployeeProfileReviewController.php`, `DemoEmployeeImportDirectorySeederTest.php`, `EmployeeOnboardingSchemaTest.php`, `Illuminate\Database\Eloquent\Model`, `HrisResourceTest.php`, `Part A — Mandatory production engineering rules`, `Illuminate\Foundation\Http\FormRequest`, `HandleInertiaRequests.php`, `ProfileUpdateTest`, `PortalTest.php`, `SecurityTest.php`, `EmployeeSpreadsheetImportTest.php`, `AuthenticationTest`, `Laravel\Fortify\Features`, `Inertia\Inertia`, `PasswordResetTest.php`, `AttendanceImport`, `AuditLog`, `AccountInvitationTest.php`, `AnnouncementTest.php`, `DashboardInsights`, `ImportAttendanceLogJob.php`, `Department`, `EmployeeAccountDeactivationTest.php`?**
  _High betweenness centrality (0.062) - this node is a cross-community bridge._
- **Should `Input` be split into smaller, more focused modules?**
  _Cohesion score 0.0953058321479374 - nodes in this community are weakly interconnected._
- **Why does `Part A — Mandatory production engineering rules` connect `Part A — Mandatory production engineering rules` to `A14. Change Register — MUST be maintained in THIS file`, `Part B — Functional HRIS overview (preserved project scope)`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Should `DailyPunchController.php` be split into smaller, more focused modules?**
  _Cohesion score 0.08333333333333333 - nodes in this community are weakly interconnected._