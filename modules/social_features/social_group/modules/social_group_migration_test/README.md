# Group Migration Test

The attached module provides update hooks that help test the group migration. Once installed it
will insert an update hook before the group migration from `social_group` which creates a bunch
of groups with members and content. It will also add an update hook that verifies that the
groups have been successfully changed to flexible groups and that their content and fields have
been migrated too.

## Usage
1. Create or load a database dump of pre-update Open Social (e.g. from the `main` branch)
2. Enable the test module: `drush en -y social_group_migration_test`
3. Run database updates: `drush updb -y`

This will create test content, perform the migration and then verify the test content was
successfully migrated. You should see output similar to the example provided below (some lines
omitted for brevity).

```
 [success] Successfully enabled: social_group_migration_test
 --------------- ----------- --------------- ---------------------------------
  Module          Update ID   Type            Description
 --------------- ----------- --------------- ---------------------------------
  social_group    13000       hook_update_n   13000 - Update old group types
                                              to flexible groups.
  social_group    13001       hook_update_n   13001 - Uninstall old group
                                              types.
  social_group    13002       hook_update_n   13002 - Clean up any group
                                              migration opt-out that might be
                                              enabled.
  social_group_   13001       hook_update_n   13001 - Create a load of groups
  migration_tes                               using the old group types.
  t
  social_group_   13002       hook_update_n   13002 - Verify the results of
  migration_tes                               our update.
  t
 --------------- ----------- --------------- ---------------------------------


 // Do you wish to run the specified pending updates?: yes.

>  [notice] Update started: social_group_migration_test_update_13001
>  [notice] Processed 50 out of 8750 tasks (1%). Ran for 0:00:01.504 out of expected 0:04:23.26.
>  [notice] Processed 100 out of 8750 tasks (1%). Ran for 0:00:02.523 out of expected 0:03:40.837.
>  [notice] Processed 150 out of 8750 tasks (2%). Ran for 0:00:03.613 out of expected 0:03:30.775.
[......]
>  [notice] Processed 8620 out of 8750 tasks (99%). Ran for 0:04:29.767 out of expected 0:04:33.835.
>  [notice] Processed 8900 out of 8750 tasks (102%). Ran for 0:04:37.226 out of expected 0:04:32.554.
>  [notice] Update completed: social_group_migration_test_update_13001
>  [notice] Update started: social_group_update_13000
>  [notice] Update completed: social_group_update_13000
>  [notice] Update started: social_group_update_13001
>  [error]  The open_group bundle (entity type: group) was deleted. As a result, the field_activity_entity dynamic entity reference field (entity_type: activity, bundle: activity) no longer has any valid bundle it can reference. The field is not working correctly anymore and has to be adjusted.
>  [notice] Update completed: social_group_update_13001
>  [notice] Update started: social_group_update_13002
>  [notice] Update completed: social_group_update_13002
>  [notice] Update started: social_group_migration_test_update_13002
>  [notice] Verified 50 out of 150 groups (33%). Ran for 0:00:01.207 out of expected 0:00:03.621.
>  [notice] Verified 100 out of 150 groups (67%). Ran for 0:00:01.428 out of expected 0:00:02.143.
>  [notice] Verified 150 out of 150 groups (100%). Ran for 0:00:01.628 out of expected 0:00:01.628.
>  [notice] Update completed: social_group_migration_test_update_13002
 [success] Finished performing updates.
```

## Configuring the size of the test
We want to make sure we don't hit any database query limits when migrating our largest platforms
which is why the size of the created test data can be configured with a few constants at the top
of the `social_group_migration_test.install` file:

```php
define('GROUP_MIGRATE_TEST_NUMBER_OF_USERS', 500);
define('GROUP_MIGRATE_TEST_GROUPS_PER_TYPE', 50);
define('GROUP_MIGRATE_TEST_MEMBERS_PER_GROUP', 50);
define('GROUP_MIGRATE_TEST_TOPICS_PER_GROUP', 5);
```

The defaults shown above are tuned to represent an average Open Social platform and will
generate the content in about 5-10 minutes (verification takes seconds).

## Extending the test
This module can be extended by creating more content in`social_group_migration_test_update_13001`
and verifying the results in `social_group_migration_test_update_13002`.


