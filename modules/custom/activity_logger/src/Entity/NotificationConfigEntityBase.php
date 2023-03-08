<?php

namespace Drupal\activity_logger\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a base configuration entity class.
 */
abstract class NotificationConfigEntityBase extends ConfigEntityBase implements NotificationConfigEntityInterface {

  /**
   * The created timestamp.
   *
   * @var int
   */
  public int $created;

  /**
   * The unique id.
   *
   * @var int
   */
  public int $unique_id;

  /**
   * The created timestamp.
   */
  public function getCreatedTime(): int {
    return $this->created ?? 0;
  }

  /**
   * The unique id.
   */
  public function getUniqueId(): int {
    return $this->unique_id ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if ($this->isNew()) {
      // Set created id.
      $this->set('created', \Drupal::time()->getRequestTime());
      // Set unique id.
      $storage = \Drupal::entityTypeManager()->getStorage($this->getEntityTypeId());
      $ids = (array) \Drupal::entityQuery($this->getEntityTypeId())->execute();
      $list = $storage->loadMultiple($ids);
      $unique_ids = [0];
      foreach ($list as $entity) {
        if (!$entity instanceof NotificationConfigEntityInterface) {
          continue;
        }
        $unique_ids[] = $entity->getUniqueId();
      }
      $max = max($unique_ids);
      // Add plus one to the biggest value.
      $this->set('unique_id', $max + 1);
    }
  }

}
