# Folder Structure and Naming

The tests in these folders are grouped by product feature.
The GitHub workflows will execute tests in separate folders as
separate jobs, so a test can be split across multiple folders to
control CI parallelism. Empty folders will automatically be skipped
and folders that contain both sub-folders and feature files
will have their own features run in a separate job from the jobs
of their sub-folders.

To make it easy for developers to open a specific test file the
path is repeated in the name:
e.g. `groups/flexible/content/edit/member/groups-flexible-content-edit-member.feature`
