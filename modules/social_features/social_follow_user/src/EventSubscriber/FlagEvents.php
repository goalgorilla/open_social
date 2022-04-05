<?php

namespace Drupal\social_follow_user\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
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
  protected FlagServiceInterface $flagService;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * FlagEvents constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(
    FlagServiceInterface $flag_service,
    CacheTagsInvalidatorInterface $cache_tags_invalidator
  ) {
    $this->flagService = $flag_service;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
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
  public function onFlag(FlaggingEvent $event): void {
    if ($event->getFlagging()->getFlagId() === 'follow_user') {
      $this->invalidateCaches();
    }
  }

  /**
   * React to unflagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The unflagging event.
   */
  public function onUnflag(UnflaggingEvent $event): void {
    $flag = $event->getFlaggings();

    /** @var \Drupal\flag\FlaggingInterface $flag */
    $flag = reset($flag);

    if ($flag->getFlagId() === 'follow_user') {
      $this->invalidateCaches();
    }
  }

  /**
   * Invalidates cache tags.
   */
  public function invalidateCaches(): void {
    $this->cacheTagsInvalidator->invalidateTags([
      'followers_user',
      'following_user',
    ]);
  }

}
