<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\social_user\Plugin\Action\SocialSendEmail as SocialSendEmailBase;

/**
 * Send email to group members.
 *
 * @Action(
 *   id = "social_group_send_email",
 *   label = @Translation("Send email to group members"),
 *   type = "group_content",
 *   confirm = FALSE,
 * )
 */
class SocialSendEmail extends SocialSendEmailBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    parent::execute($entity->getEntity());
  }

}
