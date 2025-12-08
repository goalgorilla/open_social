<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for topic entities.
 *
 * @BackfillHandler(
 *   id = "topic",
 *   label = @Translation("Topic"),
 *   entity_type = "node",
 *   bundle = "topic",
 *   handler_service = "social_topic.eda_handler",
 *   handler_method = "topicCreate"
 * )
 */
final class TopicBackfillHandler extends BackfillHandlerBase {

}
