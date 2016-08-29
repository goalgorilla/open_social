<?php

/**
 * @file
 * Contains \Drupal\entity_access_by_field\Plugin\Condition\Visibility.
 */

namespace Drupal\entity_access_by_field\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
* Provides a 'Visibility' condition to enable a condition based in module selected status.
*
* @Condition(
*   id = "visibility",
*   label = @Translation("Visibility"),
*   context = {
*     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
*   }
* )
*
*/
class Visibility extends ConditionPluginBase {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Creates a new ExampleCondition instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['visibility'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Select a visibility setting'),
      '#default_value' => $this->configuration['visibility'],
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['visibility'] = $form_state->getValue('visibility');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    if (empty($this->configuration['visibility']) && !$this->isNegated()) {
      return TRUE;
    }

//    $node = $this->getContextValue('node');
//
//    $visibility_setting = $node->getEntityAccessFields();
//    if ($referenced_entity->getEntityTypeId() == 'taxonomy_term'
//        && $referenced_entity->id() == $this->configuration['tid']) {
//        return TRUE;
//      }
//    }

    return FALSE;
  }

  /**
   * Provides a human readable summary of the condition's configuration.
   */
  public function summary()
  {
    $tid = $this->configuration['visibility'];

    if (!empty($this->configuration['negate'])) {
      return $this->t('The node is not associated with public settings @visibility.', array('@visibility' => $tid));
    }
    else {
      return $this->t('The node is associated with public settings @visibility.', array('@visibility' => $tid));
    }
 }

}
