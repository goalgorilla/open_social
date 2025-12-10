<?php

declare(strict_types=1);

namespace Drupal\social_like\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for like (vote) entities.
 *
 * @BackfillHandler(
 *   id = "like",
 *   label = @Translation("Like"),
 *   entity_type = "vote",
 *   bundle = "like",
 *   handler_service = "social_like.eda_handler",
 *   handler_method = "likeCreate"
 * )
 */
final class LikeBackfillHandler extends BackfillHandlerBase {

}
