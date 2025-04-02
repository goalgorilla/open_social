<?php

namespace Drupal\social_event\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Hook;

/**
 * Provides hook related to user deletion.
 */
final class UserDeleteHooks {

  use StringTranslationTrait;

  /**
   * The constructor for dependency injection.
   */
  public function __construct(
    /**
     * The logger.
     *
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
     */
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
    /**
     * The Entity Manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('user_delete')]
  public function userDelete(EntityInterface $entity): void {
    // We don't expect the anonymous user to be passed to
    // hook_user_delete.
    // @see https://github.com/goalgorilla/cablecar/pull/2750#discussion_r1942414712
    if ((string) $entity->id() === "0") {
      throw new \RuntimeException('Anonymous user should not be passed to hook_user_delete.');
    }

    // Fetch all event enrollments where user is enrolled.
    $storage = $this->entityTypeManager->getStorage('event_enrollment');
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);
    $entity_ids = $query->condition('field_account', $entity->id())->execute();

    // Delete all event_enrollment objects related to that user.
    if (!empty($entity_ids)) {
      $entities = $storage->loadMultiple($entity_ids);

      try {
        $storage->delete($entities);
      }
      catch (EntityStorageException $e) {
        $this->loggerChannelFactory->get('social_event')->error($e->getMessage());
      }
    }
  }

}
