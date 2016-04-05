<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a processor that transforms the results to show the translated term.
 *
 * @FacetsProcessor(
 *   id = "translate_taxonomy",
 *   label = @Translation("Translate taxonomy terms"),
 *   description = @Translation("Translate the taxonomy terms"),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class TranslateTaxonomyProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as &$result) {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = Term::load($result->getRawValue());

      if ($term->hasTranslation($language_interface->getId())) {
        $term_trans = $term->getTranslation($language_interface->getId());
        $result->setDisplayValue($term_trans->getName());
      }
      else {
        $result->setDisplayValue($term->getName());
      }
    }
    return $results;
  }

}
