<?php

declare(strict_types=1);

namespace Drupal\social_post\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for post entities (photo bundle only).
 *
 * @BackfillHandler(
 *   id = "post",
 *   label = @Translation("Post"),
 *   entity_type = "post",
 *   bundle = "photo",
 *   handler_service = "social_post.eda_handler",
 *   handler_method = "postCreate"
 * )
 */
final class PostBackfillHandler extends BackfillHandlerBase {

}
