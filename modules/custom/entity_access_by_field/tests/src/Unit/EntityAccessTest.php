<?php

namespace Drupal\entity_access_by_field\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\entity_access_by_field\EntityAccessHelper;
use Prophecy\Prophet;

/**
 * Unit test of entity_access_by_field hook_node_access implementation.
 */
class EntityAccessTest extends UnitTestCase {

  /**
   * The field type random machinename.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * The visibility value.
   *
   * @var string
   */
  protected $fieldValue;

  /**
   * The field id.
   *
   * @var string
   */
  protected $fieldId = 'node.article.field_content_visibility';

  /**
   * The account id.
   *
   * @var int
   */
  protected $accountId = 5;

  /**
   * The account id.
   *
   * @var int
   */
  protected $nodeOwnerId;

  /**
   * The prophecy object.
   *
   * @var \Prophecy\Prophet
   */
  private $prophet;

  /**
   * Set up.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->prophet = new Prophet();
  }

  /**
   * Tear down.
   */
  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * Tests the EntityAccessHelper::entityAccessCheck for Neutral Access.
   */
  public function testNeutralAccess(): void {

    $node = $this->prophet->prophesize(NodeInterface::class);

    $this->fieldType = $this->randomMachineName();
    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $fieldDefinitionInterface->expects($this->once())
      ->method('getType')
      ->willReturn($this->fieldType);

    $node->getFieldDefinitions()->willReturn([$this->fieldType => $fieldDefinitionInterface]);
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophet->prophesize(AccountInterface::class)->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = EntityAccessHelper::entityAccessCheck(
      $node,
      $op,
      $account,
      'administer nodes',
    );

    $this->assertEquals(EntityAccessHelper::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelper::ALLOW, $access_result);
    $this->assertNotEquals(EntityAccessHelper::FORBIDDEN, $access_result);
  }

  /**
   * Tests the EntityAccessHelper::entityAccessCheck for Forbidden Access.
   */
  public function testForbiddenAccess(): void {
    $node = $this->prophet->prophesize(NodeInterface::class);
    $node->getEntityTypeId()->willReturn('node');
    $node->bundle()->willReturn('article');

    $this->fieldValue = 'public';
    $this->fieldType = 'entity_access_field';
    $this->nodeOwnerId = 3;
    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->once())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->once())
      ->method('getName')
      ->willReturn('field_content_visibility');

    $fieldItemListInterface = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $fieldItemListInterface->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => 'public']]);

    $node->get('entity_access_field')->willReturn($fieldItemListInterface);
    $node->getFieldDefinitions()->willReturn([$this->fieldType => $fieldDefinitionInterface]);
    $node->getOwnerId()->willReturn($this->nodeOwnerId);
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophet->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId . ':' . $this->fieldValue . ' content')
      ->willReturn(FALSE);
    $account->isAuthenticated()->willReturn(TRUE);
    $account->id()->willReturn($this->accountId);
    $account = $account->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = EntityAccessHelper::entityAccessCheck(
      $node,
      $op,
      $account,
      'administer nodes',
    );

    $this->assertEquals(EntityAccessHelper::FORBIDDEN, $access_result);
    $this->assertNotEquals(EntityAccessHelper::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelper::ALLOW, $access_result);

  }

  /**
   * Tests the EntityAccessHelper::entityAccessCheck for Allowed Access.
   */
  public function testAllowedAccess(): void {
    $node = $this->prophet->prophesize(NodeInterface::class);
    $node->getEntityTypeId()->willReturn('node');
    $node->bundle()->willReturn('article');

    $this->fieldId = 'node.article.field_content_visibility';
    $this->fieldValue = 'public';
    $this->fieldType = 'entity_access_field';
    $this->accountId = 5;
    $this->nodeOwnerId = 3;

    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->once())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->once())
      ->method('getName')
      ->willReturn('field_content_visibility');

    $fieldItemListInterface = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $fieldItemListInterface->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => $this->fieldValue]]);

    $node->get('entity_access_field')->willReturn($fieldItemListInterface);
    $node->getFieldDefinitions()->willReturn([$this->fieldType => $fieldDefinitionInterface]);
    $node->getCacheContexts()->willReturn(NULL);
    $node->getOwnerId()->willReturn($this->nodeOwnerId);
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophet->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId . ':' . $this->fieldValue . ' content')
      ->willReturn(TRUE);
    $account->isAuthenticated()->willReturn(TRUE);
    $account->id()->willReturn($this->accountId);
    $account = $account->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = EntityAccessHelper::entityAccessCheck(
      $node,
      $op,
      $account,
      'administer nodes',
    );

    $this->assertEquals(EntityAccessHelper::ALLOW, $access_result);
    $this->assertNotEquals(EntityAccessHelper::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelper::FORBIDDEN, $access_result);
  }

  /**
   * Tests the EntityAccessHelper::entityAccessCheck for Author Access Allowed.
   */
  public function testAuthorAccessAllowed(): void {
    $node = $this->prophet->prophesize(NodeInterface::class);
    $node->getEntityTypeId()->willReturn('node');
    $node->bundle()->willReturn('article');

    $this->fieldValue = 'nonexistant';
    $this->fieldType = 'entity_access_field';
    $this->nodeOwnerId = 5;

    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->once())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->once())
      ->method('getName')
      ->willReturn('field_content_visibility');

    $fieldItemListInterface = $this->createMock('Drupal\Core\Field\FieldItemListInterface');
    $fieldItemListInterface->expects($this->any())
      ->method('getValue')
      ->willReturn([0 => ['value' => $this->fieldValue]]);

    $node->get('entity_access_field')->willReturn($fieldItemListInterface);
    $node->getFieldDefinitions()->willReturn([$this->fieldType => $fieldDefinitionInterface]);
    $node->getCacheContexts()->willReturn(NULL);
    $node->getOwnerId()->willReturn($this->nodeOwnerId);
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophet->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId . ':' . $this->fieldValue . ' content')
      ->willReturn(FALSE);
    $account->isAuthenticated()->willReturn(TRUE);
    $account->id()->willReturn($this->accountId);
    $account = $account->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = EntityAccessHelper::entityAccessCheck(
      $node,
      $op,
      $account,
      'administer nodes',
    );

    $this->assertEquals(EntityAccessHelper::ALLOW, $access_result);
    $this->assertNotEquals(EntityAccessHelper::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelper::FORBIDDEN, $access_result);
  }

}
