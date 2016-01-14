Configuration Update Manager project
------------------------------------

The Configuration Update Manager project consists of two modules:

Configuration Update Base: A base module providing functionality related to
  updating and computing differences between configuration versions. No
  user interface; used by other modules such as Features.

Configuration Update Reports (in the config_ui sub-directory of this project):
  Adds an updates report and revert functionality to configuration management.
  Depends on Configuration Update Base. For more information, see the
  README.txt file in the subdirectory.
