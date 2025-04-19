<?php

namespace Drupal\Tests\social_private_message\Unit\Hooks;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\social_private_message\Hooks\SocialPrivateMessageThreadLogger;
use Drupal\user\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the SocialPrivateMessageThreadLogger class.
 *
 * @group social_private_message
 */
class SocialPrivateMessageThreadLoggerTest extends TestCase {

  /**
   * The logger channel factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerChannelFactoryInterface|MockObject $loggerFactory;

  /**
   * The logger channel mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerChannelInterface|MockObject $loggerChannel;

  /**
   * The class under test.
   *
   * @var \Drupal\social_private_message\Hooks\SocialPrivateMessageThreadLogger
   */
  protected SocialPrivateMessageThreadLogger $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the logger channel.
    $this->loggerChannel = $this->createMock(LoggerChannelInterface::class);

    // Mock the logger factory.
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->method('get')
      ->with('private_message')
      ->willReturn($this->loggerChannel);

    // Instantiate the class under test.
    $this->logger = new SocialPrivateMessageThreadLogger($this->loggerFactory);
  }

  /**
   * Tests the createLogger method with a Group.
   */
  public function testLoggerWithGroup(): void {
    $group = $this->createMock(GroupInterface::class);
    $this->createLogger($group);
  }

  /**
   * Tests the createLogger method with a PrivateMessageThread.
   */
  public function testLoggerWithPrivateMessageThread(): void {
    $privateMessageThread = $this->createMock(PrivateMessageThreadInterface::class);
    $this->createLogger($privateMessageThread);
  }

  /**
   * Mock the creation of createLogger.
   *
   * @param (\PHPUnit\Framework\MockObject\MockObject&\Drupal\private_message\Entity\PrivateMessageThreadInterface)|(\PHPUnit\Framework\MockObject\MockObject&\Drupal\group\Entity\GroupInterface) $entity
   *   The entity object.
   */
  public function createLogger((PrivateMessageThreadInterface&MockObject)|(MockObject&GroupInterface) $entity): void {
    // Mock the entity and its getMembers method.
    $entity->method('getMembers')
      ->willReturn([
        $this->createMock(User::class),
        $this->createMock(User::class),
      ]);

    // Mock the User IDs.
    $entity->getMembers()[0]->method('id')->willReturn('1');
    $entity->getMembers()[1]->method('id')->willReturn('2');

    // Expect the logger to log the message.
    $this->loggerChannel->expects($this->once())
      ->method('info')
      ->with('Private message thread created with members: @members.', [
        '@members' => '1, 2',
      ]);

    // Call the method under test.
    $this->logger->createLogger($entity);
  }

}
