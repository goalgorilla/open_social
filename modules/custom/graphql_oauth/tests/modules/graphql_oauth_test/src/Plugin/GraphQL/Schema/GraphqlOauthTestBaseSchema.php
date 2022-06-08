<?php

namespace Drupal\graphql_oauth_test\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;

/**
 * Schema base for testing the OAuth directives.
 *
 * @Schema(
 *   id = "graphql_oauth_test",
 *   name = "Test OAuth GraphQL Schema"
 * )
 */
class GraphqlOauthTestBaseSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getExtensions(): array {
    $extensions = parent::getExtensions();
    $oauth_extension_plugin_id = 'graphql_oauth_schema_extension';
    if (!isset($extensions[$oauth_extension_plugin_id])) {
      /** @var \Drupal\graphql\Plugin\SchemaExtensionPluginInterface $plugin */
      $plugin = $this->extensionManager->createInstance($oauth_extension_plugin_id);
      $extensions[$oauth_extension_plugin_id] = $plugin;
    }
    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    return new ResolverRegistry();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    $query_fields = [
      'testAccessField',
      'testAccessFieldNonNull',
      'testAccessType',
      'testAccessTypeNonNull',
      'testQueryAccessField',
      'testQueryAccessFieldUser',
      'testQueryAccessFieldBot',
      'testQueryAccessTypeUser',
      'testQueryAccessTypeBot',
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

    foreach (['allowUserSingleScope', 'allowBotSingleScope'] as $field) {
      $registry->addFieldResolver('TestQueryAccessField', $field,
        $builder->fromValue('test')
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
      'TestQueryAccessField',
      'TestQueryAccessTypeUser',
      'TestQueryAccessTypeBot',
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

    // Instantiate the schema and add all extensions.
    return parent::getSchema($registry);
  }

}
