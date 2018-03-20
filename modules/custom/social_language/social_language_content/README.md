# Social Language - Content Translation

This module has intentionally been left empty. It serves as a way to 
enable/disable content translation for the Open Social content types.

The configurations for the various content types should be kept in their own
modules with a dependency on this module. This way the translation settings 
can be kept up to date if fields change.

# Default permissions

How do we handle permissions upon module install? Can we create a(n Open Social)
module for this that allows you to set default permissions without writing 
install hooks.

Possibly:
- Snapshot permissions before a module install/update
- Find which permissions were (dynamically added)
- Grant those permissions to the configured roles 

# TODO

Enable translation for the content types.