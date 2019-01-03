<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\views_bulk_operations\Plugin\Action\EntityDeleteAction;

/**
 * Delete group content entity action without default confirmation form.
 *
 * @Action(
 *   id = "social_group_delete_group_content_action",
 *   label = @Translation("Delete selected group content entities"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
class GroupContentEntityDeleteAction extends EntityDeleteAction {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    parent::execute($entity);
    return $this->t('Remove members from a group');
  }

}
