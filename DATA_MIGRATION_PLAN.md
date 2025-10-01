# Data Migration Guide

This document outlines the process for migrating data from existing systems to the Bail Mobilite Platform.

## Migration Overview

The data migration process involves transferring existing data from legacy systems to the new platform while ensuring data integrity and minimal downtime.

## Data Migration Checklist

### 1. Pre-Migration Phase
- [ ] Audit existing data structure
- [ ] Identify data transformation requirements
- [ ] Create backup of source data
- [ ] Prepare staging environment
- [ ] Test migration scripts on sample data

### 2. Migration Preparation
- [ ] Create migration plan with timeline
- [ ] Prepare rollback procedures
- [ ] Set up target environment
- [ ] Configure data validation rules

### 3. Migration Execution
- [ ] Execute migration scripts
- [ ] Validate migrated data
- [ ] Update references and indexes
- [ ] Run data integrity checks

### 4. Post-Migration
- [ ] Verify application functionality
- [ ] Run comparison reports
- [ ] Update documentation
- [ ] Destroy temporary migration data

## Data Mapping

### Users Table Migration
| Legacy Field | New Field | Transformation |
|--------------|-----------|----------------|
| user_id | id | Auto-increment |
| username | email | Use email as username |
| first_name, last_name | name | Concatenate to single field |
| user_type | role | Map: ADMIN->admin, STAFF->ops, CHECKER->checker |
| created | created_at | Format conversion |
| updated | updated_at | Format conversion |

### Missions Table Migration
| Legacy Field | New Field | Transformation |
|--------------|-----------|----------------|
| mission_id | id | Auto-increment |
| mission_title | title | Direct mapping |
| mission_desc | description | Direct mapping |
| property_addr | property_address | Direct mapping |
| checkin_dt | checkin_date | Format conversion |
| checkout_dt | checkout_date | Format conversion |
| assigned_to | checker_id | Foreign key lookup |
| created_by | admin_id | Foreign key lookup |
| status | status | Map: ACTIVE->approved, PENDING->pending |

### Checklist Items Migration
| Legacy Field | New Field | Transformation |
|--------------|-----------|----------------|
| item_id | id | Auto-increment |
| checklist_id | checklist_id | Foreign key lookup |
| item_name | amenity_id | Migrate to amenities table |
| condition | state | Map: GOOD->good, BAD->bad |
| notes | comment | Direct mapping |
| photo_path | photo_path | Path adjustment |

## Migration Scripts

```sql
-- Example migration script for users
INSERT INTO users (name, email, password, role, created_at, updated_at)
SELECT 
    CONCAT(first_name, ' ', last_name) as name,
    email,
    BCrypt(HASH(password, 'SHA256')) as password,
    CASE 
        WHEN user_type = 'ADMIN' THEN 'admin'
        WHEN user_type = 'STAFF' THEN 'ops'
        WHEN user_type = 'CHECKER' THEN 'checker'
        ELSE 'checker'
    END as role,
    created,
    updated
FROM legacy_users
WHERE active = 1;
```

## Data Validation Queries

```sql
-- Validate user count
SELECT COUNT(*) as legacy_count FROM legacy_users WHERE active = 1;
SELECT COUNT(*) as new_count FROM users;

-- Validate mission count
SELECT COUNT(*) as legacy_count FROM legacy_missions;
SELECT COUNT(*) as new_count FROM missions;

-- Validate checklist completeness
SELECT 
    m.id,
    COUNT(ci.id) as checklist_items_count
FROM missions m
LEFT JOIN checklists cl ON m.id = cl.mission_id
LEFT JOIN checklist_items ci ON cl.id = ci.checklist_id
WHERE m.status = 'completed'
GROUP BY m.id
HAVING checklist_items_count = 0;
```

## Rollback Procedures

In case of migration failure:

1. **Immediate Actions:**
   - Stop all write operations to the new system
   - Document the point of failure
   - Preserve the partially migrated data

2. **Rollback Steps:**
   ```bash
   # Restore from backup
   php artisan migrate:rollback --step=1
   
   # Or truncate and restore from SQL dump
   mysql -u user -p database < backup.sql
   ```

3. **Verification:**
   - Confirm all data is back to original state
   - Run validation queries on restored data
   - Verify application functionality

## Migration Timeline

| Phase | Duration | Description |
|-------|----------|-------------|
| Pre-Migration Setup | 2 days | Environment preparation |
| Staging Migration | 3 days | Dry run on staging |
| Production Migration | 6 hours | Actual migration window |
| Validation | 1 day | Data verification |

## Success Metrics

- [ ] All legacy data successfully migrated
- [ ] No data loss during migration
- [ ] All foreign key relationships maintained
- [ ] Application functions normally with new data
- [ ] Performance meets acceptable levels
- [ ] All validation queries pass

## Post-Migration Tasks

- [ ] Update application configuration
- [ ] Run final data validation
- [ ] Update user access permissions
- [ ] Notify stakeholders of completion
- [ ] Document lessons learned
- [ ] Archive migration artifacts

## Contact Information

For migration-related issues, contact:
- System Administrator: admin@company.com
- Database Administrator: dba@company.com
- Project Manager: pm@company.com