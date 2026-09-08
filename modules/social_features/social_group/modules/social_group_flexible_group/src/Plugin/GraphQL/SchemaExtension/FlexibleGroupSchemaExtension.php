<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;
use Drupal\social_profile\GraphQL\ProfileAffiliationMembershipResolversTrait;

/**
 * Adds Flexible Group related variables.
 *
 * @SchemaExtension(
 *   id = "social_group_flexible_group_schema_extension",
 *   name = "Social flexible group - Schema Extension",
 *   description = "Schema extension for Flexible Group.",
 *   schema = "open_social"
 * )
 */
class FlexibleGroupSchemaExtension extends SchemaExtensionPluginBase {

  use ProfileAffiliationMembershipResolversTrait;

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();
    $registry->addFieldResolver('User', 'flexibleGroupMembership',
      $builder->produce('social_group_flexible_group_memberships_created')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('User', 'groupMemberships',
      $builder->produce('query_user_group_memberships')
        ->map('entity', $builder->fromParent())
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Group', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('Group', 'name',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('UserGroupMembership', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver('UserGroupMembership', 'joined',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
        ->map('format', $builder->fromValue('U'))
    );
    $registry->addFieldResolver('UserGroupMembership', 'user',
      $builder->fromPath('entity:group_content', 'entity_id.entity')
    );
    $registry->addFieldResolver('UserGroupMembership', 'group',
      $builder->fromPath('entity:group_content', 'gid.entity')
    );

    $this->addProfileAffiliationMembershipResolvers($registry, $builder, 'UserGroupAffiliation', 'group');
  }

}
