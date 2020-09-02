<?php

namespace Drupal\social_content_block;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines a base content block implementation.
 *
 * This abstract class provides a method for inserting additional filters to the
 * base query of the "Custom content list block" custom block.
 *
 * @ingroup social_content_block_api
 */
abstract class ContentBlockBase extends PluginBase implements ContentBlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function supportedSortOptions() : array {
    return [
      'created' => 'Last created',
      'changed' => 'Last updated',
      'most_commented' => 'Most commented',
      'most_liked' => 'Most liked',
      'last_interacted' => 'Last interacted',
    ];
  }

}
