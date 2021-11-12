<?php

namespace Drupal\social_private_message\Service;

use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\private_message\Service\PrivateMessageNotifier;
use Drupal\user\UserInterface;

/**
 * A service class for sending notification emails for private messages.
 */
class SocialPrivateMessageNotifier extends PrivateMessageNotifier {

  /**
   * {@inheritdoc}
   */
  public function notify(PrivateMessageInterface $message, PrivateMessageThreadInterface $thread, array $members = []): void {
    foreach ($members as $id => $member) {
      if (
        !($member instanceof UserInterface) ||
        !$member->hasPermission('use private messaging system')
      ) {
        unset($members[$id]);
      }
    }

    parent::notify($message, $thread, $members);
  }

}
