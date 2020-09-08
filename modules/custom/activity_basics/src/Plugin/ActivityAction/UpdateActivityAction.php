<?php

namespace Drupal\activity_basics\Plugin\ActivityAction;

use Drupal\activity_creator\Plugin\ActivityActionBase;

/**
 * Provides a 'MoveActivityAction' activity action.
 *
 * @ActivityAction(
 *  id = "update_entity_action",
 *  label = @Translation("Action that is triggered when a entity is updated"),
 * )
 */
class UpdateActivityAction extends ActivityActionBase {

}
