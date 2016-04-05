<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a processor that transforms the results to show the list item label.
 *
 * @FacetsProcessor(
 *   id = "list_item",
 *   label = @Translation("List item label"),
 *   description = @Translation("Display the list item label instead of the key"),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class ListItemProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  private $configManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigManagerInterface $config_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $field_identifier = $facet->getFieldIdentifier();
    $entity = 'node';

    // Support multiple entities when using Search API.
    if ($facet->getFacetSource() instanceof SearchApiFacetSourceInterface) {
      $index = $facet->getFacetSource()->getIndex();
      $field = $index->getField($field_identifier);

      $entity = str_replace('entity:', '', $field->getDatasourceId());
    }

    $config_entity_name = sprintf('field.storage.%s.%s', $entity, $field_identifier);
    if ($field = $this->configManager->loadConfigEntityByName($config_entity_name)) {
      $function = $field->getSetting('allowed_values_function');

      if (empty($function)) {
        $allowed_values = $field->getSetting('allowed_values');
      }
      else {
        $allowed_values = ${$function}($field);
      }

      if (is_array($allowed_values)) {
        /** @var \Drupal\facets\Result\ResultInterface $result */
        foreach ($results as &$result) {
          if (isset($allowed_values[$result->getRawValue()])) {
            $result->setDisplayValue($allowed_values[$result->getRawValue()]);
          }
        }
      }
    }
    return $results;
  }

}
