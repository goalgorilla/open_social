<?php

/**
 * @file
 * Contains \Drupal\profile\Plugin\Derivative\ProfileLocalTask.
 */

namespace Drupal\profile\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic routes to add/edit/list profiles.
 */
class ProfileLocalTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProfileAddLocalTask.
   *
   * @param string $base_plugin_definition
   *   The base plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($base_plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_definition) {
    return new static(
      $base_plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    // Starting weight for ordering the local tasks.
    $weight = 10;

    foreach ($this->entityTypeManager->getStorage('profile_type')->loadMultiple() as $profile_type_id => $profile_type) {
      $this->derivatives["profile.type.$profile_type_id"] = [
        'title' => $profile_type->label(),
        'route_name' => "entity.profile.type.$profile_type_id.user_profile_form",
        'base_route' => 'entity.user.canonical',
        'route_parameters' => ['profile_type' => $profile_type_id],
        'weight' => ++$weight,
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
