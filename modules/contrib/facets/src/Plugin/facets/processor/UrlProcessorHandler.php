<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * The URL processor handler triggers the actual url processor.
 *
 * The URL processor handler allows managing the weight of the actual URL
 * processor per Facet.  This handler will trigger the actual.
 *
 * @FacetsUrlProcessor, which can be configured on the Facet source.
 *
 * @FacetsProcessor(
 *   id = "url_processor_handler",
 *   label = @Translation("URL handler"),
 *   description = @Translation("Triggers the URL processor, which is set in the Facet source configuration."),
 *   stages = {
 *     "pre_query" = 50,
 *     "build" = 15,
 *   },
 *   locked = true
 * )
 */
class UrlProcessorHandler extends ProcessorPluginBase implements BuildProcessorInterface, PreQueryProcessorInterface {

  /**
   * The actual url processor used for handing urls.
   *
   * @var \Drupal\facets\UrlProcessor\UrlProcessorInterface
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!isset($configuration['facet']) || !$configuration['facet'] instanceof FacetInterface) {
      throw new InvalidProcessorException("The UrlProcessorHandler doesn't have the required 'facet' in the configuration array.");
    }

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $configuration['facet'];

    /** @var \Drupal\facets\FacetSourceInterface $fs */
    $fs = $facet->getFacetSourceConfig();

    $url_processor_name = $fs->getUrlProcessorName();

    $manager = \Drupal::getContainer()->get('plugin.manager.facets.url_processor');
    $this->processor = $manager->createInstance($url_processor_name, ['facet' => $facet]);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    return $this->processor->buildUrls($facet, $results);
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $this->processor->setActiveItems($facet);
  }

}
