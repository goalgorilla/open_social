<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor that excludes specified items.
 *
 * @FacetsProcessor(
 *   id = "exclude_specified_items",
 *   label = @Translation("Exclude specified items"),
 *   description = @Translation("Excludes items by node id or title."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class ExcludeSpecifiedItemsProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $processors = $facet->getProcessors();
    $config = $processors[$this->getPluginId()];

    /** @var \Drupal\facets\Result\ResultInterface $result */
    $exclude_item = $config->getConfiguration()['exclude'];
    foreach ($results as $id => $result) {
      if ($config->getConfiguration()['regex']) {
        $matcher = '/' . trim(str_replace('/', '\\/', $exclude_item)) . '/';
        if (preg_match($matcher, $result->getRawValue()) || preg_match($matcher, $result->getDisplayValue())) {
          unset($results[$id]);
        }
      }
      else {
        if ($result->getRawValue() == $exclude_item || $result->getDisplayValue() == $exclude_item) {
          unset($results[$id]);
        }
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $processors = $facet->getProcessors();
    $config = isset($processors[$this->getPluginId()]) ? $processors[$this->getPluginId()] : NULL;

    $build['exclude'] = [
      '#title' => $this->t('Exclude items'),
      '#type' => 'textfield',
      '#default_value' => !is_null($config) ? $config->getConfiguration()['exclude'] : $this->defaultConfiguration()['exclude'],
      '#description' => $this->t("Comma separated list of titles or values that should be excluded, matching either an item's title or value."),
    ];
    $build['regex'] = [
      '#title' => $this->t('Regular expressions used'),
      '#type' => 'checkbox',
      '#default_value' => !is_null($config) ? $config->getConfiguration()['regex'] : $this->defaultConfiguration()['regex'],
      '#description' => $this->t('Interpret each exclude list item as a regular expression pattern.<br /><small>(Slashes are escaped automatically, patterns using a comma can be wrapped in "double quotes", and if such a pattern uses double quotes itself, just make them double-double-quotes (""))</small>.'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'exclude' => '',
      'regex' => 0,
    ];
  }

}
