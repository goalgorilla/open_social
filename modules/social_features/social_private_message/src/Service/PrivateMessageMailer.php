<?php

namespace Drupal\social_private_message\Service;

use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\private_message\Service\PrivateMessageMailer as PrivateMessageMailerBase;
use Drupal\user\UserInterface;

/**
 * A service class for sending notification emails for private messages.
 */
class PrivateMessageMailer extends PrivateMessageMailerBase {

  /**
   * {@inheritdoc}
   */
  public function send(PrivateMessageInterface $message, PrivateMessageThreadInterface $thread, array $members = []) {
    foreach ($members as $id => $member) {
      if (
        !($member instanceof UserInterface) ||
        !$member->hasPermission('use private messaging system')
      ) {
        unset($members[$id]);
      }
    }

    parent::send($message, $thread, $members);
  }

}
