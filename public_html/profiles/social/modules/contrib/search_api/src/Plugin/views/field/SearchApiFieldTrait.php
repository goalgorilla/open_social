<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\search_api\Plugin\views\SearchApiHandlerTrait;
use Drupal\search_api\Utility;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\ResultRow;

/**
 * Provides a trait to use for Search API Views field handlers.
 *
 * Multi-valued field handling is taken from
 * \Drupal\views\Plugin\views\field\PrerenderList.
 */
trait SearchApiFieldTrait {

  use SearchApiHandlerTrait;

  /**
   * Contains the properties needed by this field handler.
   *
   * The array is keyed by datasource ID (which might be NULL) and property
   * path, the values are the combined property paths.
   *
   * @var string[][]
   */
  protected $retrievedProperties = array();

  /**
   * The combined property path of this field.
   *
   * @var string|null
   */
  protected $combinedPropertyPath;

  /**
   * The datasource ID of this field, if any.
   *
   * @var string|null
   */
  protected $datasourceId;

  /**
   * Contains overridden values to be returned on the next getValue() call.
   *
   * The values are keyed by the field given as $field in the call, so that it's
   * possible to return different values based on the field.
   *
   * @var array
   *
   * @see SearchApiFieldTrait::getValue()
   */
  protected $overriddenValues = array();

  /**
   * Index in the current row's field values that is currently displayed.
   *
   * @var int
   *
   * @see SearchApiFieldTrait::getEntity()
   */
  protected $valueIndex = 0;

  /**
   * Determines whether this field can have multiple values.
   *
   * When this can't be reliably determined, the method defaults to TRUE.
   *
   * @return bool
   *   TRUE if this field can have multiple values (or if it couldn't be
   *   determined); FALSE otherwise.
   */
  public function isMultiple() {
    return $this instanceof MultiItemsFieldHandlerInterface;
  }

  /**
   * Information about options for all kinds of purposes will be held here.
   *
   * @code
   * 'option_name' => array(
   *  - 'default' => default value,
   *  - 'contains' => (optional) array of items this contains, with its own
   *      defaults, etc. If contains is set, the default will be ignored and
   *      assumed to be array().
   *  ),
   * @endcode
   *
   * @return array
   *   Returns the options of this handler/plugin.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::defineOptions()
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_item'] = array('default' => FALSE);

    if ($this->isMultiple()) {
      $options['multi_type'] = array('default' => 'separator');
      $options['multi_separator'] = array('default' => ', ');
    }

    return $options;
  }

  /**
   * Provide a form to edit options for this plugin.
   *
   * @param array $form
   *   The existing form structure, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @see \Drupal\views\Plugin\views\ViewsPluginInterface::buildOptionsForm()
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['link_to_item'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Link this field to its item'),
      '#description' => $this->t('Display this field as a link to its original entity or item.'),
      '#default_value' => $this->options['link_to_item'],
    );

    if ($this->isMultiple()) {
      $form['multi_value_settings'] = array(
        '#type' => 'details',
        '#title' => $this->t('Multiple values handling'),
        '#description' => $this->t('If this field contains multiple values for an item, these settings will determine how they are handled.'),
        '#weight' => 80,
      );

      $form['multi_type'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Display type'),
        '#options' => array(
          'ul' => $this->t('Unordered list'),
          'ol' => $this->t('Ordered list'),
          'separator' => $this->t('Simple separator'),
        ),
        '#default_value' => $this->options['multi_type'],
        '#fieldset' => 'multi_value_settings',
        '#weight' => 0,
      );
      $form['multi_separator'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Separator'),
        '#default_value' => $this->options['multi_separator'],
        '#states' => array(
          'visible' => array(
            ':input[name="options[multi_type]"]' => array('value' => 'separator'),
          ),
        ),
        '#fieldset' => 'multi_value_settings',
        '#weight' => 1,
      );
    }
  }

  /**
   * Determines if this field is click sortable.
   *
   * @return bool
   *   The value of "click sortable" from the plugin definition, this defaults
   *   to FALSE if not set.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::clickSortable()
   */
  public function clickSortable() {
    // Almost the same logic as in the parent class, but we want to default to
    // FALSE.
    return !empty($this->definition['click sortable']);
  }

  /**
   * Add anything to the query that we might need to.
   *
   * @see \Drupal\views\Plugin\views\ViewsPluginInterface::query()
   */
  public function query() {
    $combined_property_path = $this->getCombinedPropertyPath();
    $this->addRetrievedProperty($combined_property_path);
    if ($this->options['link_to_item']) {
      $this->addRetrievedProperty("$combined_property_path:_object");
    }
  }

  /**
   * Adds a property to be retrieved.
   *
   * @param string $combined_property_path
   *   The combined property path of the property that should be retrieved.
   *   "_object" can be used as a property name to indicate the loaded object is
   *   required.
   *
   * @return $this
   */
  protected function addRetrievedProperty($combined_property_path) {
    $this->getQuery()->addRetrievedProperty($combined_property_path);

    list($datasource_id, $property_path) = Utility::splitCombinedId($combined_property_path);
    $this->retrievedProperties[$datasource_id][$property_path] = $combined_property_path;
    return $this;
  }

  /**
   * Gets the entity matching the current row and relationship.
   *
   * @param \Drupal\views\ResultRow $values
   *   An object containing all retrieved values.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns the entity matching the values.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::getEntity
   */
  public function getEntity(ResultRow $values) {
    list($datasource_id, $property_path) = Utility::splitCombinedId($this->getCombinedPropertyPath());

    if ($values->search_api_datasource != $datasource_id) {
      return NULL;
    }

    // @todo This will work in most cases, but might fail for multi-valued
    //   fields.
    while (TRUE) {
      if (!empty($values->_relationship_objects[$property_path][$this->valueIndex])) {
        /** @var \Drupal\Core\TypedData\TypedDataInterface $object */
        $object = $values->_relationship_objects[$property_path][$this->valueIndex];
        $value = $object->getValue();
        if ($value instanceof EntityInterface) {
          return $value;
        }
      }

      if (!$property_path) {
        break;
      }
      list($property_path) = Utility::splitPropertyPath($property_path);
    }

    return NULL;
  }

  /**
   * Gets the value that's supposed to be rendered.
   *
   * This API exists so that other modules can easily set the values of the
   * field without having the need to change the render method as well.
   *
   * Overridden here to provide an easy way to let this method return arbitrary
   * values, without actually touching the $values array.
   *
   * @param \Drupal\views\ResultRow $values
   *   An object containing all retrieved values.
   * @param string $field
   *   Optional name of the field where the value is stored.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::getValue()
   */
  public function getValue(ResultRow $values, $field = NULL) {
    if (isset($this->overriddenValues[$field])) {
      return $this->overriddenValues[$field];
    }

    return parent::getValue($values, $field);
  }

  /**
   * Runs before any fields are rendered.
   *
   * This gives the handlers some time to set up before any handler has
   * been rendered.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   An array of all ResultRow objects returned from the query.
   *
   * @see \Drupal\views\Plugin\views\field\FieldHandlerInterface::preRender()
   */
  public function preRender(&$values) {
    // We deal with the properties one by one, always loading the necessary
    // values for any nested properties coming afterwards.
    // @todo This works quite well, but will load each item/entity individually.
    //   Instead, we should exploit the workflow of proceeding by each property
    //   on its own to multi-load as much as possible (maybe even entities of
    //   the same type from different properties).
    // @todo Also, this will unnecessarily load items/entities even if all
    //   required fields are provided in the results. However, to solve this,
    //   expandRequiredProperties() would have to provide more information, or
    //   provide a separate properties list for each row.
    foreach ($this->expandRequiredProperties() as $datasource_id => $properties) {
      foreach ($properties as $property_path => $combined_property_path) {
        // Determine the path of the parent property, and the property key to
        // take from it for this property. If the name is "_object", we just
        // wanted the parent object to be loaded, so we might be done – except
        // when the parent is empty, in which case we wanted to load the
        // original search result, which we haven't done yet.
        list($parent_path, $name) = Utility::splitPropertyPath($property_path);
        if ($parent_path && $name == '_object') {
          continue;
        }

        // Now go through all rows and add the property to them, if necessary.
        foreach ($values as $i => $row) {
          // Bail for rows with the wrong datasource for this property, or for
          // which this field doesn't even apply (which will usually be the
          // same, though).
          if ($datasource_id != $row->search_api_datasource || !$this->isActiveForRow($row)) {
            continue;
          }
          // Check whether there are parent objects present. If no, either load
          // them (in case the parent is the result item itself) or bail.
          if (empty($row->_relationship_objects[$parent_path])) {
            if ($parent_path) {
              continue;
            }
            else {
              $row->_relationship_objects[$parent_path] = array($row->_item->getOriginalObject());
            }
          }

          // If the property key is "_object", we just needed to load the search
          // result item, so we're now done.
          if ($name == '_object') {
            continue;
          }

          // Determine whether we want to set field values for this property on
          // this row. This is the case if the property is one of the explicitly
          // retrieved properties and not yet set on the result row object.
          $set_values = isset($this->retrievedProperties[$datasource_id][$property_path]) && !isset($row->$combined_property_path);

          if (empty($row->_relationship_objects[$property_path])) {
            // Iterate over all parent objects to get their typed data for this
            // property and to extract their values.
            $row->_relationship_objects[$property_path] = array();
            foreach ($row->_relationship_objects[$parent_path] as $parent) {
              // Follow references.
              while ($parent instanceof DataReferenceInterface) {
                $parent = $parent->getTarget();
              }
              // At this point we need the parent to be a complex item,
              // otherwise it can't have any children (and thus, our property
              // can't be present).
              if (!($parent instanceof ComplexDataInterface)) {
                continue;
              }
              // Add the typed data for the property to our relationship objects
              // for this property path. To treat list properties correctly
              // regarding possible child properties, add all the list items
              // individually.
              try {
                $typed_data = $parent->get($name);

                // If the typed data is an entity, check whether the current
                // user can access it.
                $value = $typed_data->getValue();
                if ($value instanceof EntityInterface) {
                  if (!isset($account)) {
                    $account = $this->getQuery()->getAccessAccount();
                  }
                  if (!$value->access('view', $account)) {
                    continue;
                  }
                }

                if ($typed_data instanceof ListInterface) {
                  foreach ($typed_data as $item) {
                    $row->_relationship_objects[$property_path][] = $item;
                  }
                }
                else {
                  $row->_relationship_objects[$property_path][] = $typed_data;
                }
              }
              catch (\InvalidArgumentException $e) {
                // This can easily happen, e.g., when requesting a field that
                // only exists on a different bundle. Unfortunately, there is no
                // ComplexDataInterface::hasProperty() method, so we can only
                // catch and ignore the exception.
              }
            }
          }

          // Initially the array of values, if we want to set them.
          if ($set_values) {
            $row->$combined_property_path = array();
          }
          // Iterate over the typed data objects, extract their values and set
          // the relationship objects for the next iteration of the outer loop
          // over properties.
          foreach ($row->_relationship_objects[$property_path] as $typed_data) {
            if ($set_values) {
              $row->{$combined_property_path}[] = Utility::extractFieldValues($typed_data);
            }
          }
          // If we just set any field values on the result row, clean them up
          // by merging them together (currently it's an array of arrays, but it
          // should be just a flat array).
          if ($set_values && $row->$combined_property_path) {
            $row->$combined_property_path = call_user_func_array('array_merge', $row->$combined_property_path);
          }
        }
      }
    }
  }

  /**
   * Expands the properties to retrieve for this field.
   *
   * The properties are taken from this object's $retrievedProperties property,
   * with all their ancestors also added to the array, with the ancestor
   * properties always ordered before their descendants.
   *
   * This will ensure, when dealing with these properties sequentially, that
   * the parent object necessary to load the "child" property is always already
   * loaded.
   *
   * @return string[][]
   *   The combined property paths to retrieve, keyed by their datasource ID and
   *   property path.
   */
  protected function expandRequiredProperties() {
    $required_properties = array();
    foreach ($this->retrievedProperties as $datasource_id => $properties) {
      foreach (array_keys($properties) as $property_path) {
        $path_to_add = '';
        foreach (explode(':', $property_path) as $component) {
          $path_to_add .= ($path_to_add ? ':' : '') . $component;
          if (!isset($required_properties[$path_to_add])) {
            $required_properties[$datasource_id][$path_to_add] = Utility::createCombinedId($datasource_id, $path_to_add);
          }
        }
      }
    }
    return $required_properties;
  }

  /**
   * Determines whether this field is active for the given row.
   *
   * This is usually determined by the row's datasource.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return bool
   *   TRUE if this field handler might produce output for the given row, FALSE
   *   otherwise.
   */
  protected function isActiveForRow(ResultRow $row) {
    $datasource_id = $this->getDatasourceId();
    return !$datasource_id || $row->search_api_datasource === $datasource_id;
  }

  /**
   * Retrieves the combined property path of this field.
   *
   * @return string
   *   The combined property path.
   */
  public function getCombinedPropertyPath() {
    if (!isset($this->combinedPropertyPath)) {
      // Add the property path of any relationships used to arrive at this
      // field.
      $path = $this->realField;
      $relationships = $this->view->relationship;
      $relationship = $this;
      while (!empty($relationship->options['relationship'])) {
        if (empty($relationships[$relationship->options['relationship']])) {
          break;
        }
        $relationship = $relationships[$relationship->options['relationship']];
        $path = $relationship->realField . ':' . $path;
      }
      $this->combinedPropertyPath = $path;
      // Set the field alias to the combined property path so that Views' code
      // can find the raw values, if necessary.
      $this->field_alias = $path;
    }
    return $this->combinedPropertyPath;
  }

  /**
   * Retrieves the ID of the datasource to which this field belongs.
   *
   * @return string|null
   *   The datasource ID of this field, or NULL if it doesn't belong to a
   *   specific datasource.
   */
  public function getDatasourceId() {
    if (!isset($this->datasourceId)) {
      list($this->datasourceId) = Utility::splitCombinedId($this->getCombinedPropertyPath());
    }
    return $this->datasourceId;
  }

  /**
   * Renders a single item of a row.
   *
   * @param int $count
   *   The index of the item inside the row.
   * @param mixed $item
   *   The item for the field to render.
   *
   * @return string
   *   The rendered output.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface::render_item()
   */
  public function render_item($count, $item) {
    $this->overriddenValues[NULL] = $item['value'];
    $render = $this->render(new ResultRow());
    $this->overriddenValues = array();
    return $render;
  }

  /**
   * Gets an array of items for the field.
   *
   * Items should be associative arrays with, if possible, "value" as the actual
   * displayable value of the item, plus any items that might be found in the
   * "alter" options array for creating links, etc., such as "path", "fragment",
   * "query", etc. Additionally, items that might be turned into tokens should
   * also be in this array.
   *
   * @param \Drupal\views\ResultRow $values
   *   The result row object containing the values.
   *
   * @return array[]
   *   An array of items for the field, with each item being an array itself.
   *
   * @see \Drupal\views\Plugin\views\field\PrerenderList::getItems()
   */
  public function getItems(ResultRow $values) {
    $property_path = $this->getCombinedPropertyPath();
    if (!empty($values->{$property_path})) {
      // Although it's undocumented, the field handler base class assumes items
      // will always be arrays. See #2648012 for documenting this.
      $items = array();
      foreach ((array) $values->{$property_path} as $i => $value) {
        $item = array(
          'value' => $value,
        );

        if ($this->options['link_to_item']) {
          $item['make_link'] = TRUE;
          $item['url'] = $this->getItemUrl($values, $i);
        }

        $items[] = $item;
      }
      return $items;
    }
    return array();
  }

  /**
   * Renders all items in this field together.
   *
   * @param array $items
   *   The items provided by getItems() for a single row.
   *
   * @return string
   *   The rendered items.
   *
   * @see \Drupal\views\Plugin\views\field\PrerenderList::renderItems()
   */
  public function renderItems($items) {
    if (!empty($items)) {
      if ($this->options['multi_type'] == 'separator') {
        $render = array(
          '#type' => 'inline_template',
          '#template' => '{{ items|safe_join(separator) }}',
          '#context' => array(
            'items' => $items,
            'separator' => $this->sanitizeValue($this->options['multi_separator'], 'xss_admin'),
          ),
        );
      }
      else {
        $render = array(
          '#theme' => 'item_list',
          '#items' => $items,
          '#title' => NULL,
          '#list_type' => $this->options['multi_type'],
        );
      }
      return $this->getRenderer()->render($render);
    }
    return '';
  }

  /**
   * Retrieves an alter options array for linking the given value to its item.
   *
   * @param \Drupal\views\ResultRow $row
   *   The Views result row object.
   * @param int $i
   *   The index in this field's values for which the item link should be
   *   retrieved.
   *
   * @return \Drupal\Core\Url|null
   *   The URL for the specified item, or NULL if it couldn't be found.
   */
  protected function getItemUrl(ResultRow $row, $i) {
    $this->valueIndex = $i;
    if ($entity = $this->getEntity($row)) {
      return $entity->toUrl('canonical');
    }

    if (!empty($row->_relationship_objects[NULL][0])) {
      return $this->getIndex()
        ->getDatasource($row->search_api_datasource)
        ->getItemUrl($row->_relationship_objects[NULL][0]);
    }

    return NULL;
  }

}
