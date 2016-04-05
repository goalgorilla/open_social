<?php

namespace Drupal\facets\UrlProcessor;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A base class for plugins that implements most of the boilerplate.
 */
abstract class UrlProcessorPluginBase extends ProcessorPluginBase implements UrlProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The query string variable.
   *
   * @var string
   *   The query string variable that holds all the facet information.
   */
  protected $filterKey = 'f';

  /**
   * The current request object.
   *
   * @var Request
   *  The current request object.
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function getFilterKey() {
    return $this->filterKey;
  }

  /**
   * Constructs a new instance of the class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object for the current request.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;

    if (!isset($configuration['facet'])) {
      throw new InvalidProcessorException("The url processor doesn't have the required 'facet' in the configuration array.");
    }

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $configuration['facet'];

    /** @var \Drupal\facets\FacetSourceInterface $facet_source_config */
    $facet_source_config = $facet->getFacetSourceConfig();

    $this->filterKey = $facet_source_config->getFilterKey() ?: 'f';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $container->get('request_stack')->getMasterRequest();
    return new static($configuration, $plugin_id, $plugin_definition, $request);
  }

}
