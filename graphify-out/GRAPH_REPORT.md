# Graph Report - LGU_Clarin_HRIS_SYSTEM  (2026-11-25)

## Corpus Check
- 309 files · ~112,517 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 46 file(s) not represented in the graph (top: (none) 30, .css 9, .conf 2)

## Summary
- 2172 nodes · 5176 edges · 137 communities (92 shown, 45 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 34 edges (avg confidence: 0.86)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `fad8e065`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Eloquent\Relations\BelongsTo
- react
- index.tsx
- package.json
- Button
- Illuminate\Http\Request
- Inertia\Testing\AssertableInertia
- sidebar.tsx
- EmployeeSpreadsheetImport.php
- Employee
- two-factor-setup-modal.tsx
- Illuminate\Database\Eloquent\Factories\Factory
- HrisResourceController.php
- use-appearance.tsx
- Inertia\Response
- DashboardInsights.php
- cn
- TestCase
- app-header.tsx
- import-employees.tsx
- User
- AttendanceLogImportController.php
- Illuminate\Database\Schema\Blueprint
- EmployeeImportTemplate
- web.php
- app-sidebar-header.tsx
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
- AppServiceProvider.php
- welcome.tsx
- Part A — Mandatory production engineering rules
- compilerOptions
- PasswordValidationRules
- optionalDependencies
- profile.tsx
- GradientASCII.jsx
- Illuminate\Foundation\Http\FormRequest
- Laravel\Fortify\Features
- Part B — Functional HRIS overview (preserved project scope)
- ProfileUpdateTest
- DashboardInsights
- scripts
- Architecture Decision Log
- toggle-group.tsx
- chart.tsx
- .skipUnlessFortifyHas
- BiometricAttendanceTest.php
- Department
- composer.json
- updates
- 3. User roles and their functions
- EmailVerificationTest
- LeaveApplication
- Payslip
- services
- require-dev
- 2025_01_01_000004_create_hris_recruitment_performance_tables.php
- 2. Main system modules
- ProfileCard.jsx
- Position
- Role
- 2025_01_01_000005_create_hris_system_tables.php
- 2026_11_18_000002_expand_plantilla_items_for_hrmo_form.php
- AccountInvitationTest.php
- breadcrumbs.tsx
- auth.ts
- PasswordResetTest.php
- EmployeeSpreadsheetImportTest
- volumes
- db
- rustfs
- UserFactory.php
- ProfileUpdateRequest
- Vacancy
- require
- 2026_11_18_000005_add_biometric_attendance_imports.php
- DemoPortalAccountSeeder.php
- AttendanceCorrection
- config
- 2025_01_01_000001_create_hris_organization_tables.php
- 2026_11_18_000006_add_work_calendar.php
- scripts
- alert.tsx
- TechText
- dashboard.ts
- healthcheck
- nginx
- 0001_01_01_000000_create_users_table.php
- 0001_01_01_000002_create_jobs_table.php
- 7. Common workflows in the system
- collapsible.tsx
- SpotlightCard
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
- pnpm-workspace.yaml
- placeholder-pattern.tsx

## God Nodes (most connected - your core abstractions)
1. `cn()` - 152 edges
2. `Button()` - 86 edges
3. `User` - 83 edges
4. `react` - 82 edges
5. `Employee` - 73 edges
6. `@inertiajs/react` - 59 edges
7. `lucide-react` - 50 edges
8. `Dashboard()` - 40 edges
9. `TestCase` - 40 edges
10. `Input()` - 39 edges

## Surprising Connections (you probably didn't know these)
- `A3. Architecture and Laravel code quality` --references--> `Employee`  [INFERRED]
  HRIS_PROJECT_SCAN.md → app/Models/Employee.php
- `CHG-0002 - Fixed offices and account setup` --references--> `DatabaseSeeder`  [INFERRED]
  HRIS_PROJECT_SCAN.md → database/seeders/DatabaseSeeder.php
- `{closure#39}()` --references--> `Employee`  [EXTRACTED]
  app/Http/Controllers/Hris/HrisResourceController.php → app/Models/Employee.php
- `{closure#3}()` --references--> `Department`  [EXTRACTED]
  app/Http/Controllers/Hris/PortalController.php → app/Models/Department.php
- `{closure#6}()` --references--> `Department`  [EXTRACTED]
  app/Http/Controllers/Hris/PortalController.php → app/Models/Department.php

## Import Cycles
- None detected.

## Communities (137 total, 45 thin omitted)

### Community 0 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.04
Nodes (25): {closure#27}(), {closure#6}(), Announcement, ApplicantDocument, ApplicantEvaluation, AttendanceClockInRule, AttendanceImport, DisciplinaryRecord (+17 more)

### Community 1 - "react"
Cohesion: 0.08
Nodes (27): @laravel/passkeys, react, InputError(), PasskeyRegistration(), Props, PasskeyVerify(), Props, PasswordInput() (+19 more)

### Community 2 - "index.tsx"
Cohesion: 0.08
Nodes (52): AttendanceDirectory(), AttendanceDirectoryProps, EmployeeAttendance, statuses, statusFor(), BiometricIdField(), useFeedback(), PersonAvatar() (+44 more)

### Community 3 - "package.json"
Cohesion: 0.05
Nodes (60): dependencies, aos@^2.3.4, clsx@^2.1.1, cmdk@^1.1.1, concurrently@^10.0.3, gsap@^3.15.1, @inertiajs/react@^3.0.0, input-otp@^1.4.2 (+52 more)

### Community 4 - "Button"
Cohesion: 0.09
Nodes (37): @inertiajs/react, @radix-ui/react-slot, AttendanceWorkspaceHeader(), viewLink(), views, DailyTimeRecordDialog(), PreviewContent(), columns (+29 more)

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.07
Nodes (17): AccountInvitationController, AttendanceCorrectionController, PortalController, {closure#3}(), {closure#4}(), HandleInertiaRequests, {closure#1}(), {closure#10}() (+9 more)

### Community 6 - "Inertia\Testing\AssertableInertia"
Cohesion: 0.06
Nodes (22): {closure#11}(), {closure#13}(), {closure#14}(), {closure#3}(), {closure#5}(), {closure#7}(), {closure#9}(), {closure#3}() (+14 more)

### Community 7 - "sidebar.tsx"
Cohesion: 0.12
Nodes (36): AppSidebar(), mainNavItems, NavFooter(), NavMain(), NavUser(), SidebarContent(), SidebarContext, SidebarFooter() (+28 more)

### Community 8 - "EmployeeSpreadsheetImport.php"
Cohesion: 0.07
Nodes (7): BootstrapHrmoHead, {closure#2}(), {closure#5}(), {closure#7}(), AuditLog, {closure#1}(), HrisAccountSetup

### Community 9 - "Employee"
Cohesion: 0.08
Nodes (5): {closure#2}(), {closure#1}(), Applicant, Employee, ApplicantFactory

### Community 10 - "two-factor-setup-modal.tsx"
Cohesion: 0.13
Nodes (31): input-otp, DeleteUser(), Confirm, Context, CredentialsAcknowledgment(), FeedbackProvider(), GridScanIcon(), Props (+23 more)

### Community 11 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.08
Nodes (12): {closure#1}(), {closure#2}(), AttendanceRecord, EvaluationPeriod, LeaveType, PayrollPeriod, AttendanceRecordFactory, EmployeeFactory (+4 more)

### Community 13 - "use-appearance.tsx"
Cohesion: 0.10
Nodes (27): sonner@^2.0.0, sonner, withApp(), AppearanceToggleTab(), Toaster(), TooltipProvider(), Appearance, applyTheme() (+19 more)

### Community 14 - "Inertia\Response"
Cohesion: 0.08
Nodes (6): Controller, DailyTimeRecordController, LeaveReviewController, {closure#3}(), {closure#6}(), SecurityController

### Community 15 - "DashboardInsights.php"
Cohesion: 0.08
Nodes (11): LoginActivity, Payroll, PrivacyIncident, PrivacyRequest, {closure#3}(), {closure#6}(), {closure#7}(), {closure#8}() (+3 more)

### Community 16 - "cn"
Cohesion: 0.11
Nodes (27): DirectoryResults(), DotPattern(), DotPatternProps, DropdownMenuShortcut(), DropdownMenuSubContent(), DropdownMenuSubTrigger(), NavigationMenu(), NavigationMenuContent() (+19 more)

### Community 17 - "TestCase"
Cohesion: 0.10
Nodes (8): AuthenticationTest, {closure#1}(), PasswordConfirmationTest, RegistrationTest, DashboardTest, ExampleTest, TestCase, ExampleTest

### Community 18 - "app-header.tsx"
Cohesion: 0.16
Nodes (20): AppHeader(), Props, AppLogo(), AppearanceToggle(), options, PublicHeader(), DropdownMenu(), DropdownMenuContent() (+12 more)

### Community 19 - "import-employees.tsx"
Cohesion: 0.09
Nodes (17): WorkspaceHeader(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn, WhenCurrentUrlFn, SettingsLayout(), sidebarNavItems (+9 more)

### Community 20 - "User"
Cohesion: 0.09
Nodes (4): {closure#1}(), {closure#1}(), {closure#33}(), User

### Community 21 - "AttendanceLogImportController.php"
Cohesion: 0.10
Nodes (7): AttendanceLogImportController, BiometricLinkController, {closure#1}(), AttendancePunch, AttendanceLogParser, {closure#1}(), BiometricLinkVerifier

### Community 22 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.13
Nodes (21): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+13 more)

### Community 23 - "EmployeeImportTemplate"
Cohesion: 0.15
Nodes (3): EmployeeImportController, EmployeeSpreadsheetImport, EmployeeImportTemplate

### Community 24 - "web.php"
Cohesion: 0.10
Nodes (4): DashboardController, LeaveApplicationController, EnsureHrisAccountReady, HandleAppearance

### Community 25 - "app-sidebar-header.tsx"
Cohesion: 0.19
Nodes (20): AppSidebarHeader(), Destination, Avatar(), AvatarFallback(), AvatarImage(), Command(), CommandDialog(), CommandEmpty() (+12 more)

### Community 26 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.13
Nodes (4): GoogleLoginController, NotificationController, ProfileController, ProfileDeleteRequest

### Community 27 - "MonthlyAttendanceReportController.php"
Cohesion: 0.12
Nodes (11): {closure#1}(), {closure#1}(), {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+3 more)

### Community 28 - "devDependencies"
Cohesion: 0.09
Nodes (23): @inertiajs/vite@^3.0.0, laravel-vite-plugin@^3.0.0, @tailwindcss/vite@^4.1.11, @vitejs/plugin-react@^6.1.1, devDependencies, @babel/core@^8.1.2, babel-plugin-react-compiler@^1.0.0, @laravel/vite-plugin-wayfinder@^0.1.3 (+15 more)

### Community 29 - "dashboard.tsx"
Cohesion: 0.15
Nodes (20): DropdownMenuCheckboxItem(), Select(), SelectContent(), SelectItem(), SelectLabel(), SelectScrollDownButton(), SelectScrollUpButton(), SelectSeparator() (+12 more)

### Community 30 - "lucide-react"
Cohesion: 0.13
Nodes (11): lucide-react, Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props, PasskeyItem() (+3 more)

### Community 31 - "HrisResourceController"
Cohesion: 0.18
Nodes (5): {closure#13}(), {closure#32}(), {closure#34}(), {closure#35}(), HrisResourceController

### Community 32 - "index.ts"
Cohesion: 0.18
Nodes (14): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+6 more)

### Community 34 - "components.json"
Cohesion: 0.10
Nodes (20): aliases, components, hooks, lib, ui, utils, iconLibrary, registries (+12 more)

### Community 35 - "with"
Cohesion: 0.10
Nodes (20): runs-on, steps, jobs, ci, name, on, pull_request, push (+12 more)

### Community 36 - "two-factor-recovery-codes.tsx"
Cohesion: 0.28
Nodes (13): AppLogoIcon(), Props, TwoFactorRecoveryCodes(), Card(), CardContent(), CardDescription(), CardFooter(), CardHeader() (+5 more)

### Community 37 - "leave-form-preview-dialog.tsx"
Cohesion: 0.17
Nodes (18): Choice(), creditRows, FormLine(), LeaveApplicant, LeaveDraft, LeaveFormPreview(), LeaveFormPreviewDialog(), leaveReasons (+10 more)

### Community 38 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.12
Nodes (6): {closure#1}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 40 - "AppServiceProvider.php"
Cohesion: 0.16
Nodes (5): AppServiceProvider, {closure#1}(), {closure#2}(), {closure#3}(), FortifyServiceProvider

### Community 41 - "welcome.tsx"
Cohesion: 0.22
Nodes (11): navigation, clamp01(), clampToScroll(), easeBack(), easeOut(), glowAt(), paintPiece(), ScrollReveal() (+3 more)

### Community 42 - "Part A — Mandatory production engineering rules"
Cohesion: 0.12
Nodes (16): A10. Minimum test and verification matrix, A11. Deployment checklist (copy and complete per release), A12. CI and reviewer guardrail for documentation changes, A13. Open decisions / production blockers — do not guess, A15. Mandatory major-change record template, A16. External references (for validation, not a substitute for municipal sign-off), A1. Document authority, roles, and status, A2. Major-change definition and mandatory Markdown update (+8 more)

### Community 43 - "compilerOptions"
Cohesion: 0.12
Nodes (15): compilerOptions, allowJs, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module, moduleResolution (+7 more)

### Community 44 - "PasswordValidationRules"
Cohesion: 0.21
Nodes (3): ResetUserPassword, PasswordValidationRules, PasswordUpdateRequest

### Community 45 - "optionalDependencies"
Cohesion: 0.13
Nodes (15): optionalDependencies, @laravel/multiplex@^0.4.1, lightningcss-linux-x64-gnu@^1.29.1, lightningcss-win32-x64-msvc@^1.29.1, @rollup/rollup-linux-x64-gnu@4.9.5, @rollup/rollup-win32-x64-msvc@4.9.5, @tailwindcss/oxide-linux-x64-gnu@^4.0.1, @tailwindcss/oxide-win32-x64-msvc@^4.0.1 (+7 more)

### Community 46 - "profile.tsx"
Cohesion: 0.14
Nodes (10): autocomplete, Details, Field, labels, lengths, Profile(), ProfileCard(), ProfileValues (+2 more)

### Community 47 - "GradientASCII.jsx"
Cohesion: 0.22
Nodes (13): buildAtlas(), clamp(), CLICK_EFFECTS, compile(), createTarget(), DEFAULT_COLORS, GradientASCII(), link() (+5 more)

### Community 48 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.19
Nodes (3): ImportAttendanceLogRequest, UpdateBiometricLinkRequest, TwoFactorAuthenticationRequest

### Community 50 - "Part B — Functional HRIS overview (preserved project scope)"
Cohesion: 0.14
Nodes (13): 10. Important rules before development, 11. The complete system in one simple view, 1. Main purpose of the system, 4. Suggested dashboard and sidebar structure, 5. What should appear on each dashboard?, 6. How the modules work together, 8. Important system features, 9. Recommended development order (+5 more)

### Community 53 - "scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 54 - "Architecture Decision Log"
Cohesion: 0.15
Nodes (12): Architecture Decision Log, D-001: Application stack (Laravel + React + Inertia), D-002: PHP 8.4 is the minimum version, D-003: MySQL 8.4 instead of PostgreSQL 16, D-004: RustFS replaces MinIO for local file storage, D-005: The app container does not use `env_file`, D-006: Tests run on in-memory SQLite with forced settings, D-007: Frontend is built on the host, not in Docker (+4 more)

### Community 55 - "toggle-group.tsx"
Cohesion: 0.21
Nodes (11): class-variance-authority@^0.7.1, @radix-ui/react-toggle@^1.1.2, @radix-ui/react-toggle-group@^1.1.2, class-variance-authority, @radix-ui/react-toggle, @radix-ui/react-toggle-group, ToggleGroup(), ToggleGroupContext (+3 more)

### Community 56 - "chart.tsx"
Cohesion: 0.22
Nodes (12): ChartConfig, ChartContainer(), ChartContext, ChartContextProps, ChartLegendContent(), ChartStyle(), ChartTooltipContent(), getPayloadConfigFromPayload() (+4 more)

### Community 58 - "BiometricAttendanceTest.php"
Cohesion: 0.15
Nodes (4): {closure#11}(), {closure#3}(), {closure#5}(), {closure#7}()

### Community 60 - "Department"
Cohesion: 0.18
Nodes (3): {closure#14}(), Department, DepartmentFactory

### Community 61 - "composer.json"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 62 - "updates"
Cohesion: 0.17
Nodes (11): default-days, patterns, github-actions, interval, updates, cooldown, directory, groups (+3 more)

### Community 63 - "3. User roles and their functions"
Cohesion: 0.17
Nodes (12): 10. Data Protection Officer (DPO), 11. Auditor / Authorized Audit User, 1. Employee, 2. Department Head / Office Head, 3. HR Officer, 3. User roles and their functions, 4. HRMO Head, 5. Payroll Officer (+4 more)

### Community 65 - "LeaveApplication"
Cohesion: 0.22
Nodes (4): {closure#1}(), LeaveApplication, {closure#2}(), LeaveApplicationFactory

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

### Community 72 - "Position"
Cohesion: 0.22
Nodes (3): {closure#6}(), Position, PositionFactory

### Community 73 - "Role"
Cohesion: 0.22
Nodes (3): Role, RoleFactory, {closure#2}()

### Community 74 - "2025_01_01_000005_create_hris_system_tables.php"
Cohesion: 0.20
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}()

### Community 75 - "2026_11_18_000002_expand_plantilla_items_for_hrmo_form.php"
Cohesion: 0.20
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#6}(), {closure#7}()

### Community 77 - "breadcrumbs.tsx"
Cohesion: 0.44
Nodes (8): Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink(), BreadcrumbList(), BreadcrumbPage(), BreadcrumbSeparator()

### Community 78 - "auth.ts"
Cohesion: 0.22
Nodes (8): Auth, Passkey, TwoFactorSecretKey, TwoFactorSetupData, @inertiajs/core, InertiaConfig, react, InputHTMLAttributes

### Community 81 - "volumes"
Cohesion: 0.22
Nodes (8): driver, networks, hris, volumes, mysql_data, redis_data, rustfs_data, vendor_data

### Community 82 - "db"
Cohesion: 0.22
Nodes (9): environment, image, networks, volumes, MYSQL_DATABASE, MYSQL_PASSWORD, MYSQL_ROOT_PASSWORD, MYSQL_USER (+1 more)

### Community 83 - "rustfs"
Cohesion: 0.22
Nodes (9): RUSTFS_ACCESS_KEY, RUSTFS_CONSOLE_ENABLE, RUSTFS_SECRET_KEY, environment, image, networks, ports, volumes (+1 more)

### Community 87 - "require"
Cohesion: 0.25
Nodes (8): require, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker, laravel/wayfinder, php

### Community 88 - "2026_11_18_000005_add_biometric_attendance_imports.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 90 - "AttendanceCorrection"
Cohesion: 0.33
Nodes (3): {closure#3}(), {closure#5}(), AttendanceCorrection

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

### Community 95 - "alert.tsx"
Cohesion: 0.62
Nodes (5): AlertError(), Alert(), AlertDescription(), AlertTitle(), alertVariants

### Community 96 - "TechText"
Cohesion: 0.57
Nodes (6): approach(), clamp01(), noise(), signed(), smoothstep(), TechText()

### Community 97 - "dashboard.ts"
Cohesion: 0.29
Nodes (6): DashboardAnalytics, DashboardMetric, DashboardPoint, DashboardRecord, DashboardSeries, NotificationFeed

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

### Community 104 - "SpotlightCard"
Cohesion: 0.53
Nodes (5): clamp(), falloff(), SHAPES, SpotlightCard(), THEMES

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

## Knowledge Gaps
- **45 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Http\Request`, `Inertia\Testing\AssertableInertia`, `EmployeeSpreadsheetImport.php`, `Illuminate\Database\Eloquent\Factories\Factory`, `HrisResourceController.php`, `Inertia\Response`, `DashboardInsights.php`, `TestCase`, `AttendanceLogImportController.php`, `EmployeeImportTemplate`, `web.php`, `Illuminate\Http\RedirectResponse`, `HrisResourceTest.php`, `AppServiceProvider.php`, `PasswordValidationRules`, `Laravel\Fortify\Features`, `ProfileUpdateTest`, `DashboardInsights`, `BiometricAttendanceTest.php`, `EmployeeSpreadsheetImportTest.php`, `EmailVerificationTest`, `Role`, `AccountInvitationTest.php`, `PasswordResetTest.php`, `UserFactory.php`, `DemoPortalAccountSeeder.php`, `BiometricLinkVerifier.php`?**
  _High betweenness centrality (0.054) - this node is a cross-community bridge._
- **Should `Illuminate\Database\Eloquent\Relations\BelongsTo` be split into smaller, more focused modules?**
  _Cohesion score 0.04119850187265917 - nodes in this community are weakly interconnected._
- **Why does `Employee` connect `Employee` to `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Inertia\Testing\AssertableInertia`, `EmployeeSpreadsheetImport.php`, `Illuminate\Database\Eloquent\Factories\Factory`, `HrisResourceController.php`, `Inertia\Response`, `DashboardInsights.php`, `User`, `AttendanceLogImportController.php`, `EmployeeImportTemplate`, `MonthlyAttendanceReportController.php`, `HrisResourceController`, `HrisResourceTest.php`, `Part A — Mandatory production engineering rules`, `BiometricAttendanceTest.php`, `EmployeeSpreadsheetImportTest.php`, `LeaveApplication`, `Role`, `AccountInvitationTest.php`, `BiometricLinkVerifier.php`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **Should `react` be split into smaller, more focused modules?**
  _Cohesion score 0.07885763000852515 - nodes in this community are weakly interconnected._
- **Why does `react` connect `react` to `index.tsx`, `package.json`, `Button`, `sidebar.tsx`, `two-factor-setup-modal.tsx`, `use-appearance.tsx`, `cn`, `app-header.tsx`, `import-employees.tsx`, `app-sidebar-header.tsx`, `dashboard.tsx`, `lucide-react`, `index.ts`, `two-factor-recovery-codes.tsx`, `leave-form-preview-dialog.tsx`, `welcome.tsx`, `profile.tsx`, `GradientASCII.jsx`, `toggle-group.tsx`, `chart.tsx`, `ProfileCard.jsx`, `breadcrumbs.tsx`, `alert.tsx`, `TechText`, `SpotlightCard`, `placeholder-pattern.tsx`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Should `index.tsx` be split into smaller, more focused modules?**
  _Cohesion score 0.08365384615384615 - nodes in this community are weakly interconnected._
- **Should `package.json` be split into smaller, more focused modules?**
  _Cohesion score 0.04590163934426229 - nodes in this community are weakly interconnected._