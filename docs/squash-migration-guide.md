# Squash Migration Guide — Task 5.4 Sprint 5

## Objective
Consolidate early migrations into a single schema snapshot file to reduce migration directory size and improve database initialization speed.

## Status
- [x] Development migrations verified and tested
- [x] `migrate:fresh` verified working
- [ ] Production environment squash (to be performed on production)
- [ ] Staging environment squash (to be performed on staging)
- [ ] Schema dump creation (requires MySQL environment)

## What is Schema Squashing?

Schema squashing consolidates multiple migration files into a single schema definition file (e.g., `database/schema/mysql-schema.sql`). This:
- Reduces the number of migration files to maintain
- Speeds up fresh database initialization
- Preserves migration history in archive folder

## Prerequisites

### 1. All Environments Must Have Run Migrations
Before performing schema squash, ensure all environments have successfully run through migration `2026_05_*` onwards.

**Verification (Development):**
```bash
php artisan migrate:status
```

Expected output: All migrations from `0001_01_01_*` through `2026_08_11_*` should show "Ran" status.

**Result (Development):** ✅ VERIFIED
```
0001_01_01_000000_create_users_table                      [1] Ran
0001_01_01_000001_create_cache_table                      [1] Ran
0001_01_01_000002_create_jobs_table                       [1] Ran
2026_05_08_034234_create_modules_table                    [1] Ran
2026_05_08_034235_create_exam_sessions_table              [1] Ran
2026_05_08_034236_create_participants_table               [1] Ran
2026_05_08_034237_create_questions_table                  [1] Ran
2026_05_08_034238_create_exam_attempts_table              [1] Ran
2026_05_08_034239_create_participant_answers_table        [1] Ran
2026_05_08_120000_create_exam_tokens_table                [1] Ran
2026_05_09_080000_add_access_control_to_exam_sessions_table [1] Ran
2026_05_09_130000_replace_ip_access_with_radius_access_on_exam_sessions_table [1] Ran
2026_05_09_150000_create_exam_location_settings_table     [1] Ran
2026_05_09_151000_move_session_radius_columns_to_global_setting [1] Ran
2026_05_11_123000_refactor_exam_tokens_to_session_scope   [1] Ran
2026_05_11_140000_add_participant_profile_fields_and_session_instructions [1] Ran
2026_05_11_150000_add_username_to_users_table             [1] Ran
2026_05_13_090000_add_role_to_users_table                 [1] Ran
2026_08_01_090000_fix_wrong_default_exam_location_setting [1] Ran
2026_08_11_000001_add_soft_deletes_to_exam_models         [1] Ran
2026_08_11_000001_alter_exam_relations_for_soft_delete_and_restrict_delete [1] Ran
```

### 2. Verify migrate:fresh Works

Test that `migrate:fresh` still works correctly by recreating schema from migrations:

**Development (Verified):**
```bash
php artisan migrate:fresh --seed
```

**Result:** ✅ SUCCESS
- All migrations ran successfully
- Database seeding completed
- `migrate:fresh` is fully functional

## Squash Migration Steps

### Step 1: Create Schema Dump (MySQL Environment)

On your MySQL-connected environment (production, staging, or development with MySQL):

```bash
php artisan schema:dump
```

This creates `database/schema/mysql-schema.sql` containing the full schema structure.

**Note:** This requires MySQL CLI tools to be available on the system.

### Step 2: Verify Schema Dump

Review the generated file:
- File location: `database/schema/mysql-schema.sql`
- Check that all table structures are present
- Verify all indexes are present
- Verify all foreign keys are present

### Step 3: Archive Old Migrations (Optional)

After verification, you can archive migrations to a history folder:

```bash
mkdir database/migrations/_archive
# Move old migrations to archive folder (keep recent ones)
mv database/migrations/2026_05_*.php database/migrations/_archive/
mv database/migrations/2026_08_01_*.php database/migrations/_archive/
```

Keep the most recent migrations (e.g., `2026_08_11_*`) for reference.

### Step 4: Test Fresh Installation

On a clean database:

```bash
php artisan migrate:fresh --seed
```

Verify that:
- Schema loads from dump file first (faster)
- No errors occur
- All tables are created correctly
- Seeding completes successfully

## Current Migration Timeline

- **2026-05-08 to 2026-05-13:** Core application structure
  - Tables: modules, exam_sessions, participants, questions, exam_attempts, participant_answers, exam_tokens
  - Modifications: access control, radius settings, participant profile fields
  
- **2026-08-01 to 2026-08-11:** Recent updates
  - Fix wrong default exam location
  - Add soft deletes to exam models
  - Alter relations for soft delete and restrict delete

## Total Migrations
21 migrations (3 Laravel default + 18 application-specific)

## Files Involved

**Migrations Directory:**
- Location: `database/migrations/`
- Current count: 21 files
- Estimated size: ~30-40 KB

**Schema Dump (After Squash):**
- Location: `database/schema/mysql-schema.sql`
- Will be created by `php artisan schema:dump`

**Git Status:**
- Squashed schema file should be committed
- Archived migrations can be kept for reference or moved to separate branch

## Environment Checklist

### Production
- [ ] Verify all migrations have run: `php artisan migrate:status`
- [ ] Run schema dump: `php artisan schema:dump`
- [ ] Review generated `database/schema/mysql-schema.sql`
- [ ] Test on staging first before production
- [ ] Commit schema dump file

### Staging
- [ ] Verify all migrations have run: `php artisan migrate:status`
- [ ] Run schema dump: `php artisan schema:dump`
- [ ] Run `php artisan migrate:fresh` on test database to verify
- [ ] Archive old migrations if desired
- [ ] Commit changes

### Development
- [x] Verified migrations: All 21 migrations ran successfully
- [x] Tested `migrate:fresh`: Works correctly
- [ ] Schema dump: Needs MySQL environment (sqlite3 CLI not available on Windows)
- [ ] Local testing: Complete and successful

## Next Steps

1. **For Production/Staging Team:**
   - When ready to perform squash, run: `php artisan schema:dump` on MySQL environment
   - Commit the generated `database/schema/mysql-schema.sql`
   - Optionally archive old migrations to `database/migrations/_archive/`

2. **For Development:**
   - When needed, test again with: `php artisan migrate:fresh --seed`
   - Ensure schema dump file is present before release

3. **For CI/CD:**
   - Update deployment scripts to include: `php artisan schema:dump` after all migrations run
   - Consider storing schema dumps in separate commits

## Testing Commands

```bash
# Check migration status
php artisan migrate:status

# Run all migrations
php artisan migrate

# Fresh database install
php artisan migrate:fresh

# Fresh with seeding
php artisan migrate:fresh --seed

# Create schema dump (MySQL only)
php artisan schema:dump

# Rollback migrations (if needed for testing)
php artisan migrate:rollback
php artisan migrate:rollback --step=5
```

## References

- [Laravel Migration Documentation](https://laravel.com/docs/migrations)
- [Laravel Schema Dump Documentation](https://laravel.com/docs/schema#dumping-schemas)
- [Migration Squashing Guide](https://laravel.com/docs/migrations#squashing-migrations)

## Notes

- Schema squashing does NOT delete old migration files
- It creates a snapshot of the schema state
- Fresh installations will use the schema dump file first, then apply new migrations
- This improves performance for new environments

---

**Last Updated:** 2026-08-18  
**Task:** Sprint 5 — Task 5.4 Squash Migration  
**Status:** ✅ Development Verified, ⏳ Production/Staging Pending
