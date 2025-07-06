
# Search API Processors: Writing access checker

## Overview

This documentation explains how to create Search API processors that implement the `preprocessSearchQuery()` method using the tagging system established by the `TaggingQuery` processor. This pattern allows for modular, extensible search query modifications that can be easily discovered and altered by other modules.

## The Foundation: TaggingQuery Processor

The `TaggingQuery` processor (`social_search_tagging_query`) is the foundation of this system. It runs at the `preprocess_query` stage with a very low weight (-100) to ensure it executes before other processors.

### What TaggingQuery Does

1. **Creates a structured query hierarchy** for each entity type in the search index
2. **Tags condition groups** to make them discoverable by other processors
3. **Establishes the access control framework** that other processors can extend

### Query Structure Created by TaggingQuery

```php  
[any other search conditions]  
AND  
(  
  (  
  search_api_datasource = "node"  
    AND  
    (  
      // This is where other processors add their conditions  
      // social_entity_type_node_access tagged group  
    )  
  )  
  OR  
  (  
  search_api_datasource = "group"  
    AND  
    (  
      // This is where other processors add their conditions  
      // social_entity_type_group_access tagged group  
    )  
  )  
)  
```  

## Creating Your Own Search API Processor

### 1. Basic Processor Structure

```php  
<?php  
  
declare(strict_types=1);  
  
namespace Drupal\your_module\Plugin\search_api\processor;  
  
use Drupal\search_api\Processor\ProcessorPluginBase;  
use Drupal\search_api\Query\QueryInterface;  
use Drupal\search_api\Query\ConditionGroupInterface;  
use Drupal\social_search\Utility\SocialSearchApi;  
use Drupal\social_search\Plugin\search_api\SocialSearchSearchApiProcessorTrait;  
use Symfony\Component\DependencyInjection\ContainerInterface;  
  
/**  
 * @SearchApiProcessor(  
 *   id = "your_module_entity_access",  
 *   label = @Translation("Your Module Entity Access"),  
 *   description = @Translation("Alter entity type access query conditions."),  
 *   stages = {  
 *     "preprocess_query" = -99, // Run after TaggingQuery (-100)  
 *   },  
 *   locked = true,  
 *   hidden = true,  
 * )  
 */  
class YourModuleEntityAccess extends ProcessorPluginBase {  
  
  use SocialSearchSearchApiProcessorTrait;  
  
  /**  
   * {@inheritdoc}  
   */  
  public function preprocessSearchQuery(QueryInterface $query): void {  
    // Your implementation here  
  }  
}  
```  

### 2. The getIndexData() Method

The `getIndexData()` method is a **static method** that defines which fields should be added to the search index for your processor to work correctly. This method is called during the index configuration process to ensure all required fields are available.

#### Purpose of getIndexData()

1. **Defines Required Fields**: Specifies which entity fields need to be indexed
2. **Sets Field Types**: Defines the data type for each field (string, integer, boolean, etc.)
3. **Ensures Index Completeness**: Guarantees that your processor has access to all necessary data
4. **Enables Field Discovery**: Allows the `findField()` method to locate fields in the index

#### Example Implementation

```php  
/**  
 * Returns the entity type field names list that should be added to the index.  
 *  
 * @return array  
 *   The field names list with additional settings (type, etc.) associated  
 *   by entity type (node, post, etc.).  
 */  
public static function getIndexData(): array {  
  return [  
    'node' => [  
      'nid' => ['type' => 'integer'],  
      'type' => ['type' => 'string'],  
      'status' => ['type' => 'boolean'],  
      'uid' => ['type' => 'integer'],  
      'field_content_visibility' => ['type' => 'string'],  
      'groups' => ['type' => 'integer'],  
    ],  
    'media' => [  
      'mid' => ['type' => 'integer'],  
      'bundle' => ['type' => 'string'],  
      'status' => ['type' => 'string'],  
      'field_media_visibility' => ['type' => 'string'],  
      'groups' => ['type' => 'integer'],  
    ],  
    'group' => [  
      'id' => ['type' => 'integer'],  
      'type' => ['type' => 'string'],  
      'status' => ['type' => 'boolean'],  
      'field_flexible_group_visibility' => ['type' => 'string'],  
    ],  
  ];  
}  
```  

#### Field Types Available

- `'string'` - Text fields
- `'integer'` - Numeric fields (IDs, counts, etc.)
- `'boolean'` - True/false fields
- `'date'` - Date/time fields
- `'decimal'` - Decimal numbers
- `'uri'` - URLs and URIs

### 3. Why Use SocialSearchSearchApiProcessorTrait?

The `SocialSearchSearchApiProcessorTrait` provides essential functionality that your processor needs to work effectively with the Social Search system.

#### Key Benefits

1. **Field Discovery**: The `findField()` method to locate indexed fields
2. **Type Safety**: Ensures fields exist before using them
3. **Consistent API**: Standardized way to access field data
4. **Error Prevention**: Prevents runtime errors from missing fields

#### Required Methods from the Trait

```php  
use Drupal\social_search\Plugin\search_api\SocialSearchSearchApiProcessorTrait;  
  
class YourProcessor extends ProcessorPluginBase {  
  use SocialSearchSearchApiProcessorTrait;  
    
  public function preprocessSearchQuery(QueryInterface $query): void {  
    // Use the trait's findField method  
    $visibility_field = $this->findField('entity:node', 'field_content_visibility');  
    $groups_field = $this->findField('entity:node', 'groups');  
      
    if (!$visibility_field instanceof FieldInterface || !$groups_field instanceof FieldInterface) {  
      // Required fields don't exist in the index  
      return;  
    }  
     // Now you can safely use the fields  
    $or->addCondition($visibility_field->getFieldIdentifier(), 'public');  
  }  
}  
```  

#### What the Trait Provides

- **`findField($datasource_id, $property_path)`**: Locates fields in the index
- **`getFieldsHelper()`**: Access to field helper utilities
- **Field validation methods**: Ensures fields exist before use
- **Consistent field access patterns**: Standardized approach across processors

### 4. Why Both getIndexData() and the Trait Are Required

#### The Relationship Between getIndexData() and SocialSearchSearchApiProcessorTrait

1. **`getIndexData()`** ensures fields are indexed during index configuration
2. **`SocialSearchSearchApiProcessorTrait`** provides methods to safely access those fields at runtime
3. **Together they create a complete solution** for field management in Search API processors

#### What Happens Without getIndexData()

```php  
// ❌ Bad - Field may not exist in the index  
$field = $this->findField('entity:node', 'field_custom_visibility');  
if ($field instanceof FieldInterface) {  
  // This might never execute if the field wasn't indexed  
  $or->addCondition($field->getFieldIdentifier(), 'public');  
}  
```  

#### What Happens Without the Trait

```php  
// ❌ Bad - No safe way to find fields  
$fields = $this->getIndex()->getFields();  
foreach ($fields as $field) {  
  if ($field->getPropertyPath() === 'field_content_visibility') {  
    // Manual field discovery - error-prone and inefficient  
    $or->addCondition($field->getFieldIdentifier(), 'public');  
    break;  
  }  
}  
```  

#### The Complete Solution

```php  
// ✅ Good - Both methods work together  
class YourProcessor extends ProcessorPluginBase {  
  use SocialSearchSearchApiProcessorTrait;  
    
  public static function getIndexData(): array {  
    return [  
      'node' => [  
        'field_content_visibility' => ['type' => 'string'],  
        'groups' => ['type' => 'integer'],  
      ],  
    ];  
  }  
   public function preprocessSearchQuery(QueryInterface $query): void {  
    // Safe field discovery thanks to the trait  
    $visibility = $this->findField('entity:node', 'field_content_visibility');  
    $groups = $this->findField('entity:node', 'groups');  
      
    // Fields are guaranteed to exist thanks to getIndexData()  
    if ($visibility instanceof FieldInterface && $groups instanceof FieldInterface) {  
  $or->addCondition($visibility->getFieldIdentifier(), 'public');  
    }  
  }  
}  
```  

### 5. Finding Tagged Condition Groups

Use `SocialSearchApi::findTaggedQueryConditionsGroup()` to locate the specific condition group you want to modify:

```php  
public function preprocessSearchQuery(QueryInterface $query): void {  
  // Find the access condition group for your entity type  
  $or = SocialSearchApi::findTaggedQueryConditionsGroup(  
    'social_entity_type_node_access',   
    $query->getConditionGroup()  
  );  
    
  if (!$or instanceof ConditionGroupInterface) {  
    return;  
  }  
   // Your logic here...  
}  
```  

### 6. Checking for Bypass Access

Always check if access checks should be bypassed:

```php  
// Check if we can skip access check for this condition  
if (SocialSearchApi::skipAccessCheck($or)) {  
  return;  
}  
```  

### 7. Field Validation

Ensure required fields exist in the index before using them:

```php  
$visibility = $this->findField('entity:node', 'field_content_visibility');  
$groups = $this->findField('entity:node', 'groups');  
  
if (!$visibility instanceof FieldInterface || !$groups instanceof FieldInterface) {  
  // The required fields don't exist in the index  
  return;  
}  
```  

### 8. Adding Conditions

Add your access conditions to the found condition group:

```php  
// Simple condition  
$or->addCondition($visibility->getFieldIdentifier(), 'public');  
  
// Complex condition with sub-groups  
$condition = $query->createConditionGroup('AND')  
  ->addCondition($type->getFieldIdentifier(), 'article')  
  ->addCondition($visibility->getFieldIdentifier(), 'group');  
  
$or->addConditionGroup($condition);  
```  

## Want to see examples?
Just grep the codebase with these keywords `SocialSearchApi::findTaggedQueryConditionsGroup`

## Advanced Patterns

### 1. Creating Tagged Sub-Conditions

You can create your own tagged conditions for other processors to find:

```php  
$condition = $query->createConditionGroup(  
  'AND',   
  ["your_module_entity_access:$bundle:$visibility"]  
)  
  ->addCondition($type->getFieldIdentifier(), $bundle)  
  ->addCondition($visibility->getFieldIdentifier(), $visibility);  
  
$or->addConditionGroup($condition);  
```  

### 2. Handling Multiple Entity Types

If your processor handles multiple entity types, iterate through them:

```php  
public function preprocessSearchQuery(QueryInterface $query): void {  
  $supported_entity_types = ['node', 'media', 'group'];  
    
  foreach ($supported_entity_types as $entity_type) {  
  $or = SocialSearchApi::findTaggedQueryConditionsGroup(  
      "social_entity_type_{$entity_type}_access",   
      $query->getConditionGroup()  
  );  
      
    if ($or instanceof ConditionGroupInterface) {  
  $this->processEntityTypeAccess($query, $or, $entity_type);  
    }  
  }  
}  
```  

### 3. Using the SocialSearchSearchApiProcessorTrait

The trait provides helpful methods for field discovery:

```php  
use Drupal\social_search\Plugin\search_api\SocialSearchSearchApiProcessorTrait;  
  
class YourProcessor extends ProcessorPluginBase {  
  use SocialSearchSearchApiProcessorTrait;  
    
  public function preprocessSearchQuery(QueryInterface $query): void {  
    // Use the trait's findField method  
    $field = $this->findField('entity:node', 'field_example');  
    if ($field instanceof FieldInterface) {  
      // Use the field  
    }  
  }  
}  
```  

## Best Practices

### 1. Processor Weight

- **TaggingQuery**: `-100` (runs first)
- **Your processors**: `-99` to `-1` (run after TaggingQuery)
- **Other processors**: `0` and above

### 2. Index Management

After adding a new processor with `getIndexData()`, you need to ensure the index is updated:

#### During Module Installation

```php  
// In your module's .install file  
function your_module_install() {  
  // Reindex all search api indexes with your entity types  
  if (function_exists('social_search_resave_data_source_search_indexes')) {  
  social_search_resave_data_source_search_indexes(['node', 'media']);  
  }  
}  
```  

#### During Module Updates

```php  
// In your module's .install file  
function your_module_update_11000() {  
  // Add your processor to search indexes  
  if (function_exists('social_search_resave_data_source_search_indexes')) {  
  social_search_resave_data_source_search_indexes(['node']);  
  }  
}  
```  

#### Manual Reindexing

If you need to manually reindex:

```bash  
# Reindex specific indexes  
drush search-api:reset-tracker social_content  
drush search-api:index social_content  
  
# Or reindex all indexes  
drush search-api:reset-tracker  
drush search-api:index  
```  

### 3. Error Handling

Always validate:
- Condition groups exist
- Required fields are indexed
- User account is valid
- Bypass access is not applied

### 4. Performance

- Return early if conditions aren't met
- Cache expensive operations
- Use efficient field lookups

### 5. Documentation

- Document your processor's purpose
- Explain the conditions it adds
- Note any dependencies

### 6. Testing

- Test with different user roles
- Test with various entity types
- Test bypass access scenarios
- Test field availability scenarios

## Common Pitfalls

### 1. Missing Field Validation

```php  
// ❌ Bad - may cause errors if field doesn't exist  
$or->addCondition('field_example', 'value');  
  
// ✅ Good - validate field exists first  
$field = $this->findField('entity:node', 'field_example');  
if ($field instanceof FieldInterface) {  
  $or->addCondition($field->getFieldIdentifier(), 'value');  
}  
```  

### 2. Ignoring Bypass Access

```php  
// ❌ Bad - doesn't respect bypass access  
$or->addCondition($field->getFieldIdentifier(), 'value');  
  
// ✅ Good - check for bypass access  
if (!SocialSearchApi::skipAccessCheck($or)) {  
  $or->addCondition($field->getFieldIdentifier(), 'value');  
}  
```  

### 3. Wrong Processor Weight

```php  
// ❌ Bad - may run before TaggingQuery  
'stages' => ['preprocess_query' => -101],  
  
// ✅ Good - runs after TaggingQuery  
'stages' => ['preprocess_query' => -99],  
```  

## Conclusion

The Search API processor system with tagged condition groups provides a powerful, extensible way to implement complex access control logic. By following the patterns established by `TaggingQuery` and using the utility methods provided by `SocialSearchApi`, you can create processors that are:

- **Modular**: Each processor handles one aspect of access control
- **Discoverable**: Other processors can find and modify your conditions
- **Maintainable**: Clear separation of concerns
- **Extensible**: Easy to add new access rules

Remember to always validate your assumptions, handle edge cases, and document your processor's behavior for future maintainers.