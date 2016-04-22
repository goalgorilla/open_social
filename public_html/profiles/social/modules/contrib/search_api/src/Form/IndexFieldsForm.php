<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\search_api\DataType\DataTypePluginManager;
use Drupal\search_api\UnsavedConfigurationInterface;
use Drupal\search_api\Utility;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring the fields of a search index.
 */
class IndexFieldsForm extends EntityForm {

  /**
   * The index for which the fields are configured.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $entity;

  /**
   * The shared temporary storage for unsaved search indexes.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The data type plugin manager.
   *
   * @var \Drupal\search_api\DataType\DataTypePluginManager
   */
  protected $dataTypePluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_index_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * Constructs an IndexFieldsForm object.
   *
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The factory for shared temporary storages.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager
   *   The data type plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, DataTypePluginManager $data_type_plugin_manager, RendererInterface $renderer, DateFormatter $date_formatter) {
    $this->tempStore = $temp_store_factory->get('search_api_index');
    $this->entityTypeManager = $entity_type_manager;
    $this->dataTypePluginManager = $data_type_plugin_manager;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $temp_store_factory = $container->get('user.shared_tempstore');
    $entity_type_manager = $container->get('entity_type.manager');
    $data_type_plugin_manager = $container->get('plugin.manager.search_api.data_type');
    $renderer = $container->get('renderer');
    $date_formatter = $container->get('date.formatter');

    return new static($temp_store_factory, $entity_type_manager, $data_type_plugin_manager, $renderer, $date_formatter);
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Retrieves the data type plugin manager.
   *
   * @return \Drupal\search_api\DataType\DataTypePluginManager
   *   The data type plugin manager.
   */
  public function getDataTypePluginManager() {
    return $this->dataTypePluginManager;
  }

  /**
   * Retrieves the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function getRenderer() {
    return $this->renderer;
  }

  /**
   * Retrieves the date formatter.
   *
   * @return \Drupal\Core\Datetime\DateFormatter
   *   The date formatter.
   */
  public function getDateFormatter() {
    return $this->dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $index = $this->entity;

    // Do not allow the form to be cached. See
    // \Drupal\views_ui\ViewEditForm::form().
    $form_state->disableCache();

    if ($index instanceof UnsavedConfigurationInterface && $index->hasChanges()) {
      if ($index->isLocked()) {
        $form['#disabled'] = TRUE;
        $username = array(
          '#theme' => 'username',
          '#account' => $index->getLockOwner($this->entityTypeManager),
        );
        $lock_message_substitutions = array(
          '@user' => $this->getRenderer()->render($username),
          '@age' => $this->dateFormatter->formatTimeDiffSince($index->getLastUpdated()),
          ':url' => $index->toUrl('break-lock-form')->toString(),
        );
        $form['locked'] = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array(
              'index-locked',
              'messages',
              'messages--warning',
            ),
          ),
          '#children' => $this->t('This index is being edited by user @user, and is therefore locked from editing by others. This lock is @age old. Click here to <a href=":url">break this lock</a>.', $lock_message_substitutions),
          '#weight' => -10,
        );
      }
      else {
        $form['changed'] = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array(
              'index-changed',
              'messages',
              'messages--warning',
            ),
          ),
          '#children' => $this->t('You have unsaved changes.'),
          '#weight' => -10,
        );
      }
    }

    // Set an appropriate page title.
    $form['#title'] = $this->t('Manage fields for search index %label', array('%label' => $index->label()));
    $form['#tree'] = TRUE;

    $form['description']['#markup'] = $this->t('<p>The data type of a field determines how it can be used for searching and filtering. The boost is used to give additional weight to certain fields, e.g. titles or tags.</p> <p>For information about the data types available for indexing, see the <a href="@url">data types table</a> at the bottom of the page.</p>', array('@url' => '#search-api-data-types-table'));
    if ($index->hasValidServer()) {
      $arguments = array(
        ':server-url' => $index->getServerInstance()->toUrl('canonical')->toString(),
      );
      $form['description']['#markup'] .= $this->t('<p>Check the <a href=":server-url">server\'s</a> backend class description for details.</p>', $arguments);
    }

    if ($fields = $index->getFieldsByDatasource(NULL)) {
      $form['_general'] = $this->buildFieldsTable($fields);
      $form['_general']['#title'] = $this->t('General');
    }

    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $fields = $index->getFieldsByDatasource($datasource_id);
      $form[$datasource_id] = $this->buildFieldsTable($fields);
      $form[$datasource_id]['#title'] = $datasource->label();
    }

    // Build the data type table.
    $instances = $this->dataTypePluginManager->getInstances();
    $fallback_mapping = Utility::getDataTypeFallbackMapping($index);

    $data_types = array();
    foreach($instances as $name => $type) {
      $data_types[$name] = [
        'label' => $type->label(),
        'description' => $type->getDescription(),
        'fallback' => $type->getFallbackType(),
      ];
    }

    $form['data_type_explanation'] = array(
      '#type' => 'details',
      '#id' => 'search-api-data-types-table',
      '#title' => $this->t('Data types'),
      '#description' => $this->t("The data types which can be used for indexing fields in this index. Whether a type is supported depends on the backend of the index's server. If a type is not supported, the fallback type that will be used instead is shown, too."),
      '#theme' => 'search_api_admin_data_type_table',
      '#data_types' => $data_types,
      '#fallback_mapping' => $fallback_mapping,
    );

    $form['actions'] = $this->actionsElement($form, $form_state);

    return $form;
  }

  /**
   * Builds the form fields for a set of fields.
   *
   * @param \Drupal\search_api\Item\FieldInterface[] $fields
   *   List of fields to display.
   *
   * @return array
   *   The build structure.
   */
  protected function buildFieldsTable(array $fields) {
    $data_type_plugin_manager = $this->getDataTypePluginManager();
    $types = $data_type_plugin_manager->getInstancesOptions();
    $fallback_types = Utility::getDataTypeFallbackMapping($this->entity);

    // If one of the unsupported types is actually used by the index, show a
    // warning.
    if ($fallback_types) {
      foreach ($fields as $field) {
        if (isset($fallback_types[$field->getType()])) {
          drupal_set_message($this->t("Some of the used data types aren't supported by the server's backend. See the <a href=\":url\">data types table</a> to find out which types are supported.", array(':url' => '#search-api-data-types-table')), 'warning');
          break;
        }
      }
    }

    $fulltext_types = array('text');
    // Add all data types with fallback "text" to fulltext types as well.
    foreach ($data_type_plugin_manager->getInstances() as $id => $type) {
      if ($type->getFallbackType() == 'text') {
        $fulltext_types[] = $id;
      }
    }

    $boost_values = array(
      '0.0',
      '0.1',
      '0.2',
      '0.3',
      '0.5',
      '0.8',
      '1.0',
      '2.0',
      '3.0',
      '5.0',
      '8.0',
      '13.0',
      '21.0',
    );
    $boosts = array_combine($boost_values, $boost_values);

    $build = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#theme' => 'search_api_admin_fields_table',
      '#parents' => array(),
    );

    foreach ($fields as $key => $field) {
      $build['fields'][$key]['#access'] = !$field->isHidden();

      $build['fields'][$key]['title']['#plain_text'] = $field->getLabel();
      $build['fields'][$key]['id']['#plain_text'] = $key;
      if ($field->getDescription()) {
        $build['fields'][$key]['description'] = array(
          '#type' => 'value',
          '#value' => $field->getDescription(),
        );
      }

      $css_key = '#edit-fields-' . Html::getId($key);
      $build['fields'][$key]['type'] = array(
        '#type' => 'select',
        '#options' => $types,
        '#default_value' => $field->getType(),
      );
      if ($field->isTypeLocked()) {
        $build['fields'][$key]['type']['#disabled'] = TRUE;
      }

      $build['fields'][$key]['boost'] = array(
        '#type' => 'select',
        '#options' => $boosts,
        '#default_value' => sprintf('%.1f', $field->getBoost()),
      );
      foreach ($fulltext_types as $type) {
        $build['fields'][$key]['boost']['#states']['visible'][$css_key . '-type'][] = array('value' => $type);
      }

      $build['fields'][$key]['remove']['#markup'] = '';
      if (!$field->isIndexedLocked()) {
        $route_parameters = array(
          'search_api_index' => $this->entity->id(),
          'field_id' => $key,
        );
        $build['fields'][$key]['remove'] = array(
          '#type' => 'link',
          '#title' => $this->t('Remove'),
          '#url' => Url::fromRoute('entity.search_api_index.remove_field', $route_parameters),
        );
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => $this->t('Save changes'),
        '#button_type' => 'primary',
        '#submit' => array('::submitForm', '::save'),
      ),
    );
    if ($this->entity instanceof UnsavedConfigurationInterface && $this->entity->hasChanges()) {
      $actions['cancel'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#button_type' => 'danger',
        '#submit' => array('::cancel'),
      );
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index = $this->entity;

    // Store the fields configuration.
    $values = $form_state->getValues();
    $fields = $values['fields'];
    foreach ($index->getFields() as $field_id => $field) {
      if (isset($fields[$field_id])) {
        $field->setType($fields[$field_id]['type']);
        $field->setBoost($fields[$field_id]['boost']);
        $index->addField($field);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $index = $this->entity;
    $changes = TRUE;
    if ($index instanceof UnsavedConfigurationInterface) {
      if ($index->hasChanges()) {
        $index->savePermanent();
      }
      else {
        $index->discardChanges();
        $changes = FALSE;
      }
    }
    else {
      $index->save();
    }

    if ($changes) {
      drupal_set_message($this->t('The changes were successfully saved.'));
      if ($this->entity->isReindexing()) {
        drupal_set_message(t('All content was scheduled for reindexing so the new settings can take effect.'));
      }
    }
    else {
      drupal_set_message($this->t('No values were changed.'));
    }

    return SAVED_UPDATED;
  }

  /**
   * Cancels the editing of the index's fields.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    if ($this->entity instanceof UnsavedConfigurationInterface && $this->entity->hasChanges()) {
      $this->entity->discardChanges();
    }

    $form_state->setRedirectUrl($this->entity->toUrl('canonical'));
  }

}
