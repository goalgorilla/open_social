<?php

namespace Drupal\search_api\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\Url;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\DataType\DataTypePluginManager;
use Drupal\search_api\UnsavedConfigurationInterface;
use Drupal\search_api\Utility;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding fields to a search index.
 */
class IndexAddFieldsForm extends EntityForm {

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
   * The entity manager.
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
   * The parameters of the current page request.
   *
   * @var array
   */
  protected $parameters;

  /**
   * List of types that failed to map to a Search API type.
   *
   * The unknown types are the keys and map to arrays of fields that were
   * ignored because they are of this type.
   *
   * @var string[][]
   */
  protected $unmappedFields = array();

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_index_add_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * Constructs an IndexAddFieldsForm object.
   *
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The factory for shared temporary storages.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager
   *   The data type plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter.
   * @param array $parameters
   *   The parameters for this page request.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, DataTypePluginManager $data_type_plugin_manager, RendererInterface $renderer, DateFormatter $date_formatter, array $parameters) {
    $this->tempStore = $temp_store_factory->get('search_api_index');
    $this->entityTypeManager = $entity_type_manager;
    $this->dataTypePluginManager = $data_type_plugin_manager;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
    $this->parameters = $parameters;
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
    $request_stack = $container->get('request_stack');
    $parameters = $request_stack->getCurrentRequest()->query->all();

    return new static($temp_store_factory, $entity_type_manager, $data_type_plugin_manager, $renderer, $date_formatter, $parameters);
  }

  /**
   * Retrieves the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity manager.
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
   * Retrieves a single page request parameter.
   *
   * @param string $name
   *   The name of the parameter.
   * @param string|null $default
   *   The value to return if the parameter isn't present.
   *
   * @return string|null
   *   The parameter value.
   */
  public function getParameter($name, $default = NULL) {
    return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
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
    }

    $args['%index'] = $index->label();
    $form['#title'] = $this->t('Add fields to index %index', $args);

    $form['properties'] = array(
      '#theme' => 'search_api_form_item_list',
    );
    $datasources = array(
      '' => NULL,
    );
    $datasources += $this->entity->getDatasources();
    foreach ($datasources as $datasource) {
      $form['properties'][] = $this->getDatasourceListItem($datasource);
    }

    // Log any unmapped types that were encountered.
    if ($this->unmappedFields) {
      $unmapped_types = array();
      foreach ($this->unmappedFields as $type => $fields) {
        $unmapped_types[] = implode(', ', $fields) . ' (' . new FormattableMarkup('type @type', array('@type' => $type)) . ')';
      }
      $vars['@fields'] = implode('; ', $unmapped_types);
      $vars['%index'] = $this->entity->label();
      \Drupal::logger('search_api')
        ->warning('Warning while retrieving available fields for index %index: could not find a type mapping for the following fields: @fields.', $vars);
    }

    $form['actions'] = $this->actionsElement($form, $form_state);

    return $form;
  }

  /**
   * Creates a list item for one datasource.
   *
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   The datasource, or NULL for general properties.
   *
   * @return array
   *   A render array representing the given datasource and, possibly, its
   *   attached properties.
   */
  protected function getDatasourceListItem(DatasourceInterface $datasource = NULL) {
    $item = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
    );

    $active = FALSE;
    $datasource_id = $datasource ? $datasource->getPluginId() : '';
    $active_datasource = $this->getParameter('datasource');
    if (isset($active_datasource)) {
      $active = $active_datasource == $datasource_id;
    }

    $url = $this->entity->toUrl('add-fields');
    if ($active) {
      $expand_link = array(
        '#type' => 'link',
        '#title' => '(-) ',
        '#url' => $url,
      );
    }
    else {
      $url->setOption('query', array('datasource' => $datasource_id));
      $expand_link = array(
        '#type' => 'link',
        '#title' => '(+) ',
        '#url' => $url,
      );
    }
    $item['expand_link'] = $expand_link;

    $label = $datasource ? Html::escape($datasource->label()) : $this->t('General');
    $item['label']['#markup'] = $label;

    if ($active) {
      $properties = $this->entity->getPropertyDefinitions($datasource_id ?: NULL);
      if ($properties) {
        $active_property_path = $this->getParameter('property_path', '');
        $base_url = clone $url;
        $base_url->setOption('query', array('datasource' => $datasource_id));
        $item['properties'] = $this->getPropertiesList($properties, $active_property_path, $base_url);
      }
    }

    return $item;
  }

  /**
   * Creates an items list for the given properties.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The property definitions, keyed by their property names.
   * @param string $active_property_path
   *   The relative property path to the active property.
   * @param \Drupal\Core\Url $base_url
   *   The base URL to which property path parameters should be added for
   *   the navigation links.
   * @param string $parent_path
   *   (optional) The common property path prefix of the given properties.
   * @param string $label_prefix
   *   (optional) The prefix to use for the labels of created fields.
   *
   * @return array
   *   A render array representing the given properties and, possibly, nested
   *   properties.
   */
  protected function getPropertiesList(array $properties, $active_property_path, Url $base_url, $parent_path = '', $label_prefix = '') {
    $list = array(
      '#theme' => 'search_api_form_item_list',
    );

    $active_item = '';
    if ($active_property_path) {
      list($active_item, $active_property_path) = explode(':', $active_property_path, 2) + array(1 => '');
    }

    $type_mapping = Utility::getFieldTypeMapping();

    $query_base = $base_url->getOption('query');
    foreach ($properties as $key => $property) {
      $this_path = $parent_path ? $parent_path . ':' : '';
      $this_path .= $key;

      $label = $property->getLabel();
      $property = Utility::getInnerProperty($property);

      $can_be_indexed = TRUE;
      $nested_properties = array();
      $parent_child_type = NULL;
      if ($property instanceof ComplexDataDefinitionInterface) {
        $can_be_indexed = FALSE;
        $nested_properties = $property->getPropertyDefinitions();
        $main_property = $property->getMainPropertyName();
        if ($main_property && isset($nested_properties[$main_property])) {
          $parent_child_type = $property->getDataType() . '.';
          $property = $nested_properties[$main_property];
          $parent_child_type .= $property->getDataType();
          unset($nested_properties[$main_property]);
          $can_be_indexed = TRUE;
        }

        // Don't add the additional 'entity' property for entity reference
        // fields which don't target a content entity type.
        $allowed_properties = array(
          'field_item:entity_reference',
          'field_item:image',
          'field_item:file',
        );
        if ($property instanceof FieldItemDataDefinition && in_array($property->getDataType(), $allowed_properties)) {
          $entity_type = $this->getEntityTypeManager()
            ->getDefinition($property->getSetting('target_type'));
          if (!$entity_type->isSubclassOf('Drupal\Core\Entity\ContentEntityInterface')) {
            unset($nested_properties['entity']);
          }
        }
      }

      // Don't allow indexing of properties with unmapped types. Also, prefer
      // a "parent.child" type mapping (taking into account the parent property
      // for, e.g., text fields).
      $type = $property->getDataType();
      if ($parent_child_type && !empty($type_mapping[$parent_child_type])) {
        $type = $parent_child_type;
      }
      elseif (empty($type_mapping[$type])) {
        // Remember the type only if it was not explicitly mapped to FALSE.
        if (!isset($type_mapping[$type])) {
          $this->unmappedFields[$type][] = $label_prefix . $label;
        }
        $can_be_indexed = FALSE;
      }

      // If the property can neither be expanded nor indexed, just skip it.
      if (!($nested_properties || $can_be_indexed)) {
        continue;
      }

      $nested_list = array();
      $expand_link = array();
      if ($nested_properties) {
        if ($key == $active_item) {
          $link_url = clone $base_url;
          $query_base['property_path'] = $parent_path;
          $link_url->setOption('query', $query_base);
          $expand_link = array(
            '#type' => 'link',
            '#title' => '(-) ',
            '#url' => $link_url,
          );

          $nested_list = $this->getPropertiesList($nested_properties, $active_property_path, $base_url, $this_path, $label_prefix . $label . ' » ');
        }
        else {
          $link_url = clone $base_url;
          $query_base['property_path'] = $this_path;
          $link_url->setOption('query', $query_base);
          $expand_link = array(
            '#type' => 'link',
            '#title' => '(+) ',
            '#url' => $link_url,
          );
        }
      }

      $item = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('container-inline'),
        ),
      );

      if ($expand_link) {
        $item['expand_link'] = $expand_link;
      }

      $item['label']['#markup'] = Html::escape($label) . ' ';

      if ($can_be_indexed) {
        $item['add'] = array(
          '#type' => 'submit',
          '#name' => Utility::createCombinedId($this->getParameter('datasource') ?: NULL, $this_path),
          '#value' => $this->t('Add'),
          '#submit' => array('::addField', '::save'),
          '#property' => $property,
          '#prefixed_label' => $label_prefix . $label,
          '#data_type' => $type_mapping[$type],
        );
      }

      if ($nested_list) {
        $item['properties'] = $nested_list;
      }

      $list[] = $item;
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return array(
      'done' => array(
        '#type' => 'link',
        '#title' => $this->t('Done'),
        '#url' => $this->entity->toUrl('fields'),
        '#attributes' => array(
          'class' => array('button'),
        ),
      ),
    );
  }

  /**
   * Form submission handler for adding a new field to the index.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addField(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if (!$button) {
      return;
    }

    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $property */
    $property = $button['#property'];

    list($datasource_id, $property_path) = Utility::splitCombinedId($button['#name']);
    $field = Utility::createFieldFromProperty($this->entity, $property, $datasource_id, $property_path, NULL, $button['#data_type']);
    $field->setLabel($button['#prefixed_label']);
    $this->entity->addField($field);

    $args['%label'] = $field->getLabel();
    drupal_set_message($this->t('Field %label was added to the index.', $args));
  }

}
