<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Plugin\Action\EntityDeleteAction;

/**
 * Delete group content entity action without default confirmation form.
 */
#[Action(
  id: 'social_group_delete_group_content_action',
  label: new TranslatableMarkup('Delete selected group content entities'),
  confirm_form_route_name: 'views_bulk_operations.confirm',
  type: 'group_content',
)]
class GroupContentEntityDeleteAction extends EntityDeleteAction {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    parent::execute($entity);
    return $this->t('Remove members from a group');
  }

}
