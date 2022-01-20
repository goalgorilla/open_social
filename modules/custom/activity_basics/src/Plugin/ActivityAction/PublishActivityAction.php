<?php

namespace Drupal\activity_basics\Plugin\ActivityAction;

use Drupal\activity_creator\Plugin\ActivityActionBase;

/**
 * Provides a 'PublishActivityAction' activity action.
 *
 * @ActivityAction(
 *   id = "publish_entity_action",
 *   label = @Translation("Action that is triggered when a entity is published"),
 * )
 */
class PublishActivityAction extends ActivityActionBase {

}
