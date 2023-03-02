# Social User Export

This module provides site managers with the ability to export user data from the
admin people overview by adding a views bulk operations action.

It works by gathering a set of `UserExportPlugin`s which are used to get the
headers and values for the resulting CSV file.

## Defining a UserExportPlugin
You can define your own plugin to add a field to the export CSV by creating a
plugin class with the `UserExportPlugin` annotation.

```php
/**
 * An example of a plugin annotation for the user export system.
 *
 * @UserExportPlugin(
 *  id = "plugin_id",
 *  label = @Translation("Human Readable Label"),
 *  weight = -460,
 *  dependencies = @PluginDependency(
 *    config = {
 *      "field.field.profile.profile.field_profile_address",
 *    }
 *  )
 * )
 */
```

A lower weight plugin will be run first and will produce a column that comes
before plugins with a higher weight (negative is lower than positive).

The `dependencies` key is optional and accepts `config`, `module` and `theme`.
Multiple dependencies may be provided for a single plugin, e.g.

```php
/**
 * An example of a plugin annotation for the user export system.
 *
 * @UserExportPlugin(
 *  id = "multiple_dependencies",
 *  label = @Translation("Multiple Dependencies"),
 *  weight = 0,
 *  dependencies = @PluginDependency(
 *    config = {
 *      "field.field.profile.profile.field_profile_address",
 *      "field.field.profile.profile.field_profile_profile_tag",
 *    },
*     module = {
*       "some_module",
*       "another_module",
*     },
*     theme = {
*       "socialbase",
 *    }
 *  )
 * )
 */
```
