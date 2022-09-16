<?php

namespace Drupal\entity_access_by_field\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_access_by_field\EntityAccessHelperInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit test of entity_access_by_field hook_node_access implementation.
 *
 * @coversDefaultClass \Drupal\entity_access_by_field\EntityAccessHelper
 */
class EntityAccessTest extends UnitTestCase {

  use ProphecyTrait;

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
   * The helper.
   *
   * @var \Drupal\entity_access_by_field\EntityAccessHelperInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $helper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->helper = $this->createMock('Drupal\entity_access_by_field\EntityAccessHelperInterface');

    $container = new ContainerBuilder();
    $container->set('entity_access_by_field.helper', $this->helper);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the EntityAccessHelper::process for Neutral Access.
   */
  public function testNeutralAccess() {

    $node = $this->prophesize(NodeInterface::class);

    $this->fieldType = $this->randomMachineName();
    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldDefinitionInterface');
    $fieldDefinitionInterface->expects($this->any())
      ->method('getType')
      ->willReturn($this->fieldType);

    $node->getFieldDefinitions()->willReturn([$this->fieldType => $fieldDefinitionInterface]);
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophesize(AccountInterface::class)->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = $this->helper->process($node, $op, $account);

    $this->assertEquals(EntityAccessHelperInterface::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::ALLOW, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::FORBIDDEN, $access_result);
  }

  /**
   * Tests the EntityAccessHelper::process for Forbidden Access.
   */
  public function testForbiddenAccess() {
    $node = $this->prophesize(NodeInterface::class);
    $node->bundle()->willReturn('article');

    $this->fieldValue = 'public';
    $this->fieldType = 'entity_access_field';
    $this->nodeOwnerId = 3;
    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->any())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->any())
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

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId . ':' . $this->fieldValue . ' content')
      ->willReturn(FALSE);
    $account->id()->willReturn($this->accountId);
    $account = $account->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = $this->helper->process($node, $op, $account);

    $this->assertEquals(EntityAccessHelperInterface::FORBIDDEN, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::ALLOW, $access_result);

  }

  /**
   * Tests the EntityAccessHelper::process for Allowed Access.
   */
  public function testAllowedAccess() {
    $node = $this->prophesize(NodeInterface::class);
    $node->bundle()->willReturn('article');

    $this->fieldId = 'node.article.field_content_visibility';
    $this->fieldValue = 'public';
    $this->fieldType = 'entity_access_field';
    $this->accountId = 5;
    $this->nodeOwnerId = 3;

    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->any())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->any())
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

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId . ':' . $this->fieldValue . ' content')
      ->willReturn(TRUE);
    $account->id()->willReturn($this->accountId);
    $account = $account->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = $this->helper->process($node, $op, $account);

    $this->assertEquals(EntityAccessHelperInterface::ALLOW, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::FORBIDDEN, $access_result);
  }

  /**
   * Tests the EntityAccessHelper::process for Author Access Allowed.
   */
  public function testAuthorAccessAllowed() {
    $node = $this->prophesize(NodeInterface::class);
    $node->bundle()->willReturn('article');

    $this->fieldValue = 'nonexistant';
    $this->fieldType = 'entity_access_field';
    $this->nodeOwnerId = 5;

    $fieldDefinitionInterface = $this->createMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->any())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->any())
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

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId . ':' . $this->fieldValue . ' content')
      ->willReturn(FALSE);
    $account->id()->willReturn($this->accountId);
    $account = $account->reveal();

    /** @var \Drupal\node\NodeInterface $node */
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $access_result = $this->helper->process($node, $op, $account);

    $this->assertEquals(EntityAccessHelperInterface::ALLOW, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::NEUTRAL, $access_result);
    $this->assertNotEquals(EntityAccessHelperInterface::FORBIDDEN, $access_result);
  }

}
