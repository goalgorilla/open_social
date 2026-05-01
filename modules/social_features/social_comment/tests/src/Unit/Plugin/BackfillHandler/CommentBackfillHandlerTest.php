<?php

declare(strict_types=1);

namespace Drupal\Tests\social_comment\Unit\Plugin\BackfillHandler;

use Drupal\social_comment\Plugin\BackfillHandler\CommentBackfillHandler;
use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Unit tests for CommentBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_comment\Plugin\BackfillHandler\CommentBackfillHandler
 * @group social_comment
 */
final class CommentBackfillHandlerTest extends CommentBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getPluginId(): string {
    return 'comment';
  }

  /**
   * {@inheritdoc}
   */
  protected function getBundle(): string {
    return 'comment';
  }

  /**
   * {@inheritdoc}
   */
  protected function getLabel(): string {
    return 'Comment';
  }

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): BackfillHandlerBase {
    $plugin_definition = [
      'id' => $this->getPluginId(),
      'label' => $this->getLabel(),
      'entity_type' => 'comment',
      'bundle' => $this->getBundle(),
      'handler_service' => 'social_comment.eda_handler',
      'handler_method' => 'commentCreate',
    ];

    return new CommentBackfillHandler(
      [],
      $this->getPluginId(),
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->accountSwitcher,
      $this->container
    );
  }

}
