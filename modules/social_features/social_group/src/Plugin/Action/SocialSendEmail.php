<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\social_user\Plugin\Action\SocialSendEmail as SocialSendEmailBase;

/**
 * Send email to group members.
 *
 * @Action(
 *   id = "social_group_send_email_action",
 *   label = @Translation("Send email to group members"),
 *   type = "group_content",
 *   confirm = TRUE,
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

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof GroupContentInterface) {
      /** @var \Drupal\group\Entity\GroupContentInterface $object */
      return $object->access('view', $account, $return_as_object);
    }

    return TRUE;
  }

}
