<?php

namespace Drupal\social_private_message\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\hux\Attribute\Hook;
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
  protected $loggerFactory;

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
  public function createLogger(EntityInterface $entity): void {
    // Validation to make PHPStan happy.
    if (!method_exists($entity, 'getMembers')) {
      return;
    }

    // Get usernames from members of thread.
    $usernames = [];
    foreach ($entity->getMembers() as $member) {
      $usernames[] = $member->getAccountName();
    }

    // Logger message for new threads.
    $this->loggerFactory->info('Private message thread created with members: @members.', [
      '@members' => implode(', ', $usernames),
    ]);
  }

}
