# Graph Report - LGU_Clarin_HRIS_SYSTEM  (2026-11-26)

## Corpus Check
- 385 files · ~481,582 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 47 file(s) not represented in the graph (top: (none) 30, .css 10, .conf 2)

## Summary
- 2802 nodes · 6707 edges · 158 communities (97 shown, 61 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 88 edges (avg confidence: 0.86)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `92597f89`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\RedirectResponse
- lucide-react
- two-factor-recovery-codes.tsx
- PasswordResetTest.php
- EmployeeActivationController.php
- Illuminate\Http\Request
- AttendanceRecomputer
- app-sidebar.tsx
- Illuminate\Support\Facades\DB
- Illuminate\Database\Eloquent\Builder
- WorkspaceHeader
- Role
- Illuminate\Database\Eloquent\Model
- Inertia\Response
- DashboardController.php
- cn
- AttendanceCorrection
- AuditLog
- sidebar.tsx
- Employee
- Role
- EmployeeOnboardingSchemaTest.php
- TestCase
- leave-form-preview-dialog.tsx
- leave-form-preview-dialog.tsx
- Role
- MonthlyAttendanceReportController.php
- 3. User roles and their functions
- DailyPunchController.php
- index.ts
- scripts
- BiometricAttendanceTest.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Illuminate\Http\RedirectResponse
- Department
- DailyPunchController.php
- Employee.php
- Illuminate\Support\Facades\Schema
- require-dev
- EmployeeSpreadsheetImportTest
- button.tsx
- Employee
- HrisResourceTest.php
- HandleInertiaRequests.php
- Employee.php
- AccountInvitationTest.php
- welcome.tsx
- DashboardAnalyticsTest.php
- devDependencies
- optionalDependencies
- DashboardInsights.php
- react
- config
- DashboardInsights
- AttendancePunch
- WorkspaceHeader
- welcome.tsx
- AttendanceRecomputer
- FortifyServiceProvider
- Part B — Functional HRIS overview (preserved project scope)
- app-header.tsx
- LeaveApplication
- psr-4
- dashboard-announcements.tsx
- Laravel\Fortify\Features
- logging.php
- 7. Common workflows in the system
- collapsible.tsx
- console.php
- HrisResourceController
- ProfileController.php
- 2026_11_25_000003_widen_employee_identifiers_for_encryption.php
- config/app.php
- filesystems.php
- User
- BiometricLink
- queue.php
- EmployeeAccountDeactivationTest.php
- AttendanceDailyPunchTest.php
- app.blade.php
- Pest.php
- sidebar.tsx
- Payslip
- button.tsx
- AttendanceRecomputer.php
- Applicant
- settings.php
- delete-user.tsx
- breadcrumbs.tsx
- dialog.tsx
- EmployeeSpreadsheetImportTest
- SecurityTest.php
- MonthlyAttendanceReportTest.php
- 2026_11_18_000001_add_required_mfa_to_users.php
- PasswordResetTest.php
- DailyPunchController
- 2025_01_01_000004_create_hris_recruitment_performance_tables.php
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- .validatedPlantillaData
- DailyTimeRecordController.php
- 2026_11_20_000001_add_avatar_to_users.php
- auth.ts
- 2025_01_01_000005_create_hris_system_tables.php
- daily-time-record-preview.tsx
- react
- Architecture Decision Log
- AuthenticationTest
- Payslip
- AGENTS.md
- 2026_11_18_000001_add_workflow_to_attendance_corrections.php
- AttendanceRecord
- 2026_11_18_000001_add_required_mfa_to_users.php
- 2026_11_18_000003_allow_optional_email_and_password_setup.php
- EvaluationPeriod
- EmployeeProfileReviewTest.php
- 2026_11_23_000001_expand_attendance_punches_for_zkteco.php
- ProfileCard.jsx
- 2026_11_18_000002_allow_missing_attendance_records_in_corrections.php
- Illuminate\Support\Facades\Schema
- HrisEmployeeDirectory
- 2026_11_24_000006_add_attendance_import_recompute_summary.php
- .accountAvatarQuery
- .myProfile
- 2026_11_18_000004_add_custom_fields_to_employees.php
- 2026_11_19_000001_add_biometric_lock_to_employees.php
- 2026_11_26_000001_add_slot_requests_to_attendance_corrections.php
- useIsMobile
- 2026_11_25_000003_widen_employee_identifiers_for_encryption.php
- icon.tsx
- 2026_11_24_000002_add_attendance_import_error_status.php
- 2026_11_25_000004_create_employee_profile_revisions_table.php
- 2026_11_24_000006_add_attendance_import_recompute_summary.php
- 2026_11_24_000007_allow_daily_attendance_without_schedule_source.php
- employee_eligibilities.php
- 2026_11_24_000011_allow_pending_employee_passwords.php
- 2026_11_24_000010_create_attendance_department_assignments_table.php
- attendance-log-import.md
- employee-identifier-encryption.md

## God Nodes (most connected - your core abstractions)
1. `cn()` - 152 edges
2. `User` - 124 edges
3. `Employee` - 112 edges
4. `Button()` - 109 edges
5. `react` - 87 edges
6. `@inertiajs/react` - 67 edges
7. `Input()` - 57 edges
8. `TestCase` - 56 edges
9. `lucide-react` - 50 edges
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

## Communities (158 total, 61 thin omitted)

### Community 0 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.07
Nodes (11): GoogleLoginController, Controller, BiometricLinkController, {closure#2}(), LeaveApplicationController, LeaveReviewController, {closure#3}(), {closure#6}() (+3 more)

### Community 1 - "lucide-react"
Cohesion: 0.15
Nodes (14): Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props, PasskeyItem(), Props (+6 more)

### Community 2 - "two-factor-recovery-codes.tsx"
Cohesion: 0.24
Nodes (14): AppLogoIcon(), Props, TwoFactorRecoveryCodes(), Card(), CardContent(), CardDescription(), CardFooter(), CardHeader() (+6 more)

### Community 3 - "PasswordResetTest.php"
Cohesion: 0.05
Nodes (42): dependencies, aos, class-variance-authority, clsx, cmdk, concurrently, gsap, @hugeicons/core-free-icons (+34 more)

### Community 4 - "EmployeeActivationController.php"
Cohesion: 0.11
Nodes (3): {closure#1}(), EmployeeActivationController, EmployeeActivationCode

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.08
Nodes (16): AccountInvitationController, {closure#1}(), EmployeeCredentialController, EmployeeProfileDraftController, EmployeeActivationSlips, EmployeeSpreadsheetImport, {closure#1}(), {closure#11}() (+8 more)

### Community 6 - "AttendanceRecomputer"
Cohesion: 0.06
Nodes (28): {closure#3}(), {closure#4}(), {closure#6}(), {closure#2}(), {closure#4}(), {closure#6}(), {closure#7}(), {closure#3}() (+20 more)

### Community 7 - "app-sidebar.tsx"
Cohesion: 0.19
Nodes (13): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+5 more)

### Community 8 - "Illuminate\Support\Facades\DB"
Cohesion: 0.06
Nodes (9): AttendanceRecomputeCommand, BootstrapHrmoHead, {closure#1}(), {closure#1}(), {closure#5}(), {closure#6}(), AuditLog, {closure#1}() (+1 more)

### Community 9 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.12
Nodes (17): {closure#10}(), {closure#18}(), {closure#19}(), {closure#2}(), {closure#21}(), {closure#22}(), {closure#23}(), {closure#3}() (+9 more)

### Community 10 - "WorkspaceHeader"
Cohesion: 0.19
Nodes (31): DailyTimeRecordDialog(), PreviewContent(), DeleteUser(), EmployeeAccountStatusAction(), EmployeeCodeReissue(), EmployeeIdentifierReveal(), Confirm, Context (+23 more)

### Community 11 - "Role"
Cohesion: 0.05
Nodes (16): {closure#20}(), {closure#7}(), Department, LeaveType, PayrollPeriod, Position, {closure#3}(), DepartmentFactory (+8 more)

### Community 13 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.10
Nodes (27): sonner, withApp(), AppearanceToggleTab(), Toaster(), TooltipProvider(), Appearance, applyTheme(), getStoredAppearance() (+19 more)

### Community 14 - "Inertia\Response"
Cohesion: 0.11
Nodes (3): DashboardController, AttendanceLogImportController, DailyTimeRecordController

### Community 15 - "DashboardController.php"
Cohesion: 0.06
Nodes (19): {closure#4}(), {closure#5}(), {closure#7}(), {closure#1}(), {closure#1}(), LeaveApplication, LoginActivity, Payroll (+11 more)

### Community 16 - "cn"
Cohesion: 0.09
Nodes (32): class-variance-authority, @radix-ui/react-navigation-menu, @radix-ui/react-toggle, @radix-ui/react-toggle-group, AlertError(), DirectoryResults(), Alert(), AlertDescription() (+24 more)

### Community 17 - "AttendanceCorrection"
Cohesion: 0.06
Nodes (11): AttendanceScheduleSchemaTest, {closure#1}(), PasswordConfirmationTest, RegistrationTest, {closure#1}(), TwoFactorChallengeTest, DashboardTest, ExampleTest (+3 more)

### Community 18 - "AuditLog"
Cohesion: 0.18
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#7}(), EmployeeProfileReviewController, EmployeeProfileRevision

### Community 19 - "sidebar.tsx"
Cohesion: 0.08
Nodes (42): @radix-ui/react-separator, AppLogo(), AppSidebar(), mainNavItems, NavFooter(), NavMain(), Separator(), SidebarContent() (+34 more)

### Community 20 - "Employee"
Cohesion: 0.06
Nodes (12): {closure#4}(), {closure#1}(), {closure#7}(), EmployeeIdentifierRevealController, {closure#13}(), {closure#29}(), {closure#41}(), {closure#1}() (+4 more)

### Community 21 - "Role"
Cohesion: 0.04
Nodes (27): DepartmentAssignment, {closure#30}(), {closure#6}(), {closure#10}(), ApplicantDocument, ApplicantEvaluation, AttendanceClockInRule, AttendanceCorrection (+19 more)

### Community 22 - "EmployeeOnboardingSchemaTest.php"
Cohesion: 0.09
Nodes (26): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+18 more)

### Community 24 - "leave-form-preview-dialog.tsx"
Cohesion: 0.12
Nodes (36): AppHeader(), Props, AppSidebarHeader(), Destination, AppearanceToggle(), options, NavUser(), Avatar() (+28 more)

### Community 25 - "leave-form-preview-dialog.tsx"
Cohesion: 0.17
Nodes (18): Choice(), creditRows, FormLine(), LeaveApplicant, LeaveDraft, LeaveFormPreview(), LeaveFormPreviewDialog(), leaveReasons (+10 more)

### Community 26 - "Role"
Cohesion: 0.08
Nodes (3): Role, RoleFactory, {closure#2}()

### Community 27 - "MonthlyAttendanceReportController.php"
Cohesion: 0.15
Nodes (8): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), MonthlyAttendanceReportController, DashboardInsights

### Community 28 - "3. User roles and their functions"
Cohesion: 0.25
Nodes (8): devDependencies, @babel/core, babel-plugin-react-compiler, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @types/aos, @types/node, vite-plus

### Community 29 - "DailyPunchController.php"
Cohesion: 0.07
Nodes (40): recharts, DashboardAnnouncement, ChartConfig, ChartContainer(), ChartContext, ChartContextProps, ChartLegendContent(), ChartStyle() (+32 more)

### Community 30 - "index.ts"
Cohesion: 0.07
Nodes (37): @laravel/passkeys, lucide-react, InputError(), PasskeyRegistration(), Props, PasskeyVerify(), Props, PasswordInput() (+29 more)

### Community 31 - "scripts"
Cohesion: 0.22
Nodes (5): {closure#18}(), {closure#35}(), {closure#36}(), {closure#37}(), HrisResourceController

### Community 32 - "BiometricAttendanceTest.php"
Cohesion: 0.10
Nodes (9): {closure#10}(), {closure#12}(), {closure#14}(), {closure#15}(), {closure#19}(), {closure#3}(), {closure#5}(), {closure#7}() (+1 more)

### Community 34 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.10
Nodes (20): aliases, components, hooks, lib, ui, utils, iconLibrary, registries (+12 more)

### Community 35 - "Department"
Cohesion: 0.10
Nodes (20): runs-on, steps, jobs, ci, name, on, pull_request, push (+12 more)

### Community 36 - "DailyPunchController.php"
Cohesion: 0.10
Nodes (4): {closure#14}(), {closure#24}(), {closure#26}(), {closure#8}()

### Community 37 - "Employee.php"
Cohesion: 0.12
Nodes (3): Applicant, Vacancy, ApplicantFactory

### Community 38 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.05
Nodes (13): {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}(), {closure#2}() (+5 more)

### Community 39 - "require-dev"
Cohesion: 0.11
Nodes (19): input-otp, react, GridScanIcon(), Props, TwoFactorSetupModal(), TwoFactorSetupStep(), TwoFactorVerificationStep(), InputOTP (+11 more)

### Community 41 - "button.tsx"
Cohesion: 0.05
Nodes (42): overrides, vite, private, $schema, type, @babel/core, babel-plugin-react-compiler, clsx (+34 more)

### Community 42 - "Employee"
Cohesion: 0.13
Nodes (14): A10. Minimum test and verification matrix, A11. Deployment checklist (copy and complete per release), A12. CI and reviewer guardrail for documentation changes, A13. Open decisions / production blockers — do not guess, A15. Mandatory major-change record template, A16. External references (for validation, not a substitute for municipal sign-off), A1. Document authority, roles, and status, A2. Major-change definition and mandatory Markdown update (+6 more)

### Community 43 - "HrisResourceTest.php"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

### Community 44 - "HandleInertiaRequests.php"
Cohesion: 0.07
Nodes (11): ResetUserPassword, PasswordValidationRules, ProfileValidationRules, SecurityController, ImportAttendanceLogRequest, UpdateBiometricLinkRequest, PasswordUpdateRequest, ProfileDeleteRequest (+3 more)

### Community 45 - "Employee.php"
Cohesion: 0.25
Nodes (8): optionalDependencies, @laravel/multiplex, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu, @tailwindcss/oxide-win32-x64-msvc

### Community 46 - "AccountInvitationTest.php"
Cohesion: 0.07
Nodes (12): {closure#32}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#7}(), {closure#8}(), {closure#9}(), HandleInertiaRequests (+4 more)

### Community 47 - "welcome.tsx"
Cohesion: 0.08
Nodes (37): aos, gsap, buildAtlas(), clamp(), CLICK_EFFECTS, compile(), createTarget(), DEFAULT_COLORS (+29 more)

### Community 48 - "DashboardAnalyticsTest.php"
Cohesion: 0.13
Nodes (7): {closure#11}(), {closure#13}(), {closure#14}(), {closure#3}(), {closure#5}(), {closure#7}(), {closure#9}()

### Community 49 - "devDependencies"
Cohesion: 0.22
Nodes (8): PunchClassifier, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), schedule()

### Community 50 - "optionalDependencies"
Cohesion: 0.14
Nodes (13): 10. Important rules before development, 11. The complete system in one simple view, 1. Main purpose of the system, 4. Suggested dashboard and sidebar structure, 5. What should appear on each dashboard?, 6. How the modules work together, 8. Important system features, 9. Recommended development order (+5 more)

### Community 52 - "react"
Cohesion: 0.06
Nodes (39): @inertiajs/react, AttendanceWorkspaceHeader(), viewLink(), views, columns, DailyTimeRecordData, DailyTimeRecordPreview(), useFeedback() (+31 more)

### Community 53 - "config"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 54 - "DashboardInsights"
Cohesion: 0.15
Nodes (12): Architecture Decision Log, D-001: Application stack (Laravel + React + Inertia), D-002: PHP 8.4 is the minimum version, D-003: MySQL 8.4 instead of PostgreSQL 16, D-004: RustFS replaces MinIO for local file storage, D-005: The app container does not use `env_file`, D-006: Tests run on in-memory SQLite with forced settings, D-007: Frontend is built on the host, not in Docker (+4 more)

### Community 55 - "AttendancePunch"
Cohesion: 0.24
Nodes (5): {closure#25}(), {closure#27}(), {closure#6}(), {closure#7}(), AttendancePunch

### Community 56 - "WorkspaceHeader"
Cohesion: 0.08
Nodes (26): EmployeeProfileDraft(), fields, ReviewFields, WorkspaceHeader(), downloadEmployeeImportErrors(), AttendanceCorrections(), Correction, Props (+18 more)

### Community 57 - "welcome.tsx"
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#2}(), SecurityTest

### Community 58 - "AttendanceRecomputer"
Cohesion: 0.08
Nodes (11): EmployeeDirectory, {closure#1}(), ScheduleController, DepartmentSchedule, EmployeeScheduleOverride, AttendanceRecomputer, ScheduleResolver, AttendanceCorrectionController (+3 more)

### Community 61 - "Part B — Functional HRIS overview (preserved project scope)"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 62 - "app-header.tsx"
Cohesion: 0.17
Nodes (11): default-days, patterns, github-actions, interval, updates, cooldown, directory, groups (+3 more)

### Community 63 - "LeaveApplication"
Cohesion: 0.17
Nodes (12): 10. Data Protection Officer (DPO), 11. Auditor / Authorized Audit User, 1. Employee, 2. Department Head / Office Head, 3. HR Officer, 3. User roles and their functions, 4. HRMO Head, 5. Payroll Officer (+4 more)

### Community 65 - "dashboard-announcements.tsx"
Cohesion: 0.40
Nodes (5): AnnouncementEntry(), DashboardAnnouncements(), manilaClock, manilaDateTime, manilaDateTimeOptions

### Community 67 - "logging.php"
Cohesion: 0.18
Nodes (11): build, networks, volumes, context, dockerfile, image, networks, ports (+3 more)

### Community 68 - "7. Common workflows in the system"
Cohesion: 0.18
Nodes (11): require-dev, fakerphp/faker, larastan/larastan, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+3 more)

### Community 69 - "collapsible.tsx"
Cohesion: 0.18
Nodes (8): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}()

### Community 70 - "console.php"
Cohesion: 0.18
Nodes (11): 2. Main system modules, A. Employee Management / 201 Files, B. Organization and Plantilla Management, C. Attendance and Daily Time Record (DTR), D. Leave Management, E. Payroll Management, F. Recruitment and Applicant Management, G. Performance Management (+3 more)

### Community 71 - "HrisResourceController"
Cohesion: 0.28
Nodes (7): @hugeicons/core-free-icons, @hugeicons/react, clamp(), FOILS, isDark(), ProfileCard(), THEMES

### Community 72 - "ProfileController.php"
Cohesion: 0.09
Nodes (5): EmployeeDayController, EnsureHrisAccountReady, ExpireIdleHrisSession, HandleAppearance, HrisEnrollmentPolicy

### Community 74 - "config/app.php"
Cohesion: 0.18
Nodes (6): EmployeeIdentifier, {closure#1}(), {closure#2}(), EncryptEmployeeIdentifiers, {closure#2}(), {closure#5}()

### Community 75 - "filesystems.php"
Cohesion: 0.20
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}()

### Community 76 - "User"
Cohesion: 0.06
Nodes (14): User, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#3}(), BiometricLinkVerifier, {closure#2}() (+6 more)

### Community 77 - "BiometricLink"
Cohesion: 0.20
Nodes (4): BiometricLink, {closure#1}(), {closure#2}(), {closure#5}()

### Community 78 - "queue.php"
Cohesion: 0.40
Nodes (5): Auth, InertiaConfig, @inertiajs/core, InputHTMLAttributes, react

### Community 80 - "AttendanceDailyPunchTest.php"
Cohesion: 0.10
Nodes (7): {closure#1}(), {closure#1}(), ImportAttendanceLogJob, AttendanceImport, AttendanceLogParser, {closure#2}(), {closure#3}()

### Community 81 - "app.blade.php"
Cohesion: 0.22
Nodes (8): driver, networks, hris, volumes, mysql_data, redis_data, rustfs_data, vendor_data

### Community 82 - "Pest.php"
Cohesion: 0.22
Nodes (9): environment, image, networks, volumes, MYSQL_DATABASE, MYSQL_PASSWORD, MYSQL_ROOT_PASSWORD, MYSQL_USER (+1 more)

### Community 83 - "sidebar.tsx"
Cohesion: 0.22
Nodes (9): RUSTFS_ACCESS_KEY, RUSTFS_CONSOLE_ENABLE, RUSTFS_SECRET_KEY, environment, image, networks, ports, volumes (+1 more)

### Community 85 - "button.tsx"
Cohesion: 0.06
Nodes (65): @radix-ui/react-slot, AttendanceDirectory(), AttendanceDirectoryProps, EmployeeAttendance, statuses, statusFor(), BiometricIdField(), PersonAvatar() (+57 more)

### Community 86 - "AttendanceRecomputer.php"
Cohesion: 0.15
Nodes (7): AttendanceDaily, {closure#10}(), {closure#11}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}()

### Community 87 - "Applicant"
Cohesion: 0.25
Nodes (8): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php

### Community 88 - "settings.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 89 - "delete-user.tsx"
Cohesion: 0.21
Nodes (4): DatabaseSeeder, DemoPortalAccountSeeder, PlantillaDepartmentSeeder, CHG-0002 - Fixed offices and account setup

### Community 90 - "breadcrumbs.tsx"
Cohesion: 0.47
Nodes (8): Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink(), BreadcrumbList(), BreadcrumbPage(), BreadcrumbSeparator()

### Community 91 - "dialog.tsx"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 92 - "EmployeeSpreadsheetImportTest"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 94 - "MonthlyAttendanceReportTest.php"
Cohesion: 0.29
Nodes (7): scripts, build, build:ssr, check, check:fix, dev, types:check

### Community 98 - "2025_01_01_000004_create_hris_recruitment_performance_tables.php"
Cohesion: 0.53
Nodes (6): healthcheck, interval, retries, test, timeout, healthcheck

### Community 99 - "2025_08_14_170933_add_two_factor_columns_to_users_table.php"
Cohesion: 0.33
Nodes (6): depends_on, image, networks, ports, volumes, nginx

### Community 100 - ".validatedPlantillaData"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 101 - "DailyTimeRecordController.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 102 - "2026_11_20_000001_add_avatar_to_users.php"
Cohesion: 0.33
Nodes (6): 7. Common workflows in the system, A. Registering a new employee, B. Filing a leave application, C. Correcting attendance, D. Preparing payroll, E. Recruitment

### Community 104 - "2025_01_01_000005_create_hris_system_tables.php"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 105 - "daily-time-record-preview.tsx"
Cohesion: 0.40
Nodes (5): depends_on, condition, db, redis, condition

### Community 106 - "react"
Cohesion: 0.40
Nodes (5): command, image, networks, volumes, redis

### Community 107 - "Architecture Decision Log"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 108 - "AuthenticationTest"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 111 - "AGENTS.md"
Cohesion: 0.17
Nodes (9): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+1 more)

### Community 113 - "AttendanceRecord"
Cohesion: 0.25
Nodes (4): {closure#1}(), {closure#2}(), AttendanceRecord, AttendanceRecordFactory

### Community 120 - "2026_11_18_000002_allow_missing_attendance_records_in_corrections.php"
Cohesion: 0.50
Nodes (4): A14. Change Register — MUST be maintained in THIS file, CHG-0003 - Employee spreadsheet import and department directory, CHG-0004 - Employee import workbook columns, CHG-0005 - Employee account creation workflow

### Community 137 - ".accountAvatarQuery"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 142 - "useIsMobile"
Cohesion: 0.70
Nodes (4): getServerSnapshot(), isSmallerThanBreakpoint(), mediaQueryListener(), useIsMobile()

## Knowledge Gaps
- **20 isolated node(s):** `@types/react`, `@types/react-dom`, `concurrently`, `motion`, `react-dom` (+15 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1045 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **61 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Http\RedirectResponse`, `EmployeeActivationController.php`, `Illuminate\Http\Request`, `AttendanceRecomputer`, `Illuminate\Support\Facades\DB`, `Role`, `Button`, `DashboardController.php`, `BiometricLinkVerifier.php`, `AttendanceCorrection`, `2026_11_25_000003_widen_employee_identifiers_for_encryption.php`, `BiometricLinkVerifier.php`, `Employee`, `Role`, `Role`, `MonthlyAttendanceReportController.php`, `BiometricAttendanceTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Employee`, `HandleInertiaRequests.php`, `AccountInvitationTest.php`, `DashboardAnalyticsTest.php`, `DashboardInsights.php`, `welcome.tsx`, `AttendanceRecomputer`, `EmployeeSpreadsheetImportTest.php`, `psr-4`, `Laravel\Fortify\Features`, `ProfileController.php`, `EmployeeAccountDeactivationTest.php`, `AttendanceDailyPunchTest.php`, `delete-user.tsx`, `SecurityTest.php`, `PasswordResetTest.php`, `EmployeeActivationTest.php`?**
  _High betweenness centrality (0.081) - this node is a cross-community bridge._
- **What connects `@types/react`, `@types/react-dom`, `concurrently` to the rest of the system?**
  _20 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\RedirectResponse` be split into smaller, more focused modules?**
  _Cohesion score 0.06802721088435375 - nodes in this community are weakly interconnected._
- **Why does `Employee` connect `Employee` to `Illuminate\Http\RedirectResponse`, `Illuminate\Http\Request`, `AttendanceRecomputer`, `Illuminate\Support\Facades\DB`, `Role`, `Button`, `Inertia\Response`, `DashboardController.php`, `BiometricLinkVerifier.php`, `AttendanceCorrection`, `AuditLog`, `BiometricLinkVerifier.php`, `Role`, `Role`, `MonthlyAttendanceReportController.php`, `scripts`, `BiometricAttendanceTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `DailyPunchController.php`, `Employee.php`, `Employee`, `DashboardAnalyticsTest.php`, `AttendancePunch`, `AttendanceRecomputer`, `EmployeeSpreadsheetImportTest.php`, `psr-4`, `ProfileController.php`, `User`, `EmployeeAccountDeactivationTest.php`, `AttendanceDailyPunchTest.php`, `AttendanceRecomputer.php`, `AttendanceRecord`, `EmployeeActivationTest.php`?**
  _High betweenness centrality (0.072) - this node is a cross-community bridge._
- **Should `PasswordResetTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.047619047619047616 - nodes in this community are weakly interconnected._
- **Why does `Part A — Mandatory production engineering rules` connect `Employee` to `2026_11_18_000002_allow_missing_attendance_records_in_corrections.php`, `optionalDependencies`, `Employee`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **Should `EmployeeActivationController.php` be split into smaller, more focused modules?**
  _Cohesion score 0.11462450592885376 - nodes in this community are weakly interconnected._