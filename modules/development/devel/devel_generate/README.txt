This module creates the "DevelGenerate" plugin type.

All you need to do to provide a new instance for "DevelGenerate" plugin type
is to create your class extending "DevelGenerateBase" and following the next steps.

1 - Declaring your plugin with annotations:

/**
 * Provides a ExampleDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "example",
 *   label = @Translation("example"),
 *   description = @Translation("Generate a given number of example elements. Optionally delete current example elements."),
 *   url = "example",
 *   permission = "administer example",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "another_property" = "default_value"
 *   }
 * )
 */

2 - Implement "settingsForm" method to create a form using the properties from annotations.

3 - Implement "handleDrushParams" method. It should return an array of values.

4 - Implement "generateElements" method. You can write here your business logic
using the array of values.

Notes:

You can alter existing properties for every plugin implementing hook_devel_generate_info_alter.

DevelGenerateBaseInterface details base wrapping methods that most DevelGenerate implementations
will want to directly inherit from Drupal\devel_generate\DevelGenerateBase.

DevelGenerateFieldBaseInterface details base wrapping methods that most class implementations
for supporting new field types will want to directly inherit from Drupal\devel_generate\DevelGenerateFieldBase.
So to give support for a new field type should be enough to create a class called
"DevelGenerateFieldNewfieldtype" extending DevelGenerateFieldBase and to implement "generateValues" method.
