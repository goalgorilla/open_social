<?php

namespace Drupal\social_event\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_user\Plugin\Action\SocialSendEmail as SocialSendEmailBase;

/**
 * Send email to event enrollment users.
 *
 * @Action(
 *   id = "social_event_send_email_action",
 *   label = @Translation("Send email to event enrollment users"),
 *   type = "event_enrollment",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event.views_bulk_operations.confirm",
 * )
 */
class SocialSendEmail extends SocialSendEmailBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof EventEnrollmentInterface) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    return $form;
  }

}
