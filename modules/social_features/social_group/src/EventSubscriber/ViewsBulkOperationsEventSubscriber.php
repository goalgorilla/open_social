<?php

declare(strict_types=1);

namespace Drupal\social_group\EventSubscriber;

use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\CurrentGroupProviderInterface;
use Drupal\views_bulk_operations\ViewsBulkOperationsTempStoreEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ViewsBulkOperationsEventSubscriber.
 *
 * @package Drupal\social_group\EventSubscriber
 */
class ViewsBulkOperationsEventSubscriber implements EventSubscriberInterface {

  // Priority for setting the tempstore key.
  // Run it after default VBO subscriber.
  private const TEMPSTORE_PRIORITY = -10;

  /**
   * Object constructor.
   *
   * @param \Drupal\social_group\CurrentGroupProviderInterface $currentGroupProvider
   *   The current group provider.
   */
  public function __construct(
    protected readonly CurrentGroupProviderInterface $currentGroupProvider,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ViewsBulkOperationsTempStoreEvent::NAME => [
        ['setTempStoreKey', self::TEMPSTORE_PRIORITY],
      ],
    ];
  }

  /**
   * Set default tempstore key.
   *
   * @param \Drupal\views_bulk_operations\ViewsBulkOperationsTempStoreEvent $event
   *   The tempstore event.
   */
  public function setTempStoreKey(ViewsBulkOperationsTempStoreEvent $event): void {
    $group = $this->currentGroupProvider->getCurrentGroup();
    if ($group instanceof GroupInterface) {
      $key = sprintf('%s_%s', $group->id(), $group->bundle());
      $event->setTempStoreKey($key);
    }
  }

}
