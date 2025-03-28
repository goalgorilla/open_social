<?php

declare(strict_types=1);

namespace Drupal\social_search\Plugin\search_api;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\SearchApiException;

/**
 * Provides a trait for processing Search API indexes for social search.
 */
trait SocialSearchSearchApiProcessorTrait {

  use LoggerTrait;

  /**
   * Returns the entity field type manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field type manager.
   */
  protected function entityFieldManager(): EntityFieldManagerInterface {
    // @phpstan-ignore-next-line
    return $this->entityFieldManager ?: \Drupal::service('entity_field.manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index): bool {
    $data = static::getIndexData();

    foreach ($index->getDatasources() as $datasource) {
      if (isset($data[$datasource->getEntityTypeId()])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function preIndexSave(): void {
    // Add to index the custom properties.
    foreach ($this->getPropertyDefinitions() as $property_name => $property_definition) {
      $this->ensureField(NULL, $property_name, $property_definition->getDataType());
    }

    // Add to index entity type fields.
    $data = static::getIndexData();

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();

      // Index defined entity type fields.
      if (!isset($data[$entity_type_id])) {
        continue;
      }

      try {
        // We need to make sure that at least one node bundle has the field.
        foreach (array_keys($datasource->getBundles()) as $bundle) {
          $field_definitions = $this->entityFieldManager()->getFieldDefinitions((string) $entity_type_id, $bundle);
          // Before adding field to the index, we should make sure it doesn't
          // exist, otherwise we will add a duplicate.
          $fields = $data[$entity_type_id];
          foreach ($fields as $name => $settings) {
            if (!isset($field_definitions[$name])) {
              // Field doesn't exist in node bundle definition.
              // Probably, wrong field name.
              continue;
            }

            if ($this->findField($datasource_id, $name)) {
              // Already exists in index.
              continue;
            }

            // "Type" should always be provided.
            $type = $settings['type'];
            $this->ensureField($datasource_id, $name, $type);
          }
        }
      }
      catch (SearchApiException $e) {
        $this->getLogger()->error($e->getMessage());
      }
    }
  }

}
