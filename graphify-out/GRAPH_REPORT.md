# Graph Report - LGU_Clarin_HRIS_SYSTEM  (2026-11-25)

## Corpus Check
- 309 files · ~112,729 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 47 file(s) not represented in the graph (top: (none) 30, .css 10, .conf 2)

## Summary
- 2175 nodes · 5179 edges · 148 communities (99 shown, 49 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 35 edges (avg confidence: 0.86)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `7a2bd25d`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Input
- index.tsx
- dependencies
- Button
- Illuminate\Http\Request
- Inertia\Testing\AssertableInertia
- sidebar.tsx
- PortalController.php
- App\Models\Applicant
- two-factor-setup-modal.tsx
- Illuminate\Database\Eloquent\Factories\Factory
- HrisResourceController.php
- use-appearance.tsx
- Inertia\Response
- DashboardInsights.php
- cn
- TestCase
- app-header.tsx
- layout.tsx
- User
- Illuminate\Database\Eloquent\Model
- Illuminate\Database\Schema\Blueprint
- EmployeeImportTemplate
- app-sidebar-header.tsx
- plantilla-items.tsx
- Illuminate\Http\RedirectResponse
- MonthlyAttendanceReportController.php
- devDependencies
- dashboard.tsx
- lucide-react
- HrisResourceController
- index.ts
- HrisResourceTest
- components.json
- with
- two-factor-recovery-codes.tsx
- leave-form-preview-dialog.tsx
- Illuminate\Database\Migrations\Migration
- two-factor-setup-modal.tsx
- AppServiceProvider.php
- package.json
- Part A — Mandatory production engineering rules
- compilerOptions
- Illuminate\Foundation\Http\FormRequest
- optionalDependencies
- HandleInertiaRequests.php
- welcome.tsx
- Button
- Laravel\Fortify\Features
- Part B — Functional HRIS overview (preserved project scope)
- ProfileUpdateTest
- .accountAvatarQuery
- scripts
- Architecture Decision Log
- LeaveApplicationController
- import-employees.tsx
- .skipUnlessFortifyHas
- BiometricAttendanceTest.php
- EmployeeSpreadsheetImportTest.php
- AuthenticationTest
- composer.json
- updates
- 3. User roles and their functions
- EmailVerificationTest
- AttendanceRecord
- AttendancePunch
- services
- require-dev
- 2025_01_01_000004_create_hris_recruitment_performance_tables.php
- 2. Main system modules
- ProfileCard.jsx
- Employee
- lucide-react
- 2025_01_01_000005_create_hris_system_tables.php
- 2026_11_18_000002_expand_plantilla_items_for_hrmo_form.php
- Laravel\Fortify\Features
- cmdk
- auth.ts
- EmployeeSpreadsheetImport.php
- EmployeeSpreadsheetImportTest
- volumes
- db
- rustfs
- Illuminate\Support\Str
- attendance-corrections.tsx
- @radix-ui/react-dialog
- require
- 2026_11_18_000005_add_biometric_attendance_imports.php
- DemoPortalAccountSeeder.php
- GradientASCII.jsx
- config
- 2025_01_01_000001_create_hris_organization_tables.php
- 2026_11_18_000006_add_work_calendar.php
- scripts
- DashboardInsights
- profile.tsx
- chart.tsx
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
- 2025_01_01_000006_alter_users_for_hris_relationships.php
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- 2026_11_18_000001_add_required_mfa_to_users.php
- 2026_11_18_000002_allow_missing_attendance_records_in_corrections.php
- 2026_11_18_000003_allow_optional_email_and_password_setup.php
- 2026_11_18_000004_add_custom_fields_to_employees.php
- 2026_11_20_000001_add_avatar_to_users.php
- 2026_11_23_000001_add_commutation_to_leave_applications.php
- 2026_11_24_000001_add_profile_details_to_users.php
- A14. Change Register — MUST be maintained in THIS file
- AGENTS.md
- SpotlightCard
- pnpm-workspace.yaml
- placeholder-pattern.tsx
- WorkspaceHeader
- AttendanceCorrection
- App\Models\EvaluationPeriod
- alert.tsx
- TechText
- dashboard.ts
- PayrollDeduction
- motion@^14.14.0
- @radix-ui/react-checkbox
- recharts
- typescript@^5.7.2

## God Nodes (most connected - your core abstractions)
1. `cn()` - 152 edges
2. `Button()` - 86 edges
3. `react` - 82 edges
4. `User` - 78 edges
5. `Employee` - 63 edges
6. `@inertiajs/react` - 59 edges
7. `lucide-react` - 51 edges
8. `TestCase` - 40 edges
9. `Dashboard()` - 40 edges
10. `Input()` - 39 edges

## Surprising Connections (you probably didn't know these)
- `A3. Architecture and Laravel code quality` --references--> `Employee`  [INFERRED]
  HRIS_PROJECT_SCAN.md → app/Models/Employee.php
- `CHG-0002 - Fixed offices and account setup` --references--> `DatabaseSeeder`  [INFERRED]
  HRIS_PROJECT_SCAN.md → database/seeders/DatabaseSeeder.php
- `{closure#1}()` --calls--> `PrivacyIncident`  [EXTRACTED]
  database/seeders/DemoDashboardSeeder.php → app/Models/PrivacyIncident.php
- `{closure#1}()` --calls--> `PrivacyRequest`  [EXTRACTED]
  database/seeders/DemoDashboardSeeder.php → app/Models/PrivacyRequest.php
- `{closure#2}()` --references_constant--> `Employee`  [EXTRACTED]
  app/Http/Controllers/Hris/BiometricLinkController.php → app/Models/Employee.php

## Import Cycles
- None detected.

## Communities (148 total, 49 thin omitted)

### Community 0 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.08
Nodes (8): ApplicantEvaluation, EmployeeAppointmentChange, LeaveCredit, OvertimeCtoRecord, PerformanceCommitment, PerformanceReview, PlantillaItem, RecruitmentSelection

### Community 1 - "Input"
Cohesion: 0.13
Nodes (13): InputError(), PasswordInput(), Props, TextLink(), Checkbox(), Input(), ForgotPassword(), GoogleIcon() (+5 more)

### Community 2 - "index.tsx"
Cohesion: 0.20
Nodes (14): DirectoryLayout, directoryLayouts, DirectoryOverview(), Field, HrisRow, HrisValue, emptyValue(), FormData (+6 more)

### Community 3 - "dependencies"
Cohesion: 0.07
Nodes (30): dependencies, aos@^2.3.4, @inertiajs/react@^3.0.0, input-otp@^1.4.2, @laravel/passkeys@^0.2.0, lucide-react@^0.475.0, @radix-ui/react-avatar@^1.1.3, @radix-ui/react-dropdown-menu@^2.1.6 (+22 more)

### Community 4 - "Button"
Cohesion: 0.12
Nodes (22): AttendanceDirectory(), AttendanceDirectoryProps, EmployeeAttendance, statuses, statusFor(), AttendanceWorkspaceHeader(), viewLink(), DailyTimeRecordDialog() (+14 more)

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.08
Nodes (15): AccountInvitationController, AttendanceCorrectionController, EnsureHrisAccountReady, HandleAppearance, {closure#1}(), {closure#10}(), {closure#11}(), {closure#3}() (+7 more)

### Community 6 - "Inertia\Testing\AssertableInertia"
Cohesion: 0.07
Nodes (20): {closure#11}(), {closure#13}(), {closure#14}(), {closure#3}(), {closure#5}(), {closure#7}(), {closure#9}(), {closure#3}() (+12 more)

### Community 7 - "sidebar.tsx"
Cohesion: 0.11
Nodes (38): @radix-ui/react-tooltip@^1.1.8, @radix-ui/react-tooltip, AppLogo(), AppSidebar(), mainNavItems, NavFooter(), NavMain(), NavUser() (+30 more)

### Community 8 - "PortalController.php"
Cohesion: 0.07
Nodes (8): {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}(), AuditLog, AttendanceLogParser, {closure#1}(), BiometricLinkVerifier

### Community 10 - "two-factor-setup-modal.tsx"
Cohesion: 0.33
Nodes (15): DeleteUser(), Confirm, Context, CredentialsAcknowledgment(), FeedbackProvider(), TwoFactorSetupModal(), Dialog(), DialogClose() (+7 more)

### Community 11 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.07
Nodes (11): LeaveType, PayrollPeriod, Vacancy, AttendanceRecordFactory, DepartmentFactory, EmployeeFactory, LeaveApplicationFactory, LeaveTypeFactory (+3 more)

### Community 12 - "HrisResourceController.php"
Cohesion: 0.05
Nodes (7): {closure#13}(), {closure#32}(), {closure#33}(), {closure#34}(), {closure#39}(), {closure#6}(), {closure#7}()

### Community 13 - "use-appearance.tsx"
Cohesion: 0.10
Nodes (28): sonner@^2.0.0, sonner, withApp(), AppearanceToggleTab(), Toaster(), TooltipProvider(), Appearance, applyTheme() (+20 more)

### Community 14 - "Inertia\Response"
Cohesion: 0.08
Nodes (7): Controller, DashboardController, {closure#2}(), DailyTimeRecordController, ProfileController, SecurityController, TwoFactorAuthenticationRequest

### Community 15 - "DashboardInsights.php"
Cohesion: 0.09
Nodes (11): LeaveApplication, LoginActivity, Payroll, PrivacyIncident, PrivacyRequest, {closure#2}(), {closure#3}(), {closure#6}() (+3 more)

### Community 16 - "cn"
Cohesion: 0.11
Nodes (31): class-variance-authority@^0.7.1, class-variance-authority, Breadcrumbs(), DirectoryResults(), Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink() (+23 more)

### Community 17 - "TestCase"
Cohesion: 0.11
Nodes (8): {closure#1}(), PasswordConfirmationTest, RegistrationTest, DashboardTest, ExampleTest, {closure#2}(), TestCase, ExampleTest

### Community 18 - "app-header.tsx"
Cohesion: 0.21
Nodes (17): AppHeader(), Props, AppearanceToggle(), options, DropdownMenu(), DropdownMenuContent(), DropdownMenuGroup(), DropdownMenuItem() (+9 more)

### Community 19 - "layout.tsx"
Cohesion: 0.16
Nodes (12): DotPattern(), DotPatternProps, Separator(), SidebarSeparator(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn (+4 more)

### Community 20 - "User"
Cohesion: 0.06
Nodes (6): BootstrapHrmoHead, {closure#1}(), {closure#1}(), User, {closure#1}(), HrisAccountSetup

### Community 21 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.07
Nodes (14): {closure#27}(), Announcement, ApplicantDocument, AttendanceClockInRule, AttendanceImport, DisciplinaryRecord, EmployeeDependent, EmployeeDocument (+6 more)

### Community 22 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.13
Nodes (21): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+13 more)

### Community 23 - "EmployeeImportTemplate"
Cohesion: 0.17
Nodes (3): EmployeeImportController, EmployeeSpreadsheetImport, EmployeeImportTemplate

### Community 24 - "app-sidebar-header.tsx"
Cohesion: 0.19
Nodes (21): AppSidebarHeader(), Destination, Avatar(), AvatarFallback(), AvatarImage(), Command(), CommandDialog(), CommandEmpty() (+13 more)

### Community 25 - "plantilla-items.tsx"
Cohesion: 0.16
Nodes (20): RecordPageLink, RecordPagination(), Sheet(), SheetContent(), SheetDescription(), SheetFooter(), SheetHeader(), SheetOverlay() (+12 more)

### Community 26 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.13
Nodes (4): GoogleLoginController, BiometricLinkController, LeaveReviewController, NotificationController

### Community 27 - "MonthlyAttendanceReportController.php"
Cohesion: 0.17
Nodes (7): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), MonthlyAttendanceReportController

### Community 28 - "devDependencies"
Cohesion: 0.09
Nodes (23): @inertiajs/vite@^3.0.0, laravel-vite-plugin@^3.0.0, @tailwindcss/vite@^4.1.11, @vitejs/plugin-react@^6.1.1, devDependencies, @babel/core@^8.1.2, babel-plugin-react-compiler@^1.0.0, @laravel/vite-plugin-wayfinder@^0.1.3 (+15 more)

### Community 29 - "dashboard.tsx"
Cohesion: 0.15
Nodes (20): DropdownMenuCheckboxItem(), Select(), SelectContent(), SelectItem(), SelectLabel(), SelectScrollDownButton(), SelectScrollUpButton(), SelectSeparator() (+12 more)

### Community 30 - "lucide-react"
Cohesion: 0.13
Nodes (13): Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props, PasskeyRegistration(), Props (+5 more)

### Community 32 - "index.ts"
Cohesion: 0.17
Nodes (13): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+5 more)

### Community 34 - "components.json"
Cohesion: 0.10
Nodes (20): aliases, components, hooks, lib, ui, utils, iconLibrary, registries (+12 more)

### Community 35 - "with"
Cohesion: 0.10
Nodes (20): runs-on, steps, jobs, ci, name, on, pull_request, push (+12 more)

### Community 36 - "two-factor-recovery-codes.tsx"
Cohesion: 0.26
Nodes (13): AppLogoIcon(), Props, TwoFactorRecoveryCodes(), Card(), CardContent(), CardDescription(), CardFooter(), CardHeader() (+5 more)

### Community 37 - "leave-form-preview-dialog.tsx"
Cohesion: 0.17
Nodes (19): Choice(), creditRows, FormLine(), LeaveApplicant, LeaveDraft, LeaveFormPreview(), LeaveFormPreviewDialog(), leaveReasons (+11 more)

### Community 38 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.12
Nodes (6): {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 39 - "two-factor-setup-modal.tsx"
Cohesion: 0.12
Nodes (16): input-otp, GridScanIcon(), Props, TwoFactorSetupStep(), TwoFactorVerificationStep(), InputOTP, InputOTPGroup, InputOTPSeparator (+8 more)

### Community 40 - "AppServiceProvider.php"
Cohesion: 0.16
Nodes (5): AppServiceProvider, {closure#1}(), {closure#2}(), {closure#3}(), FortifyServiceProvider

### Community 41 - "package.json"
Cohesion: 0.09
Nodes (21): clsx@^2.1.1, concurrently@^10.0.3, gsap@^3.15.1, @radix-ui/react-toggle@^1.1.2, react-dom@^19.2.0, tailwindcss@^4.0.0, @types/react@^19.2.0, vite@npm:@voidzero-dev/vite-plus-core@1.4.0 (+13 more)

### Community 42 - "Part A — Mandatory production engineering rules"
Cohesion: 0.12
Nodes (16): A10. Minimum test and verification matrix, A11. Deployment checklist (copy and complete per release), A12. CI and reviewer guardrail for documentation changes, A13. Open decisions / production blockers — do not guess, A15. Mandatory major-change record template, A16. External references (for validation, not a substitute for municipal sign-off), A1. Document authority, roles, and status, A2. Major-change definition and mandatory Markdown update (+8 more)

### Community 43 - "compilerOptions"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

### Community 44 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.08
Nodes (8): ResetUserPassword, PasswordValidationRules, ProfileValidationRules, ImportAttendanceLogRequest, UpdateBiometricLinkRequest, PasswordUpdateRequest, ProfileDeleteRequest, ProfileUpdateRequest

### Community 45 - "optionalDependencies"
Cohesion: 0.13
Nodes (15): optionalDependencies, @laravel/multiplex@^0.4.1, lightningcss-linux-x64-gnu@^1.29.1, lightningcss-win32-x64-msvc@^1.29.1, @rollup/rollup-linux-x64-gnu@4.9.5, @rollup/rollup-win32-x64-msvc@4.9.5, @tailwindcss/oxide-linux-x64-gnu@^4.0.1, @tailwindcss/oxide-win32-x64-msvc@^4.0.1 (+7 more)

### Community 46 - "HandleInertiaRequests.php"
Cohesion: 0.12
Nodes (5): PortalController, {closure#3}(), {closure#4}(), HandleInertiaRequests, Notification

### Community 47 - "welcome.tsx"
Cohesion: 0.23
Nodes (11): PublicHeader(), clamp01(), clampToScroll(), easeBack(), easeOut(), glowAt(), paintPiece(), ScrollReveal() (+3 more)

### Community 48 - "Button"
Cohesion: 0.12
Nodes (24): @inertiajs/react, react, PreviewContent(), columns, DailyTimeRecordData, DailyTimeRecordPreview(), useFeedback(), PasskeyItem() (+16 more)

### Community 49 - "Laravel\Fortify\Features"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#2}()

### Community 50 - "Part B — Functional HRIS overview (preserved project scope)"
Cohesion: 0.14
Nodes (13): 10. Important rules before development, 11. The complete system in one simple view, 1. Main purpose of the system, 4. Suggested dashboard and sidebar structure, 5. What should appear on each dashboard?, 6. How the modules work together, 8. Important system features, 9. Recommended development order (+5 more)

### Community 52 - ".accountAvatarQuery"
Cohesion: 0.25
Nodes (5): {closure#1}(), AttendanceLogImportController, {closure#1}(), {closure#1}(), {closure#5}()

### Community 53 - "scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 54 - "Architecture Decision Log"
Cohesion: 0.15
Nodes (12): Architecture Decision Log, D-001: Application stack (Laravel + React + Inertia), D-002: PHP 8.4 is the minimum version, D-003: MySQL 8.4 instead of PostgreSQL 16, D-004: RustFS replaces MinIO for local file storage, D-005: The app container does not use `env_file`, D-006: Tests run on in-memory SQLite with forced settings, D-007: Frontend is built on the host, not in Docker (+4 more)

### Community 56 - "import-employees.tsx"
Cohesion: 0.20
Nodes (5): Credential, ImportEmployees(), PreviewRow, Result, TemplateColumn

### Community 58 - "BiometricAttendanceTest.php"
Cohesion: 0.15
Nodes (4): {closure#11}(), {closure#3}(), {closure#5}(), {closure#7}()

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
Nodes (3): {closure#1}(), AttendanceRecord, {closure#2}()

### Community 66 - "AttendancePunch"
Cohesion: 0.18
Nodes (3): {closure#7}(), AttendancePunch, Payslip

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
Cohesion: 0.22
Nodes (9): @hugeicons/core-free-icons@^4.4.0, @hugeicons/react@^1.1.10, @hugeicons/core-free-icons, @hugeicons/react, clamp(), FOILS, isDark(), ProfileCard() (+1 more)

### Community 73 - "lucide-react"
Cohesion: 0.13
Nodes (8): @laravel/passkeys, lucide-react, PasskeyVerify(), Props, IconProps, Spinner(), ConfirmPassword(), VerifyEmail()

### Community 74 - "2025_01_01_000005_create_hris_system_tables.php"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 75 - "2026_11_18_000002_expand_plantilla_items_for_hrmo_form.php"
Cohesion: 0.20
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}()

### Community 78 - "auth.ts"
Cohesion: 0.20
Nodes (9): Auth, Passkey, TwoFactorSecretKey, TwoFactorSetupData, User, @inertiajs/core, InertiaConfig, react (+1 more)

### Community 79 - "EmployeeSpreadsheetImport.php"
Cohesion: 0.11
Nodes (5): {closure#5}(), {closure#6}(), {closure#7}(), Position, PositionFactory

### Community 81 - "volumes"
Cohesion: 0.22
Nodes (8): driver, networks, hris, volumes, mysql_data, redis_data, rustfs_data, vendor_data

### Community 82 - "db"
Cohesion: 0.22
Nodes (9): environment, image, networks, volumes, MYSQL_DATABASE, MYSQL_PASSWORD, MYSQL_ROOT_PASSWORD, MYSQL_USER (+1 more)

### Community 83 - "rustfs"
Cohesion: 0.22
Nodes (9): RUSTFS_ACCESS_KEY, RUSTFS_CONSOLE_ENABLE, RUSTFS_SECRET_KEY, environment, image, networks, ports, volumes (+1 more)

### Community 85 - "attendance-corrections.tsx"
Cohesion: 0.22
Nodes (16): BiometricIdField(), ResourceDirectory(), TableAction(), Table(), TableBody(), TableCaption(), TableCell(), TableFooter() (+8 more)

### Community 87 - "require"
Cohesion: 0.25
Nodes (8): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php

### Community 88 - "2026_11_18_000005_add_biometric_attendance_imports.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 89 - "DemoPortalAccountSeeder.php"
Cohesion: 0.29
Nodes (3): DatabaseSeeder, DemoDashboardSeeder, DemoPortalAccountSeeder

### Community 90 - "GradientASCII.jsx"
Cohesion: 0.22
Nodes (13): buildAtlas(), clamp(), CLICK_EFFECTS, compile(), createTarget(), DEFAULT_COLORS, GradientASCII(), link() (+5 more)

### Community 91 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 92 - "2025_01_01_000001_create_hris_organization_tables.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 93 - "2026_11_18_000006_add_work_calendar.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 94 - "scripts"
Cohesion: 0.29
Nodes (7): scripts, build, build:ssr, check, check:fix, dev, types:check

### Community 96 - "profile.tsx"
Cohesion: 0.17
Nodes (10): autocomplete, Details, Field, labels, lengths, Profile(), ProfileCard(), ProfileValues (+2 more)

### Community 97 - "chart.tsx"
Cohesion: 0.22
Nodes (12): ChartConfig, ChartContainer(), ChartContext, ChartContextProps, ChartLegendContent(), ChartStyle(), ChartTooltipContent(), getPayloadConfigFromPayload() (+4 more)

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
Cohesion: 0.20
Nodes (4): {closure#14}(), {closure#3}(), {closure#6}(), Department

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

### Community 120 - "A14. Change Register — MUST be maintained in THIS file"
Cohesion: 0.40
Nodes (5): A14. Change Register — MUST be maintained in THIS file, CHG-0002 - Fixed offices and account setup, CHG-0003 - Employee spreadsheet import and department directory, CHG-0004 - Employee import workbook columns, CHG-0005 - Employee account creation workflow

### Community 123 - "SpotlightCard"
Cohesion: 0.33
Nodes (8): clamp(), falloff(), SHAPES, SpotlightCard(), THEMES, WorkspaceMetric, WorkspaceMetrics(), WorkspaceOverview()

### Community 137 - "WorkspaceHeader"
Cohesion: 0.36
Nodes (5): views, WorkspaceHeader(), EmployeeProfile, MyProfile(), sections

### Community 138 - "AttendanceCorrection"
Cohesion: 0.33
Nodes (3): {closure#3}(), {closure#5}(), AttendanceCorrection

### Community 140 - "alert.tsx"
Cohesion: 0.62
Nodes (5): AlertError(), Alert(), AlertDescription(), AlertTitle(), alertVariants

### Community 141 - "TechText"
Cohesion: 0.57
Nodes (6): approach(), clamp01(), noise(), signed(), smoothstep(), TechText()

### Community 142 - "dashboard.ts"
Cohesion: 0.29
Nodes (6): DashboardAnalytics, DashboardMetric, DashboardPoint, DashboardRecord, DashboardSeries, NotificationFeed

## Knowledge Gaps
- **49 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Http\Request`, `Inertia\Testing\AssertableInertia`, `PortalController.php`, `Illuminate\Database\Eloquent\Factories\Factory`, `HrisResourceController.php`, `Inertia\Response`, `DashboardInsights.php`, `TestCase`, `Illuminate\Http\RedirectResponse`, `HrisResourceTest`, `AppServiceProvider.php`, `Illuminate\Foundation\Http\FormRequest`, `Laravel\Fortify\Features`, `ProfileUpdateTest`, `BiometricAttendanceTest.php`, `EmployeeSpreadsheetImportTest.php`, `AuthenticationTest`, `EmailVerificationTest`, `AttendanceRecord`, `Laravel\Fortify\Features`, `EmployeeSpreadsheetImport.php`, `EmployeeSpreadsheetImportTest`, `Illuminate\Support\Str`, `DashboardInsights`?**
  _High betweenness centrality (0.063) - this node is a cross-community bridge._
- **Should `Illuminate\Database\Eloquent\Relations\BelongsTo` be split into smaller, more focused modules?**
  _Cohesion score 0.08108108108108109 - nodes in this community are weakly interconnected._
- **Why does `Employee` connect `Employee` to `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Inertia\Testing\AssertableInertia`, `PortalController.php`, `App\Models\Applicant`, `Illuminate\Database\Eloquent\Factories\Factory`, `HrisResourceController.php`, `Inertia\Response`, `DashboardInsights.php`, `TestCase`, `User`, `Illuminate\Database\Eloquent\Model`, `EmployeeImportTemplate`, `Illuminate\Http\RedirectResponse`, `MonthlyAttendanceReportController.php`, `HrisResourceController`, `HrisResourceTest`, `Part A — Mandatory production engineering rules`, `.accountAvatarQuery`, `BiometricAttendanceTest.php`, `EmployeeSpreadsheetImportTest.php`, `EmailVerificationTest`, `AttendanceRecord`, `EmployeeSpreadsheetImport.php`?**
  _High betweenness centrality (0.058) - this node is a cross-community bridge._
- **Should `Input` be split into smaller, more focused modules?**
  _Cohesion score 0.13068181818181818 - nodes in this community are weakly interconnected._
- **Why does `react` connect `Button` to `Input`, `index.tsx`, `dependencies`, `Button`, `sidebar.tsx`, `WorkspaceHeader`, `two-factor-setup-modal.tsx`, `alert.tsx`, `use-appearance.tsx`, `TechText`, `cn`, `app-header.tsx`, `layout.tsx`, `app-sidebar-header.tsx`, `plantilla-items.tsx`, `dashboard.tsx`, `lucide-react`, `index.ts`, `two-factor-recovery-codes.tsx`, `leave-form-preview-dialog.tsx`, `two-factor-setup-modal.tsx`, `package.json`, `welcome.tsx`, `import-employees.tsx`, `ProfileCard.jsx`, `attendance-corrections.tsx`, `GradientASCII.jsx`, `profile.tsx`, `chart.tsx`, `SpotlightCard`, `placeholder-pattern.tsx`?**
  _High betweenness centrality (0.024) - this node is a cross-community bridge._
- **Should `dependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.06666666666666667 - nodes in this community are weakly interconnected._
- **Should `Button` be split into smaller, more focused modules?**
  _Cohesion score 0.11965811965811966 - nodes in this community are weakly interconnected._