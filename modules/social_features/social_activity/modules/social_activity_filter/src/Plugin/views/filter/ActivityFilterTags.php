<?php

namespace Drupal\social_activity_filter\Plugin\views\filter;

use Drupal\social_activity\Plugin\views\filter\ActivityFilterTags as BaseActivityFilterTags;

/**
 * Filters activity by the taxonomy tags in the stream block.
 *
 * @deprecated in social:13.0.0 and is removed from social:14.0.0. Use
 *   \Drupal\social_activity\Plugin\views\filter\ActivityFilterTags instead.
 * @see https://www.drupal.org/project/social/issues/3559851
 *
 * @ingroup views_filter_handlers
 */
class ActivityFilterTags extends BaseActivityFilterTags {

  /**
   * Constructs an ActivityFilterTags object.
   *
   * @param mixed ...$args
   *   The constructor arguments to pass to the parent class.
   */
  public function __construct(mixed ...$args) {
    @trigger_error('Drupal\social_activity_filter\Plugin\views\filter\ActivityFilterTags is deprecated in social:13.0.0 and is removed from social:14.0.0. Use \Drupal\social_activity\Plugin\views\filter\ActivityFilterTags instead. See https://www.drupal.org/project/social/issues/3559851', E_USER_DEPRECATED);
    parent::__construct(...$args);
  }

}
