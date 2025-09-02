# Bail Mobilité Management System

## Purpose of the Application

This Laravel + Vue.js PWA is designed to manage "Bail Mobilité" (mobility leases) - a specific type of short-term rental arrangement in France. The system handles the complete lifecycle of property inspections and lease management for mobility rentals.

## Core Functionality

### Mission Management
- Scheduling and tracking property inspection missions (entry/exit inspections)
- Assignment of field agents (checkers) to specific properties
- Real-time status tracking and updates
- Calendar integration with conflict detection

### Checklist System
- Digital property condition reports with standardized forms
- Photo documentation for property conditions
- Electronic signatures from tenants and agents
- Automated PDF generation of inspection reports

### User Role Management
Different dashboards and permissions for:
- **Super Admins**: Full system oversight and mission management
- **Ops Staff**: Bail mobilité lifecycle management and checker coordination
- **Checkers (Field Agents)**: Property inspections and checklist completion
- **Admins**: Analytics, contract templates, and system administration

### Bail Mobilité Lifecycle
- Complete management from lease creation to completion
- Entry and exit mission scheduling
- Status tracking (assigned → in_progress → completed)
- Incident detection and management
- Automated notifications and reminders

### Digital Signatures & Contracts
- Contract template management with versioning
- Electronic signature workflows
- Digital contract signing with validation
- Signature archiving and retrieval

### Incident Management
- Automated incident detection during inspections
- Corrective action tracking
- Status management and resolution workflows
- Incident statistics and reporting

## Key Features

- **Multi-role authentication system** with role-based permissions
- **Property inspection workflows** with comprehensive checklists
- **Contract management** with digital templates and signatures
- **Notification system** for automated alerts and reminders
- **PDF generation** for reports and contracts
- **Google OAuth integration** for social login
- **PWA capabilities** for mobile app functionality
- **Real-time updates** with calendar synchronization
- **Analytics dashboard** with performance metrics
- **Incident tracking** with corrective action management

## Technology Stack

- **Backend**: Laravel 12 with Inertia.js
- **Frontend**: Vue.js 3 with Tailwind CSS and Alpine.js
- **Authentication**: Laravel Breeze + Socialite (Google OAuth)
- **Permissions**: Spatie Laravel Permission package
- **PDF Generation**: DomPDF (barryvdh/laravel-dompdf)
- **Charts**: Chart.js for analytics
- **Signatures**: Vue Signature Pad for electronic signatures
- **Routing**: Ziggy for Laravel route integration in Vue
- **Date Handling**: date-fns for JavaScript date operations

## Target Market

This application is specifically tailored for the French rental market's "bail mobilité" regulations, providing a comprehensive digital workflow management solution for:
- Property management companies
- Real estate agencies
- Landlords managing short-term mobility rentals
- Field inspection services

The system ensures compliance with French rental regulations while streamlining the inspection and documentation process through digital transformation.