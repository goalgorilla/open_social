<?php

declare(strict_types=1);

namespace Drupal\social_comment\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for post_comment entities.
 *
 * @BackfillHandler(
 *   id = "post_comment",
 *   label = @Translation("Post Comment"),
 *   entity_type = "comment",
 *   bundle = "post_comment",
 *   handler_service = "social_comment.eda_handler",
 *   handler_method = "commentCreate"
 * )
 */
final class PostCommentBackfillHandler extends BackfillHandlerBase {

}
