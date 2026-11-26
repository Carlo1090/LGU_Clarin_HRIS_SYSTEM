# Graph Report - LGU_Clarin_HRIS_SYSTEM  (2026-11-26)

## Corpus Check
- 386 files · ~482,323 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 47 file(s) not represented in the graph (top: (none) 30, .css 10, .conf 2)

## Summary
- 2810 nodes · 6737 edges · 164 communities (96 shown, 68 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 92 edges (avg confidence: 0.86)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `68c58ada`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\RedirectResponse
- react
- two-factor-recovery-codes.tsx
- PasswordResetTest.php
- EmployeeActivationController.php
- Illuminate\Http\Request
- AttendanceRecomputer
- app-sidebar.tsx
- Illuminate\Support\Facades\DB
- Illuminate\Database\Eloquent\Builder
- WorkspaceHeader
- Illuminate\Database\Eloquent\Factories\Factory
- Button
- Illuminate\Database\Eloquent\Model
- Inertia\Response
- DashboardController.php
- cn
- AttendanceCorrection
- AuditLog
- sidebar.tsx
- monthly-attendance-report.tsx
- Role
- EmployeeOnboardingSchemaTest.php
- EmployeeImportTemplate
- leave-form-preview-dialog.tsx
- leave-form-preview-dialog.tsx
- Role
- MonthlyAttendanceReportController.php
- 3. User roles and their functions
- DailyPunchController.php
- lucide-react
- scripts
- BiometricAttendanceTest.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Illuminate\Http\RedirectResponse
- Department
- DailyPunchController.php
- Illuminate\Support\Facades\Schema
- require-dev
- EmployeeSpreadsheetImportTest
- button.tsx
- Employee
- HrisResourceTest.php
- Illuminate\Foundation\Http\FormRequest
- Employee.php
- HandleInertiaRequests.php
- welcome.tsx
- BiometricLinkController.php
- devDependencies
- optionalDependencies
- DashboardInsights.php
- index.tsx
- config
- DashboardInsights
- AttendancePunch
- Button
- Symfony\Component\HttpFoundation\Response
- AttendanceRecomputer
- FortifyServiceProvider
- Part B — Functional HRIS overview (preserved project scope)
- app-header.tsx
- LeaveApplication
- settings.php
- GradientASCII.jsx
- Laravel\Fortify\Features
- logging.php
- 7. Common workflows in the system
- collapsible.tsx
- console.php
- HrisResourceController
- PasswordValidationRules
- 2026_11_25_000003_widen_employee_identifiers_for_encryption.php
- config/app.php
- filesystems.php
- User
- BiometricLink
- DashboardInsights
- EmployeeAccountDeactivationTest.php
- EmployeeAccountCreator
- app.blade.php
- Pest.php
- sidebar.tsx
- Department
- button.tsx
- AttendanceRecomputer.php
- Applicant
- settings.php
- ProfileUpdateRequest
- AppServiceProvider.php
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
- AuthenticationTest
- 2026_11_18_000001_add_required_mfa_to_users.php
- 2026_11_18_000003_allow_optional_email_and_password_setup.php
- LeaveApplication
- EmployeeProfileReviewTest.php
- 2026_11_23_000001_expand_attendance_punches_for_zkteco.php
- ProfileCard.jsx
- 2026_11_18_000002_allow_missing_attendance_records_in_corrections.php
- Illuminate\Support\Facades\Schema
- HrisEmployeeDirectory.php
- 2026_11_24_000006_add_attendance_import_recompute_summary.php
- SecurityTest.php
- .accountAvatarQuery
- UserFactory.php
- 2026_11_18_000004_add_custom_fields_to_employees.php
- 2026_11_19_000001_add_biometric_lock_to_employees.php
- 2026_11_26_000001_add_slot_requests_to_attendance_corrections.php
- employee-onboarding-result.tsx
- 2026_11_25_000003_widen_employee_identifiers_for_encryption.php
- @inertiajs/react
- AttendanceScheduleSchemaTest
- .validatedPlantillaData
- Clarin Municipal HRIS — Production Implementation & Governance Guide
- 2026_11_24_000002_add_attendance_import_error_status.php
- 2026_11_25_000004_create_employee_profile_revisions_table.php
- 2026_11_24_000006_add_attendance_import_recompute_summary.php
- 2026_11_24_000007_allow_daily_attendance_without_schedule_source.php
- employee_eligibilities.php
- 2026_11_24_000011_allow_pending_employee_passwords.php
- 2026_11_24_000010_create_attendance_department_assignments_table.php
- .navigation
- attendance-log-import.md
- employee-identifier-encryption.md

## God Nodes (most connected - your core abstractions)
1. `cn()` - 152 edges
2. `User` - 124 edges
3. `Employee` - 113 edges
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

## Communities (164 total, 68 thin omitted)

### Community 0 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.09
Nodes (8): GoogleLoginController, Controller, LeaveReviewController, NotificationController, ProfileController, SecurityController, ExpireIdleHrisSession, HrisEnrollmentPolicy

### Community 1 - "react"
Cohesion: 0.08
Nodes (32): @laravel/passkeys, react, useFeedback(), Heading(), InputError(), EmptyState(), ManagePasskeys(), Props (+24 more)

### Community 2 - "two-factor-recovery-codes.tsx"
Cohesion: 0.25
Nodes (14): AppLogoIcon(), Props, TwoFactorRecoveryCodes(), Card(), CardContent(), CardDescription(), CardFooter(), CardHeader() (+6 more)

### Community 3 - "PasswordResetTest.php"
Cohesion: 0.05
Nodes (42): dependencies, aos, class-variance-authority, clsx, cmdk, concurrently, gsap, @hugeicons/core-free-icons (+34 more)

### Community 4 - "EmployeeActivationController.php"
Cohesion: 0.36
Nodes (3): {closure#1}(), EmployeeActivationController, EmployeeActivationCode

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.09
Nodes (15): ScheduleController, AccountInvitationController, {closure#1}(), {closure#11}(), {closure#12}(), {closure#13}(), {closure#2}(), {closure#3}() (+7 more)

### Community 6 - "AttendanceRecomputer"
Cohesion: 0.04
Nodes (39): {closure#11}(), {closure#13}(), {closure#14}(), {closure#3}(), {closure#5}(), {closure#7}(), {closure#9}(), {closure#2}() (+31 more)

### Community 7 - "app-sidebar.tsx"
Cohesion: 0.12
Nodes (20): sonner, AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), getServerSnapshot() (+12 more)

### Community 8 - "Illuminate\Support\Facades\DB"
Cohesion: 0.05
Nodes (8): AttendanceRecomputeCommand, BootstrapHrmoHead, {closure#5}(), {closure#2}(), {closure#6}(), AuditLog, {closure#1}(), HrisAccountSetup

### Community 9 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.11
Nodes (17): {closure#10}(), {closure#18}(), {closure#19}(), {closure#2}(), {closure#21}(), {closure#22}(), {closure#23}(), {closure#3}() (+9 more)

### Community 10 - "WorkspaceHeader"
Cohesion: 0.20
Nodes (28): DeleteUser(), EmployeeAccountStatusAction(), EmployeeCodeReissue(), EmployeeIdentifierReveal(), Confirm, Context, CredentialsAcknowledgment(), FeedbackProvider() (+20 more)

### Community 11 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.05
Nodes (16): {closure#7}(), Applicant, EvaluationPeriod, Payroll, PayrollPeriod, Position, Vacancy, {closure#6}() (+8 more)

### Community 13 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (30): withApp(), AppearanceToggleTab(), Toaster(), TooltipProvider(), Appearance, applyTheme(), getStoredAppearance(), handleSystemThemeChange() (+22 more)

### Community 14 - "Inertia\Response"
Cohesion: 0.08
Nodes (8): DashboardController, AttendanceLogImportController, {closure#1}(), {closure#2}(), DailyTimeRecordController, EmployeeIdentifierRevealController, LeaveApplicationController, PortalController

### Community 15 - "DashboardController.php"
Cohesion: 0.06
Nodes (16): {closure#4}(), {closure#5}(), {closure#7}(), {closure#1}(), AttendanceRecord, LoginActivity, PrivacyIncident, PrivacyRequest (+8 more)

### Community 16 - "cn"
Cohesion: 0.07
Nodes (47): class-variance-authority, @radix-ui/react-navigation-menu, @radix-ui/react-slot, @radix-ui/react-toggle, @radix-ui/react-toggle-group, AlertError(), Breadcrumbs(), DirectoryResults() (+39 more)

### Community 17 - "AttendanceCorrection"
Cohesion: 0.07
Nodes (10): {closure#1}(), PasswordConfirmationTest, RegistrationTest, {closure#1}(), TwoFactorChallengeTest, DashboardTest, ExampleTest, TrustedProxyHttpsTest (+2 more)

### Community 18 - "AuditLog"
Cohesion: 0.14
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#7}(), EmployeeProfileReviewController, EmployeeProfileRevision, {closure#5}()

### Community 19 - "sidebar.tsx"
Cohesion: 0.10
Nodes (38): @radix-ui/react-tooltip, AppLogo(), AppSidebar(), mainNavItems, NavFooter(), NavMain(), SidebarContent(), SidebarContext (+30 more)

### Community 20 - "monthly-attendance-report.tsx"
Cohesion: 0.07
Nodes (29): PersonAvatar(), Column, SearchForm(), SearchProps, useTableSearch(), ImportAttendanceLogs(), importSelectedFile(), Page (+21 more)

### Community 21 - "Role"
Cohesion: 0.04
Nodes (24): DepartmentAssignment, {closure#30}(), {closure#6}(), ApplicantDocument, ApplicantEvaluation, AttendanceClockInRule, DisciplinaryRecord, EmployeeAppointmentChange (+16 more)

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
Nodes (6): Role, RoleFactory, DatabaseSeeder, DemoPortalAccountSeeder, PlantillaDepartmentSeeder, CHG-0002 - Fixed offices and account setup

### Community 27 - "MonthlyAttendanceReportController.php"
Cohesion: 0.17
Nodes (7): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), MonthlyAttendanceReportController

### Community 28 - "3. User roles and their functions"
Cohesion: 0.25
Nodes (8): devDependencies, @babel/core, babel-plugin-react-compiler, @laravel/vite-plugin-wayfinder, @rolldown/plugin-babel, @types/aos, @types/node, vite-plus

### Community 29 - "DailyPunchController.php"
Cohesion: 0.07
Nodes (45): recharts, AnnouncementEntry(), DashboardAnnouncement, DashboardAnnouncements(), manilaClock, manilaDateTime, manilaDateTimeOptions, Badge() (+37 more)

### Community 30 - "lucide-react"
Cohesion: 0.11
Nodes (15): lucide-react, PasskeyVerify(), Props, PasswordInput(), Checkbox(), IconProps, Input(), Separator() (+7 more)

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
Nodes (5): {closure#14}(), {closure#20}(), {closure#24}(), {closure#26}(), {closure#8}()

### Community 38 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.05
Nodes (13): {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}(), {closure#2}() (+5 more)

### Community 39 - "require-dev"
Cohesion: 0.10
Nodes (18): input-otp, Props, GridScanIcon(), Props, TwoFactorSetupStep(), TwoFactorVerificationStep(), InputOTP, InputOTPGroup (+10 more)

### Community 41 - "button.tsx"
Cohesion: 0.05
Nodes (42): overrides, vite, private, $schema, type, @babel/core, babel-plugin-react-compiler, clsx (+34 more)

### Community 42 - "Employee"
Cohesion: 0.14
Nodes (14): A10. Minimum test and verification matrix, A11. Deployment checklist (copy and complete per release), A12. CI and reviewer guardrail for documentation changes, A13. Open decisions / production blockers — do not guess, A15. Mandatory major-change record template, A16. External references (for validation, not a substitute for municipal sign-off), A1. Document authority, roles, and status, A2. Major-change definition and mandatory Markdown update (+6 more)

### Community 43 - "HrisResourceTest.php"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

### Community 44 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.19
Nodes (3): ImportAttendanceLogRequest, UpdateBiometricLinkRequest, TwoFactorAuthenticationRequest

### Community 45 - "Employee.php"
Cohesion: 0.25
Nodes (8): optionalDependencies, @laravel/multiplex, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu, @tailwindcss/oxide-win32-x64-msvc

### Community 46 - "HandleInertiaRequests.php"
Cohesion: 0.11
Nodes (9): {closure#32}(), {closure#10}(), {closure#3}(), {closure#7}(), {closure#8}(), {closure#9}(), HandleInertiaRequests, Announcement (+1 more)

### Community 47 - "welcome.tsx"
Cohesion: 0.11
Nodes (25): aos, gsap, navigation, PublicHeader(), clamp01(), clampToScroll(), easeBack(), easeOut() (+17 more)

### Community 48 - "BiometricLinkController.php"
Cohesion: 0.14
Nodes (3): BiometricLinkController, {closure#1}(), HrisPasswordConfirmation

### Community 49 - "devDependencies"
Cohesion: 0.30
Nodes (8): PunchClassifier, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), schedule()

### Community 50 - "optionalDependencies"
Cohesion: 0.18
Nodes (11): 10. Important rules before development, 11. The complete system in one simple view, 1. Main purpose of the system, 4. Suggested dashboard and sidebar structure, 5. What should appear on each dashboard?, 6. How the modules work together, 8. Important system features, 9. Recommended development order (+3 more)

### Community 52 - "index.tsx"
Cohesion: 0.13
Nodes (23): AttendanceDirectory(), AttendanceDirectoryProps, EmployeeAttendance, statuses, statusFor(), RecordPageLink, RecordPagination(), TableAction() (+15 more)

### Community 53 - "config"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 54 - "DashboardInsights"
Cohesion: 0.15
Nodes (12): Architecture Decision Log, D-001: Application stack (Laravel + React + Inertia), D-002: PHP 8.4 is the minimum version, D-003: MySQL 8.4 instead of PostgreSQL 16, D-004: RustFS replaces MinIO for local file storage, D-005: The app container does not use `env_file`, D-006: Tests run on in-memory SQLite with forced settings, D-007: Frontend is built on the host, not in Docker (+4 more)

### Community 55 - "AttendancePunch"
Cohesion: 0.24
Nodes (5): {closure#25}(), {closure#27}(), {closure#6}(), {closure#7}(), AttendancePunch

### Community 56 - "Button"
Cohesion: 0.16
Nodes (14): Props, TextLink(), Button(), buttonVariants, ForgotPassword(), VerifyEmail(), AuditEntry, EmployeeProfileReview() (+6 more)

### Community 57 - "Symfony\Component\HttpFoundation\Response"
Cohesion: 0.09
Nodes (12): {closure#1}(), EmployeeCredentialController, EmployeeImportController, EmployeeProfileDraftController, EnsureHrisAccountReady, HandleAppearance, EmployeeActivationSlips, {closure#1}() (+4 more)

### Community 58 - "AttendanceRecomputer"
Cohesion: 0.07
Nodes (14): EmployeeDirectory, {closure#1}(), DepartmentSchedule, EmployeeScheduleOverride, AttendanceRecomputer, ScheduleResolver, AttendanceCorrectionController, {closure#2}() (+6 more)

### Community 61 - "Part B — Functional HRIS overview (preserved project scope)"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 62 - "app-header.tsx"
Cohesion: 0.17
Nodes (11): default-days, patterns, github-actions, interval, updates, cooldown, directory, groups (+3 more)

### Community 63 - "LeaveApplication"
Cohesion: 0.17
Nodes (12): 10. Data Protection Officer (DPO), 11. Auditor / Authorized Audit User, 1. Employee, 2. Department Head / Office Head, 3. HR Officer, 3. User roles and their functions, 4. HRMO Head, 5. Payroll Officer (+4 more)

### Community 65 - "GradientASCII.jsx"
Cohesion: 0.22
Nodes (13): buildAtlas(), clamp(), CLICK_EFFECTS, compile(), createTarget(), DEFAULT_COLORS, GradientASCII(), link() (+5 more)

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

### Community 72 - "PasswordValidationRules"
Cohesion: 0.21
Nodes (4): ResetUserPassword, PasswordValidationRules, PasswordUpdateRequest, ProfileDeleteRequest

### Community 74 - "config/app.php"
Cohesion: 0.15
Nodes (6): EmployeeIdentifier, {closure#1}(), {closure#2}(), EncryptEmployeeIdentifiers, AccountInvitationTest, {closure#5}()

### Community 75 - "filesystems.php"
Cohesion: 0.20
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}()

### Community 76 - "User"
Cohesion: 0.04
Nodes (14): {closure#1}(), {closure#1}(), {closure#3}(), {closure#7}(), {closure#13}(), {closure#29}(), {closure#41}(), {closure#1}() (+6 more)

### Community 77 - "BiometricLink"
Cohesion: 0.20
Nodes (4): BiometricLink, {closure#1}(), {closure#2}(), {closure#5}()

### Community 80 - "EmployeeAccountCreator"
Cohesion: 0.08
Nodes (10): {closure#1}(), EmployeeSpreadsheetImport, {closure#1}(), ImportAttendanceLogJob, AttendanceImport, AttendanceLogParser, {closure#2}(), {closure#3}() (+2 more)

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
Nodes (6): {closure#18}(), {closure#3}(), {closure#6}(), {closure#7}(), Department, Payslip

### Community 85 - "button.tsx"
Cohesion: 0.11
Nodes (36): BiometricIdField(), DirectoryLayout, directoryLayouts, DirectoryOverview(), Field, HrisRow, HrisValue, ResourceDirectory() (+28 more)

### Community 86 - "AttendanceRecomputer.php"
Cohesion: 0.15
Nodes (7): AttendanceDaily, {closure#10}(), {closure#11}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}()

### Community 87 - "Applicant"
Cohesion: 0.25
Nodes (8): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php

### Community 88 - "settings.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 90 - "AppServiceProvider.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

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

### Community 116 - "LeaveApplication"
Cohesion: 0.11
Nodes (6): {closure#1}(), LeaveApplication, LeaveType, {closure#2}(), LeaveApplicationFactory, LeaveTypeFactory

### Community 120 - "2026_11_18_000002_allow_missing_attendance_records_in_corrections.php"
Cohesion: 0.50
Nodes (4): A14. Change Register — MUST be maintained in THIS file, CHG-0003 - Employee spreadsheet import and department directory, CHG-0004 - Employee import workbook columns, CHG-0005 - Employee account creation workflow

### Community 126 - "SecurityTest.php"
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#2}(), SecurityTest

### Community 137 - ".accountAvatarQuery"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 143 - "employee-onboarding-result.tsx"
Cohesion: 0.38
Nodes (4): downloadEmployeeImportErrors(), EmployeeOnboardingResult(), RejectedRow, Slip

### Community 145 - "@inertiajs/react"
Cohesion: 0.08
Nodes (29): @inertiajs/react, AttendanceWorkspaceHeader(), viewLink(), views, DailyTimeRecordDialog(), PreviewContent(), columns, DailyTimeRecordData (+21 more)

## Knowledge Gaps
- **20 isolated node(s):** `@types/react`, `@types/react-dom`, `concurrently`, `motion`, `react-dom` (+15 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1045 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **68 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Employee` connect `User` to `AttendanceRecomputer`, `Illuminate\Support\Facades\DB`, `Illuminate\Database\Eloquent\Factories\Factory`, `Button`, `Inertia\Response`, `DashboardController.php`, `AttendanceDailyPunchTest.php`, `AttendanceCorrection`, `AuditLog`, `BiometricLinkVerifier.php`, `.validatedPlantillaData`, `Role`, `Role`, `MonthlyAttendanceReportController.php`, `scripts`, `BiometricAttendanceTest.php`, `DailyPunchController.php`, `HrisResourceTest.php`, `BiometricLinkController.php`, `AttendancePunch`, `Symfony\Component\HttpFoundation\Response`, `AttendanceRecomputer`, `EmployeeSpreadsheetImportTest.php`, `EmployeeAccountDeactivationTest.php`, `EmployeeAccountCreator`, `Department`, `AttendanceRecomputer.php`, `LeaveApplication`, `HrisEmployeeDirectory.php`?**
  _High betweenness centrality (0.070) - this node is a cross-community bridge._
- **What connects `@types/react`, `@types/react-dom`, `concurrently` to the rest of the system?**
  _20 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\RedirectResponse` be split into smaller, more focused modules?**
  _Cohesion score 0.08536585365853659 - nodes in this community are weakly interconnected._
- **Why does `User` connect `User` to `Illuminate\Http\RedirectResponse`, `EmployeeActivationController.php`, `Illuminate\Http\Request`, `AttendanceRecomputer`, `Illuminate\Support\Facades\DB`, `UserFactory.php`, `Illuminate\Database\Eloquent\Factories\Factory`, `Button`, `Inertia\Response`, `DashboardController.php`, `AttendanceDailyPunchTest.php`, `AttendanceCorrection`, `AuditLog`, `2026_11_25_000003_widen_employee_identifiers_for_encryption.php`, `BiometricLinkVerifier.php`, `Role`, `.navigation`, `BiometricAttendanceTest.php`, `HrisResourceTest.php`, `HandleInertiaRequests.php`, `DashboardInsights.php`, `Symfony\Component\HttpFoundation\Response`, `AttendanceRecomputer`, `EmployeeSpreadsheetImportTest.php`, `FortifyServiceProvider`, `Laravel\Fortify\Features`, `PasswordValidationRules`, `DashboardInsights`, `EmployeeAccountDeactivationTest.php`, `EmployeeAccountCreator`, `Department`, `ProfileUpdateRequest`, `AppServiceProvider.php`, `SecurityTest.php`, `PasswordResetTest.php`, `AuthenticationTest`, `LeaveApplication`, `SecurityTest.php`?**
  _High betweenness centrality (0.066) - this node is a cross-community bridge._
- **Should `react` be split into smaller, more focused modules?**
  _Cohesion score 0.07547169811320754 - nodes in this community are weakly interconnected._
- **Why does `Part A — Mandatory production engineering rules` connect `Employee` to `2026_11_18_000002_allow_missing_attendance_records_in_corrections.php`, `User`, `Clarin Municipal HRIS — Production Implementation & Governance Guide`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **Should `PasswordResetTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.047619047619047616 - nodes in this community are weakly interconnected._