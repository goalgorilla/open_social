<?php

namespace Drupal\social_profile\EventSubscriber;

use Drupal\profile\Entity\Profile;
use Drupal\profile\Event\ProfileEvents;
use Drupal\profile\Event\ProfileLabelEvent;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProfileLabelSubscriber.
 *
 * @package Drupal\social_profile\EventSubscriber
 */
class ProfileLabelSubscriber implements EventSubscriberInterface {

  /**
   * Get the label event.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[ProfileEvents::PROFILE_LABEL][] = ['overrideProfileLabel'];
    return $events;
  }

  /**
   * Subscriber Callback for the event.
   *
   * @param \Drupal\profile\Event\ProfileLabelEvent $event
   *   The event.
   */
  public function overrideProfileLabel(ProfileLabelEvent $event) {
    $profile = $event->getProfile();

    if ($profile instanceof Profile) {
      $account = User::load($profile->getOwnerId());
      if ($account instanceof User) {
        $label = t('Profile of @name', ['@name' => $account->getDisplayName()]);
        $event->setLabel($label);
      }
    }
  }

}
