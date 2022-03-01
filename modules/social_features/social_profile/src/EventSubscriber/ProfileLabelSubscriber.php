<?php

namespace Drupal\social_profile\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Event\ProfileEvents;
use Drupal\profile\Event\ProfileLabelEvent;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProfileLabelSubscriber.
 *
 * @package Drupal\social_profile\EventSubscriber
 */
class ProfileLabelSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * ProfileLabelSubscriber construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

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
      $account = $this->userStorage->load($profile->getOwnerId());
      if ($account instanceof User) {
        $label = $this->t('Profile of @name', [
          '@name' => $account->getDisplayName(),
        ]
        );
        $event->setLabel($label);
      }
    }
  }

}
