<?php

namespace Drupal\social_activity_filter\Plugin\views\display;

use Drupal\social_activity\Plugin\views\display\FilterBlock as BaseFilterBlock;

/**
 * The plugin that handles a block.
 *
 * @deprecated in social:13.0.0 and is removed from social:14.0.0. Use
 *   \Drupal\social_activity\Plugin\views\display\FilterBlock instead.
 * @see https://www.drupal.org/project/social/issues/3559851
 *
 * @ingroup views_display_plugins
 */
class FilterBlock extends BaseFilterBlock {

  /**
   * Constructs a FilterBlock object.
   *
   * @param mixed ...$args
   *   The constructor arguments to pass to the parent class.
   */
  public function __construct(mixed ...$args) {
    @trigger_error('Drupal\social_activity_filter\Plugin\views\display\FilterBlock is deprecated in social:13.0.0 and is removed from social:14.0.0. Use \Drupal\social_activity\Plugin\views\display\FilterBlock instead. See https://www.drupal.org/project/social/issues/3559851', E_USER_DEPRECATED);
    parent::__construct(...$args);
  }

}
