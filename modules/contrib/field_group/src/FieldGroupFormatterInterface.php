<?php

/**
 * @file
 * Contains \Drupal\field_group\FieldGroupFormatterInterface.
 */

namespace Drupal\field_group;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface definition for fieldgroup formatter plugins.
 *
 * @ingroup field_group_formatter
 */
interface FieldGroupFormatterInterface extends PluginInspectionInterface {

  /**
   * Allows the field group formatter to manipulate the field group array and attach the formatters rendering element.
   *
   * @param array $element
   *   The field group render array.
   * @param object $rendering_object
   *   The object / entity beïng rendered.
   */
  public function preRender(&$element, $rendering_object);

  /**
   * Returns a form to configure settings for the formatter.
   *
   * Invoked in field_group_field_ui_display_form_alter to allow
   * administrators to configure the formatter. The field_group module takes care
   * of handling submitted form values.
   *
   * @return array
   *   The form elements for the formatter settings.
   */
  public function settingsForm();

  /**
   * Returns a short summary for the current formatter settings.
   *
   * If an empty result is returned, a UI can still be provided to display
   * a settings form in case the formatter has configurable settings.
   *
   * @return array()
   *   A short summary of the formatter settings.
   */
  public function settingsSummary();

  /**
   * Defines the default settings for this plugin.
   *
   * @param string $context
   *   The context to get the default settings for.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultContextSettings($context);

}
