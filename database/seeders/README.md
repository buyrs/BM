# Database Seeders

## ComprehensiveDummyDataSeeder

This seeder creates realistic dummy data for all features of the Bail Mobilité application.

### What it creates:

1. **Users for all roles:**
   - 2 Admin users
   - 3 Ops users  
   - 6 Checker users
   - All with proper role assignments and permissions

2. **Contract Templates:**
   - Entry contract template (signed by admin)
   - Exit contract template (signed by admin)
   - Premium entry template (signed by admin)
   - Draft template (unsigned)

3. **Bail Mobilités in all status states:**
   - 25 bail mobilités with realistic dates and scheduling
   - Distributed across: assigned, in_progress, completed, incident
   - Realistic tenant information and addresses

4. **Missions with realistic scheduling:**
   - Entry and exit missions for each bail mobilité
   - Proper status progression based on bail mobilité status
   - Realistic scheduling with time slots

5. **Completed checklists with photos:**
   - Checklists for completed missions
   - Realistic room conditions and utility readings
   - Checklist items with photos
   - Validation comments and signatures

6. **Signatures and contracts:**
   - Entry signatures for in_progress and completed bail mobilités
   - Exit signatures for completed bail mobilités
   - Generated PDF paths for signed contracts

7. **Notifications:**
   - Exit reminder notifications
   - Mission assignment notifications
   - Checklist validation notifications
   - Calendar update notifications
   - Mix of sent and pending notifications

8. **Incident reports with corrective actions:**
   - Various incident types (missing checklist, incomplete data, etc.)
   - Different severity levels
   - Open, in progress, and resolved incidents
   - Corrective actions assigned to ops users

### Usage:

#### Using the seeder directly:
```bash
php artisan db:seed --class=ComprehensiveDummyDataSeeder
```

#### Using the custom command:
```bash
# Seed dummy data (adds to existing data)
php artisan seed:dummy-data

# Clear existing data and seed fresh
php artisan seed:dummy-data --fresh
```

#### Using with database refresh:
```bash
# Complete fresh start
php artisan migrate:fresh --seed
```

### Test Accounts:

After seeding, you can login with these accounts:

**Admin:**
- admin@bailmobilite.com / password
- admin2@bailmobilite.com / password

**Ops:**
- ops@bailmobilite.com / password
- ops2@bailmobilite.com / password
- ops3@bailmobilite.com / password

**Checker:**
- checker@bailmobilite.com / password
- checker2@bailmobilite.com / password
- checker3@bailmobilite.com / password
- checker4@bailmobilite.com / password
- checker5@bailmobilite.com / password
- checker6@bailmobilite.com / password

### Data Verification:

After seeding, you can verify the data was created correctly:

```bash
php artisan tinker --execute="
echo 'Users: ' . App\Models\User::count() . PHP_EOL;
echo 'Bail Mobilités: ' . App\Models\BailMobilite::count() . PHP_EOL;
echo 'Missions: ' . App\Models\Mission::count() . PHP_EOL;
echo 'Checklists: ' . App\Models\Checklist::count() . PHP_EOL;
echo 'Signatures: ' . App\Models\BailMobiliteSignature::count() . PHP_EOL;
echo 'Notifications: ' . App\Models\Notification::count() . PHP_EOL;
echo 'Incidents: ' . App\Models\IncidentReport::count() . PHP_EOL;
"
```

### Features Covered:

This seeder provides comprehensive test data for:

- ✅ User management and role-based access
- ✅ Bail mobilité lifecycle management
- ✅ Mission scheduling and assignment
- ✅ Checklist completion with photos
- ✅ Digital signature workflow
- ✅ Contract template management
- ✅ Notification system
- ✅ Incident detection and resolution
- ✅ Calendar integration
- ✅ Dashboard analytics
- ✅ Mobile responsiveness testing
- ✅ Error handling scenarios

### Notes:

- All dates are generated relative to the current date for realistic testing
- Signatures are generated as dummy hashes (not actual signature images)
- Photo paths are dummy paths (actual files are not created)
- All data is designed to be realistic and representative of actual usage
- The seeder is idempotent - running it multiple times will create additional data