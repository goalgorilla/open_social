<?php

declare(strict_types=1);

namespace Drupal\Tests\social_comment\Unit\Plugin\BackfillHandler;

use Drupal\social_comment\Plugin\BackfillHandler\PostCommentBackfillHandler;
use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Unit tests for PostCommentBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_comment\Plugin\BackfillHandler\PostCommentBackfillHandler
 * @group social_comment
 */
final class PostCommentBackfillHandlerTest extends CommentBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getPluginId(): string {
    return 'post_comment';
  }

  /**
   * {@inheritdoc}
   */
  protected function getBundle(): string {
    return 'post_comment';
  }

  /**
   * {@inheritdoc}
   */
  protected function getLabel(): string {
    return 'Post Comment';
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

    return new PostCommentBackfillHandler(
      [],
      $this->getPluginId(),
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->container
    );
  }

}
