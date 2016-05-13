<?php

/**
 * @file
 * Contains \Drupal\group\Annotation\GroupContentEnabler.
 */

namespace Drupal\group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GroupContentEnabler annotation object.
 *
 * Plugin Namespace: Plugin\GroupContentEnabler
 *
 * For a working example, see
 * \Drupal\group\Plugin\GroupContentEnabler\GroupMembership
 *
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see \Drupal\group\Plugin\GroupContentEnablerManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupContentEnabler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the GroupContentEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the GroupContentEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The ID of the entity type you want to enable as group content.
   *
   * @var string
   */
  public $entity_type_id;

  /**
   * (optional) The bundle of the entity type you want to enable as group content.
   *
   * Do not specify if your plugin manages all bundles.
   *
   * @var string|false
   */
  public $entity_bundle = FALSE;

  /**
   * (optional) The key to use in automatically generated paths.
   *
   * This will be used by the base content enabler plugin to generate common
   * paths with. If left blank, no paths will be generated for you and you have
   * to specify them yourself in GroupContentEnablerInterface::getPaths().
   *
   * @var string
   *
   * @see \Drupal\group\Plugin\GroupContentEnablerBase::getPaths()
   */
  public $pathKey;

  /**
   * (optional) Whether this plugin is always on.
   *
   * @var bool
   */
  public $enforced = FALSE;

}
