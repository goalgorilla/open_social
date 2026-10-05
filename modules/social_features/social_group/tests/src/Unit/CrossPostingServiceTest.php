<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\CrossPostingService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the unfiltered relationship lookup of CrossPostingService.
 *
 * The other lookups of the service are pre-existing and untouched, so this
 * test covers every branch of the unfiltered lookup only.
 *
 * @group social_group
 *
 * @coversDefaultClass \Drupal\social_group\CrossPostingService
 */
final class CrossPostingServiceTest extends UnitTestCase {

  /**
   * The database connection mock.
   *
   * @var \Drupal\Core\Database\Connection&\PHPUnit\Framework\MockObject\MockObject
   */
  private Connection&MockObject $database;

  /**
   * The group storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $groupStorage;

  /**
   * The group relation type manager mock.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private GroupRelationTypeManagerInterface&MockObject $groupRelationTypeManager;

  /**
   * The node the lookup runs for.
   *
   * @var \Drupal\node\NodeInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private NodeInterface&MockObject $node;

  /**
   * The social_group.settings cross_posting value the config factory returns.
   *
   * @var array<string, bool>|null
   */
  private ?array $crossPosting = NULL;

  /**
   * The service under test.
   */
  private CrossPostingService $service;

  /**
   * The fields the service added to the relationship select.
   *
   * @var string[]
   */
  private array $selectedFields = [];

  /**
   * The conditions the service put on the relationship select.
   *
   * @var array
   */
  private array $selectConditions = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->groupStorage = $this->createMock(EntityStorageInterface::class);
    $this->groupRelationTypeManager = $this->createMock(GroupRelationTypeManagerInterface::class);

    $this->node = $this->createMock(NodeInterface::class);
    $this->node->method('id')->willReturn(42);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturnCallback(
      fn (string $entity_type_id): EntityStorageInterface => match ($entity_type_id) {
        'group' => $this->groupStorage,
        default => throw new \LogicException('Unexpected storage: ' . $entity_type_id),
      }
    );

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(
      fn (string $key): ?array => $key === 'cross_posting' ? $this->crossPosting : NULL
    );
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('social_group.settings')->willReturn($config);

    $this->service = new CrossPostingService(
      $entity_type_manager,
      $this->database,
      $this->groupRelationTypeManager,
      $config_factory
    );
  }

  /**
   * Declares the installed node relation plugins.
   *
   * @param string[] $relationship_type_ids
   *   The relationship type ids the group_node plugin resolves to.
   */
  private function installNodePlugins(array $relationship_type_ids): void {
    $this->groupRelationTypeManager->method('getAllInstalledIds')
      ->willReturn(['group_membership', 'group_node:topic']);
    $this->groupRelationTypeManager->method('getRelationshipTypeIds')
      ->with('group_node:topic')
      ->willReturn($relationship_type_ids);
  }

  /**
   * Arms the database with the rows the relationship select returns.
   *
   * @param array $rows
   *   The relationship id to group id mapping the statement yields.
   */
  private function expectRelationshipQuery(array $rows): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())
      ->method('fetchAllKeyed')
      ->with(0, 1)
      ->willReturn($rows);

    $query = $this->createMock(SelectInterface::class);
    $query->method('addField')->willReturnCallback(
      function (string $table, string $field) use ($query): SelectInterface {
        $this->selectedFields[] = $table . '.' . $field;
        return $query;
      }
    );
    $query->method('condition')->willReturnCallback(
      function (string $field, $value, $operator = '=') use ($query): SelectInterface {
        $this->selectConditions[$field] = [$value, $operator];
        return $query;
      }
    );
    $query->method('execute')->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->with('group_relationship_field_data', 'gc')
      ->willReturn($query);
  }

  /**
   * No installed node relation plugin means no relationship and no query.
   *
   * @covers ::getGroupIdsByRelationshipForNodeWithoutAccessCheck
   */
  public function testNoValidPluginsReturnsEmpty(): void {
    $this->groupRelationTypeManager->method('getAllInstalledIds')->willReturn(['group_membership']);
    $this->groupRelationTypeManager->expects($this->never())->method('getRelationshipTypeIds');
    $this->database->expects($this->never())->method('select');

    $this->assertSame([], $this->service->getGroupIdsByRelationshipForNodeWithoutAccessCheck($this->node));
  }

  /**
   * A node that is in no group at all yields an empty mapping.
   *
   * @covers ::getGroupIdsByRelationshipForNodeWithoutAccessCheck
   */
  public function testNodeWithoutRelationshipsReturnsEmpty(): void {
    $this->installNodePlugins(['flexible_group-group_node-topic']);
    $this->expectRelationshipQuery([]);

    $this->assertSame([], $this->service->getGroupIdsByRelationshipForNodeWithoutAccessCheck($this->node));
  }

  /**
   * Every relationship of the node is returned, keyed by relationship id.
   *
   * @covers ::getGroupIdsByRelationshipForNodeWithoutAccessCheck
   */
  public function testEveryRelationshipIsReturnedKeyedById(): void {
    $this->installNodePlugins(['flexible_group-group_node-topic', 'open_group-group_node-topic']);
    // The group ids are strings because fetchAllKeyed() returns them as such,
    // and callers order on these values.
    $this->expectRelationshipQuery([11 => '3', 12 => '1', 13 => '2']);

    $this->assertSame(
      [11 => '3', 12 => '1', 13 => '2'],
      $this->service->getGroupIdsByRelationshipForNodeWithoutAccessCheck($this->node)
    );

    // The relationships of this node only, and of node relation plugins only.
    $this->assertSame(['gc.id', 'gc.gid'], $this->selectedFields);
    $this->assertSame([42, '='], $this->selectConditions['gc.entity_id']);
    $this->assertSame(
      [['flexible_group-group_node-topic', 'open_group-group_node-topic'], 'IN'],
      $this->selectConditions['gc.type']
    );
  }

  /**
   * A statement the database declined to return yields an empty mapping.
   *
   * @covers ::getGroupIdsByRelationshipForNodeWithoutAccessCheck
   */
  public function testQueryWithoutStatementReturnsEmpty(): void {
    $this->installNodePlugins(['flexible_group-group_node-topic']);

    $query = $this->createMock(SelectInterface::class);
    $query->method('addField')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('execute')->willReturn(NULL);

    $this->database->expects($this->once())
      ->method('select')
      ->with('group_relationship_field_data', 'gc')
      ->willReturn($query);

    $this->assertSame([], $this->service->getGroupIdsByRelationshipForNodeWithoutAccessCheck($this->node));
  }

  /**
   * The group storage is never queried, so no access check can happen.
   *
   * The method runs inside the notification queue, where the current user is
   * cron or the anonymous user. An access check there would answer a question
   * about the wrong account, so the absence of one is the property that makes
   * the method safe to use from a queue worker.
   *
   * @covers ::getGroupIdsByRelationshipForNodeWithoutAccessCheck
   */
  public function testGroupStorageIsNeverQueried(): void {
    $this->installNodePlugins(['flexible_group-group_node-topic']);
    $this->expectRelationshipQuery([11 => '3', 12 => '1']);

    $this->groupStorage->expects($this->never())->method('getQuery');
    $this->groupStorage->expects($this->never())->method('loadMultiple');

    $this->assertSame(
      [11 => '3', 12 => '1'],
      $this->service->getGroupIdsByRelationshipForNodeWithoutAccessCheck($this->node)
    );
  }

  /**
   * Notifying every group needs cross-posting and the option both on.
   *
   * @param array<string, bool>|null $cross_posting
   *   The stored cross_posting value, NULL when the site has none yet.
   * @param bool $expected
   *   Whether every group is notified.
   *
   * @covers ::notifiesAllGroups
   * @dataProvider notifiesAllGroupsProvider
   */
  public function testNotifiesAllGroups(?array $cross_posting, bool $expected): void {
    $this->crossPosting = $cross_posting;

    $this->assertSame($expected, $this->service->notifiesAllGroups());
  }

  /**
   * Cross-posting settings and whether they notify every group.
   *
   * @return array<string, array{0: array<string, bool>|null, 1: bool}>
   *   The stored value and the expected answer.
   */
  public static function notifiesAllGroupsProvider(): array {
    return [
      'both on' => [['status' => TRUE, 'notify_all_groups' => TRUE], TRUE],
      'option off' => [['status' => TRUE, 'notify_all_groups' => FALSE], FALSE],
      'leftover option with cross-posting off' => [['status' => FALSE, 'notify_all_groups' => TRUE], FALSE],
      'option not saved yet' => [['status' => TRUE], FALSE],
      'no cross-posting settings' => [NULL, FALSE],
    ];
  }

}
