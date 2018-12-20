<?php

namespace Drupal\social_event\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Delete event enrollment entity action.
 *
 * @Action(
 *   id = "social_event_delete_event_enrollment_action",
 *   label = @Translation("Delete event enrollment of selected profile entities"),
 *   type = "profile",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event.views_bulk_operations.confirm",
 * )
 */
class EventEnrollmentEntityDeleteAction extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entities = \Drupal::entityTypeManager()->getStorage('event_enrollment')
      ->loadByProperties([
        'field_account' => $entity->uid->target_id,
        'field_event' => $this->context['arguments'][0],
      ]);

    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $entities = \Drupal::entityTypeManager()->getStorage('event_enrollment')
      ->loadByProperties([
        'field_account' => $object->uid->target_id,
        'field_event' => $this->context['arguments'][0],
      ]);

    $access = AccessResult::allowedIf(!empty($entities));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
