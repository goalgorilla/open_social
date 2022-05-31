<?php

namespace Drupal\social_graphql_oauth2_test\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;

/**
 * Adds test data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "test_schema_extension",
 *   name = "Open Social - Test Schema Extension",
 *   description = "GraphQL schema extension for testing the directives.",
 *   schema = "open_social"
 * )
 */
class TestSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $query_fields = [
      'testAccessField',
      'testAccessFieldNonNull',
      'testAccessType',
      'testAccessTypeNonNull',
    ];
    foreach ($query_fields as $field) {
      $registry->addFieldResolver('Query', $field,
        $builder->fromValue([])
      );
    }

    $query_array_fields = [
      'testAccessFieldArray',
      'testAccessFieldNonNullArray',
      'testAccessFieldNonNullArrayItem',
      'testAccessFieldNonNullArrayAndItem',
      'testAccessTypeArray',
      'testAccessTypeNonNullArray',
      'testAccessTypeNonNullArrayItem',
      'testAccessTypeNonNullArrayAndItem',
    ];
    foreach ($query_array_fields as $query_array_field) {
      $registry->addFieldResolver('Query', $query_array_field,
        $builder->fromValue([[]])
      );
    }

    $fields = [
      'allowUserSingleScope',
      'allowBotSingleScope',
      'allowAllSingleScope',
      'allowUserMultipleScopes',
      'allowBotMultipleScopes',
      'allowAllMultipleScopes',
      'allowMultipleDirectiveScopes',
    ];
    foreach ($fields as $field) {
      $registry->addFieldResolver('TestAccessField', $field,
        $builder->fromValue('test')
      );
      $registry->addFieldResolver('TestAccessType', $field,
        $builder->fromValue([])
      );
    }

    $types = [
      'AllowUserSingleScope',
      'AllowBotSingleScope',
      'AllowAllSingleScope',
      'AllowUserMultipleScopes',
      'AllowBotMultipleScopes',
      'AllowAllMultipleScopes',
      'AllowMultipleDirectiveScopes',
    ];
    foreach ($types as $type) {
      $registry->addFieldResolver($type, 'test',
        $builder->fromValue('test')
      );
      if (in_array($type, ['AllowUserSingleScope', 'AllowAllSingleScope'])) {
        $registry->addFieldResolver($type, 'fieldUser',
          $builder->fromValue('test')
        );
      }
      if (in_array($type, ['AllowBotSingleScope', 'AllowAllSingleScope'])) {
        $registry->addFieldResolver($type, 'fieldBot',
          $builder->fromValue('test')
        );
      }
      if (in_array($type, [
        'AllowUserSingleScope',
        'AllowBotSingleScope',
        'AllowAllSingleScope',
      ])) {
        $registry->addFieldResolver($type, 'fieldAll',
          $builder->fromValue('test')
        );
      }
    }
  }

}
