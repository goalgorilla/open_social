<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor that hides results that don't narrow results.
 *
 * @FacetsProcessor(
 *   id = "hide_active_items_processor",
 *   label = @Translation("Hide active items"),
 *   description = @Translation("Do not display items that are active."),
 *   stages = {
 *     "build" = 25
 *   }
 * )
 */
class HideActiveItemsProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $id => $result) {
      if ($result->isActive()) {
        unset($results[$id]);
      }
    }

    return $results;
  }

}
