<?php

namespace Drupal\kpi_analytics\Plugin\KPIDatasource;

use Drupal\block_content\BlockContentInterface;
use Drupal\kpi_analytics\Plugin\KPIDatasourceBase;

/**
 * Provides a 'DrupalContextKPIDatasource' KPI Datasource.
 *
 * @KPIDatasource(
 *  id = "drupal_context_kpi_datasource",
 *  label = @Translation("Drupal context datasource"),
 * )
 */
class DrupalContextKPIDatasource extends KPIDatasourceBase {

  /**
   * {@inheritdoc}
   */
  public function query(BlockContentInterface $entity, $block): array {
    $data = [];
    $args = [];

    if (!$entity->hasField('field_kpi_query')
      || $entity->get('field_kpi_query')->isEmpty()
    ) {
      return $data;
    }

    $query = $entity->field_kpi_query->value;
    // Look for placeholders in query.
    preg_match_all('/:(\w+)/', $query, $placeholders);

    // Look for placeholders replacements in current route.
    if (!empty($placeholders[1])) {
      foreach ($placeholders[1] as $placeholder) {
        $args[":$placeholder"] = $this->routeMatch->getRawParameter($placeholder);
      }
    }

    $results = $this->database->query($query, $args)->fetchAll();
    foreach ($results as $result) {
      $data[] = (array) $result;
    }
    return $data;
  }

}
