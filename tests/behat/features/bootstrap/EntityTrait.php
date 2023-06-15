<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides helpers around entity management.
 */
trait EntityTrait {

  /**
   * Validate that the specified values are actually fields on the entity.
   *
   * Throws an exception if the user provided fields that aren't part of the
   * entity. This helps prevent tests that make statements of "given" entities
   * while some of the data may not actually have been persisted giving false
   * negatives on later assertions.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array &$values
   *   The provided values. These may be massaged to convert e.g. taxonomy term
   *   IDs to numeric IDs.
   */
  protected function validateEntityFields(string $entity_type, array &$values) : void {
    $entity_type_manager = \Drupal::service("entity_type.manager");
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $definition = $entity_type_manager->getDefinition($entity_type);
    assert($definition instanceof EntityTypeInterface);
    /** @var ?string $bundle */
    $bundle = $definition->getKey('bundle') ?: NULL;
    if ($bundle !== NULL && !isset($values[$bundle])) {
      throw new \Exception("Must specify '$bundle' for '$entity_type' type entity.");
    }

    $entityClass = $definition->getClass();
    /** @var \Drupal\Core\Entity\EntityInterface $dummy */
    $dummy = $entityClass::create([$bundle => $values[$bundle]]);

    foreach ($values as $field_name => $field_value) {
      if ($definition->get($field_name) === NULL && !($dummy instanceof FieldableEntityInterface && $dummy->hasField($field_name))) {
        throw new \Exception("Entity type '$entity_type' does not have property or field '$field_name'.");
      }

      // Allow taxonomy fields to be a comma separated list of IDs or labels.
      // We can only do this for the default handler because other handlers
      // might have a different settings format so we can't know the target
      // bundles.
      $field_definition = $dummy->getFieldDefinition($field_name);
      if ($field_definition !== NULL && $field_definition->getType() === "entity_reference" && $field_definition->getSetting("target_type") === "taxonomy_term") {
        $taxonomy_storage = $entity_type_manager->getStorage("taxonomy_term");
        if ($field_definition->getSetting("handler") === "default:taxonomy_term") {
          assert(is_string($field_value), "The taxonomy value for '$field_name' has already been converted to entities but this isn't needed.");
          if (trim($field_value) === "") {
            unset($values[$field_name]);
            continue;
          }
          $allowed_bundles = $field_definition->getSetting("handler_settings")["target_bundles"];
          // Split 0,1 to [0, 1] and convert 2,someLabel to [2, 4], making a
          // lookup of the taxonomy id by name.
          $values[$field_name] = array_map(
            function ($id_or_name) use ($taxonomy_storage, $allowed_bundles) {
              $id_or_name = trim($id_or_name);
              if (is_numeric($id_or_name)) {
                return ["target_id" => $id_or_name];
              }

              $term_ids = $taxonomy_storage->getQuery()
                ->condition("vid", $allowed_bundles, "IN")
                ->condition("name", $id_or_name)
                ->execute();

              if (!is_array($term_ids) || empty($term_ids)) {
                throw new \InvalidArgumentException("Taxonomy term $id_or_name does not exist within vocabulary " . implode(", ", $allowed_bundles));
              }
              return ["target_id" => (int) reset($term_ids)];
            },
            explode(",", $field_value)
          );
        }
      }
      // Allow date time fields to be provided as an offset to the current day
      // (e.g. "+1 day" or "-5 days").
      if ($field_definition !== NULL && $field_definition->getType() === "datetime") {
        $values[$field_name] = date('Y-m-d\TH:i:s', strtotime($values[$field_name]));
      }
      // Created and changed fields are stored as a normal timestamp but require
      // the same human-readable input as datetime fields.
      if ($field_definition !== NULL && in_array($field_definition->getType(), ["created", "changed"], TRUE)) {
        $values[$field_name] = strtotime($values[$field_name]);
      }
    }
  }

}
