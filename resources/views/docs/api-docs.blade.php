# Bail Mobilite Platform API Documentation

Version: v1  
Last Updated: {{ date('Y-m-d') }}

## Table of Contents

1. [Authentication](#authentication)
2. [Users](#users)
3. [Missions](#missions)
4. [Properties](#properties)
5. [Checklists](#checklists)
6. [Amenities](#amenities)
7. [Reports](#reports)
8. [Error Codes](#error-codes)
9. [Rate Limiting](#rate-limiting)

## Authentication

All API requests require authentication using Bearer tokens.

### Get Authentication Token

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

### Refresh Token

```http
POST /api/v1/auth/refresh
Authorization: Bearer {token}
```

**Response:**
```json
{
  "access_token": "new_token...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

### Logout

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

## Users

### Get Current User

```http
GET /api/v1/user
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "role": "checker",
  "created_at": "2023-01-01T12:00:00Z",
  "updated_at": "2023-01-01T12:00:00Z"
}
```

### Get All Users

```http
GET /api/v1/users
Authorization: Bearer {token}
```

**Query Parameters:**
- `role`: Filter by role (admin, ops, checker)
- `search`: Search by name or email
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "role": "checker",
      "created_at": "2023-01-01T12:00:00Z",
      "updated_at": "2023-01-01T12:00:00Z"
    }
  ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": "...",
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "...",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

### Create User

```http
POST /api/v1/users
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Smith",
  "email": "jane.smith@example.com",
  "password": "securepassword",
  "role": "checker"
}
```

### Update User

```http
PUT /api/v1/users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Smith Updated",
  "email": "jane.updated@example.com",
  "role": "ops"
}
```

### Delete User

```http
DELETE /api/v1/users/{id}
Authorization: Bearer {token}
```

## Missions

### Get All Missions

```http
GET /api/v1/missions
Authorization: Bearer {token}
```

**Query Parameters:**
- `status`: Filter by status (pending, approved, in_progress, completed, cancelled)
- `checker_id`: Filter by assigned checker
- `ops_id`: Filter by assigned ops staff
- `date_from`: Filter by date range start
- `date_to`: Filter by date range end
- `search`: Search by title or property address
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Property Inspection",
      "description": "Annual property inspection",
      "property_address": "123 Main St, City, State",
      "checkin_date": "2023-06-01",
      "checkout_date": "2023-06-30",
      "status": "approved",
      "checker_id": 2,
      "ops_id": 3,
      "admin_id": 1,
      "created_at": "2023-05-01T12:00:00Z",
      "updated_at": "2023-05-01T12:00:00Z"
    }
  ]
}
```

### Get Mission by ID

```http
GET /api/v1/missions/{id}
Authorization: Bearer {token}
```

### Create Mission

```http
POST /api/v1/missions
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "New Property Inspection",
  "description": "Inspect new rental property",
  "property_address": "456 Oak Ave, City, State",
  "checkin_date": "2023-07-01",
  "checkout_date": "2023-07-31",
  "checker_id": 2,
  "ops_id": 3
}
```

### Update Mission

```http
PUT /api/v1/missions/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Property Inspection",
  "status": "approved"
}
```

### Delete Mission

```http
DELETE /api/v1/missions/{id}
Authorization: Bearer {token}
```

### Approve Mission

```http
POST /api/v1/missions/{id}/approve
Authorization: Bearer {token}
```

### Cancel Mission

```http
POST /api/v1/missions/{id}/cancel
Authorization: Bearer {token}
```

## Properties

### Get All Properties

```http
GET /api/v1/properties
Authorization: Bearer {token}
```

**Query Parameters:**
- `type`: Filter by property type
- `search`: Search by address
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

### Get Property by ID

```http
GET /api/v1/properties/{id}
Authorization: Bearer {token}
```

### Create Property

```http
POST /api/v1/properties
Authorization: Bearer {token}
Content-Type: application/json

{
  "property_address": "789 Pine St, City, State",
  "property_type": "Apartment",
  "owner_name": "Property Owner",
  "owner_contact": "owner@example.com"
}
```

### Update Property

```http
PUT /api/v1/properties/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "property_address": "Updated Address",
  "property_type": "House"
}
```

### Delete Property

```http
DELETE /api/v1/properties/{id}
Authorization: Bearer {token}
```

## Checklists

### Get All Checklists

```http
GET /api/v1/checklists
Authorization: Bearer {token}
```

**Query Parameters:**
- `mission_id`: Filter by mission
- `type`: Filter by type (checkin, checkout)
- `status`: Filter by status (pending, completed)
- `page`: Page number for pagination
- `per_page`: Items per page (default: 15)

### Get Checklist by ID

```http
GET /api/v1/checklists/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "mission_id": 1,
  "type": "checkin",
  "status": "pending",
  "checklist_items": [
    {
      "id": 1,
      "amenity_id": 1,
      "state": "good",
      "comment": "In good condition",
      "photo_path": "/storage/photos/item1.jpg",
      "created_at": "2023-05-01T12:00:00Z"
    }
  ],
  "created_at": "2023-05-01T12:00:00Z",
  "updated_at": "2023-05-01T12:00:00Z"
}
```

### Update Checklist

```http
PUT /api/v1/checklists/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "items": {
    "1": {
      "state": "average",
      "comment": "Some wear and tear"
    }
  }
}
```

### Submit Checklist

```http
POST /api/v1/checklists/{id}/submit
Authorization: Bearer {token}
```

## Amenities

### Get All Amenities

```http
GET /api/v1/amenities
Authorization: Bearer {token}
```

### Get Amenity by ID

```http
GET /api/v1/amenities/{id}
Authorization: Bearer {token}
```

### Create Amenity

```http
POST /api/v1/amenities
Authorization: Bearer {token}
Content-Type: application/json

{
  "amenity_type_id": 1,
  "name": "Living Room Sofa",
  "description": "3-seater fabric sofa"
}
```

### Update Amenity

```http
PUT /api/v1/amenities/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Living Room Sofa",
  "description": "3-seater leather sofa"
}
```

### Delete Amenity

```http
DELETE /api/v1/amenities/{id}
Authorization: Bearer {token}
```

## Reports

### Generate Mission Report

```http
POST /api/v1/reports/missions
Authorization: Bearer {token}
Content-Type: application/json

{
  "date_from": "2023-01-01",
  "date_to": "2023-12-31",
  "format": "pdf" // pdf, excel, csv
}
```

**Response:**
```json
{
  "report_id": "abc123",
  "download_url": "/api/v1/reports/download/abc123",
  "expires_at": "2023-05-02T12:00:00Z"
}
```

### Download Report

```http
GET /api/v1/reports/download/{report_id}
Authorization: Bearer {token}
```

## Error Codes

All API responses follow standard HTTP status codes:

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 204 | No Content - Request successful, no content returned |
| 400 | Bad Request - Invalid request data |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation errors |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

**Error Response Format:**
```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Error message for field"]
  }
}
```

## Rate Limiting

API requests are rate limited to prevent abuse:

- **Anonymous requests**: 60 requests per hour
- **Authenticated requests**: 1,000 requests per hour
- **Admin requests**: 5,000 requests per hour

**Rate Limit Headers:**
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1620000000
```

When rate limit is exceeded, the API returns HTTP 429 Too Many Requests.

---

*This API documentation is subject to change. Always check the latest version at `/api/docs`.*