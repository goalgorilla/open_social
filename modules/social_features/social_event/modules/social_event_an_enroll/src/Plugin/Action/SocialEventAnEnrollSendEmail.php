<?php

namespace Drupal\social_event_an_enroll\Plugin\Action;

use Drupal\social_event\Plugin\Action\SocialEventSendEmail;

/**
 * Send email to event enrollment users.
 *
 * @Action(
 *   id = "social_event_an_enroll_send_email_action",
 *   label = @Translation("Send email to event enrollment users"),
 *   type = "event_enrollment",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event.views_bulk_operations.confirm",
 * )
 */
class SocialEventAnEnrollSendEmail extends SocialEventSendEmail {

  /**
   * The event enrollment.
   *
   * @var \Drupal\social_event\EventEnrollmentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->entity = $entity;

    parent::execute($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail($entity) {
    if ($this->entity->field_account->target_id) {
      return parent::getEmail($entity);
    }

    return $this->entity->field_email->value;
  }

}
