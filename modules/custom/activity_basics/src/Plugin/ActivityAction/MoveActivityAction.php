<?php

namespace Drupal\activity_basics\Plugin\ActivityAction;

use Drupal\activity_creator\Plugin\ActivityActionBase;

/**
 * Provides a 'MoveActivityAction' activity action.
 *
 * @ActivityAction(
 *  id = "move_entity_action",
 *  label = @Translation("Action that is triggered when a entity is moved"),
 * )
 */
class MoveActivityAction extends ActivityActionBase {

}
