# Property Management System

This Laravel application now includes a comprehensive property management system with CSV upload functionality for both Admin and OPS users.

## Features

### 🏢 Property Management
- **Property CRUD Operations**: Create, Read, Update, Delete properties
- **Property Information**: Address, Owner Name, Owner Address, Description
- **Search Functionality**: Search properties by address or owner name
- **Pagination**: Efficient handling of large property datasets

### 📊 CSV Upload System
- **Bulk Import**: Upload multiple properties via CSV files
- **Data Validation**: Row-by-row validation with detailed error reporting
- **Dry Run Mode**: Test imports without saving data
- **Template Download**: Pre-formatted CSV template with sample data
- **Update Logic**: Updates existing properties or creates new ones based on address matching

### 👥 Role-Based Access
- **Admin Users**: Full CRUD + CSV upload + delete permissions
- **OPS Users**: Create, read, update + CSV upload (no delete permissions)
- **Secure Routes**: Authentication middleware protection

## Navigation

### Admin Dashboard
- **URL**: `http://127.0.0.1:8001/admin/dashboard`
- **Properties Button**: Direct access to property management
- **Navigation Tab**: "Properties" tab in main navigation

### OPS Dashboard
- **URL**: `http://127.0.0.1:8001/ops/dashboard`
- **Properties Button**: Direct access to property management
- **Navigation Tab**: "Properties" tab in main navigation

## Property Management URLs

### Admin Access
- **Property List**: `http://127.0.0.1:8001/admin/properties`
- **Add Property**: `http://127.0.0.1:8001/admin/properties/create`
- **CSV Upload**: `http://127.0.0.1:8001/admin/properties-upload`
- **CSV Template**: `http://127.0.0.1:8001/admin/properties-template`

### OPS Access
- **Property List**: `http://127.0.0.1:8001/ops/properties`
- **Add Property**: `http://127.0.0.1:8001/ops/properties/create`
- **CSV Upload**: `http://127.0.0.1:8001/ops/properties-upload`
- **CSV Template**: `http://127.0.0.1:8001/ops/properties-template`

## CSV Format

### Required Columns
- `property_address` (string, max 255 characters)

### Optional Columns
- `owner_name` (string, max 255 characters)
- `owner_address` (text, max 1000 characters)

### Sample CSV
```csv
property_address,owner_name,owner_address
"123 Main Street, Downtown, NY 10001","John Smith","456 Oak Avenue, Uptown, NY 10002"
"789 Broadway Avenue, Midtown, NY 10003","Sarah Johnson","321 Pine Street, Brooklyn, NY 11201"
```

## Demo Data

The system includes 15 sample properties with realistic data:
- Various property types and locations
- Complete owner information
- Detailed property descriptions
- NYC-based addresses for consistency

## Database Structure

### Properties Table
```sql
- id (primary key)
- property_address (string, indexed)
- owner_name (nullable string)
- owner_address (nullable text)
- description (nullable text)
- created_at (timestamp)
- updated_at (timestamp)
```

## Technical Components

### Backend
- **Model**: `App\Models\Property`
- **Controllers**: `Admin\PropertyController`, `Ops\PropertyController`
- **Form Requests**: `StorePropertyRequest`, `UpdatePropertyRequest`, `ImportPropertiesRequest`
- **Service**: `PropertyCsvImportService`
- **DTO**: `PropertyImportResult`

### Frontend
- **Admin Views**: `resources/views/admin/properties/`
- **OPS Views**: `resources/views/ops/properties/`
- **Shared Components**: `resources/views/properties/_form.blade.php`

### Routes
- **Admin Routes**: `/admin/properties*`
- **OPS Routes**: `/ops/properties*`
- **Authentication**: Middleware protected

## Usage Instructions

1. **Login**: Access admin or ops dashboard
2. **Navigate**: Click "Properties" in navigation or dashboard button
3. **Add Property**: Use "Add Property" button or CSV upload
4. **CSV Upload**: Download template, fill data, upload with dry-run option
5. **Manage**: View, edit, or delete (admin only) properties
6. **Search**: Use search bar to find specific properties

## File Upload Limits
- **Maximum Size**: 10MB
- **Supported Formats**: CSV, TXT
- **Error Handling**: Comprehensive validation with row-specific error messages

## Security Features
- **Authentication**: Required for all property operations
- **Authorization**: Role-based access control
- **Validation**: Server-side validation for all inputs
- **CSRF Protection**: All forms protected against CSRF attacks

The property management system is now fully functional and ready for production use!