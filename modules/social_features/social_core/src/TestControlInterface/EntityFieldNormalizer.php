<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Turns a table row of human-readable field values into entity values.
 *
 * Operations that build an entity from a table accept the values a person
 * would write: an entity's label instead of its id, "-20 minutes" instead of
 * a Unix timestamp. This rejects values that name no field at all, and
 * converts the ones that need it into the shape entity storage expects.
 *
 * This is deliberately not a service. Test Control Interface code must stay
 * unreferenced from container configuration so that it remains inert while
 * the `test_control_interface` module is disabled; construct it directly.
 */
final readonly class EntityFieldNormalizer {

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Validates that every value names a field, and converts the values.
   *
   * @param string $entity_type
   *   The entity type the values belong to.
   * @param array<string, mixed> $values
   *   The values, keyed by field or property name.
   *
   * @return array<string, mixed>
   *   The values, converted to what entity storage expects. A field given a
   *   blank value is dropped rather than stored empty.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the entity type is unknown, a bundle is required but missing, a
   *   value names no field or property, or a value can not be converted.
   */
  public function normalize(string $entity_type, array $values): array {
    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      throw new BadRequestHttpException('Invalid entity type.');
    }
    $definition = $this->entityTypeManager->getDefinition($entity_type);
    /** @var ?string $bundle */
    $bundle = $definition->getKey('bundle') ?: NULL;
    if ($bundle !== NULL && !isset($values[$bundle])) {
      throw new BadRequestHttpException("Must specify '$bundle' for '$entity_type' type entity.");
    }

    $entityClass = $definition->getClass();
    /** @var \Drupal\Core\Entity\EntityInterface $dummy */
    $dummy = $bundle !== NULL ? $entityClass::create([$bundle => $values[$bundle]]) : $entityClass::create([]);

    foreach (array_keys($values) as $field_name) {
      if ($definition->get($field_name) === NULL && !($dummy instanceof FieldableEntityInterface && $dummy->hasField($field_name))) {
        throw new BadRequestHttpException("Entity type '$entity_type' does not have property or field '$field_name'.");
      }

      $field_definition = ($dummy instanceof FieldableEntityInterface) ? $dummy->getFieldDefinition($field_name) : NULL;
      $this->processFieldValue($field_definition, $field_name, $values);
    }

    return $values;
  }

  /**
   * Dispatch field-type-specific value coercion for a single field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   The field definition, or NULL when '$field_name' is not a field (e.g.
   *   a base entity property).
   * @param string $field_name
   *   The field or property name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processFieldValue(?FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    if ($field_definition === NULL) {
      return;
    }

    switch ($field_definition->getType()) {
      case 'entity_reference':
        $this->processEntityReference($field_definition, $field_name, $values);
        break;

      case 'datetime':
        $this->processDatetime($field_definition, $field_name, $values);
        break;

      case 'timestamp':
      case 'created':
      case 'changed':
        $this->processTimestamp($field_name, $values);
        break;

      case 'list_string':
        // Only `field_segment_visibility` names its values by label. Every
        // other list_string field takes its stored values directly, and some
        // arrive already expanded into an array, which resolution by label
        // would reject as "not a string".
        if ($field_name === 'field_segment_visibility') {
          $this->processListString($field_definition, $field_name, $values);
        }
        break;
    }
  }

  /**
   * Resolve a taxonomy term or group entity reference field's value.
   *
   * Accepts a comma-separated list of numeric target IDs or entity labels
   * and converts it to the `target_id` array structure Drupal expects.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processEntityReference(FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    $target_type = $field_definition->getSetting('target_type');

    if ($target_type === 'taxonomy_term' && $field_definition->getSetting('handler') === 'default:taxonomy_term') {
      $field_value = $values[$field_name];
      if (!is_string($field_value)) {
        throw new BadRequestHttpException("The taxonomy value for '$field_name' must be a string.");
      }
      if (trim($field_value) === '') {
        unset($values[$field_name]);
        return;
      }
      $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $allowed_bundles = $field_definition->getSetting('handler_settings')['target_bundles'];
      $values[$field_name] = array_map(
        function ($id_or_name) use ($taxonomy_storage, $allowed_bundles) {
          $id_or_name = trim($id_or_name);
          if (is_numeric($id_or_name)) {
            return ['target_id' => $id_or_name];
          }

          $term_ids = $taxonomy_storage->getQuery()
            ->condition('vid', $allowed_bundles, 'IN')
            ->condition('name', $id_or_name)
            ->accessCheck(TRUE)
            ->execute();

          if ($term_ids === []) {
            throw new BadRequestHttpException('Taxonomy term ' . $id_or_name . ' does not exist within vocabulary ' . implode(', ', $allowed_bundles));
          }
          if (count($term_ids) > 1) {
            throw new BadRequestHttpException('Taxonomy term name ' . $id_or_name . ' is ambiguous: ' . count($term_ids) . ' terms share that name within vocabulary ' . implode(', ', $allowed_bundles));
          }
          return ['target_id' => (int) reset($term_ids)];
        },
        explode(',', $field_value)
      );
      return;
    }

    if ($target_type === 'group' && $field_definition->getSetting('handler') === 'default:group') {
      $field_value = $values[$field_name];
      if (!is_string($field_value)) {
        throw new BadRequestHttpException("The group value for '$field_name' must be a string.");
      }
      if (trim($field_value) === '') {
        unset($values[$field_name]);
        return;
      }
      $group_storage = $this->entityTypeManager->getStorage('group');
      $allowed_bundles = $field_definition->getSetting('handler_settings')['target_bundles'];
      $values[$field_name] = array_map(
        function ($id_or_name) use ($group_storage, $allowed_bundles) {
          $id_or_name = trim($id_or_name);
          if (is_numeric($id_or_name)) {
            return ['target_id' => $id_or_name];
          }

          $group_ids = $group_storage->getQuery()
            ->condition('type', $allowed_bundles, 'IN')
            ->condition('label', $id_or_name)
            ->accessCheck(TRUE)
            ->execute();

          if ($group_ids === []) {
            throw new BadRequestHttpException('Group ' . $id_or_name . ' does not exist within type: ' . implode(', ', $allowed_bundles));
          }
          if (count($group_ids) > 1) {
            throw new BadRequestHttpException('Group label ' . $id_or_name . ' is ambiguous: ' . count($group_ids) . ' groups share that label within type: ' . implode(', ', $allowed_bundles));
          }
          return ['target_id' => (int) reset($group_ids)];
        },
        explode(',', $field_value)
      );
    }
  }

  /**
   * Normalize a datetime field's value to Drupal's storage format.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processDatetime(FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    $date = new DrupalDateTime($values[$field_name], 'UTC');
    if ($field_definition->getSetting('datetime_type') === 'date') {
      $values[$field_name] = $date->format('Y-m-d');
    }
    else {
      $values[$field_name] = $date->format('Y-m-d\TH:i:s');
    }
  }

  /**
   * Convert a human-readable date/time string to a Unix timestamp.
   *
   * Covers the `timestamp`, `created` and `changed` field types, which are
   * all stored as a plain Unix timestamp.
   *
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processTimestamp(string $field_name, array &$values): void {
    $timestamp = strtotime((string) $values[$field_name]);
    if ($timestamp === FALSE) {
      throw new BadRequestHttpException(sprintf("The value '%s' for '%s' is not a valid date/time string.", $values[$field_name], $field_name));
    }
    $values[$field_name] = $timestamp;
  }

  /**
   * Resolve the `field_segment_visibility` list_string field's value.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $field_name
   *   The field name.
   * @param array<string, mixed> $values
   *   The values, keyed by field/property name.
   */
  private function processListString(FieldDefinitionInterface $field_definition, string $field_name, array &$values): void {
    $field_value = $values[$field_name];
    if (!is_string($field_value)) {
      throw new BadRequestHttpException("The segment visibility value for '$field_name' must be a string.");
    }
    if (trim($field_value) === '') {
      unset($values[$field_name]);
      return;
    }

    $allowed_values_function = $field_definition->getSetting('allowed_values_function');
    if ($allowed_values_function && is_callable($allowed_values_function)) {
      $allowed_values = $allowed_values_function($field_definition);
      $values[$field_name] = array_map(
        function ($segment_label) use ($allowed_values) {
          $segment_label = trim($segment_label);
          $segment_id = array_search($segment_label, $allowed_values);
          if ($segment_id === FALSE) {
            throw new BadRequestHttpException("Segment '$segment_label' does not exist or is not available for visibility.");
          }
          return $segment_id;
        },
        explode(',', $field_value)
      );
    }
  }

}
