<?php

namespace Drupal\social_profile\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\GroupAffiliation;
use Drupal\social_profile\Service\AffiliationDisplay;
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
   * @param \Drupal\social_profile\Service\AffiliationDisplay $affiliation_display
   *   The affiliation display service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entity_type_manager,
    protected AffiliationDisplay $affiliation_display,
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
      $container->get(AffiliationDisplay::class),
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
    if (!isset($values->_relationship_entities['gc__user'])) {
      return '';
    }

    $user = $values->_relationship_entities['gc__user'];
    if (!$user instanceof UserInterface) {
      return '';
    }

    $profile = $this->getUserProfile($user);
    if (!$profile instanceof ProfileAffiliationInterface) {
      return '';
    }

    // Rendering an empty build still bubbles the metadata into the view, which
    // matters: hiding the affiliation is as viewer specific as showing it.
    $cacheability = new CacheableMetadata();
    $build = $this->affiliation_display->buildLabel($profile, $cacheability);
    $cacheability->applyTo($build);

    return $this->getRenderer()->render($build);
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
