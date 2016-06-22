<?php

/**
 * @file
 * Contains \Drupal\field_group\Annotation\FieldGroupFormatter.
 */

namespace Drupal\field_group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FieldGroupFormatter annotation object.
 *
 * Formatters handle the display of fieldgroups.
 *
 * Additional annotation keys for formatters can be defined in
 * hook_field_group_formatter_info_alter().
 *
 * @Annotation
 *
 * @see \Drupal\field_group\FieldGroupFormatterPluginManager
 * @see \Drupal\field_group\FieldGroupFormatterInterface
 *
 * @ingroup field_formatter
 */
class FieldGroupFormatter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the formatter type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the formatter type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The name of the fieldgroup formatter class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

  /**
   * An array of contexts the formatter supports (form / view).
   *
   * @var array
   */
  public $supported_contexts = array();

  /**
   * The different format types available for this formatter.
   *
   * @var array
   */
  public $format_types = array();

  /**
   * An integer to determine the weight of this formatter relative to other
   * formatter in the Field UI when selecting a formatter for a given group.
   *
   * @var int optional
   */
  public $weight = NULL;

}
