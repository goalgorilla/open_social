<?php

namespace Drupal\social_management_overview\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Social management overview group item annotation object.
 *
 * @see \Drupal\social_management_overview\Plugin\SocialManagementOverviewGroupManager
 * @see plugin_api
 *
 * @Annotation
 */
class SocialManagementOverviewGroup extends Plugin {


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
   * The group weight.
   *
   * @var int
   */
  public $weight;

}
