<?php

namespace Drupal\social_profile\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\GroupAffiliation;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides field to get primary affiliation of user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("primary_affiliation")
 */
class PrimaryAffiliation extends FieldPluginBase {

  /**
   * Constructs a PrimaryAffiliation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\social_profile\GroupAffiliation $group_affiliation
   *   The group affiliation service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entity_type_manager,
    protected GroupAffiliation $group_affiliation,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('social_profile.group_affiliation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Check if we have a relationship of group user entity.
    if (isset($values->_relationship_entities['gc__user'])) {
      $user = $values->_relationship_entities['gc__user'];
      if ($user instanceof UserInterface) {
        // Check if the affiliation feature is enabled.
        $affiliation_enabled = $this->group_affiliation->isAffiliationFeatureEnabled();

        if ($affiliation_enabled) {
          // Get primary affiliation from profile.
          $profile = $this->getUserProfile($user);
          if ($profile instanceof ProfileAffiliationInterface) {
            $affiliation = $profile->getPrimaryAffiliation();
            if (!empty($affiliation['affiliation_name'])) {
              $affiliation_name = $affiliation['affiliation_name'];
              $affiliation_type = $affiliation['affiliation_type'] ?? '';

              // If it's not a non-platform affiliation (i.e., it's a platform
              // affiliation), make it a link to the group.
              if ($affiliation_type !== 'External') {
                // Get the group from the profile field.
                if (
                  $profile->hasField(GroupAffiliation::AFFILIATION_FIELD_NAME) &&
                  !$profile->get(GroupAffiliation::AFFILIATION_FIELD_NAME)->isEmpty()
                ) {
                  $group = $profile->get(GroupAffiliation::AFFILIATION_FIELD_NAME)->entity;
                  if ($group instanceof GroupInterface) {
                    $url = Url::fromRoute('entity.group.canonical', ['group' => $group->id()]);
                    $link = Link::fromTextAndUrl($affiliation_name, $url);
                    $link_renderable = $link->toRenderable();
                    return $this->getRenderer()->render($link_renderable);
                  }
                }
              }

              // For non-platform affiliations or if a group is not available,
              // return plain text.
              return $affiliation_name;
            }
          }
        }
      }
    }
    return '';
  }

  /**
   * Get user profile.
   *
   * Gets the user profile that has affiliation fields enabled.
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   Profile entity with affiliation fields or NULL.
   */
  protected function getUserProfile(UserInterface $user): ?ProfileInterface {
    $profile_storage = $this->entity_type_manager->getStorage('profile');

    // Query for profiles that have the affiliation field.
    $query = $profile_storage->getQuery()
      ->condition('uid', $user->id())
      ->condition('type', 'profile')
      ->condition('status', 1)
      ->exists(GroupAffiliation::AFFILIATION_FIELD_NAME)
      ->accessCheck(FALSE)
      ->range(0, 1);

    $profile_ids = $query->execute();
    if (empty($profile_ids)) {
      return NULL;
    }

    $profile = $profile_storage->load(reset($profile_ids));
    return $profile instanceof ProfileInterface ? $profile : NULL;
  }

}
