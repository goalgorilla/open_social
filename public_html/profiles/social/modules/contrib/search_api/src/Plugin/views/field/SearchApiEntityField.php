<?php

namespace Drupal\search_api\Plugin\views\field;

use Drupal\Core\Form\FormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\EntityFieldRenderer;
use Drupal\search_api\Utility;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\Field;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Displays entity field data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("search_api_field")
 */
class SearchApiEntityField extends Field {

  use SearchApiFieldTrait {
    preRender as traitPreRender;
  }

  /**
   * The parent path of this property.
   *
   * NULL for properties of a result item.
   *
   * @var string|null
   */
  protected $parentPath;

  /**
   * Fallback handler for this field, if Field API rendering should not be used.
   *
   * @var \Drupal\views\Plugin\views\field\FieldHandlerInterface
   */
  protected $fallbackHandler;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    // Prepare our fallback handler.
    $fallback_handler_id = !empty($this->definition['fallback_handler']) ? $this->definition['fallback_handler'] : 'search_api';
    $this->fallbackHandler = Views::handlerManager('field')
      ->getHandler($options, $fallback_handler_id);
    $options += array('fallback_options' => array());
    $fallback_options = $options['fallback_options'] + $options;
    $this->fallbackHandler->init($view, $display, $fallback_options);

    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // If we're not using Field API field rendering, just use the query()
    // implementation of the fallback handler.
    if (!$this->options['field_rendering']) {
      $this->fallbackHandler->query();
      return;
    }

    // If we do use Field API rendering, we need the entity object for the
    // parent property.
    $parent_path = $this->getParentPath();
    $property_path = $parent_path ? "$parent_path:_object" : '_object';
    $combined_property_path = Utility::createCombinedId($this->getDatasourceId(), $property_path);
    $this->addRetrievedProperty($combined_property_path);
  }

  /**
   * Retrieves the property path of the parent property.
   *
   * @return string|null
   *   The property path of the parent property.
   */
  protected function getParentPath() {
    if (!isset($this->parentPath)) {
      $combined_property_path = $this->getCombinedPropertyPath();
      list(, $property_path) = Utility::splitCombinedId($combined_property_path);
      list($this->parentPath) = Utility::splitPropertyPath($property_path);
    }

    return $this->parentPath;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['field_rendering'] = array('default' => TRUE);
    $options['fallback_handler'] = array('default' => $this->fallbackHandler->getPluginId());
    $options['fallback_options'] = array('contains' => $this->fallbackHandler->defineOptions());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['field_rendering'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use entity field rendering'),
      '#description' => $this->t("If checked, Drupal's built-in field rendering mechanism will be used for rendering this field's values, which requires the entity to be loaded. If unchecked, a type-specific, entity-independent rendering mechanism will be used."),
      '#default_value' => $this->options['field_rendering'],
    );

    // Wrap the (immediate) parent options in their own field set, to clean up
    // the UI when (un)checking the above checkbox.
    $form['parent_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Render settings'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[field_rendering]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Include the parent options form and move all fields that were added by
    // our direct parent (\Drupal\views\Plugin\views\field\Field) to the
    // "parent_options" fieldset.
    parent::buildOptionsForm($form, $form_state);
    $parent_keys = array(
      'multiple_field_settings',
      'click_sort_column',
      'type',
      'field_api_classes',
      'settings',
    );
    foreach ($parent_keys as $key) {
      if (!empty($form[$key])) {
        $form[$key]['#fieldset'] = 'parent_options';
      }
    }
    // The Core boolean formatter hard-codes the field name to "field_boolean".
    // This breaks the parent class's call of rewriteStatesSelector() for fixing
    // "#states". We therefore apply that behavior again here.
    if (!empty($form['settings'])) {
      FormHelper::rewriteStatesSelector($form['settings'], "fields[field_boolean][settings_edit_form]", 'options');
    }

    // Get the options form for the fallback handler.
    $fallback_form = array();
    $this->fallbackHandler->buildOptionsForm($fallback_form, $form_state);
    // Remove all fields from FieldPluginBase from the fallback form, but leave
    // those in that were only added by our immediate parent,
    // \Drupal\views\Plugin\views\field\Field. (E.g., the "type" option is
    // especially prone to conflicts here.) The others come from the plugin base
    // classes and will be identical, so it would be confusing to include them
    // twice.
    $parent_keys[] = '#pre_render';
    $remove_from_fallback = array_diff_key($form, array_flip($parent_keys));
    $fallback_form = array_diff_key($fallback_form, $remove_from_fallback);
    // Fix the "#states" selectors in the fallback form, and put an additional
    // "#states" directive on it to only be visible for the corresponding
    // "field_rendering" setting.
    if ($fallback_form) {
      FormHelper::rewriteStatesSelector($fallback_form, '"options[', '"options[fallback_options][');
      $form['fallback_options'] = $fallback_form;
      $form['fallback_options']['#type'] = 'fieldset';
      $form['fallback_options']['#title'] = $this->t('Render settings');
      $form['fallback_options']['#states']['visible'][':input[name="options[field_rendering]"]'] = array('checked' => FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    if ($this->options['field_rendering']) {
      $this->traitPreRender($values);
      parent::preRender($values);
    }
    else {
      $this->fallbackHandler->preRender($values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!$this->options['field_rendering']) {
      return $this->fallbackHandler->render($values);
    }
    return parent::render($values);
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    if (!$this->options['field_rendering']) {
      if ($this->fallbackHandler instanceof MultiItemsFieldHandlerInterface) {
        return $this->fallbackHandler->render_item($count, $item);
      }
      return '';
    }
    return parent::render_item($count, $item);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityFieldRenderer() {
    if (!isset($this->entityFieldRenderer)) {
      // This can be invoked during field handler initialization in which case
      // view fields are not set yet.
      if (!empty($this->view->field)) {
        foreach ($this->view->field as $field) {
          // An entity field renderer can handle only a single relationship.
          if (isset($field->entityFieldRenderer)) {
            if ($field->entityFieldRenderer instanceof EntityFieldRenderer && $field->entityFieldRenderer->compatibleWithField($this)) {
              $this->entityFieldRenderer = $field->entityFieldRenderer;
              break;
            }
          }
        }
      }
      if (!isset($this->entityFieldRenderer)) {
        $entity_type = $this->entityManager->getDefinition($this->getEntityType());
        $this->entityFieldRenderer = new EntityFieldRenderer($this->view, $this->relationship, $this->languageManager, $entity_type, $this->entityManager);
        $this->entityFieldRenderer->setDatasourceId($this->getDatasourceId());
      }
    }

    return $this->entityFieldRenderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    if (!$this->options['field_rendering']) {
      if ($this->fallbackHandler instanceof MultiItemsFieldHandlerInterface) {
        return $this->fallbackHandler->getItems($values);
      }
      return array();
    }

    if ($values->search_api_datasource != $this->getDatasourceId()) {
      return array();
    }

    $parent_path = $this->getParentPath();
    if (empty($values->_relationship_objects[$parent_path])) {
      return array();
    }
    $build = array();
    foreach (array_keys($values->_relationship_objects[$parent_path]) as $i) {
      $this->valueIndex = $i;
      $build[] = parent::getItems($values);
    }
    return $build ? call_user_func_array('array_merge', $build) : array();
  }

  /**
   * {@inheritdoc}
   */
  public function renderItems($items) {
    if (!$this->options['field_rendering']) {
      if ($this->fallbackHandler instanceof MultiItemsFieldHandlerInterface) {
        return $this->fallbackHandler->renderItems($items);
      }
      return '';
    }

    return parent::renderItems($items);
  }

}
