Profile
-------

The *Profile* module provides configurable user profiles.

## Requirements

* [Entity API](https://www.drupal.org/project/entity)

## Comparison to user account fields

Why use profiles instead of user account fields?

* With profile, user account settings and user profiles are conceptually different things, e.g. with the "Profile" module enabled users get two separate menu links "My account" and "My profile".
* Profile allows for creating multiple profile types, which may be assigned to roles via permissions (e.g. a general profile + a customer profile)
* Profile supports private profile fields, which are only shown to the user owning the profile and to administrators.

## Features

* Multiple profile types may be created via the UI (e.g. a general profile + a customer profile), whereas the module provides separated permissions for those.
* Optionally, profile forms are shown during user account registration.
* Fields may be configured to be private - thus visible only to the profile owner and administrators.
* Profile types are displayed on the user view page, and can be configured through "Manage Display" on account settings.