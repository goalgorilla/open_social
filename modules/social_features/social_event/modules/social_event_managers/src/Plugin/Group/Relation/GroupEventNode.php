<?php

namespace Drupal\social_event_managers\Plugin\Group\Relation;

use Drupal\gnode\Plugin\Group\Relation\GroupNode

/**
 * Provides a content enabler for Event nodes.
 *
 * @GroupRelationType(
 *   id = "group_node",
 *   label = @Translation("Group node events"),
 *   description = @Translation("Adds event nodes to groups both publicly and privately."),
 *   entity_type_id = "node",
 *   entity_bundle = "event",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the node to add to the group"),
 *   deriver = "Drupal\gnode\Plugin\Group\Relation\GroupNodeDeriver",
 *   handlers = {
 *     "access" = "Drupal\social_event_managers\Plugin\EventsGroupContentAccessControlHandler",
 *     "permission_provider" = "Drupal\gnode\Plugin\GroupNodePermissionProvider",
 *   }
 * )
 */
class GroupEventNode extends GroupNode {

}
