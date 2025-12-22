<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Plugin\BackfillHandler;

use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for flexible group entities.
 *
 * @BackfillHandler(
 *   id = "group",
 *   label = @Translation("Group"),
 *   entity_type = "group",
 *   bundle = "flexible_group",
 *   handler_service = "social_group_flexible_group.eda_handler",
 *   handler_method = "groupCreate"
 * )
 */
final class GroupBackfillHandler extends BackfillHandlerBase {
}
