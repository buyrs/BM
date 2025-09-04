# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Development Commands

### Primary Development Workflow
- **Development Server**: `composer dev` - Starts Laravel server, queue listener, logging, and Vite dev server concurrently
- **Frontend Build**: `npm run build` - Vite production build for frontend assets
- **Frontend Dev Only**: `npm run dev` - Vite development server only

### Testing
- **PHP Tests**: `composer test` or `php artisan test` - Run PHPUnit test suite
- **Single PHP Test**: `php artisan test --filter=TestMethodName` - Run specific test method
- **JavaScript Tests**:
  - `npm run test` - Vitest single run
  - `npm run test:watch` - Watch mode for continuous testing
  - `npm run test:ui` - Interactive UI testing mode
  - `npm run test:unit` - Unit tests only
  - `npm run test:integration` - Integration tests only
  - `npm run test:comprehensive` - Complete test suite
  - `npm run test:coverage` - Test coverage report
  - `npm run test -- TestFileName` - Run specific JS test file

### Code Quality
- **PHP Formatting**: `vendor/bin/pint` - Laravel Pint code formatting (PSR-12)
- **Configuration Clear**: `php artisan config:clear` - Clear Laravel config cache

### Database Management
- **Migrations**: `php artisan migrate` - Run database migrations
- **Fresh Database**: `php artisan migrate:fresh --seed` - Reset database with seeders

## Architecture Overview

### Technology Stack
- **Backend**: Laravel 12 with Inertia.js bridge
- **Frontend**: Vue 3 SPA with TailwindCSS and Alpine.js enhancements
- **Database**: SQLite (development), PostgreSQL (production)
- **Authentication**: Laravel Breeze + Google OAuth (Socialite) + Spatie Permissions
- **UI Framework**: Flowbite components + Headless UI + TailwindCSS

### Core Application Structure
This is a **Bail Mobilité Management System** - a specialized French short-term rental property inspection and management platform.

#### Role-Based Architecture
The system implements four distinct user roles with specialized dashboards:
- **Super Admin**: System oversight and mission management
- **Ops Staff**: Bail mobilité lifecycle management and checker coordination  
- **Checkers**: Field agents conducting property inspections
- **Admin**: Analytics, contract templates, and system administration

#### Key Domain Models
- **Mission**: Property inspection assignments (entry/exit inspections)
- **BailMobilite**: Complete rental lifecycle management
- **Checklist**: Digital inspection forms with photo documentation
- **Signature**: Digital contract signing with tenant validation
- **IncidentReport**: Automated incident detection and corrective actions
- **ContractTemplate**: Versioned contract templates with placeholders

### Service Layer Pattern
The application uses a comprehensive service layer architecture:
- **MissionService**: Mission assignment and lifecycle management
- **ChecklistService**: Inspection form processing and validation
- **ContractService**: Digital contract generation and management
- **NotificationService**: Multi-channel notification system
- **CalendarService**: Mission scheduling with conflict detection
- **IncidentDetectionService**: Automated property condition analysis
- **SignatureService**: Digital signature validation and archival

### Frontend Architecture
- **Hybrid SPA**: Vue 3 with Inertia.js for seamless server-side integration
- **PWA Capabilities**: Offline functionality with service worker
- **Component Libraries**: 
  - Headless UI for accessible components
  - TailwindCSS for styling
  - Chart.js for analytics visualization
  - Vue Signature Pad for digital signatures
  - Tiptap for rich text editing
- **State Management**: Reactive data flow with Ziggy route integration

### Key Integrations
- **PDF Generation**: DomPDF for inspection reports and contracts
- **File Storage**: Laravel filesystem with secure document handling
- **Queue System**: Background processing for notifications and reports
- **Google OAuth**: Social authentication integration
- **Calendar Sync**: Mission scheduling with external calendar integration

### Database Design Patterns
- **Multi-tenant aware**: Agent-based data segregation
- **Audit logging**: Complete action history tracking
- **Encrypted storage**: Sensitive data protection for signatures and documents
- **Soft deletes**: Data retention for compliance requirements

## Design System Rules

### Component Standards
- **Primary Framework**: Flowbite component library (avoid indigo/blue unless specified)
- **Responsive Design**: Mandatory mobile-first approach
- **Typography**: Google Fonts only - prefer DM Sans, Space Mono, Geist families
- **Icons**: Lucide icons via CDN integration
- **CSS Framework**: TailwindCSS via CDN with !important overrides for Flowbite conflicts

### Theme Preferences
- **Neo-brutalism**: 90s web design aesthetic with sharp edges and high contrast
- **Modern Dark Mode**: Vercel/Linear inspired dark themes
- **Custom Properties**: CSS custom properties for consistent theming
- **Design Iterations**: Save all design work to `.superdesign/design_iterations/`

### Development Patterns
- **PHP**: Laravel conventions, PSR-12 compliance, snake_case for variables/DB
- **JavaScript**: Composition API, TypeScript where applicable, ES6 modules
- **Vue Components**: PascalCase naming, Composition API preferred
- **CSS**: TailwindCSS utilities, avoid custom CSS, use Flowbite components
- **File Naming**: camelCase (JS), snake_case (PHP/DB), PascalCase (Classes/Components)

## Critical Business Logic

### Mission Workflow
1. **Creation**: Super admin creates missions with property and schedule details
2. **Assignment**: Ops staff assigns missions to available checkers
3. **Execution**: Checkers complete digital inspections with photos and signatures
4. **Validation**: Ops staff validates completed inspections
5. **Incident Handling**: Automated detection and corrective action workflows

### Bail Mobilité Lifecycle
- Entry and exit inspection coordination
- Digital signature collection from tenants
- Automated incident detection during inspections
- PDF report generation and archival
- Calendar integration for scheduling coordination

### Security Considerations
- Role-based access control with Spatie Permissions
- Encrypted document storage for sensitive information
- Audit logging for all user actions
- Secure file upload handling with validation
- Digital signature integrity validation

## Testing Strategy

### PHP Testing
- **Feature Tests**: End-to-end workflow testing
- **Unit Tests**: Service layer and model testing
- **Browser Tests**: Laravel Dusk for complex user interactions

### JavaScript Testing
- **Component Tests**: Vue component isolation testing
- **Integration Tests**: Full user workflow simulation
- **E2E Tests**: Cross-browser compatibility testing
- **Performance Tests**: Frontend performance monitoring

The codebase emphasizes French rental market compliance while providing a comprehensive digital workflow for property inspection management.
