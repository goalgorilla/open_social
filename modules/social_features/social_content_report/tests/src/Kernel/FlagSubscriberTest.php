<?php

namespace Drupal\Tests\social_content_report\Kernel;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\FlaggingInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\social_content_report\ContentReportServiceInterface;
use Drupal\social_content_report\EventSubscriber\FlagSubscriber;
use Drupal\social_content_report_legacy_entity_test\Entity\TestLegacyEntity;

/**
 * Tests reported content unpublishing.
 *
 * @group social_content_report
 */
class FlagSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'filter',
    'social_content_report_legacy_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('test_legacy_entity');
    $this->installSchema('node', ['node_access']);

    NodeType::create([
      'type' => 'test',
      'name' => 'Test',
    ])->save();
  }

  /**
   * Tests reported entities using EntityPublishedInterface are published.
   *
   * Test scenario:
   *   - set configuration immediate unpublishing is disabled.
   */
  public function testReportedEntityStaysPublishedWhenUnpublishingIsDisabled(): void {
    $node = Node::create([
      'type' => 'test',
      'title' => 'Reported node',
      'status' => TRUE,
    ]);
    $node->save();

    $subscriber = $this->createSubscriber(FALSE);
    $subscriber->onFlag($this->createFlaggingEvent($node));

    $node = Node::load($node->id());

    $this->assertTrue($node instanceof EntityPublishedInterface);
    $this->assertTrue($node->isPublished());
  }

  /**
   * Tests reported entities using EntityPublishedInterface are unpublished.
   *
   * Test scenario:
   *   - set configuration immediate unpublishing is enabled.
   */
  public function testReportedEntityIsUnpublishedWhenUnpublishingIsEnabled(): void {
    $node = Node::create([
      'type' => 'test',
      'title' => 'Reported node',
      'status' => TRUE,
    ]);
    $node->save();

    $subscriber = $this->createSubscriber(TRUE);
    $subscriber->onFlag($this->createFlaggingEvent($node));

    $node = Node::load($node->id());

    $this->assertTrue($node instanceof EntityPublishedInterface);
    $this->assertFalse($node->isPublished());
  }

  /**
   * Tests legacy entities with setPublished(FALSE) are published.
   *
   * Test scenario:
   *   - set configuration immediate unpublishing is disabled.
   */
  public function testLegacyEntityStaysPublishedWhenUnpublishingIsDisabled(): void {
    $entity = TestLegacyEntity::create([
      'name' => 'Reported legacy entity',
      'status' => TRUE,
    ]);
    $entity->save();

    $subscriber = $this->createSubscriber(FALSE);
    $subscriber->onFlag($this->createFlaggingEvent($entity));

    $entity = TestLegacyEntity::load($entity->id());

    $this->assertTrue($entity instanceof EntityInterface);
    $this->assertTrue((bool) $entity->get('status')->value);
  }

  /**
   * Tests legacy entities with setPublished(FALSE) are unpublished.
   *
   * Test scenario:
   *   - set configuration immediate unpublishing is enabled.
   */
  public function testLegacyEntityIsUnpublishedWhenUnpublishingIsEnabled(): void {
    $entity = TestLegacyEntity::create([
      'name' => 'Reported legacy entity',
      'status' => TRUE,
    ]);
    $entity->save();

    $subscriber = $this->createSubscriber(TRUE);
    $subscriber->onFlag($this->createFlaggingEvent($entity));

    $entity = TestLegacyEntity::load($entity->id());

    $this->assertTrue($entity instanceof EntityInterface);
    $this->assertFalse((bool) $entity->get('status')->value);
  }

  /**
   * Creates the subscriber under test.
   */
  private function createSubscriber(bool $unpublish_immediately = FALSE): FlagSubscriber {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('unpublish_threshold')
      ->willReturn($unpublish_immediately);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('social_content_report.settings')
      ->willReturn($config);

    $messenger = $this->createMock(MessengerInterface::class);
    $cache_invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);

    $content_report = $this->createMock(ContentReportServiceInterface::class);
    $content_report->method('getReportFlagTypes')
      ->willReturn(['report_node']);

    return new FlagSubscriber(
      $config_factory,
      $messenger,
      $cache_invalidator,
      $content_report,
    );
  }

  /**
   * Creates a flagging event for the given entity.
   */
  private function createFlaggingEvent(EntityInterface $entity): FlaggingEvent {
    $flagging = $this->createMock(FlaggingInterface::class);
    $flagging->method('getFlagId')
      ->willReturn('report_node');
    $flagging->method('getFlaggable')
      ->willReturn($entity);

    $event = $this->createMock(FlaggingEvent::class);
    $event->method('getFlagging')
      ->willReturn($flagging);

    return $event;
  }

}
