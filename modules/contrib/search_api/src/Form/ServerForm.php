<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api\Backend\BackendPluginManager;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\ServerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for creating and editing search servers.
 */
class ServerForm extends EntityForm {

  /**
   * The server storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The backend plugin manager.
   *
   * @var \Drupal\search_api\Backend\BackendPluginManager
   */
  protected $backendPluginManager;

  /**
   * Constructs a ServerForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\search_api\Backend\BackendPluginManager $backend_plugin_manager
   *   The backend plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BackendPluginManager $backend_plugin_manager) {
    $this->storage = $entity_type_manager->getStorage('search_api_server');
    $this->backendPluginManager = $backend_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\search_api\Backend\BackendPluginManager $backend_plugin_manager */
    $backend_plugin_manager = $container->get('plugin.manager.search_api.backend');
    return new static($entity_type_manager, $backend_plugin_manager);
  }

  /**
   * Retrieves the server storage controller.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The server storage controller.
   */
  protected function getStorage() {
    return $this->storage ?: \Drupal::service('entity_type.manager')->getStorage('search_api_server');
  }

  /**
   * Retrieves the backend plugin manager.
   *
   * @return \Drupal\search_api\Backend\BackendPluginManager
   *   The backend plugin manager.
   */
  protected function getBackendPluginManager() {
    return $this->backendPluginManager ?: \Drupal::service('plugin.manager.search_api.backend');
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

    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->getEntity();

    // Set the page title according to whether we are creating or editing the
    // server.
    if ($server->isNew()) {
      $form['#title'] = $this->t('Add search server');
    }
    else {
      $form['#title'] = $this->t('Edit search server %label', array('%label' => $server->label()));
    }

    $this->buildEntityForm($form, $form_state, $server);
    // Skip adding the backend config form if we cleared the server form due to
    // an error.
    if ($form) {
      $this->buildBackendConfigForm($form, $form_state, $server);
    }

    return $form;
  }

  /**
   * Builds the form for the basic server properties.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   The server that is being created or edited.
   */
  public function buildEntityForm(array &$form, FormStateInterface $form_state, ServerInterface $server) {
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Server name'),
      '#description' => $this->t('Enter the displayed name for the server.'),
      '#default_value' => $server->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $server->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => array(
        'exists' => array($this->getStorage(), 'load'),
        'source' => array('name'),
      ),
      '#disabled' => !$server->isNew(),
    );
    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Only enabled servers can index items or execute searches.'),
      '#default_value' => $server->status(),
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Enter a description for the server.'),
      '#default_value' => $server->getDescription(),
    );
    $backend_options = $this->getBackendOptions();
    if ($backend_options) {
      if (count($backend_options) == 1) {
        $server->set('backend', key($backend_options));
      }
      $form['backend'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Backend'),
        '#description' => $this->t('Choose a backend to use for this server.'),
        '#options' => $backend_options,
        '#default_value' => $server->getBackendId(),
        '#required' => TRUE,
        '#ajax' => array(
          'callback' => array(get_class($this), 'buildAjaxBackendConfigForm'),
          'wrapper' => 'search-api-backend-config-form',
          'method' => 'replace',
          'effect' => 'fade',
        ),
      );
    }
    else {
      drupal_set_message($this->t('There are no backend plugins available for the Search API. Please install a <a href=":url">module that provides a backend plugin</a> to proceed.', array(':url' => Url::fromUri('https://www.drupal.org/node/1254698')->toString())), 'error');
      $form = array();
    }
  }

  /**
   * Returns all available backend plugins, as an options list.
   *
   * @return string[]
   *   An associative array mapping backend plugin IDs to their (HTML-escaped)
   *   labels.
   */
  protected function getBackendOptions() {
    $options = array();
    foreach ($this->getBackendPluginManager()->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = Html::escape($plugin_definition['label']);
    }
    return $options;
  }

  /**
   * Builds the backend-specific configuration form.
   *
   * @param \Drupal\search_api\ServerInterface $server
   *   The server that is being created or edited.
   */
  public function buildBackendConfigForm(array &$form, FormStateInterface $form_state, ServerInterface $server) {
    $form['backend_config'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'search-api-backend-config-form',
      ),
      '#tree' => TRUE,
    );

    if ($server->hasValidBackend()) {
      $backend = $server->getBackend();
      if (($backend_form = $backend->buildConfigurationForm(array(), $form_state))) {
        // If the backend plugin changed, notify the user.
        if (!empty($form_state->getValues()['backend'])) {
          drupal_set_message($this->t('Please configure the used backend.'), 'warning');
        }

        // Modify the backend plugin configuration container element.
        $form['backend_config']['#type'] = 'details';
        $form['backend_config']['#title'] = $this->t('Configure %plugin backend', array('%plugin' => $backend->label()));
        $form['backend_config']['#description'] = $backend->getDescription();
        $form['backend_config']['#open'] = TRUE;
        // Attach the backend plugin configuration form.
        $form['backend_config'] += $backend_form;
      }
    }
    // Only notify the user of a missing backend plugin if we're editing an
    // existing server.
    elseif (!$server->isNew()) {
      drupal_set_message($this->t('The backend plugin is missing or invalid.'), 'error');
    }
  }

  /**
   * Handles switching the selected backend plugin.
   */
  public static function buildAjaxBackendConfigForm(array $form, FormStateInterface $form_state) {
    // The work is already done in form(), where we rebuild the entity according
    // to the current form values and then create the backend configuration form
    // based on that. So we just need to return the relevant part of the form
    // here.
    return $form['backend_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->getEntity();

    // Check if the backend plugin changed.
    $backend_id = $server->getBackendId();
    if ($backend_id !== $form_state->getValues()['backend']) {
      // This can only happen during initial server creation, since we don't
      // allow switching the backend afterwards. The user has selected a
      // different backend, so any values entered for the other backend should
      // be discarded.
      // @todo Make sure this works both with and without AJAX.
      $input = $form_state->getUserInput();
      $input['backend_config'] = array();
      $form_state->set('input', $input);
    }
    // Check before loading the backend plugin so we don't throw an exception.
    elseif ($form['backend_config']['#type'] == 'details' && $server->hasValidBackend()) {
      $backend_form_state = new SubFormState($form_state, array('backend_config'));
      $server->getBackend()->validateConfigurationForm($form['backend_config'], $backend_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $this->getEntity();
    // Check before loading the backend plugin so we don't throw an exception.
    if ($form['backend_config']['#type'] == 'details' && $server->hasValidBackend()) {
      $backend_form_state = new SubFormState($form_state, array('backend_config'));
      $server->getBackend()->submitConfigurationForm($form['backend_config'], $backend_form_state);
    }

    return $server;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Only save the server if the form doesn't need to be rebuilt.
    if (!$form_state->isRebuilding()) {
      try {
        $server = $this->getEntity();
        $server->save();
        drupal_set_message($this->t('The server was successfully saved.'));
        $form_state->setRedirect('entity.search_api_server.canonical', array('search_api_server' => $server->id()));
      }
      catch (SearchApiException $e) {
        $form_state->setRebuild();
        watchdog_exception('search_api', $e);
        drupal_set_message($this->t('The server could not be saved.'), 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('search_api.server_delete', array('search_api_server' => $this->getEntity()->id()));
  }

}
