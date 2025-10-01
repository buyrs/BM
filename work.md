# Bail Mobilité Application Rebuild: Work Plan

This document outlines the step-by-step tasks to rebuild the Bail Mobilité management application.

## Phase 1: Project Scaffolding & Initial Setup

-   [x] **Task 1.1:** Clear the existing project directory.
-   [x] **Task 1.2:** Initialize a new Laravel 11 project.
-   [x] **Task 1.3:** Configure the `.env` file for local development.
-   [x] **Task 1.4:** Set up the database (SQLite).
-   [x] **Task 1.5:** Install and configure Vite, Tailwind CSS, and Alpine.js.
-   [x] **Task 1.6:** Install Flowbite and Tailwind Elements.
-   [x] **Task 1.7:** Create the main layout file.

## Phase 2: Authentication & Welcome Page

-   [x] **Task 2.1:** Create the non-scrollable welcome page with the title.
-   [x] **Task 2.2:** Add login links for "Admin", "Ops", and "Checker" on the welcome page.
-   [x] **Task 2.3:** Create the specific login routes: `admin/login`, `ops/login`, `checker/login`.
-   [x] **Task 2.4:** Create the login views for each role.
-   [x] **Task 2.5:** Implement the authentication logic for each role.
-   [x] **Task 2.6:** Create middleware to protect routes based on user roles.

## Phase 3: User Management

-   [x] **Task 3.1:** Create User models and migrations (for Admin, Ops, Checker).
-   [x] **Task 3.2:** Implement the UI for Admins to create and manage Ops users.
-   [x] **Task 3.3:** Implement the UI for Admins and Ops to create and manage Checker users.
-   [x] **Task 3.4:** Implement the backend logic for user creation and management.

## Phase 4: Mission & Lifecycle Management

-   [x] **Task 4.1:** Create the `Mission` model and migration with `checkin_date` and `checkout_date`.
-   [x] **Task 4.2:** A mission will have two associated checklists: one for check-in and one for check-out.
-   [x] **Task 4.3:** Implement the UI for Ops to create a "bail mobilité" mission.
-   [x] **Task 4.4:** Implement the UI for Admins to review and validate missions.
-   [x] **Task 4.5:** Implement the UI for Admins and Ops to manage all missions.
-   [x] **Task 4.6:** Implement the backend logic for mission creation, validation, and management.

## Phase 5: Amenity & Checklist Management

-   [x] **Task 5.1:** Create `AmenityType` model and migration (e.g., 'entrance', 'living room'). Admins will manage these.
-   [x] **Task 5.1.1:** Implement the UI for Admins to manage Amenity Types.
-   [x] **Task 5.2:** Create `Amenity` model and migration, which belongs to an `AmenityType` and a property/apartment.
-   [x] **Task 5.2.1:** Implement the UI for Admins to manage Amenities.
-   [x] **Task 5.3:** Create `Checklist` model and migration, associated with a `Mission` and a type (check-in/check-out).
-   [x] **Task 5.4:** Create `ChecklistItem` model and migration, linking a `Checklist` and an `Amenity`, with a state (`bad`, `average`, `good`, `excellent`, `need_a_fix`).
-   [x] **Task 5.5:** Implement the UI for Checkers to fill out the checklist, selecting the state for each amenity.
-   [x] **Task 5.6:** Implement photo upload functionality for checklist items.
-   [x] **Task 5.7:** Implement the signature pad for tenant signatures (for both check-in and check-out).
-   [x] **Task 5.8:** Implement the logic for Checkers to submit the completed checklist.
-   [x] **Task 5.9:** Implement the functionality for Admins to send checklists to guests via email.
-   [x] **Task 5.10:** Create a public, tokenized view for guests to see the checklist.
