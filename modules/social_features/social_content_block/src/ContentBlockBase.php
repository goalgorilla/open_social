<?php

namespace Drupal\social_content_block;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a base content block implementation.
 *
 * This abstract class provides a method for inserting additional filters to the
 * base query of the "Custom content list block" custom block.
 *
 * @ingroup social_content_block_api
 */
abstract class ContentBlockBase extends PluginBase implements ContentBlockPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function supportedSortOptions(): array {
    return [
      'created' => [
        'label' => $this->t('Most recent'),
        'description' => 'Show the newest posts first.',
        'limit' => FALSE,
      ],
      'changed' => [
        'label' => $this->t('Last updated'),
        'limit' => FALSE,
      ],
      'most_commented' => [
        'label' => $this->t('Most commented'),
        'description' => 'See posts with the most comments first.',
      ],
      'last_commented' => [
        'label' => 'Last commented',
        'description' => 'See the last commented nodes first.',
      ],
      'most_liked' => [
        'label' => $this->t('Most liked'),
        'description' => 'See posts with the most likes first.',
      ],
      'last_interacted' => [
        'label' => $this->t('Trending'),
        'description' => 'See the posts people are currently interacting with first.',
        'limit' => FALSE,
      ],
      'trending' => [
        'label' => $this->t('Most popular'),
        'description' => 'Show posts with the highest comments and likes first.',
      ],
    ];
  }

}
