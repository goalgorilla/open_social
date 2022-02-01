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
      'created' => [
        'label' => 'Most recent',
        'description' => 'Show the newest posts first.',
        'limit' => FALSE,
      ],
      'changed' => [
        'label' => 'Last updated',
        'limit' => FALSE,
      ],
      'most_commented' => [
        'label' => 'Most commented',
        'description' => 'See posts with the most comments first.',
      ],
      'last_commented' => [
        'label' => 'Last commented',
        'description' => 'See the last commented nodes first.',
      ],
      'most_liked' => [
        'label' => 'Most liked',
        'description' => 'See posts with the most likes first.',
      ],
      'last_interacted' => [
        'label' => 'Trending',
        'description' => 'See the posts people are currently interacting with first.',
        'limit' => FALSE,
      ],
      'trending' => [
        'label' => 'Most popular',
        'description' => 'Show posts with the highest comments and likes first.',
      ],
    ];
  }

}
