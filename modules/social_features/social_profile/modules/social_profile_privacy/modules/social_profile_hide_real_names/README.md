# Social Profile Hide Real Names

This module allows the user to hide his real name and use a nickname to identify him.

In order to use this functionality, the SM must enable it (on the configuration form: _/admin/config/people/social-profile_)

After that, the user can enable the "Hide my real names on the platform" checkbox on the edit page (_/user/{uid}/edit_). 
If the user's `nickname` field is empty, he will have to fill it in.


This module uses `hook_user_name_saggestions` to determine what name should be displayed.

`ConfigEventsSubscriber` performs the reset data functionality and when "Allow hide real names" functionality is disabled (globally) it removes all settings from `user.data` (in case the user has used this feature) and mark all profiles for re-indexing for search api.

In order for SM to see the real names of other users (even if they have hidden them), he must have the permission `social profile privacy view hidden fields`
