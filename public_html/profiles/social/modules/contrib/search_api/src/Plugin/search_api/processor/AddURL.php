<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "add_url",
 *   label = @Translation("URL field"),
 *   description = @Translation("Adds the item's URL to the indexed data."),
 *   stages = {
 *     "preprocess_index" = -30
 *   },
 *   locked = true,
 *   hidden = true
 * )
 */
class AddURL extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    $definition = array(
      'label' => $this->t('URI'),
      'description' => $this->t('A URI where the item can be accessed'),
      'type' => 'string',
    );
    $properties['search_api_url'] = new DataDefinition($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      $url = $item->getDatasource()->getItemUrl($item->getOriginalObject());
      if ($url) {
        foreach ($this->filterForPropertyPath($item->getFields(), 'search_api_url') as $field) {
          $field->addValue($url->toString());
        }
      }
    }
  }

}
