<?php

namespace Drupal\kpi_analytics\Plugin\KPIDatasource;

use Drupal\block\BlockInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\kpi_analytics\Plugin\KPIDatasourceBase;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides a 'DrupalKPIDatasource' KPI Datasource.
 *
 * @KPIDatasource(
 *  id = "drupal_kpi_term_datasource",
 *  label = @Translation("Drupal term datasource"),
 * )
 */
class DrupalKPITermDatasource extends KPIDatasourceBase {

  /**
   * {@inheritdoc}
   */
  public function query(BlockContentInterface $entity, $block): array {
    $data = [];
    $args = [];

    if ($block === NULL) {
      return $data;
    }
    if ($block instanceof BlockInterface) {
      $block_settings = $block->get('settings');
    }
    elseif ($block instanceof SectionComponent) {
      $block_settings = $block->get('configuration');
    }
    else {
      return $data;
    }
    if (empty($block_settings['taxonomy_filter'])) {
      return $data;
    }

    $query = $entity->field_kpi_query->value;
    preg_match_all('/:(\w+)/', $query, $placeholders);
    if (!empty($placeholders[1])) {
      foreach ($placeholders[1] as $placeholder) {
        if ($placeholder === 'term_ids') {
          $args[':term_ids[]'] = $block_settings['taxonomy_filter'];
        }
      }
    }
    $results = $this->database->query($query, $args)->fetchAll();
    foreach ($results as $result) {
      $data[] = (array) $result;
    }

    return $data;
  }

}
