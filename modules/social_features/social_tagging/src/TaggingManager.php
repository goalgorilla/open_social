<?php

declare(strict_types=1);

namespace Drupal\social_tagging;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Manager for the social tagging functionality.
 *
 * Can be used to get the current state of the tagging functionality, either to
 * update it in the form or to apply effects in hooks.
 */
final class TaggingManager {

  /**
   * Cache for installed bundles.
   *
   * @phpstan-var ?array<string, string[]>
   */
  protected ?array $installedBundles = NULL;

  /**
   * Cache for installed fields.
   *
   * @phpstan-var ?array<string, \Drupal\field\FieldConfigInterface>
   */
  protected ?array $fields = NULL;

  /**
   * Create a new tagging manager instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityFieldManagerInterface $fieldManager,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * Gets the entities and bundles that contain our tagging field.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array of bundles in which
   *   the field appears.
   *
   * @phpstan-return array<string, string[]>
   */
  public function getInstalledBundles() {
    if ($this->installedBundles === NULL) {
      $this->installedBundles = array_map(
        fn(array $bundles) => $bundles["field_social_tagging"]["bundles"],
        array_filter(
          $this->fieldManager->getFieldMap(),
          fn(array $entity_fields) => isset($entity_fields["field_social_tagging"])
        )
      );
    }
    return $this->installedBundles;
  }

  /**
   * Get the field config instances of all installed tagging fields.
   *
   * @return \Drupal\field\FieldConfigInterface[]
   *   The field config instances of all installed tagging fields.
   *
   * @phpstan-return array<string, \Drupal\field\FieldConfigInterface>
   */
  public function getInstalledFieldDefinitions() : array {
    if ($this->fields === NULL) {
      $fields = [];
      foreach ($this->getInstalledBundles() as $entity_type => $bundles) {
        foreach ($bundles as $bundle) {
          $fields[] = "$entity_type.$bundle.field_social_tagging";
        }
      }
      $this->fields = $this->entityTypeManager
        ->getStorage("field_config")
        ->loadMultiple($fields);
    }
    return $this->fields;
  }

  /**
   * Reset the per-request caches.
   */
  public function reset() : void {
    $this->installedBundles = NULL;
    $this->fields = NULL;
  }

}
