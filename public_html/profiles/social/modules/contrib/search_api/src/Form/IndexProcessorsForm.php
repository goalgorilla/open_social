<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Processor\ProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the processors of a search index.
 */
class IndexProcessorsForm extends EntityForm {

  /**
   * The index being configured.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The datasource manager.
   *
   * @var \Drupal\search_api\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * Constructs an IndexProcessorsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\search_api\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The processor plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ProcessorPluginManager $processor_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->processorPluginManager = $processor_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\search_api\Processor\ProcessorPluginManager $processor_plugin_manager */
    $processor_plugin_manager = $container->get('plugin.manager.search_api.processor');
    return new static($entity_type_manager, $processor_plugin_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    // Retrieve lists of all processors, and the stages and weights they have.
    if (!$form_state->has('processors')) {
      $all_processors = $this->entity->getProcessors(FALSE);
      $sort_processors = function (ProcessorInterface $a, ProcessorInterface $b) {
        return strnatcasecmp($a->label(), $b->label());
      };
      uasort($all_processors, $sort_processors);
    }
    else {
      $all_processors = $form_state->get('processors');
    }

    $stages = $this->processorPluginManager->getProcessingStages();
    $processors_by_stage = array();
    foreach ($stages as $stage => $definition) {
      $processors_by_stage[$stage] = $this->entity->getProcessorsByStage($stage, FALSE);
    }

    if ($this->entity->getServerInstance()) {
      $backend_discouraged_processors = $this->entity->getServerInstance()
        ->getBackend()
        ->getDiscouragedProcessors();

      foreach ($backend_discouraged_processors as $processor_id) {
        // Remove processors from the overview.
        unset($all_processors[$processor_id]);

        // Remove processors from the stages.
        foreach ($processors_by_stage as $stage => $processors) {
          unset($processors_by_stage[$stage][$processor_id]);
        }
      }
    }

    $enabled_processors = $this->entity->getProcessors();

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'search_api/drupal.search_api.index-active-formatters';
    $form['#title'] = $this->t('Manage processors for search index %label', array('%label' => $this->entity->label()));
    $form['description']['#markup'] = '<p>' . $this->t('Configure processors which will pre- and post-process data at index and search time.') . '</p>';

    // Add the list of processors with checkboxes to enable/disable them.
    $form['status'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled'),
      '#attributes' => array(
        'class' => array(
          'search-api-status-wrapper',
        ),
      ),
    );
    foreach ($all_processors as $processor_id => $processor) {
      $clean_css_id = Html::cleanCssIdentifier($processor_id);
      $form['status'][$processor_id] = array(
        '#type' => 'checkbox',
        '#title' => $processor->label(),
        '#default_value' => $processor->isLocked() || !empty($enabled_processors[$processor_id]),
        '#description' => $processor->getDescription(),
        '#attributes' => array(
          'class' => array(
            'search-api-processor-status-' . $clean_css_id,
          ),
          'data-id' => $clean_css_id,
        ),
        '#disabled' => $processor->isLocked(),
        '#access' => !$processor->isHidden(),
      );
    }

    $form['weights'] = array(
      '#type' => 'fieldset',
      '#title' => t('Processor order'),
    );
    // Order enabled processors per stage.
    foreach ($stages as $stage => $description) {
      $form['weights'][$stage] = array(
        '#type' => 'fieldset',
        '#title' => $description['label'],
        '#attributes' => array(
          'class' => array(
            'search-api-stage-wrapper',
            'search-api-stage-wrapper-' . Html::cleanCssIdentifier($stage),
          ),
        ),
      );
      $form['weights'][$stage]['order'] = array(
        '#type' => 'table',
      );
      $form['weights'][$stage]['order']['#tabledrag'][] = array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
      );
    }
    foreach ($processors_by_stage as $stage => $processors) {
      /** @var \Drupal\search_api\Processor\ProcessorInterface $processor */
      foreach ($processors as $processor_id => $processor) {
        $weight = $processor->getDefaultWeight($stage);
        if ($processor->isHidden()) {
          $form['processors'][$processor_id]['weights'][$stage] = array(
            '#type' => 'value',
            '#value' => $weight,
          );
          continue;
        }
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'draggable';
        $form['weights'][$stage]['order'][$processor_id]['#attributes']['class'][] = 'search-api-processor-weight--' . Html::cleanCssIdentifier($processor_id);
        $form['weights'][$stage]['order'][$processor_id]['#weight'] = $weight;
        $form['weights'][$stage]['order'][$processor_id]['label']['#plain_text'] = $processor->label();
        $form['weights'][$stage]['order'][$processor_id]['weight'] = array(
          '#type' => 'weight',
          '#title' => $this->t('Weight for processor %title', array('%title' => $processor->label())),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#parents' => array('processors', $processor_id, 'weights', $stage),
          '#attributes' => array(
            'class' => array(
              'search-api-processor-weight-' . Html::cleanCssIdentifier($stage),
            ),
          ),
        );
      }
    }

    // Add vertical tabs containing the settings for the processors. Tabs for
    // disabled processors are hidden with JS magic, but need to be included in
    // case the processor is enabled.
    $form['processor_settings'] = array(
      '#title' => $this->t('Processor settings'),
      '#type' => 'vertical_tabs',
    );

    foreach ($all_processors as $processor_id => $processor) {
      $sub_keys = array('processors', $processor_id, 'settings');
      $processor_form_state = new SubFormState($form_state, $sub_keys);
      $processor_form = $processor->buildConfigurationForm($form, $processor_form_state);
      if ($processor_form) {
        $form['settings'][$processor_id] = array(
          '#type' => 'details',
          '#title' => $processor->label(),
          '#group' => 'processor_settings',
          '#parents' => array('processors', $processor_id, 'settings'),
          '#attributes' => array(
            'class' => array(
              'search-api-processor-settings-' . Html::cleanCssIdentifier($processor_id),
            ),
          ),
        );
        $form['settings'][$processor_id] += $processor_form;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    /** @var \Drupal\search_api\Processor\ProcessorInterface[] $processors */
    $processors = $this->entity->getProcessors(FALSE);

    // Iterate over all processors that have a form and are enabled.
    foreach ($form['settings'] as $processor_id => $processor_form) {
      if (!empty($values['status'][$processor_id])) {
        $sub_keys = array('processors', $processor_id, 'settings');
        $processor_form_state = new SubFormState($form_state, $sub_keys);
        $processors[$processor_id]->validateConfigurationForm($form['settings'][$processor_id], $processor_form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $new_settings = array();

    $old_processors = $this->entity->getProcessors();
    $old_configurations = array();
    foreach ($old_processors as $id => $processor) {
      $old_configurations[$id] = $processor->getConfiguration();
    }

    // Store processor settings.
    /** @var \Drupal\search_api\Processor\ProcessorInterface $processor */
    $processors = $this->entity->getProcessors(FALSE);
    foreach ($processors as $processor_id => $processor) {
      if (empty($values['status'][$processor_id])) {
        if (isset($old_processors[$processor_id])) {
          $this->entity->removeProcessor($processor_id);
        }
        continue;
      }
      $new_settings[$processor_id] = array(
        'plugin_id' => $processor_id,
        'settings' => array(),
      );
      $processor_values = $values['processors'][$processor_id];
      if (isset($form['settings'][$processor_id])) {
        $sub_keys = array('processors', $processor_id, 'settings');
        $processor_form_state = new SubFormState($form_state, $sub_keys);
        $processor->submitConfigurationForm($form['settings'][$processor_id], $processor_form_state);
        $new_settings[$processor_id]['settings'] = $processor->getConfiguration();
        $new_settings[$processor_id]['settings'] += array('index' => $this->entity);
      }
      if (!empty($processor_values['weights'])) {
        $new_settings[$processor_id]['settings']['weights'] = $processor_values['weights'];
      }
    }

    $new_configurations = array();
    foreach ($new_settings as $plugin_id => $new_processor_settings) {
      /** @var \Drupal\search_api\Processor\ProcessorInterface $new_processor */
      $new_processor_settings['settings']['index'] = $this->entity;
      $new_processor = $this->processorPluginManager->createInstance($plugin_id, $new_processor_settings['settings']);
      $this->entity->addProcessor($new_processor);
      $new_configurations[$plugin_id] = $new_processor->getConfiguration();
    }

    if ($old_configurations != $new_configurations) {
      $form_state->set('processors_changed', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($form_state->get('processors_changed')) {
      $save_status = parent::save($form, $form_state);
      drupal_set_message(t('The indexing workflow was successfully edited.'));
      if ($this->entity->isReindexing()) {
        drupal_set_message(t('All content was scheduled for reindexing so the new settings can take effect.'));
      }
    }
    else {
      drupal_set_message(t('No values were changed.'));
      $save_status = SAVED_UPDATED;
    }

    return $save_status;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // We don't have a "delete" action here.
    unset($actions['delete']);

    return $actions;
  }

}
