<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for event entities.
 *
 * @BackfillHandler(
 *   id = "event",
 *   label = @Translation("Event"),
 *   entity_type = "node",
 *   bundle = "event",
 *   handler_service = "social_event.eda_handler",
 *   handler_method = "eventCreate"
 * )
 */
final class EventBackfillHandler extends BackfillHandlerBase {
}
