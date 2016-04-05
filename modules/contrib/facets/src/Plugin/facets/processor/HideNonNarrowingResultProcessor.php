<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor that hides results that don't narrow results.
 *
 * @FacetsProcessor(
 *   id = "hide_non_narrowing_result_processor",
 *   label = @Translation("Hide non narrowing results"),
 *   description = @Translation("Do not display items that do not narrow results."),
 *   stages = {
 *     "build" = 40
 *   }
 * )
 */
class HideNonNarrowingResultProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $facet_results = $facet->getResults();
    $result_count = 0;
    foreach ($facet_results as $result) {
      if ($result->isActive()) {
        $result_count += $result->getCount();
      }
    }

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $id => $result) {
      if ($result->getCount() == $result_count && !$result->isActive()) {
        unset($results[$id]);
      }
    }

    return $results;
  }

}
