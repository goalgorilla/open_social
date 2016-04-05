<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a count limit processor.
 *
 * @FacetsProcessor(
 *   id = "count_limit",
 *   label = @Translation("Count limit"),
 *   description = @Translation("Show or hide facets with based on item count."),
 *   stages = {
 *     "build" = 50
 *   }
 * )
 */
class CountLimitProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $processors = $facet->getProcessors();
    $config = $processors[$this->getPluginId()]->getConfiguration();

    $min_count = $config['minimum_items'];
    $max_count = $config['maximum_items'];
    /** @var \Drupal\facets\Result\Result $result */
    foreach ($results as $id => $result) {
      if (($min_count && $result->getCount() < $min_count) ||
        ($max_count && $result->getCount() > $max_count)) {
        unset($results[$id]);
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

    $build['minimum_items'] = array(
      '#title' => $this->t('Minimum items'),
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => !is_null($config) ? $config->getConfiguration()['minimum_items'] : $this->defaultConfiguration()['minimum_items'],
      '#description' => $this->t('Hide block if the facet contains less than this number of results.'),
    );

    $max_default_value = !is_null($config) ? $config->getConfiguration()['maximum_items'] : $this->defaultConfiguration()['maximum_items'];
    $build['maximum_items'] = array(
      '#title' => $this->t('Maximum items'),
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $max_default_value ? $max_default_value : '',
      '#description' => $this->t('Hide block if the facet contains more than this number of results.'),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $values = $form_state->getValues();
    if (!empty($values['maximum_items']) && !empty($values['minimum_items']) && $values['maximum_items'] <= $values['minimum_items']) {
      $form_state->setErrorByName('maximum_items', t('If both minimum and maximum item count are specified, the maximum item count should be higher than the minimum item count.'));
    }
    return parent::validateConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'minimum_items' => 1,
      'maximum_items' => 0,
    );
  }

}
