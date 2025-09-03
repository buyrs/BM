# AGENTS.md - Laravel Bail Mobilité Management System

## Build/Test/Development Commands
- **Development**: `composer dev` (runs Laravel server, queue listener, logging, and Vite dev server)
- **Frontend Build**: `npm run build` (Vite build for production)
- **Frontend Dev**: `npm run dev` (Vite dev server only)
- **PHP Tests**: `composer test` or `php artisan test` (PHPUnit test suite)
- **JS Tests**: `npm run test` (Vitest, single run) | `npm run test:watch` (watch mode) | `npm run test:ui` (UI mode)
- **Lint/Format**: `vendor/bin/pint` (Laravel Pint for PHP code formatting)
- **Single Test**: `php artisan test --filter=TestMethodName` | `npm run test -- TestFileName`

## Architecture Overview
- **Framework**: Laravel 12 + Inertia.js + Vue 3 SPA
- **Frontend**: Vue 3 + TailwindCSS + Alpine.js + Headless UI + Chart.js
- **Database**: SQLite (dev), configured for PostgreSQL (production)
- **Auth**: Laravel Breeze + Google OAuth (Socialite) + Spatie Permissions (roles: super-admin, ops, admin, checker)
- **Key Features**: Bail Mobilité management, property inspections, digital signatures, PDF reports, PWA, calendar sync

## Code Style Guidelines
- **PHP**: Laravel conventions, PSR-12, use Laravel Pint for formatting
- **Vue/JS**: Use Composition API, TypeScript where possible, follow Vue style guide
- **CSS**: TailwindCSS utilities, avoid custom CSS unless necessary, use Flowbite components
- **Imports**: Laravel auto-discovery for PHP, ES6 imports for JS, use Ziggy for route helpers
- **Naming**: camelCase (JS), snake_case (PHP/DB), PascalCase (Vue components, PHP classes)
- **Error Handling**: Laravel exceptions, Vue error boundaries, API returns standard Laravel error responses

## Design System Rules (from CLAUDE.md/.windsurfrules/.cursor/rules)
- Use Flowbite component library as base, avoid indigo/blue unless specified
- Responsive designs mandatory, Google Fonts only (prefer DM Sans, Space Mono, Geist)
- Neo-brutalism or modern dark themes preferred, save designs to `.superdesign/design_iterations/`
- Use Lucide icons, TailwindCSS via CDN, include !important for overrides
