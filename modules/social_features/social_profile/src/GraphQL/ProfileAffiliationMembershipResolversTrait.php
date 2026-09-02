<?php

declare(strict_types=1);

namespace Drupal\social_profile\GraphQL;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * Shared GraphQL field resolvers for platform affiliations.
 */
trait ProfileAffiliationMembershipResolversTrait {

  /**
   * Registers id, function, user, and hub fields for an affiliation type.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   * @param string $type
   *   The GraphQL affiliation type name.
   * @param string $hub_field
   *   The GraphQL field name that returns the hub entity.
   */
  protected function addProfileAffiliationMembershipResolvers(
    ResolverRegistryInterface $registry,
    ResolverBuilder $builder,
    string $type,
    string $hub_field,
  ): void {
    $registry->addFieldResolver($type, 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );
    $registry->addFieldResolver($type, 'function',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_affiliation_function')),
        $builder->fromPath('field:string', '0.value')
      )
    );
    $registry->addFieldResolver($type, 'user',
      $builder->fromPath('entity:group_content', 'entity_id.entity')
    );
    $registry->addFieldResolver($type, $hub_field,
      $builder->fromPath('entity:group_content', 'gid.entity')
    );
  }

}
