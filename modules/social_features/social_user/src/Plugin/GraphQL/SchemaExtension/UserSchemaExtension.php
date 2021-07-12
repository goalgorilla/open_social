<?php

namespace Drupal\social_user\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;
use Drupal\social_user\GraphQL\UserActorTypeResolver;

/**
 * Adds user data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_user_schema_extension",
 *   name = "Open Social - User Schema Extension",
 *   description = "GraphQL schema extension for Open Social user data.",
 *   schema = "open_social"
 * )
 */
class UserSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    // Type resolvers.
    $registry->addTypeResolver('Actor', new UserActorTypeResolver($registry->getTypeResolver('Actor')));

    // Root Query fields.
    $registry->addFieldResolver('Query', 'viewer',
      $builder->produce('viewer')
    );

    $registry->addFieldResolver('Query', 'users',
      $builder->produce('query_user')
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Query', 'user',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('user'))
        ->map('uuid', $builder->fromArgument('id'))
    );

    // User type fields.
    $registry->addFieldResolver('User', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('User', 'displayName',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('User', 'mail',
      $builder->fromPath('entity:user', 'mail.value')
    );

    $registry->addFieldResolver('User', 'created',
      $builder->fromPath('entity:user', 'created.value')
    );

    $registry->addFieldResolver('User', 'updated',
      $builder->fromPath('entity:user', 'changed.value')
    );

    $registry->addFieldResolver('User', 'status',
      $builder->produce('user_status')
        ->map('user', $builder->fromParent())
    );

    $registry->addFieldResolver('User', 'roles',
      $builder->produce('user_roles')
        ->map('user', $builder->fromParent())
    );
  }

}
