<?php

/**
 * @file
 * Contains \Drupal\field_group\FieldGroupFormatterBase.
 */

namespace Drupal\field_group;

use Drupal\Core\Field\PluginSettingsBase;

/**
 * Base class for 'Fieldgroup formatter' plugin implementations.
 *
 * @ingroup field_group_formatter
 */
abstract class FieldGroupFormatterBase extends PluginSettingsBase implements FieldGroupFormatterInterface {

  /**
   * The group this formatter needs to render.
   * @var stdClass
   */
  protected $group;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The label display setting.
   *
   * @var string
   */
  protected $label;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * The context mode.
   *
   * @var string
   */
  protected $context;

  /**
   * Constructs a FieldGroupFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param $group
   *   The group object.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   */
  public function __construct($plugin_id, $plugin_definition, $group, array $settings, $label) {
    parent::__construct(array(), $plugin_id, $plugin_definition);

    $this->group = $group;
    $this->settings = $settings;
    $this->label = $label;
    $this->context = $group->context;
  }

  /**
   * Get the current label.
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = array();
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Field group label'),
      '#default_value' => $this->label,
      '#weight' => -5,
    );

    $form['id'] = array(
      '#title' => t('ID'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('id'),
      '#weight' => 10,
      '#element_validate' => array('field_group_validate_id'),
    );

    $form['classes'] = array(
      '#title' => t('Extra CSS classes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('classes'),
      '#weight' => 11,
      '#element_validate' => array('field_group_validate_css_class'),
    );

    $form['#validate'] = array('field_group_format_settings_form_validate');

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = array();

    if ($this->getSetting('formatter')) {
      $summary[] = $this->pluginDefinition['label'] . ': ' . $this->getSetting('formatter');
    }

    if ($this->getSetting('id')) {
      $summary[] = $this->t('Id: @id', array('@id' => $this->getSetting('id')));
    }

    if ($this->getSetting('classes')) {
      $summary[] = \Drupal::translation()->translate('Extra CSS classes: @classes', array('@classes' => $this->getSetting('classes')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::defaultContextSettings('view');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return array(
      'classes' => '',
      'id' => '',
    );
  }

  /**
   * Get the classes to add to the group.
   */
  protected function getClasses() {

    $classes = array();
    // Add a required-fields class to trigger the js.
    if ($this->getSetting('required_fields')) {
      $classes[] = 'required-fields';
    }

    if ($this->getSetting('classes')) {
      $classes = array_merge($classes, explode(' ', trim($this->getSetting('classes'))));
    }

    return $classes;
  }

}
