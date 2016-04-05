<?php

namespace Drupal\facets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for creating and editing facets.
 */
class FacetForm extends EntityForm {

  /**
   * The facet storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * The plugin manager for facet sources.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * The plugin manager for processors.
   *
   * @var \Drupal\facets\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * Constructs a FacetForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity manager.
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_plugin_manager
   *   The plugin manager for facet sources.
   * @param \Drupal\facets\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The plugin manager for processors.
   */
  public function __construct(EntityTypeManager $entity_type_manager, FacetSourcePluginManager $facet_source_plugin_manager, ProcessorPluginManager $processor_plugin_manager) {
    $this->facetStorage = $entity_type_manager->getStorage('facets_facet');
    $this->facetSourcePluginManager = $facet_source_plugin_manager;
    $this->processorPluginManager = $processor_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    /** @var \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_plugin_manager */
    $facet_source_plugin_manager = $container->get('plugin.manager.facets.facet_source');

    /** @var \Drupal\facets\Processor\ProcessorPluginManager $processor_plugin_manager */
    $processor_plugin_manager = $container->get('plugin.manager.facets.processor');

    return new static($entity_type_manager, $facet_source_plugin_manager, $processor_plugin_manager);
  }

  /**
   * Retrieves the facet storage controller.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The facet storage controller.
   */
  protected function getFacetStorage() {
    return $this->facetStorage ?: \Drupal::service('entity_type.manager')->getStorage('facets_facet');
  }

  /**
   * Returns the facet source plugin manager.
   *
   * @return \Drupal\facets\FacetSource\FacetSourcePluginManager
   *   The facet source plugin manager.
   */
  protected function getFacetSourcePluginManager() {
    return $this->facetSourcePluginManager ?: \Drupal::service('plugin.manager.facets.facet_source');
  }

  /**
   * Returns the processor plugin manager.
   *
   * @return \Drupal\facets\Processor\ProcessorPluginManager
   *   The processor plugin manager.
   */
  protected function getProcessorPluginManager() {
    return $this->processorPluginManager ?: \Drupal::service('plugin.manager.facets.processor');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // If the form is being rebuilt, rebuild the entity with the current form
    // values.
    if ($form_state->isRebuilding()) {
      $this->entity = $this->buildEntity($form, $form_state);
    }

    $form = parent::form($form, $form_state);

    // Set the page title according to whether we are creating or editing the
    // facet.
    if ($this->getEntity()->isNew()) {
      $form['#title'] = $this->t('Add facet');
    }
    else {
      $form['#title'] = $this->t('Edit facet %label', ['%label' => $this->getEntity()->label()]);
    }

    $this->buildEntityForm($form, $form_state, $this->getEntity());

    return $form;
  }

  /**
   * Builds the form for editing and creating a facet.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facets facet entity that is being created or edited.
   */
  public function buildEntityForm(array &$form, FormStateInterface $form_state, FacetInterface $facet) {

    $facet_sources = [];
    foreach ($this->getFacetSourcePluginManager()->getDefinitions() as $facet_source_id => $definition) {
      $facet_sources[$definition['id']] = !empty($definition['label']) ? $definition['label'] : $facet_source_id;
    }

    if (count($facet_sources) == 0) {
      $form['#markup'] = $this->t('You currently have no facet sources defined. You should start by adding a facet source before creating facets.');
      return;
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facet name'),
      '#description' => $this->t('Enter the displayed name for the facet.'),
      '#default_value' => $facet->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $facet->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this->getFacetStorage(), 'load'],
        'source' => ['name'],
      ],
    ];

    $form['url_alias'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('The name of the facet for usage in URLs'),
      '#default_value' => $facet->getUrlAlias(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this->getFacetStorage(), 'load'],
        'source' => ['name'],
      ],
    ];

    $form['facet_source_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Facet source'),
      '#description' => $this->t('Select the source where this facet can find its fields.'),
      '#options' => $facet_sources,
      '#default_value' => $facet->getFacetSourceId(),
      '#required' => TRUE,
      '#ajax' => [
        'trigger_as' => ['name' => 'facet_source_configure'],
        'callback' => '::buildAjaxFacetSourceConfigForm',
        'wrapper' => 'facets-facet-sources-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];
    $form['facet_source_configs'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'facets-facet-sources-config-form',
      ],
      '#tree' => TRUE,
    ];
    $form['facet_source_configure_button'] = [
      '#type' => 'submit',
      '#name' => 'facet_source_configure',
      '#value' => $this->t('Configure facet source'),
      '#limit_validation_errors' => [['facet_source_id']],
      '#submit' => ['::submitAjaxFacetSourceConfigForm'],
      '#ajax' => [
        'callback' => '::buildAjaxFacetSourceConfigForm',
        'wrapper' => 'facets-facet-sources-config-form',
      ],
      '#attributes' => ['class' => ['js-hide']],
    ];
    $this->buildFacetSourceConfigForm($form, $form_state);

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('The weight of the facet'),
      '#default_value' => $facet->getWeight(),
      '#maxlength' => 4,
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Only enabled facets can be displayed.'),
      '#default_value' => $facet->status(),
    ];
  }

  /**
   * Handles form submissions for the facet source subform.
   */
  public function submitAjaxFacetSourceConfigForm($form, FormStateInterface $form_state) {
    $form_state->setValue('id', NULL);
    $form_state->setRebuild();
  }

  /**
   * Handles changes to the selected facet sources.
   */
  public function buildAjaxFacetSourceConfigForm(array $form, FormStateInterface $form_state) {
    return $form['facet_source_configs'];
  }

  /**
   * Builds the configuration forms for all possible facet sources.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function buildFacetSourceConfigForm(array &$form, FormStateInterface $form_state) {
    $facet_source_id = $this->getEntity()->getFacetSourceId();

    if (!is_null($facet_source_id) && $facet_source_id !== '') {
      /** @var \Drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source */
      $facet_source = $this->getFacetSourcePluginManager()->createInstance($facet_source_id, ['facet' => $this->getEntity()]);

      if ($config_form = $facet_source->buildConfigurationForm([], $form_state)) {
        $form['facet_source_configs'][$facet_source_id]['#type'] = 'container';
        $form['facet_source_configs'][$facet_source_id]['#title'] = $this->t('%plugin settings', ['%plugin' => $facet_source->getPluginDefinition()['label']]);
        $form['facet_source_configs'][$facet_source_id] += $config_form;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $facet_source_id = $form_state->getValue('facet_source_id');
    if (!is_null($facet_source_id) && $facet_source_id !== '') {
      /** @var \Drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source */
      $facet_source = $this->getFacetSourcePluginManager()->createInstance($facet_source_id, ['facet' => $this->getEntity()]);
      $facet_source->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->getEntity();
    $is_new = $facet->isNew();
    if ($is_new) {
      // On facet creation, enable all locked processors by default, using their
      // default settings.
      $stages = $this->getProcessorPluginManager()->getProcessingStages();
      $processors_definitions = $this->getProcessorPluginManager()->getDefinitions();

      foreach ($processors_definitions as $processor_id => $processor) {
        if (isset($processor['locked']) && $processor['locked'] == TRUE) {
          $weights = [];
          foreach ($stages as $stage_id => $stage) {
            if (isset($processor['stages'][$stage_id])) {
              $weights[$stage_id] = $processor['stages'][$stage_id];
            }
          }
          $facet->addProcessor([
            'processor_id' => $processor_id,
            'weights' => $weights,
            'settings' => [],
          ]);
        }
      }

      // Set a default widget for new facets.
      $facet->setWidget('links');

      // Set default empty behaviour.
      $facet->setEmptyBehavior(['behavior' => 'none']);
      $facet->setOnlyVisibleWhenFacetSourceIsVisible(TRUE);
    }

    $facet->setWeight((int) $form_state->getValue('weight'));

    $facet_source_id = $form_state->getValue('facet_source_id');
    if (!is_null($facet_source_id) && $facet_source_id !== '') {
      /** @var \Drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source */
      $facet_source = $this->getFacetSourcePluginManager()->createInstance($facet_source_id, ['facet' => $this->getEntity()]);
      $facet_source->submitConfigurationForm($form, $form_state);
    }
    $facet->save();

    // Ensure that the caching of the view display is disabled, so the search
    // correctly returns the facets. Only apply this when the facet source is
    // actually a view by exploding on :.
    list($type,) = explode(':', $facet_source_id);

    if ($type === 'search_api_views') {
      list(, $view_id, $display) = explode(':', $facet_source_id);
    }

    if (isset($view_id)) {
      $view = Views::getView($view_id);
      $view->setDisplay($display);
      $view->display_handler->overrideOption('cache', ['type' => 'none']);
      $view->save();

      $display_plugin = $view->getDisplay()->getPluginId();
    }

    if ($is_new) {
      if (\Drupal::moduleHandler()->moduleExists('block')) {
        $message = $this->t('Facet %name has been created. Go to the <a href=":block_overview">Block overview page</a> to place the new block in the desired region.', ['%name' => $facet->getName(), ':block_overview' => \Drupal::urlGenerator()->generateFromRoute('block.admin_display')]);
        drupal_set_message($message);
        $form_state->setRedirect('entity.facets_facet.display_form', ['facets_facet' => $facet->id()]);
      }

      if (isset($view_id) && $display_plugin === 'block') {
        $facet->setOnlyVisibleWhenFacetSourceIsVisible(FALSE);
      }
    }
    else {
      drupal_set_message(t('Facet %name has been updated.', ['%name' => $facet->getName()]));
      $form_state->setRedirect('entity.facets_facet.edit_form', ['facets_facet' => $facet->id()]);
    }

    // Clear Drupal cache for blocks to reflect recent changes.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();

    return $facet;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.facets_facet.delete_form', ['facets_facet' => $this->getEntity()->id()]);
  }

}
