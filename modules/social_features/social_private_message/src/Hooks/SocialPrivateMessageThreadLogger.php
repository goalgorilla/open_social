<?php

namespace Drupal\social_private_message\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hux class to create a logger for new private message thread.
 */
class SocialPrivateMessageThreadLogger implements ContainerInjectionInterface {

  /**
   * The logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerFactory;

  /**
   * Social private message thread logger constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    $this->loggerFactory = $logger->get('private_message');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.factory'),
    );
  }

  /**
   * Create a logger for new private message thread.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @see hook_ENTITY_TYPE_insert()
   */
  #[Hook('private_message_thread_insert')]
  public function createPrivateMessageThreadLogger(EntityInterface $entity): void {
    // Validation to make PHPStan happy.
    if (!method_exists($entity, 'getMembers')) {
      return;
    }

    // Get usernames from members of thread.
    $user_ids = [];
    foreach ($entity->getMembers() as $member) {
      assert($member instanceof User);
      $user_ids[] = $member->id();
    }

    // Logger message for new threads.
    $this->loggerFactory->info('Private message thread created with members: @members.', [
      '@members' => implode(', ', $user_ids),
    ]);
  }

  /**
   * Create a logger for new private message.
   *
   * The private message will create without relationship with thread
   * after private message is created, the thread is updated with
   * private message id, because of that I am trigger the logger on
   * private message thread update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @see hook_ENTITY_TYPE_insert()
   */
  #[Hook('private_message_thread_update')]
  public function createPrivateMessageLogger(EntityInterface $entity): void {
    // Validation to make PHPStan happy.
    if (
      !method_exists($entity, 'getMembers')
      || !method_exists($entity, 'getMessages')
      || empty($entity->original)
    ) {
      return;
    }

    // When don't have new messages being added, return early.
    if ($entity->getMessages() == $entity->original->getMessages()) {
      return;
    }

    // Get usernames from members of thread.
    $user_ids = [];
    foreach ($entity->getMembers() as $member) {
      assert($member instanceof User);
      $user_ids[] = $member->id();
    }

    // Logger message for new threads.
    $this->loggerFactory->info('Private message created on thread @thread-id with members: @members.', [
      '@thread-id' => $entity->id(),
      '@members' => implode(', ', $user_ids),
    ]);
  }

}
