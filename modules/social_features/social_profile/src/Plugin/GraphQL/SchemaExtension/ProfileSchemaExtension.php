<?php

namespace Drupal\social_profile\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

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
class ProfileSchemaExtension extends SdlSchemaExtensionPluginBase {

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
    $registry->addFieldResolver('Profile', 'first_name',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('entity:profile', 'field_profile_first_name.0.value')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:profile'))
        ->map('path', $builder->fromValue('field_profile_first_name.0.value'))
        ->map('value', $builder->fromParent())
    );

    $registry->addFieldResolver('Profile', 'last_name',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('entity:profile', 'field_profile_last_name.0.value')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:profile'))
        ->map('path', $builder->fromValue('field_profile_last_name.0.value'))
        ->map('value', $builder->fromParent())
    );

    $registry->addFieldResolver('Profile', 'nickname',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('entity:profile', 'field_profile_nick_name.0.value')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:profile'))
        ->map('path', $builder->fromValue('field_profile_nick_name.0.value'))
        ->map('value', $builder->fromParent())
    );

    // $value->getEntity()->get('field_profile_self_introduction')->first()->get('processed')->getString()
    $registry->addFieldResolver('Profile', 'introduction',
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_profile_self_introduction'))
    );

    $registry->addFieldResolver('Profile', 'phone',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('entity:profile', 'field_profile_phone_number.0.value')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:profile'))
        ->map('path', $builder->fromValue('field_profile_phone_number.0.value'))
        ->map('value', $builder->fromParent())
    );

    $registry->addFieldResolver('Profile', 'function',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('entity:profile', 'field_profile_function.0.value')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:profile'))
        ->map('path', $builder->fromValue('field_profile_function.0.value'))
        ->map('value', $builder->fromParent())
    );

    $registry->addFieldResolver('Profile', 'organization',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('entity:profile', 'field_profile_organization.0.value')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:profile'))
        ->map('path', $builder->fromValue('field_profile_organization.0.value'))
        ->map('value', $builder->fromParent())
    );
  }

}
