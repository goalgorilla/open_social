# Social GraphQL

This is the base module for the Open Social GraphQL API.

If you're trying to find out how to extend this and what all the `.graphqls`
files and plugins are for, I strongly recommend going through the
[GraphQL 4.x documentation](https://drupal-graphql.gitbook.io/graphql/v/8.x-4.x/)
which explains the what's and why's of Drupal GraphQL APIs.

## Optional schema extensions via dependency attribute

Schema extension classes can declare a `#[SchemaExtensionDependency]` attribute
(from `Drupal\social_graphql\Attribute\SchemaExtensionDependency`) so that the
extension is only loaded when certain modules, themes or config are present.
This allows optional API functionality without hard dependencies in `.info.yml`.

- **When the attribute is present:** The extension is included only if all
  listed modules are enabled, all listed themes exist, and all listed config
  exists and is enabled. If any dependency is missing, the extension is excluded
  from the schema.
- **When the attribute is absent:** The extension is always included (subject
  to the normal GraphQL schema extension discovery).

Example:

```php
use Drupal\graphql\Annotation\SchemaExtension;
use Drupal\social_graphql\Attribute\SchemaExtensionDependency;

/**
 * @SchemaExtension(
 *   id = "my_optional_extension",
 *   schema = "open_social",
 *   ...
 * )
 */
#[SchemaExtensionDependency(module: ['social_topic', 'social_group_flexible_group'])]
class MyOptionalSchemaExtension extends SchemaExtensionPluginBase {
  // ...
}
```

Attribute arguments (all optional): `module` (array of module names), `config`
(array of config names), `theme` (array of theme names).
