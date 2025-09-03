### Application Overview

This is a comprehensive web application designed to manage operations related to the "Bail Mobilité," a specific type of furnished residential lease in France. The system appears to serve as a central hub for managing properties, missions (tasks), agents, and the entire lifecycle of a rental contract, from generation and signing to final checkout and incident reporting. It seems geared towards an operational team ("Ops") and field agents.

### Core Features

*   **Mission Management:** Assigning and tracking tasks (missions) for agents, likely related to property inspections, check-ins, and check-outs. Includes reminders and notifications.
*   **Contract Lifecycle Management:**
    *   **Generation:** Creates "Bail Mobilité" contracts from templates, likely in PDF format using `laravel-dompdf`.
    *   **E-Signatures:** Facilitates electronic signing of contracts, using a dedicated signature pad component on the frontend.
*   **Checklist & Inspections:** A detailed checklist system for property inspections (probably for entry and exit states), including photo uploads and comments from both tenants and operational staff. Features a validation/approval workflow.
*   **Incident Reporting:** A system for logging, tracking, and managing incidents that occur at properties, including assigning corrective actions.
*   **User & Role Management:** A robust Role-Based Access Control (RBAC) system to manage permissions for different user types (e.g., admins, operational staff, agents).
*   **Notifications:** A comprehensive notification system for events like mission assignments, reminders, and incident alerts.
*   **Auditing & Security:** Keeps detailed audit logs of user actions and emphasizes security with features like data encryption for sensitive model attributes.
*   **Dashboard & Reporting:** The inclusion of Chart.js suggests the presence of a dashboard with analytics and reports for visualizing operational data.

### Technology Stack

The application is built on a modern, full-stack architecture using PHP for the backend and Vue.js for the frontend.

*   **Backend:**
    *   **Framework:** Laravel 12 (PHP)
    *   **Key Libraries:**
        *   `inertiajs/inertia-laravel`: Connects the Laravel backend to the Vue.js frontend, allowing for a modern single-page application (SPA) experience without building a separate API.
        *   `spatie/laravel-permission`: For Role-Based Access Control (RBAC).
        *   `barryvdh/laravel-dompdf`: For generating PDF documents (likely contracts).
    *   **Database:** Likely uses a relational database like MySQL or PostgreSQL, with SQLite for development (as indicated by `database.sqlite`).
    *   **Testing:** PHPUnit.

*   **Frontend:**
    *   **Framework:** Vue.js 3
    *   **Key Libraries:**
        *   `@inertiajs/vue3`: The frontend adapter for the Inertia.js architecture.
        *   `@headlessui/vue`: For creating accessible UI components.
        *   `vue-signature-pad`: For capturing electronic signatures.
        *   `chart.js`: For creating charts and visualizations.
        *   `tiptap/vue-3`: For a rich text editor.
    *   **Styling:** Tailwind CSS.
    *   **Build Tool:** Vite.
    *   **Testing:** Vitest.

*   **Development & Tooling:**
    *   `concurrently`: Used to run multiple development processes (Laravel server, queue worker, Vite) at the same time for a streamlined development experience.
    *   `laravel/pail`: For real-time log monitoring.
