<?php

namespace Drupal\social_search\Plugin\search_api;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;

trait SocialSearchSearchApiProcessorTrait {

  /**
   * Returns the entity field type manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field type manager.
   */
  protected function entityFieldManager(): EntityFieldManagerInterface {
    if (NULL === $this->entityFieldManager) {
      $this->entityFieldManager = \Drupal::service('entity_field.manager');
    }

    return $this->entityFieldManager;
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
   */
  public function preIndexSave(): void {
    $data = static::getIndexData();

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      // We want to process node datasource only.
      if (isset($data[$datasource->getEntityTypeId()])) {
        try {
          // We need to make sure that at least one node bundle has the field.
          foreach (array_keys($datasource->getBundles()) as $bundle) {
            $field_definitions = $this->entityFieldManager()->getFieldDefinitions('node', $bundle);
            // Before adding field to the index, we should make sure it doesn't
            // exist, otherwise we will add a duplicate.
            $fields = $data[$datasource->getEntityTypeId()];
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

}
