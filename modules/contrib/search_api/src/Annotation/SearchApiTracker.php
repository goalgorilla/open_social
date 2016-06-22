<?php

namespace Drupal\search_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Search API tracker annotation object.
 *
 * @see \Drupal\search_api\Tracker\TrackerPluginManager
 * @see \Drupal\search_api\Tracker\TrackerInterface
 * @see \Drupal\search_api\Tracker\TrackerPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class SearchApiTracker extends Plugin {

  /**
   * The tracker plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the tracker plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the tracker.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
