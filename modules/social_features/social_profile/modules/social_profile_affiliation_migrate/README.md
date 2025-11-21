# Social Profile Affiliation Migration

This module migrates legacy profile fields (`field_profile_function` and `field_profile_organization`) to the new affiliation paragraph structure.

## Purpose

When the affiliation feature was introduced, profile data needed to be migrated from the old field structure to the new paragraph-based structure. This module automates that migration process.

## Usage

### Installation

Simply enable the module:

```bash
drush en social_profile_affiliation_migrate
```

The migration will run automatically during module installation using Drupal's Batch API.

### Monitoring Progress

- The batch process will display progress in the UI if installed via the admin interface
- When installed via Drush, check the logs for migration results

### Uninstallation

The module automatically uninstalls itself after the migration completes successfully. If for any reason the automatic uninstallation fails, you can manually uninstall it:

```bash
drush pm:uninstall social_profile_affiliation_migrate
```

## What It Does

The migration:

1. Finds all profiles with `field_profile_function` or `field_profile_organization` values
2. Creates new affiliation paragraphs with the data from these fields
3. Enables the affiliation feature for profiles with migrated data
4. Processes profiles in batches of 25 to prevent memory issues
5. Provides detailed statistics on processed, updated, and skipped profiles

## Technical Details

- **Batch processing**: Uses Drupal's Batch API for reliable processing of large datasets
- **Batch size**: 25 profiles per batch (configurable via `entity_update_batch_size` setting)
- **Safe execution**: Only creates paragraphs if at least one field has a value
- **Progress tracking**: Reports total, processed, updated, and skipped counts

## Notes

- This is a one-time migration module
- The module can be safely uninstalled after the migration is complete
- The migration is idempotent - running it multiple times won't create duplicate affiliations if the original field values are compared

