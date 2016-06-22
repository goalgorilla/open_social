<?php

namespace Drupal\search_api\Tracker;

use Drupal\search_api\Plugin\IndexPluginBase;

/**
 * Defines a base class from which other tracker classes may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_tracker_info_alter(). The definition includes the following
 * keys:
 * - id: The unique, system-wide identifier of the tracker class.
 * - label: The human-readable name of the tracker class, translated.
 * - description: A human-readable description for the tracker class,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiTracker(
 *   id = "my_tracker",
 *   label = @Translation("My tracker"),
 *   description = @Translation("Simple tracking system.")
 * )
 * @endcode
 *
 * @see \Drupal\search_api\Annotation\SearchApiTracker
 * @see \Drupal\search_api\Tracker\TrackerPluginManager
 * @see \Drupal\search_api\Tracker\TrackerInterface
 * @see plugin_api
 */
abstract class TrackerPluginBase extends IndexPluginBase implements TrackerInterface {

  // @todo Move some of the methods from
  //   \Drupal\search_api\Plugin\search_api\tracker\Basic to here?

}
