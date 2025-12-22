<?php

declare(strict_types=1);

namespace Drupal\social_comment\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for comment entities.
 *
 * @BackfillHandler(
 *   id = "comment",
 *   label = @Translation("Comment"),
 *   entity_type = "comment",
 *   bundle = "comment",
 *   handler_service = "social_comment.eda_handler",
 *   handler_method = "commentCreate"
 * )
 */
final class CommentBackfillHandler extends BackfillHandlerBase {
}
