<?php

namespace Drupal\social_profile\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;

/**
 * Adds user data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_profile_schema_extension",
 *   name = "Open Social - Profile Schema Extension",
 *   description = "GraphQL schema extension for Open Social profile data.",
 *   schema = "open_social"
 * )
 */
class ProfileSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    // User type fields.
    $registry->addFieldResolver('User', 'profile',
      $builder->produce('default_profile_load')
        ->map('user', $builder->fromParent())
    );

    // Profile type fields.
    $registry->addFieldResolver('Profile', 'firstName',
      $builder->fromPath('entity:profile', 'field_profile_first_name.0.value')
    );

    $registry->addFieldResolver('Profile', 'lastName',
      $builder->fromPath('entity:profile', 'field_profile_last_name.0.value')
    );

    $registry->addFieldResolver('Profile', 'avatar',
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_profile_image'))
    );

    // $value->getEntity()->get('field_profile_self_introduction')->first()->get('processed')->getString()
    $registry->addFieldResolver('Profile', 'introduction',
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_profile_self_introduction'))
    );

    $registry->addFieldResolver('Profile', 'phone',
      $builder->fromPath('entity:profile', 'field_profile_phone_number.0.value')
    );

    $registry->addFieldResolver('Profile', 'function',
      $builder->fromPath('entity:profile', 'field_profile_function.0.value')
    );

    $registry->addFieldResolver('Profile', 'organization',
      $builder->fromPath('entity:profile', 'field_profile_organization.0.value')
    );
  }

}
