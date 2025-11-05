<?php

namespace Drupal\kpi_analytics\Plugin\KPIDatasource;

use Drupal\block_content\BlockContentInterface;
use Drupal\kpi_analytics\Plugin\KPIDatasourceBase;

/**
 * Provides a 'DrupalKPIDatasource' KPI Datasource.
 *
 * @KPIDatasource(
 *  id = "drupal_kpi_datasource",
 *  label = @Translation("Drupal datasource"),
 * )
 */
class DrupalKPIDatasource extends KPIDatasourceBase {

  /**
   * {@inheritdoc}
   */
  public function query(BlockContentInterface $entity, $block): array {
    $data = [];
    // @todo check if we can use Views module.
    if (!$entity->get('field_kpi_query')->isEmpty()) {
      $query = $entity->field_kpi_query->value;
      $results = $this->database->query($query)->fetchAll();
      foreach ($results as $result) {
        $data[] = (array) $result;
      }
    }

    return $data;
  }

}
