<?php

namespace Drupal\social_management_overview\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Social management overview item item annotation object.
 *
 * @see \Drupal\social_management_overview\Plugin\SocialManagementOverviewItemManager
 * @see plugin_api
 *
 * @Annotation
 */
class SocialManagementOverviewItem extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The plugin item weight.
   *
   * @var int
   */
  public $weight;

  /**
   * Parent group.
   *
   * @var string
   */
  public $group;

  /**
   * Route for link.
   *
   * @var string
   */
  public $route;

}
