<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility;

/**
 * Adds customized aggregations of existing fields to the index.
 *
 * @SearchApiProcessor(
 *   id = "aggregated_field",
 *   label = @Translation("Aggregated fields"),
 *   description = @Translation("Add customized aggregations of existing fields to the index."),
 *   stages = {
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -25
 *   }
 * )
 */
class AggregatedFields extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'fields' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['description'] = array(
      '#markup' => $this->t('This processor lets you define additional fields that will be added to this index. Each of these new fields will be an aggregation of one or more existing fields.<br />To add a new aggregated field, click the "Add new field" button and then fill out the form.<br />To remove a previously defined field, click the "Remove field" button.<br />You can also change the names or contained fields of existing aggregated fields.'),
    );

    $this->buildFieldsForm($form, $form_state);

    $form['actions']['#type'] = 'actions';
    $form['actions']['add'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add new Field'),
      '#submit' => array(array($this, 'submitAjaxFieldButton')),
      '#limit_validation_errors' => array(),
      '#name' => 'add_aggregation_field',
      '#ajax' => array(
        'callback' => array($this, 'buildAjaxAddFieldButton'),
        'wrapper' => 'search-api-alter-add-aggregation-field-settings',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  // @todo Make sure this works both with and without Javascript.
  public function buildFieldsForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->has('fields')) {
      $form_state->set('fields', $this->configuration['fields']);
    }
    $form_state_fields = $form_state->get('fields');

    // Check if we need to add a new field, or remove one.
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#name'])) {
      drupal_set_message(t('Changes in this form will not be saved until the %button button at the form bottom is clicked.', array('%button' => t('Save'))), 'warning');
      $button_name = $triggering_element['#name'];
      if ($button_name == 'add_aggregation_field') {
        // Increment $i until the corresponding field is not set, then create
        // the field with that number as suffix.
        for ($i = 1; isset($form_state_fields['search_api_aggregation_' . $i]); ++$i) {
        }
        $form_state_fields['search_api_aggregation_' . $i] = array(
          'label' => '',
          'type' => 'union',
          'fields' => array(),
        );
      }
      else {
        // Get the field ID from the button name.
        $field_id = substr($button_name, 25);
        unset($form_state_fields[$field_id]);
      }
      $form_state->set('fields', $form_state_fields);
    }

    // Get index type descriptions.
    $type_descriptions = $this->getTypeDescriptions();
    $types = $this->getTypes();

    // Get the available properties for this index.
    $field_options = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Contained fields'),
      '#options' => array(),
      '#attributes' => array('class' => array('search-api-checkboxes-list')),
      '#required' => TRUE,
    );
    $datasource_labels = $this->getDatasourceLabelPrefixes();
    $properties = $this->getAvailableProperties();
    ksort($properties);
    foreach ($properties as $combined_id => $property) {
      list($datasource_id, $name) = Utility::splitCombinedId($combined_id);
      $field_options['#options'][$combined_id] = $datasource_labels[$datasource_id] . $property->getLabel();
      $field_options[$combined_id] = array(
        '#attributes' => array('title' => $this->t('Machine name: @name', array('@name' => $name))),
        '#description' => $property->getDescription(),
      );
    }

    $form['fields'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'search-api-alter-add-aggregation-field-settings',
      ),
      '#tree' => TRUE,
    );

    foreach ($form_state_fields as $field_id => $field) {
      $new = !$field['label'];
      $form['fields'][$field_id] = array(
        '#type' => 'details',
        '#title' => $new ? $this->t('New field') : $field['label'],
        '#open' => $new,
      );
      $form['fields'][$field_id]['label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('New field name'),
        '#default_value' => $field['label'],
        '#required' => TRUE,
      );
      $form['fields'][$field_id]['type'] = array(
        '#type' => 'select',
        '#title' => $this->t('Aggregation type'),
        '#options' => $types,
        '#default_value' => $field['type'],
        '#required' => TRUE,
      );

      $form['fields'][$field_id]['type_descriptions'] = $type_descriptions;
      foreach (array_keys($types) as $type) {
        // @todo This shouldn't rely on undocumented form array structure.
        $form['fields'][$field_id]['type_descriptions'][$type]['#states']['visible'][':input[name="processors[aggregated_field][settings][fields][' . $field_id . '][type]"]']['value'] = $type;
      }

      // @todo Order checked fields first in list?
      $form['fields'][$field_id]['fields'] = $field_options;
      $form['fields'][$field_id]['fields']['#default_value'] = $field['fields'];

      $form['fields'][$field_id]['actions'] = array(
        '#type' => 'actions',
        'remove' => array(
          '#type' => 'submit',
          '#value' => $this->t('Remove field'),
          '#submit' => array(array($this, 'submitAjaxFieldButton')),
          '#limit_validation_errors' => array(),
          '#name' => 'remove_aggregation_field_' . $field_id,
          '#ajax' => array(
            'callback' => array($this, 'buildAjaxAddFieldButton'),
            'wrapper' => 'search-api-alter-add-aggregation-field-settings',
          ),
        ),
      );
    }
  }

  /**
   * Retrieves form elements with the descriptions of all aggregation types.
   *
   * @return array
   *   An array containing form elements with the descriptions of all
   *   aggregation types.
   */
  protected function getTypeDescriptions() {
    $form = array();
    foreach ($this->getTypes('description') as $type => $description) {
      $form[$type] = array(
        '#type' => 'item',
        '#description' => $description,
      );
    }
    return $form;
  }

  /**
   * Retrieves information about available aggregation types.
   *
   * @param string $info
   *   (optional) One of "label", "type" or "description", to indicate what
   *   values should be returned for the types.
   *
   * @return array
   *   An array of the identifiers of the available types mapped to, depending
   *   on $info, their labels, their data types or their descriptions.
   */
  protected function getTypes($info = 'label') {
    switch ($info) {
      case 'label':
        return array(
          'union' => $this->t('Union'),
          'concat' => $this->t('Concatenation'),
          'sum' => $this->t('Sum'),
          'count' => $this->t('Count'),
          'max' => $this->t('Maximum'),
          'min' => $this->t('Minimum'),
          'first' => $this->t('First'),
        );

      case 'type':
        return array(
          'union' => 'string',
          'concat' => 'string',
          'sum' => 'integer',
          'count' => 'integer',
          'max' => 'integer',
          'min' => 'integer',
          'first' => 'string',
        );

      case 'description':
        return array(
          'union' => $this->t('The Union aggregation does an union operation of all the values of the field. 2 fields with 2 values each become 1 field with 4 values.'),
          'concat' => $this->t('The Concatenation aggregation concatenates the text data of all contained fields.'),
          'sum' => $this->t('The Sum aggregation adds the values of all contained fields numerically.'),
          'count' => $this->t('The Count aggregation takes the total number of contained field values as the aggregated field value.'),
          'max' => $this->t('The Maximum aggregation computes the numerically largest contained field value.'),
          'min' => $this->t('The Minimum aggregation computes the numerically smallest contained field value.'),
          'first' => $this->t('The First aggregation will simply keep the first encountered field value.'),
        );

    }
    return array();
  }

  /**
   * Retrieves label prefixes for this index's datasources.
   *
   * @return string[]
   *   An associative array mapping datasource IDs (and an empty string for
   *   datasource-independent properties) to their label prefixes.
   */
  protected function getDatasourceLabelPrefixes() {
    $prefixes = array(
      NULL => $this->t('General') . ' » ',
    );

    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $prefixes[$datasource_id] = $datasource->label() . ' » ';
    }

    return $prefixes;
  }

  /**
   * Retrieve all properties available on the index.
   *
   * The properties will be keyed by combined ID, which is a combination of the
   * datasource ID and the property path. This is used internally in this class
   * to easily identify any property on the index.
   *
   * @param bool $alter
   *   (optional) Whether to pass the property definitions to the index's
   *   enabled processors for altering before returning them. Must be set to
   *   FALSE when called from within alterProperties(), for obvious reasons.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   All the properties available on the index, keyed by combined ID.
   *
   * @see \Drupal\search_api\Utility::createCombinedId()
   */
  protected function getAvailableProperties($alter = TRUE) {
    $properties = array();

    $datasource_ids = $this->index->getDatasourceIds();
    $datasource_ids[] = NULL;
    foreach ($datasource_ids as $datasource_id) {
      foreach ($this->index->getPropertyDefinitions($datasource_id, $alter) as $property_path => $property) {
        $properties[Utility::createCombinedId($datasource_id, $property_path)] = $property;
      }
    }

    return $properties;
  }

  /**
   * Form submission handler for this processor form's AJAX buttons.
   */
  public static function submitAjaxFieldButton(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Handles adding or removing of aggregated fields via AJAX.
   */
  public static function buildAjaxAddFieldButton(array $form, FormStateInterface $form_state) {
    return $form['settings']['aggregated_field']['fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['fields'])) {
      return;
    }
    foreach ($values['fields'] as $field_id => &$field) {
      if ($field['label'] && !$field['fields']) {
        $error_message = $this->t('You have to select at least one field to aggregate.');
        $form_state->setError($form['fields'][$field_id]['fields'], $error_message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Remove the unnecessary form_state values, so no overhead is stored.
    unset($values['actions']);
    if (!empty($values['fields'])) {
      foreach ($values['fields'] as &$field_definition) {
        unset($field_definition['type_descriptions'], $field_definition['actions']);
        $field_definition['fields'] = array_values(array_filter($field_definition['fields']));
      }
    }
    else {
      $values['fields'] = array();
    }

    $form_state->setValues($values);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    foreach ($this->configuration['fields'] as $field_id => $field_definition) {
      $this->ensureField(NULL, $field_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    if (!$items || empty($this->configuration['fields'])) {
      return;
    }

    $label_not_empty = function (array $field_definition) {
      return !empty($field_definition['label']);
    };
    $aggregated_fields = array_filter($this->configuration['fields'], $label_not_empty);
    if (!$aggregated_fields) {
      return;
    }

    $required_properties_by_datasource = array_fill_keys($this->index->getDatasourceIds(), array());
    $required_properties_by_datasource[NULL] = array();
    foreach ($aggregated_fields as $field_definition) {
      foreach ($field_definition['fields'] as $combined_id) {
        list($datasource_id, $property_path) = Utility::splitCombinedId($combined_id);
        $required_properties_by_datasource[$datasource_id][$property_path] = $combined_id;
      }
    }

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    foreach ($items as $item) {
      // Extract the required properties.
      $property_values = array();
      /** @var \Drupal\search_api\Item\FieldInterface[] $missing_fields */
      $missing_fields = array();
      foreach (array(NULL, $item->getDatasourceId()) as $datasource_id) {
        foreach ($required_properties_by_datasource[$datasource_id] as $property_path => $combined_id) {
          // If a field with the right property path is already set on the item,
          // use it. This might actually make problems in case the values have
          // already been processed in some way, or use a data type that
          // transformed their original value – but on the other hand, it's
          // (currently – see #2575003) the only way to include computed
          // (processor-added) properties here, so it seems like a fair
          // trade-off.
          foreach ($this->filterForPropertyPath($item->getFields(FALSE), $property_path) as $field) {
            if ($field->getDatasourceId() === $datasource_id) {
              $property_values[$combined_id] = $field->getValues();
              continue 2;
            }
          }

          // If the field is not already on the item, we need to extract it. We
          // set our own combined ID as the field identifier as kind of a hack,
          // to easily be able to add the field values to $property_values
          // afterwards.
          if ($datasource_id) {
            $missing_fields[$property_path] = Utility::createField($this->index, $combined_id);
          }
          else {
            // Extracting properties without a datasource is pointless.
            $property_values[$combined_id] = array();
          }
        }
      }
      if ($missing_fields) {
        Utility::extractFields($item->getOriginalObject(), $missing_fields);
        foreach ($missing_fields as $field) {
          $property_values[$field->getFieldIdentifier()] = $field->getValues();
        }
      }

      foreach ($this->configuration['fields'] as $aggregated_field_id => $aggregated_field) {
        $values = array();
        foreach ($aggregated_field['fields'] as $combined_id) {
          if (!empty($property_values[$combined_id])) {
            $values = array_merge($values, $property_values[$combined_id]);
          }
        }

        switch ($aggregated_field['type']) {
          case 'concat':
            $values = array(implode("\n\n", $values));
            break;

          case 'sum':
            $values = array(array_sum($values));
            break;

          case 'count':
            $values = array(count($values));
            break;

          case 'max':
            $values = array(max($values));
            break;

          case 'min':
            $values = array(min($values));
            break;

          case 'first':
            if ($values) {
              $values = array(reset($values));
            }
            break;

        }

        if ($values) {
          foreach ($this->filterForPropertyPath($item->getFields(), $aggregated_field_id) as $field) {
            $field->setValues($values);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }

    $types = $this->getTypes('type');
    if (!empty($this->configuration['fields'])) {
      // Collect all available properties, keyed by combined ID.
      $available_properties = $this->getAvailableProperties(FALSE);
      $datasource_label_prefixes = $this->getDatasourceLabelPrefixes();
      foreach ($this->configuration['fields'] as $aggregated_field_id => $field_definition) {
        $definition = array(
          'label' => $field_definition['label'],
          'description' => $this->fieldDescription($field_definition, $available_properties, $datasource_label_prefixes),
          'type' => $types[$field_definition['type']],
        );
        $properties[$aggregated_field_id] = new DataDefinition($definition);
      }
    }
  }

  /**
   * Creates a description for an aggregated field.
   *
   * @param array $field_definition
   *   The settings of the aggregated field.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   All available properties on the index, keyed by combined ID.
   * @param string[] $datasource_label_prefixes
   *   The label prefixes for all datasources.
   *
   * @return string
   *   A description for the given aggregated field.
   */
  protected function fieldDescription(array $field_definition, array $properties, array $datasource_label_prefixes) {
    $fields = array();
    foreach ($field_definition['fields'] as $combined_id) {
      list($datasource_id, $property_path) = Utility::splitCombinedId($combined_id);
      $label = $property_path;
      if (isset($properties[$combined_id])) {
        $label = $properties[$combined_id]->getLabel();
      }
      $fields[] = $datasource_label_prefixes[$datasource_id] . $label;
    }
    $type = $this->getTypes()[$field_definition['type']];

    $arguments = array('@type' => $type, '@fields' => implode(', ', $fields));
    return $this->t('A @type aggregation of the following fields: @fields.', $arguments);
  }

}
