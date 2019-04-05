<?php

namespace Drupal\social_event_managers\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Delete event enrollment entity action.
 *
 * @Action(
 *   id = "social_event_managers_delete_event_enrollment_action",
 *   label = @Translation("Delete selected event enrollment entities"),
 *   type = "event_enrollment",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event_managers.vbo.confirm",
 * )
 */
class EventEnrollmentEntityDeleteAction extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\social_event\EventEnrollmentInterface $entity */
    $entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::forbidden();

    if ($object instanceof EventEnrollmentInterface) {
      $access = $object->access('delete', $account, TRUE);
    }
    // Also Event organizers can do this.
    if (social_event_manager_or_organizer()) {
      $access = TRUE;
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

}
