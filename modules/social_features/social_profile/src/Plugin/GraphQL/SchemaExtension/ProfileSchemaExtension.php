<?php

namespace Drupal\social_profile\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;
use Drupal\social_profile\GraphQL\ProfileAffiliatableTypeResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Constructs a ProfileSchemaExtension.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\social_profile\GraphQL\ProfileAffiliatableTypeResolver $profileAffiliatableTypeResolver
   *   Resolves ProfileAffiliatable to a concrete GraphQL type.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    ModuleHandlerInterface $moduleHandler,
    protected ProfileAffiliatableTypeResolver $profileAffiliatableTypeResolver,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $moduleHandler);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('social_profile.profile_affiliatable_type_resolver'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    // User type fields.
    $registry->addFieldResolver('User', 'profile',
      $builder->produce('default_profile_load')
        ->map('user', $builder->fromParent())
    );

    // Profile type fields.
    $registry->addFieldResolver('Profile', 'firstName',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_profile_first_name')),
        $builder->fromPath('field:string', '0.value')
      )
    );

    $registry->addFieldResolver('Profile', 'lastName',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_profile_last_name')),
        $builder->fromPath('field:string', '0.value')
      )
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
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_profile_phone_number')),
        $builder->fromPath('field:telephone', '0.value')
      )
    );

    $registry->addFieldResolver('Profile', 'function',
      $builder->callback(fn ($profile) => (string) $profile->getPrimaryAffiliationFunction())
    );

    $registry->addFieldResolver('Profile', 'organization',
      $builder->callback(fn ($profile) => (string) $profile->getPrimaryAffiliationName())
    );

    $this->addProfileAffiliationsResolvers($registry, $builder);
  }

  /**
   * Registers ProfileAffiliatable type and affiliation fields resolvers.
   */
  private function addProfileAffiliationsResolvers(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    $registry->addTypeResolver(
      'ProfileAffiliatable',
      $this->profileAffiliatableTypeResolver,
    );

    $this->registerPrimaryAffiliationResolver($registry, $builder);
    $this->registerAffiliationsResolver($registry, $builder);

    $registry->addFieldResolver('ExternalProfileAffiliation', 'name',
      $builder->defaultValue(
        $builder->compose(
          $builder->produce('field')
            ->map('entity', $builder->fromParent())
            ->map('field', $builder->fromValue('field_affiliation_org_name')),
          $builder->fromPath('field:string', '0.value')
        ),
        $builder->fromValue('')
      )
    );

    $registry->addFieldResolver('ExternalProfileAffiliation', 'function',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_affiliation_org_function')),
        $builder->fromPath('field:string', '0.value')
      )
    );
  }

  /**
   * Registers Profile.primaryAffiliation.
   *
   * First group affiliation item when that hub is visible to the viewer,
   * otherwise the first external affiliation when other affiliations are
   * enabled. Null when the feature is disabled or no visible affiliation
   * exists.
   *
   * @see \Drupal\social_profile\Entity\Bundle\SocialProfile::getPrimaryAffiliation()
   */
  private function registerPrimaryAffiliationResolver(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Profile', 'primaryAffiliation',
      $builder->cond([
        [
          // 1. Make sure the affiliation feature is enabled globally.
          $builder->produce('social_group_affiliation_status'),
          // 2. Provide value for primary affiliation.
          $builder->defaultValue(
            // 2.1. Read the affiliation from group membership selected in the
            // "field_group_affiliation" profile field.
            $builder->compose(
              $builder->context('uid', $builder->fromPath('entity:profile', 'uid.target_id')),
              $builder->produce('field')
                ->map('entity', $builder->fromParent())
                ->map('field', $builder->fromValue('field_group_affiliation')),
              $builder->fromPath('field:string', '0.target_id'),
              $builder->produce('entity_load')
                ->map('type', $builder->fromValue('group'))
                ->map('id', $builder->fromParent())
                ->map('access', $builder->fromValue(TRUE)),
              $builder->produce('entity_id')
                ->map('entity', $builder->fromParent()),
              $builder->produce('group_membership')
                ->map('group_id', $builder->fromParent())
                ->map('user_id', $builder->fromContext('uid')),
            ),
            // 2.2. Fallback to an affiliation from the
            // "field_other_affiliations" field.
            $builder->cond([
              [
                $builder->compose(
                  $builder->produce('field')
                    ->map('entity', $builder->fromParent())
                    ->map('field', $builder->fromValue('field_enable_other_affiliations')),
                  $builder->fromPath('field:boolean', '0.value'),
                ),
                $builder->cond([
                  [
                    $builder->produce('field')
                      ->map('entity', $builder->fromParent())
                      ->map('field', $builder->fromValue('field_other_affiliations')),
                    $builder->compose(
                      $builder->produce('entity_reference_revisions')
                        ->map('entity', $builder->fromParent())
                        ->map('field', $builder->fromValue('field_other_affiliations'))
                        ->map('access', $builder->fromValue(TRUE))
                        ->map('bundle', $builder->fromValue(['other_affiliations'])),
                      $builder->produce('seek')
                        ->map('input', $builder->fromParent())
                        ->map('position', $builder->fromValue(0)),
                    ),
                  ],
                  [
                    $builder->fromValue(TRUE),
                    $builder->fromValue(NULL),
                  ],
                ]),
              ],
              [
                $builder->fromValue(TRUE),
                $builder->fromValue(NULL),
              ],
            ]),
          ),
        ],
        [
          $builder->fromValue(TRUE),
          $builder->fromValue(NULL),
        ],
      ])
    );
  }

  /**
   * Registers Profile.affiliations.
   *
   * Visible hub memberships from group affiliation, then external
   * affiliations. Empty when none, or when the feature is disabled.
   */
  private function registerAffiliationsResolver(ResolverRegistryInterface $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Profile', 'affiliations',
      $builder->cond([
        [
          // 1. Make sure the affiliation feature is enabled globally.
          $builder->produce('social_group_affiliation_status'),
          // 2. Provide value for affiliations.
          $builder->compose(
            $builder->context('uid', $builder->fromPath('entity:profile', 'uid.target_id')),
            // 2.1. Read affiliations from group membership selected in the
            // "field_group_affiliation" profile field.
            $builder->tap($builder->context('internals',
              $builder->cond([
                [
                  $builder->produce('field')
                    ->map('entity', $builder->fromParent())
                    ->map('field', $builder->fromValue('field_group_affiliation')),
                  $builder->compose(
                    $builder->produce('field')
                      ->map('entity', $builder->fromParent())
                      ->map('field', $builder->fromValue('field_group_affiliation')),
                    $builder->map(
                      $builder->fromPath('field:string', 'target_id'),
                    ),
                    $builder->produce('entity_load_multiple')
                      ->map('type', $builder->fromValue('group'))
                      ->map('ids', $builder->fromParent())
                      ->map('access', $builder->fromValue(TRUE)),
                    $builder->produce('array_filter')
                      ->map('array', $builder->fromParent())
                      ->map('preserve_keys', $builder->fromValue(FALSE)),
                    $builder->map(
                      $builder->produce('entity_id')
                        ->map('entity', $builder->fromParent()),
                    ),
                    $builder->produce('group_membership_multiple')
                      ->map('group_ids', $builder->fromParent())
                      ->map('user_id', $builder->fromContext('uid')),
                  ),
                ],
                [
                  $builder->fromValue(TRUE),
                  $builder->fromValue([]),
                ],
              ])
            )),
            // 2.2. Read external affiliations from the
            // "field_other_affiliations" field.
            $builder->tap($builder->context('externals',
              $builder->cond([
                [
                  $builder->compose(
                    $builder->produce('field')
                      ->map('entity', $builder->fromParent())
                      ->map('field', $builder->fromValue('field_enable_other_affiliations')),
                    $builder->fromPath('field:boolean', '0.value'),
                  ),
                  $builder->cond([
                    [
                      $builder->produce('field')
                        ->map('entity', $builder->fromParent())
                        ->map('field', $builder->fromValue('field_other_affiliations')),
                      $builder->produce('entity_reference_revisions')
                        ->map('entity', $builder->fromParent())
                        ->map('field', $builder->fromValue('field_other_affiliations'))
                        ->map('access', $builder->fromValue(TRUE))
                        ->map('bundle', $builder->fromValue(['other_affiliations'])),
                    ],
                    [
                      $builder->fromValue(TRUE),
                      $builder->fromValue([]),
                    ],
                  ]),
                ],
                [
                  $builder->fromValue(TRUE),
                  $builder->fromValue([]),
                ],
              ])
            )),
            // 2.3. Combine group and external affiliations.
            $builder->produce('array_filter')
              ->map('array',
                $builder->produce('array_merge')
                  ->map('first', $builder->fromContext('internals', fn () => []))
                  ->map('second', $builder->fromContext('externals', fn () => []))
              )
              ->map('preserve_keys', $builder->fromValue(FALSE)),
          ),
        ],
        [
          $builder->fromValue(TRUE),
          $builder->fromValue([]),
        ],
      ])
    );
  }

}
