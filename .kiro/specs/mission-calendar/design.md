# Design Document

## Overview

The Mission Calendar feature provides a comprehensive calendar interface for Ops and Admin users to visualize, manage, and create Bail Mobilité (BM) missions. The calendar integrates with the existing Laravel/Inertia.js/Vue.js architecture and leverages the current BailMobilite and Mission models to provide a unified view of all mission activities.

The design follows the existing application patterns with a Vue.js frontend component, Laravel backend API endpoints, and seamless integration with the current Ops dashboard and role-based access control system.

## Architecture

### Frontend Architecture

The calendar will be implemented as a Vue.js component with the following structure:

- **Main Calendar Component**: `resources/js/Pages/Ops/Calendar.vue`
- **Calendar Grid Component**: `resources/js/Components/Calendar/CalendarGrid.vue`
- **Mission Event Component**: `resources/js/Components/Calendar/MissionEvent.vue`
- **Mission Details Modal**: `resources/js/Components/Calendar/MissionDetailsModal.vue`
- **Mission Creation Modal**: `resources/js/Components/Calendar/CreateMissionModal.vue`
- **Calendar Navigation**: `resources/js/Components/Calendar/CalendarNavigation.vue`

### Backend Architecture

The backend will extend the existing controller structure:

- **Calendar Controller**: `app/Http/Controllers/CalendarController.php`
- **Calendar Service**: `app/Services/CalendarService.php`
- **Mission Calendar Resource**: `app/Http/Resources/MissionCalendarResource.php`

### Integration Points

- Extends existing `DashboardOps.vue` layout
- Integrates with current role-based middleware (`CheckOpsAccess`)
- Utilizes existing `BailMobilite` and `Mission` models
- Leverages current notification system for calendar events

## Components and Interfaces

### 1. Calendar Controller (`app/Http/Controllers/CalendarController.php`)

```php
class CalendarController extends Controller
{
    public function index(Request $request): Response
    public function getMissions(Request $request): JsonResponse
    public function createMission(Request $request): JsonResponse
    public function updateMission(Request $request, Mission $mission): JsonResponse
    public function getMissionDetails(Mission $mission): JsonResponse
}
```

**Key Methods:**
- `index()`: Renders the calendar page with initial data
- `getMissions()`: Returns missions for a specific date range with filtering
- `createMission()`: Creates a new BM mission from calendar
- `updateMission()`: Updates mission details from calendar
- `getMissionDetails()`: Returns detailed mission information for modal display

### 2. Calendar Service (`app/Services/CalendarService.php`)

```php
class CalendarService
{
    public function getMissionsForDateRange(Carbon $startDate, Carbon $endDate, array $filters = []): Collection
    public function formatMissionsForCalendar(Collection $missions): array
    public function createBailMobiliteMission(array $data): BailMobilite
    public function getAvailableTimeSlots(Carbon $date): array
    public function detectSchedulingConflicts(Carbon $date, string $time, ?int $checkerId = null): array
}
```

**Key Responsibilities:**
- Mission data retrieval and formatting for calendar display
- BM mission creation with automatic entry/exit mission generation
- Time slot availability checking
- Scheduling conflict detection

### 3. Main Calendar Component (`resources/js/Pages/Ops/Calendar.vue`)

```vue
<template>
  <DashboardOps>
    <CalendarNavigation 
      :current-date="currentDate"
      :view-mode="viewMode"
      @date-change="handleDateChange"
      @view-change="handleViewChange"
    />
    
    <CalendarFilters
      :filters="filters"
      :checkers="checkers"
      @filter-change="handleFilterChange"
    />
    
    <CalendarGrid
      :missions="missions"
      :current-date="currentDate"
      :view-mode="viewMode"
      @mission-click="showMissionDetails"
      @date-click="showCreateMission"
      @mission-drag="handleMissionDrag"
    />
    
    <MissionDetailsModal
      :mission="selectedMission"
      :show="showDetailsModal"
      @close="closeDetailsModal"
      @update="handleMissionUpdate"
    />
    
    <CreateMissionModal
      :show="showCreateModal"
      :selected-date="selectedDate"
      @close="closeCreateModal"
      @create="handleMissionCreate"
    />
  </DashboardOps>
</template>
```

### 4. Calendar Grid Component (`resources/js/Components/Calendar/CalendarGrid.vue`)

**Features:**
- Monthly, weekly, and daily view modes
- Responsive grid layout
- Mission event rendering with color coding
- Drag-and-drop support for mission rescheduling
- Time slot visualization
- Multi-mission day handling

### 5. Mission Event Component (`resources/js/Components/Calendar/MissionEvent.vue`)

```vue
<template>
  <div 
    :class="eventClasses"
    @click="$emit('click', mission)"
    @mouseenter="showTooltip = true"
    @mouseleave="showTooltip = false"
  >
    <div class="mission-content">
      <span class="mission-type">{{ missionTypeLabel }}</span>
      <span class="mission-tenant">{{ mission.tenant_name }}</span>
      <span class="mission-time">{{ formattedTime }}</span>
    </div>
    
    <div class="mission-status-indicator" :class="statusClass"></div>
    
    <Tooltip v-if="showTooltip" :mission="mission" />
  </div>
</template>
```

**Visual Design:**
- Color-coded by mission type (entry: blue, exit: orange)
- Status indicators (assigned, in_progress, completed, incident)
- Hover tooltips with quick mission info
- Expandable on click for detailed view

## Data Models

### Calendar Mission Data Structure

```typescript
interface CalendarMission {
  id: number;
  type: 'entry' | 'exit';
  scheduled_at: string;
  scheduled_time?: string;
  status: 'unassigned' | 'assigned' | 'in_progress' | 'completed' | 'cancelled';
  tenant_name: string;
  address: string;
  agent?: {
    id: number;
    name: string;
  };
  bail_mobilite: {
    id: number;
    status: string;
    start_date: string;
    end_date: string;
    duration_days: number;
  };
  conflicts?: string[];
  can_edit: boolean;
  can_assign: boolean;
}
```

### Calendar View State

```typescript
interface CalendarState {
  currentDate: Date;
  viewMode: 'month' | 'week' | 'day';
  missions: CalendarMission[];
  filters: {
    status: string[];
    checker_id?: number;
    mission_type: string[];
    search: string;
  };
  selectedMission?: CalendarMission;
  selectedDate?: Date;
  loading: boolean;
}
```

### Mission Creation Payload

```typescript
interface CreateMissionPayload {
  start_date: string;
  end_date: string;
  address: string;
  tenant_name: string;
  tenant_phone?: string;
  tenant_email?: string;
  notes?: string;
  entry_scheduled_time?: string;
  exit_scheduled_time?: string;
  entry_checker_id?: number;
  exit_checker_id?: number;
}
```

## Error Handling

### Frontend Error Handling

1. **Network Errors**: Display toast notifications for API failures
2. **Validation Errors**: Show inline validation messages in forms
3. **Loading States**: Skeleton loaders during data fetching
4. **Empty States**: Friendly messages when no missions exist
5. **Permission Errors**: Redirect to appropriate error pages

### Backend Error Handling

1. **Validation Errors**: Return structured validation error responses
2. **Authorization Errors**: HTTP 403 responses with clear messages
3. **Database Errors**: Log errors and return generic user-friendly messages
4. **Scheduling Conflicts**: Return conflict details with suggested alternatives

### Error Response Format

```json
{
  "success": false,
  "message": "User-friendly error message",
  "errors": {
    "field_name": ["Specific validation error"]
  },
  "code": "ERROR_CODE",
  "suggestions": ["Alternative action 1", "Alternative action 2"]
}
```

## Testing Strategy

### Unit Tests

1. **CalendarService Tests**
   - Mission data formatting
   - Date range calculations
   - Conflict detection logic
   - BM mission creation

2. **CalendarController Tests**
   - API endpoint responses
   - Authorization checks
   - Input validation
   - Error handling

3. **Vue Component Tests**
   - Calendar grid rendering
   - Mission event display
   - User interactions
   - State management

### Integration Tests

1. **Calendar API Integration**
   - Full mission CRUD operations
   - Filter and search functionality
   - Date navigation
   - Mission assignment workflow

2. **Mission Creation Flow**
   - End-to-end BM mission creation
   - Automatic entry/exit mission generation
   - Checker assignment
   - Notification triggering

### Feature Tests

1. **Calendar Navigation**
   - Month/week/day view switching
   - Date range navigation
   - URL state persistence

2. **Mission Management**
   - Mission creation from calendar
   - Mission details expansion
   - Mission editing and updates
   - Mission status transitions

3. **Filtering and Search**
   - Status-based filtering
   - Checker-based filtering
   - Text search functionality
   - Filter combination logic

### Performance Tests

1. **Large Dataset Handling**
   - Calendar performance with 1000+ missions
   - Pagination and lazy loading
   - Memory usage optimization

2. **Real-time Updates**
   - Mission status change propagation
   - Calendar refresh performance
   - Concurrent user handling

## Implementation Phases

### Phase 1: Core Calendar Infrastructure
- Calendar controller and service setup
- Basic calendar grid component
- Mission data API endpoints
- Monthly view implementation

### Phase 2: Mission Display and Interaction
- Mission event components
- Mission details modal
- Click-to-expand functionality
- Basic filtering

### Phase 3: Mission Creation
- Create mission modal
- BM mission creation workflow
- Form validation and error handling
- Success notifications

### Phase 4: Advanced Features
- Weekly and daily views
- Advanced filtering and search
- Drag-and-drop rescheduling
- Conflict detection

### Phase 5: Performance and Polish
- Performance optimization
- Responsive design refinement
- Accessibility improvements
- Comprehensive testing

## Security Considerations

1. **Role-Based Access Control**
   - Ops and Admin role verification
   - Mission visibility based on user permissions
   - Action authorization (create, edit, assign)

2. **Data Validation**
   - Server-side input validation
   - SQL injection prevention
   - XSS protection in calendar display

3. **API Security**
   - CSRF protection for state-changing operations
   - Rate limiting for calendar API endpoints
   - Secure mission data transmission

## Performance Considerations

1. **Data Loading Strategy**
   - Lazy loading for large date ranges
   - Efficient database queries with proper indexing
   - Caching for frequently accessed data

2. **Frontend Optimization**
   - Virtual scrolling for large mission lists
   - Debounced search and filtering
   - Optimized re-rendering with Vue.js reactivity

3. **Database Optimization**
   - Indexed queries on date ranges
   - Efficient joins between missions and bail mobilités
   - Query result caching for common filters

## Accessibility Features

1. **Keyboard Navigation**
   - Tab navigation through calendar grid
   - Arrow key navigation between dates
   - Enter/Space for mission selection

2. **Screen Reader Support**
   - ARIA labels for calendar elements
   - Semantic HTML structure
   - Mission status announcements

3. **Visual Accessibility**
   - High contrast color schemes
   - Scalable text and UI elements
   - Color-blind friendly mission indicators