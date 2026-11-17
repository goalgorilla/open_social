# Login Tracker
A simple module that tracks each time a user logs in and stores a historical
record in your database. This data is then exposed to views so you can report on
it if required.

Note: User 1 will not be tracked by default.

## Installation
Install, and activate the module as you would any other module. As soon as the
module is active, then logins will be recorded in your database.

If you wish to exclude certain roles from being tracked, then you can assign the
role the permission *Excluded from login tracking* and their logins will not be
tracked.

## Fine grained control over login tracking
If you need finer grained control over which logins are tracked, then you can
implement your required login in hook_login_tracker_track_login_alter() to
override whether a login is tracked for an individual instance.

## Storage of additional data
You can store additional data along with the login tracking by implementing
hook_login_tracker_login_data. The default value is an empty array, so you may
add to that as required.

## Views integration
You can access the data in the login tracker tables either as a base view, or
more usefully as a relationship against the user table.
