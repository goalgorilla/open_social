<?php

namespace Drupal\kpi_analytics\Plugin\KPIDataFormatter;

use Drupal\block\BlockInterface;
use Drupal\kpi_analytics\Plugin\KPIDataFormatterBase;
use Drupal\layout_builder\SectionComponent;
use Drupal\taxonomy\TermInterface;

/**
 * Provides a 'TagsFollowKPIDataFormatter' KPI data formatter.
 *
 * @KPIDataFormatter(
 *  id = "tags_follow_kpi_data_formatter",
 *  label = @Translation("Tags follow KPI data formatter"),
 * )
 */
class TagsFollowKPIDataFormatter extends KPIDataFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(array $data, $block = NULL): array {
    $formatted_data = [];

    if ($block === NULL) {
      return $formatted_data;
    }
    if ($block instanceof BlockInterface) {
      $block_settings = $block->get('settings');
    }
    elseif ($block instanceof SectionComponent) {
      $block_settings = $block->get('configuration');
    }
    else {
      return $formatted_data;
    }
    if (empty($block_settings['taxonomy_filter'])) {
      return $formatted_data;
    }

    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($block_settings['taxonomy_filter'] as $tid) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $term_storage->load($tid);
      $formatted_data[$tid] = [
        'tid' => $tid,
        'current' => '0',
        'difference' => '0',
      ];
      if ($term instanceof TermInterface) {
        $formatted_data[$tid]['label'] = $term->label();
      }
    }

    // Combine multiple values in one value.
    foreach ($data as $value) {
      $formatted_data[$value['tid']]['current'] = $value['current'];
      $formatted_data[$value['tid']]['difference'] = $value['difference'];
    }
    return array_values($formatted_data);
  }

}
