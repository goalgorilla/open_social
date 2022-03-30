<?php

namespace Drupal\social_follow_user\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\Event\FlagEvents as Flag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Flag events subscriber.
 */
class FlagEvents implements EventSubscriberInterface {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(FlagServiceInterface $flag_service, StateInterface $state) {
    $this->flagService = $flag_service;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[Flag::ENTITY_FLAGGED] = ['onFlag', 50];
    $events[Flag::ENTITY_UNFLAGGED] = ['onUnflag', 50];
    return $events;
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    /** @var \Drupal\flag\Entity\Flagging $flag */
    $flag = $event->getFlagging();

    if ($flag->getFlagId() === 'follow_user') {
      $this->invalidateCaches();
    }
  }

  /**
   * React to unflagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The unflagging event.
   */
  public function onUnflag(UnflaggingEvent $event) {
   $flag = $event->getFlaggings();
   /** @var \Drupal\flag\Entity\Flagging $flag */
   $flag = reset($flag);

    if ($flag->getFlagId() === 'follow_user') {
      $this->invalidateCaches();
    }
  }

  /**
   * Invalidates cache tags.
   */
  public function invalidateCaches() {
    \Drupal::service('cache_tags.invalidator')->invalidateTags([
      'followers_user',
      'following_user',
    ]);
  }

}
