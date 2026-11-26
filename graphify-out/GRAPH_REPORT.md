# Graph Report - LGU_Clarin_HRIS_SYSTEM  (2026-11-26)

## Corpus Check
- 384 files · ~481,196 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 47 file(s) not represented in the graph (top: (none) 30, .css 10, .conf 2)

## Summary
- 2780 nodes · 6679 edges · 158 communities (102 shown, 56 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 85 edges (avg confidence: 0.86)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `38e0d1a5`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- EmployeeAccountDeactivation
- lucide-react
- two-factor-recovery-codes.tsx
- PasswordResetTest.php
- PunchClassifier
- cn
- AttendanceRecomputer
- app-sidebar.tsx
- AttendanceLogImportController.php
- Inertia\Inertia
- WorkspaceHeader
- use-appearance.tsx
- Illuminate\Database\Eloquent\Model
- Illuminate\Http\RedirectResponse
- Position
- components.json
- AttendanceCorrection
- AuditLog
- @inertiajs/react
- Employee
- Role
- EmployeeOnboardingSchemaTest.php
- TestCase
- leave-form-preview-dialog.tsx
- leave-form-preview-dialog.tsx
- DailyTimeRecordController.php
- MonthlyAttendanceReportController.php
- 3. User roles and their functions
- DailyPunchController.php
- index.ts
- scripts
- BiometricAttendanceTest.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Illuminate\Http\RedirectResponse
- Department
- Illuminate\Database\Eloquent\Builder
- toggle-group.tsx
- Illuminate\Support\Facades\Schema
- require-dev
- Illuminate\Validation\ValidationException
- button.tsx
- Employee
- HrisResourceTest.php
- HandleInertiaRequests.php
- Employee.php
- AccountInvitationTest.php
- welcome.tsx
- EmployeeActivationController.php
- devDependencies
- optionalDependencies
- DashboardInsights.php
- input.tsx
- config
- DashboardInsights
- 2. Main system modules
- WorkspaceHeader
- welcome.tsx
- BiometricLink
- EmployeeSpreadsheetImport.php
- FortifyServiceProvider
- Part B — Functional HRIS overview (preserved project scope)
- app-header.tsx
- LeaveApplication
- psr-4
- DashboardInsights
- Applicant
- logging.php
- 7. Common workflows in the system
- collapsible.tsx
- console.php
- HrisResourceController
- ProfileController.php
- EmployeeAccountCreator
- config/app.php
- filesystems.php
- User
- mail.php
- queue.php
- services.php
- AttendanceDailyPunchTest.php
- app.blade.php
- Pest.php
- sidebar.tsx
- Department
- plantilla-items.tsx
- AttendancePunch
- Applicant
- settings.php
- delete-user.tsx
- breadcrumbs.tsx
- dialog.tsx
- EmployeeSpreadsheetImportTest
- SecurityTest.php
- MonthlyAttendanceReportTest.php
- BootstrapHrmoHead.php
- PasswordResetTest
- UserFactory.php
- 2025_01_01_000004_create_hris_recruitment_performance_tables.php
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- .validatedPlantillaData
- DailyTimeRecordController.php
- 2026_11_20_000001_add_avatar_to_users.php
- auth.ts
- AttendanceScheduleSchemaTest
- daily-time-record-preview.tsx
- react
- Architecture Decision Log
- AuthenticationTest
- Payslip
- AGENTS.md
- 2026_11_18_000001_add_workflow_to_attendance_corrections.php
- EmployeeProfileReviewTest.php
- 2026_11_18_000001_add_required_mfa_to_users.php
- TwoFactorChallengeTest.php
- 2026_11_18_000004_add_custom_fields_to_employees.php
- EmployeeProfileReviewTest.php
- 2026_11_23_000001_expand_attendance_punches_for_zkteco.php
- ProfileCard.jsx
- 2026_11_18_000002_allow_missing_attendance_records_in_corrections.php
- Illuminate\Support\Facades\Schema
- AppServiceProvider.php
- 2026_11_24_000006_add_attendance_import_recompute_summary.php
- 2026_11_24_000011_allow_pending_employee_passwords.php
- .accountAvatarQuery
- Role
- useIsMobile
- 2026_11_19_000001_add_biometric_lock_to_employees.php
- Inertia\Response
- LeaveApplicationOnBehalfTest.php
- 2026_11_25_000003_widen_employee_identifiers_for_encryption.php
- Illuminate\Support\Facades\DB
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
2. `User` - 121 edges
3. `Employee` - 111 edges
4. `Button()` - 109 edges
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
- `{closure#1}()` --calls--> `PunchClassifier`  [EXTRACTED]
  app/Providers/AppServiceProvider.php → app/Attendance/Services/PunchClassifier.php
- `{closure#1}()` --calls--> `PunchClassifier`  [EXTRACTED]
  tests/Unit/PunchClassifierTest.php → app/Attendance/Services/PunchClassifier.php

## Import Cycles
- None detected.

## Communities (158 total, 56 thin omitted)

### Community 0 - "EmployeeAccountDeactivation"
Cohesion: 0.21
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), EmployeeAccountDeactivation

### Community 1 - "lucide-react"
Cohesion: 0.10
Nodes (15): lucide-react, useFeedback(), Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props (+7 more)

### Community 2 - "two-factor-recovery-codes.tsx"
Cohesion: 0.22
Nodes (15): AppLogoIcon(), PublicHeader(), Props, TwoFactorRecoveryCodes(), Card(), CardContent(), CardDescription(), CardFooter() (+7 more)

### Community 3 - "PasswordResetTest.php"
Cohesion: 0.05
Nodes (42): dependencies, aos, class-variance-authority, clsx, cmdk, concurrently, gsap, @hugeicons/core-free-icons (+34 more)

### Community 4 - "PunchClassifier"
Cohesion: 0.06
Nodes (56): AttendanceDirectory(), AttendanceDirectoryProps, EmployeeAttendance, statuses, statusFor(), AttendanceWorkspaceHeader(), viewLink(), views (+48 more)

### Community 5 - "cn"
Cohesion: 0.13
Nodes (12): GoogleLoginController, AccountInvitationController, {closure#1}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#2}(), {closure#3}() (+4 more)

### Community 6 - "AttendanceRecomputer"
Cohesion: 0.06
Nodes (27): {closure#11}(), {closure#13}(), {closure#14}(), {closure#3}(), {closure#5}(), {closure#7}(), {closure#9}(), {closure#2}() (+19 more)

### Community 7 - "app-sidebar.tsx"
Cohesion: 0.13
Nodes (23): AppContent(), Props, AppShell(), Props, AppSidebar(), mainNavItems, NavFooter(), NavMain() (+15 more)

### Community 9 - "Inertia\Inertia"
Cohesion: 0.14
Nodes (5): {closure#1}(), EmployeeCredentialController, EmployeeProfileDraftController, EmployeeActivationSlips, HrisPasswordConfirmation

### Community 10 - "WorkspaceHeader"
Cohesion: 0.16
Nodes (32): react, DeleteUser(), EmployeeAccountStatusAction(), EmployeeCodeReissue(), EmployeeIdentifierReveal(), Confirm, Context, CredentialsAcknowledgment() (+24 more)

### Community 11 - "use-appearance.tsx"
Cohesion: 0.08
Nodes (10): EvaluationPeriod, LeaveType, PayrollPeriod, DepartmentFactory, EmployeeFactory, EvaluationPeriodFactory, LeaveApplicationFactory, LeaveTypeFactory (+2 more)

### Community 13 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (24): sonner, AppearanceToggleTab(), Toaster(), Appearance, applyTheme(), getStoredAppearance(), handleSystemThemeChange(), initializeTheme() (+16 more)

### Community 14 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.07
Nodes (14): Controller, DashboardController, AttendanceCorrectionController, AttendanceLogImportController, DailyTimeRecordController, {closure#1}(), {closure#2}(), LeaveApplicationController (+6 more)

### Community 15 - "Position"
Cohesion: 0.08
Nodes (11): {closure#4}(), LeaveApplication, LoginActivity, Payroll, PrivacyIncident, PrivacyRequest, {closure#2}(), {closure#6}() (+3 more)

### Community 16 - "components.json"
Cohesion: 0.08
Nodes (47): @radix-ui/react-navigation-menu, DirectoryResults(), CommandShortcut(), DotPattern(), DotPatternProps, NavigationMenu(), NavigationMenuContent(), NavigationMenuIndicator() (+39 more)

### Community 17 - "AttendanceCorrection"
Cohesion: 0.07
Nodes (11): AuthenticationTest, {closure#1}(), PasswordConfirmationTest, RegistrationTest, VerificationNotificationTest, DashboardTest, {closure#2}(), ExampleTest (+3 more)

### Community 18 - "AuditLog"
Cohesion: 0.10
Nodes (11): {closure#5}(), {closure#7}(), EmployeeIdentifierRevealController, {closure#1}(), {closure#2}(), {closure#3}(), {closure#5}(), {closure#7}() (+3 more)

### Community 19 - "@inertiajs/react"
Cohesion: 0.18
Nodes (12): @inertiajs/react, withApp(), Props, TooltipProvider(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn (+4 more)

### Community 20 - "Employee"
Cohesion: 0.09
Nodes (6): {closure#4}(), {closure#1}(), {closure#13}(), {closure#26}(), {closure#39}(), Employee

### Community 21 - "Role"
Cohesion: 0.04
Nodes (26): DepartmentAssignment, {closure#27}(), {closure#6}(), {closure#10}(), ApplicantDocument, ApplicantEvaluation, AttendanceClockInRule, DisciplinaryRecord (+18 more)

### Community 22 - "EmployeeOnboardingSchemaTest.php"
Cohesion: 0.08
Nodes (30): {closure#1}(), {closure#10}(), {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}() (+22 more)

### Community 23 - "TestCase"
Cohesion: 0.21
Nodes (3): EmployeeImportController, EmployeeSpreadsheetImport, EmployeeImportTemplate

### Community 24 - "leave-form-preview-dialog.tsx"
Cohesion: 0.10
Nodes (40): AppHeader(), Props, AppLogo(), AppSidebarHeader(), Destination, AppearanceToggle(), options, NavUser() (+32 more)

### Community 25 - "leave-form-preview-dialog.tsx"
Cohesion: 0.17
Nodes (18): Choice(), creditRows, FormLine(), LeaveApplicant, LeaveDraft, LeaveFormPreview(), LeaveFormPreviewDialog(), leaveReasons (+10 more)

### Community 26 - "DailyTimeRecordController.php"
Cohesion: 0.18
Nodes (4): {closure#2}(), {closure#3}(), AttendanceRecord, AttendanceRecordFactory

### Community 27 - "MonthlyAttendanceReportController.php"
Cohesion: 0.17
Nodes (7): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), MonthlyAttendanceReportController

### Community 28 - "3. User roles and their functions"
Cohesion: 0.25
Nodes (8): devDependencies, @babel/core, babel-plugin-react-compiler, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @types/aos, @types/node, vite-plus

### Community 29 - "DailyPunchController.php"
Cohesion: 0.06
Nodes (52): @radix-ui/react-slot, recharts, AnnouncementEntry(), DashboardAnnouncement, DashboardAnnouncements(), manilaClock, manilaDateTime, manilaDateTimeOptions (+44 more)

### Community 30 - "index.ts"
Cohesion: 0.07
Nodes (34): @laravel/passkeys, InputError(), PasskeyRegistration(), Props, PasskeyVerify(), Props, PasswordInput(), TextLink() (+26 more)

### Community 31 - "scripts"
Cohesion: 0.15
Nodes (7): {closure#32}(), {closure#33}(), {closure#34}(), {closure#7}(), HrisResourceController, {closure#4}(), {closure#5}()

### Community 32 - "BiometricAttendanceTest.php"
Cohesion: 0.10
Nodes (9): {closure#10}(), {closure#12}(), {closure#14}(), {closure#15}(), {closure#19}(), {closure#3}(), {closure#5}(), {closure#7}() (+1 more)

### Community 34 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.10
Nodes (20): aliases, components, hooks, lib, ui, utils, iconLibrary, registries (+12 more)

### Community 35 - "Department"
Cohesion: 0.10
Nodes (20): runs-on, steps, jobs, ci, name, on, pull_request, push (+12 more)

### Community 36 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.07
Nodes (16): {closure#1}(), {closure#2}(), {closure#17}(), {closure#18}(), {closure#2}(), {closure#20}(), {closure#21}(), {closure#22}() (+8 more)

### Community 37 - "toggle-group.tsx"
Cohesion: 0.20
Nodes (13): class-variance-authority, @radix-ui/react-toggle, @radix-ui/react-toggle-group, AlertError(), Alert(), AlertDescription(), AlertTitle(), alertVariants (+5 more)

### Community 38 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.05
Nodes (13): {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}(), {closure#2}() (+5 more)

### Community 39 - "require-dev"
Cohesion: 0.12
Nodes (16): input-otp, GridScanIcon(), Props, TwoFactorSetupStep(), TwoFactorVerificationStep(), InputOTP, InputOTPGroup, InputOTPSeparator (+8 more)

### Community 40 - "Illuminate\Validation\ValidationException"
Cohesion: 0.15
Nodes (7): {closure#1}(), {closure#3}(), {closure#5}(), {closure#1}(), AttendanceCorrection, {closure#1}(), {closure#5}()

### Community 41 - "button.tsx"
Cohesion: 0.05
Nodes (43): overrides, vite, private, $schema, type, @babel/core, babel-plugin-react-compiler, clsx (+35 more)

### Community 42 - "Employee"
Cohesion: 0.11
Nodes (16): A10. Minimum test and verification matrix, A11. Deployment checklist (copy and complete per release), A12. CI and reviewer guardrail for documentation changes, A13. Open decisions / production blockers — do not guess, A15. Mandatory major-change record template, A16. External references (for validation, not a substitute for municipal sign-off), A1. Document authority, roles, and status, A2. Major-change definition and mandatory Markdown update (+8 more)

### Community 43 - "HrisResourceTest.php"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

### Community 44 - "HandleInertiaRequests.php"
Cohesion: 0.07
Nodes (9): ResetUserPassword, PasswordValidationRules, BiometricLinkController, {closure#7}(), ImportAttendanceLogRequest, UpdateBiometricLinkRequest, PasswordUpdateRequest, TwoFactorAuthenticationRequest (+1 more)

### Community 45 - "Employee.php"
Cohesion: 0.25
Nodes (8): optionalDependencies, @laravel/multiplex, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu, @tailwindcss/oxide-win32-x64-msvc

### Community 46 - "AccountInvitationTest.php"
Cohesion: 0.12
Nodes (7): {closure#29}(), {closure#3}(), {closure#7}(), {closure#8}(), {closure#9}(), HandleInertiaRequests, Announcement

### Community 47 - "welcome.tsx"
Cohesion: 0.10
Nodes (31): aos, gsap, buildAtlas(), clamp(), CLICK_EFFECTS, compile(), createTarget(), DEFAULT_COLORS (+23 more)

### Community 48 - "EmployeeActivationController.php"
Cohesion: 0.22
Nodes (3): {closure#1}(), EmployeeActivationController, EmployeeActivationCode

### Community 49 - "devDependencies"
Cohesion: 0.22
Nodes (8): PunchClassifier, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), schedule()

### Community 50 - "optionalDependencies"
Cohesion: 0.14
Nodes (13): 10. Important rules before development, 11. The complete system in one simple view, 1. Main purpose of the system, 4. Suggested dashboard and sidebar structure, 5. What should appear on each dashboard?, 6. How the modules work together, 8. Important system features, 9. Recommended development order (+5 more)

### Community 52 - "input.tsx"
Cohesion: 0.14
Nodes (12): BiometricIdField(), ImportAttendanceLogs(), importSelectedFile(), Page, Paging(), AttendanceImportPreview, BiometricPunchGroup, DailyPunch (+4 more)

### Community 53 - "config"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 54 - "DashboardInsights"
Cohesion: 0.15
Nodes (12): Architecture Decision Log, D-001: Application stack (Laravel + React + Inertia), D-002: PHP 8.4 is the minimum version, D-003: MySQL 8.4 instead of PostgreSQL 16, D-004: RustFS replaces MinIO for local file storage, D-005: The app container does not use `env_file`, D-006: Tests run on in-memory SQLite with forced settings, D-007: Frontend is built on the host, not in Docker (+4 more)

### Community 55 - "2. Main system modules"
Cohesion: 0.13
Nodes (6): EmployeeDirectory, AttendanceDaily, DepartmentSchedule, EmployeeScheduleOverride, ScheduleResolver, {closure#35}()

### Community 56 - "WorkspaceHeader"
Cohesion: 0.08
Nodes (23): EmployeeProfileDraft(), fields, ReviewFields, WorkspaceHeader(), downloadEmployeeImportErrors(), EmployeeOnboardingResult(), RejectedRow, Slip (+15 more)

### Community 57 - "welcome.tsx"
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#2}(), SecurityTest

### Community 59 - "EmployeeSpreadsheetImport.php"
Cohesion: 0.06
Nodes (4): {closure#6}(), {closure#7}(), Position, PositionFactory

### Community 61 - "Part B — Functional HRIS overview (preserved project scope)"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 62 - "app-header.tsx"
Cohesion: 0.17
Nodes (11): default-days, patterns, github-actions, interval, updates, cooldown, directory, groups (+3 more)

### Community 63 - "LeaveApplication"
Cohesion: 0.17
Nodes (12): 10. Data Protection Officer (DPO), 11. Auditor / Authorized Audit User, 1. Employee, 2. Department Head / Office Head, 3. HR Officer, 3. User roles and their functions, 4. HRMO Head, 5. Payroll Officer (+4 more)

### Community 66 - "Applicant"
Cohesion: 0.11
Nodes (4): Applicant, Vacancy, ApplicantFactory, VacancyFactory

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
Cohesion: 0.06
Nodes (8): ProfileValidationRules, ProfileController, EnsureHrisAccountReady, ExpireIdleHrisSession, HandleAppearance, ProfileDeleteRequest, ProfileUpdateRequest, HrisEnrollmentPolicy

### Community 73 - "EmployeeAccountCreator"
Cohesion: 0.21
Nodes (5): {closure#1}(), BiometricLinkVerifier, {closure#1}(), {closure#2}(), EmployeeAccountCreator

### Community 74 - "config/app.php"
Cohesion: 0.17
Nodes (6): EmployeeIdentifier, AttendanceRecomputeCommand, {closure#1}(), {closure#2}(), EncryptEmployeeIdentifiers, {closure#5}()

### Community 75 - "filesystems.php"
Cohesion: 0.20
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}()

### Community 77 - "mail.php"
Cohesion: 0.23
Nodes (4): {closure#13}(), {closure#25}(), {closure#8}(), DailyPunchController

### Community 78 - "queue.php"
Cohesion: 0.28
Nodes (7): Auth, TwoFactorSecretKey, TwoFactorSetupData, InertiaConfig, @inertiajs/core, InputHTMLAttributes, react

### Community 80 - "AttendanceDailyPunchTest.php"
Cohesion: 0.06
Nodes (8): {closure#1}(), {closure#1}(), ImportAttendanceLogJob, AttendanceImport, AttendanceLogParser, {closure#2}(), {closure#3}(), EmployeeSpreadsheetImportTest

### Community 81 - "app.blade.php"
Cohesion: 0.22
Nodes (8): driver, networks, hris, volumes, mysql_data, redis_data, rustfs_data, vendor_data

### Community 82 - "Pest.php"
Cohesion: 0.22
Nodes (9): environment, image, networks, volumes, MYSQL_DATABASE, MYSQL_PASSWORD, MYSQL_ROOT_PASSWORD, MYSQL_USER (+1 more)

### Community 83 - "sidebar.tsx"
Cohesion: 0.22
Nodes (9): RUSTFS_ACCESS_KEY, RUSTFS_CONSOLE_ENABLE, RUSTFS_SECRET_KEY, environment, image, networks, ports, volumes (+1 more)

### Community 84 - "Department"
Cohesion: 0.08
Nodes (8): {closure#19}(), {closure#15}(), {closure#3}(), {closure#6}(), {closure#7}(), Department, Payslip, {closure#3}()

### Community 85 - "plantilla-items.tsx"
Cohesion: 0.16
Nodes (28): PersonAvatar(), DirectoryLayout, ResourceDirectory(), SheetTrigger(), Table(), TableBody(), TableCaption(), TableCell() (+20 more)

### Community 86 - "AttendancePunch"
Cohesion: 0.11
Nodes (11): BiometricLink, {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}(), {closure#24}(), {closure#26}() (+3 more)

### Community 87 - "Applicant"
Cohesion: 0.25
Nodes (8): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php

### Community 88 - "settings.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 89 - "delete-user.tsx"
Cohesion: 0.18
Nodes (5): DatabaseSeeder, DemoDashboardSeeder, DemoPortalAccountSeeder, PlantillaDepartmentSeeder, CHG-0002 - Fixed offices and account setup

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

### Community 95 - "BootstrapHrmoHead.php"
Cohesion: 0.23
Nodes (4): BootstrapHrmoHead, {closure#1}(), {closure#1}(), HrisAccountSetup

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

### Community 113 - "EmployeeProfileReviewTest.php"
Cohesion: 0.29
Nodes (3): {closure#3}(), {closure#4}(), {closure#6}()

### Community 120 - "2026_11_18_000002_allow_missing_attendance_records_in_corrections.php"
Cohesion: 0.50
Nodes (4): A14. Change Register — MUST be maintained in THIS file, CHG-0003 - Employee spreadsheet import and department directory, CHG-0004 - Employee import workbook columns, CHG-0005 - Employee account creation workflow

### Community 123 - "AppServiceProvider.php"
Cohesion: 0.12
Nodes (6): EmployeeDayController, HrisEmployeeDirectory, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 137 - ".accountAvatarQuery"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 139 - "useIsMobile"
Cohesion: 0.70
Nodes (4): getServerSnapshot(), isSmallerThanBreakpoint(), mediaQueryListener(), useIsMobile()

### Community 141 - "Inertia\Response"
Cohesion: 0.28
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 148 - "Illuminate\Support\Facades\DB"
Cohesion: 0.08
Nodes (9): {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}() (+1 more)

## Knowledge Gaps
- **20 isolated node(s):** `@types/react`, `@types/react-dom`, `concurrently`, `motion`, `react-dom` (+15 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1040 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **56 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Employee` connect `Employee` to `EmployeeAccountDeactivation`, `AttendanceRecomputer`, `AttendanceLogImportController.php`, `Inertia\Inertia`, `Role`, `use-appearance.tsx`, `Button`, `Illuminate\Http\RedirectResponse`, `Position`, `Illuminate\Database\Migrations\Migration`, `AttendanceCorrection`, `AuditLog`, `BiometricLinkVerifier.php`, `LeaveApplicationOnBehalfTest.php`, `Role`, `DailyTimeRecordController.php`, `MonthlyAttendanceReportController.php`, `scripts`, `BiometricAttendanceTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Database\Eloquent\Builder`, `Illuminate\Validation\ValidationException`, `Employee`, `HandleInertiaRequests.php`, `2. Main system modules`, `BiometricLink`, `EmployeeSpreadsheetImport.php`, `psr-4`, `Applicant`, `ProfileController.php`, `EmployeeAccountCreator`, `AttendanceDailyPunchTest.php`, `Department`, `AttendancePunch`, `EmployeeProfileReviewTest.php`, `2026_11_24_000011_allow_pending_employee_passwords.php`?**
  _High betweenness centrality (0.078) - this node is a cross-community bridge._
- **What connects `@types/react`, `@types/react-dom`, `concurrently` to the rest of the system?**
  _20 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `lucide-react` be split into smaller, more focused modules?**
  _Cohesion score 0.09885057471264368 - nodes in this community are weakly interconnected._
- **Why does `User` connect `User` to `EmployeeAccountDeactivation`, `cn`, `AttendanceRecomputer`, `AttendanceLogImportController.php`, `Inertia\Inertia`, `Role`, `use-appearance.tsx`, `Button`, `Illuminate\Http\RedirectResponse`, `Position`, `Illuminate\Database\Migrations\Migration`, `AttendanceCorrection`, `AuditLog`, `2026_11_25_000003_widen_employee_identifiers_for_encryption.php`, `BiometricLinkVerifier.php`, `Role`, `LeaveApplicationOnBehalfTest.php`, `scripts`, `BiometricAttendanceTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Validation\ValidationException`, `Employee`, `HandleInertiaRequests.php`, `AccountInvitationTest.php`, `EmployeeActivationController.php`, `DashboardInsights.php`, `welcome.tsx`, `EmployeeSpreadsheetImport.php`, `psr-4`, `DashboardInsights`, `ProfileController.php`, `EmployeeAccountCreator`, `AttendanceDailyPunchTest.php`, `Department`, `delete-user.tsx`, `SecurityTest.php`, `BootstrapHrmoHead.php`, `UserFactory.php`, `EmployeeProfileReviewTest.php`, `TwoFactorChallengeTest.php`, `AppServiceProvider.php`, `2026_11_24_000011_allow_pending_employee_passwords.php`?**
  _High betweenness centrality (0.065) - this node is a cross-community bridge._
- **Should `PasswordResetTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.047619047619047616 - nodes in this community are weakly interconnected._
- **Why does `Part A — Mandatory production engineering rules` connect `Employee` to `2026_11_18_000002_allow_missing_attendance_records_in_corrections.php`, `optionalDependencies`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Should `PunchClassifier` be split into smaller, more focused modules?**
  _Cohesion score 0.06278538812785388 - nodes in this community are weakly interconnected._