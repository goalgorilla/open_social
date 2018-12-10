<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\DeleteAction;

/**
 * Deletes group content.
 *
 * @Action(
 *   id = "entity:delete_action:group_content",
 *   label = @Translation("Delete group content")
 * )
 */
class DeleteGroupContent extends DeleteAction {

}
