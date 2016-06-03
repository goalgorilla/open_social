<?php

namespace Drupal\entity_access_by_field\Tests;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\entity_access_by_field\EntityAccessHelper;


/**
 * Unit test of entity_access_by_field hook_node_access implementation.
 */
class EntityAccessTest extends UnitTestCase {

  /**
   * Tests the EntityAccessHelper::nodeAccessCheck for Neutral Access.
   */
  public function testNeutralAccess() {

    $node = $this->prophesize(NodeInterface::class);

    $this->fieldType = $this->randomMachineName();
    $fieldDefinitionInterface = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $fieldDefinitionInterface->expects($this->once())
      ->method('getType')
      ->willReturn($this->fieldType);

    $node->getFieldDefinitions()->willReturn(array($this->fieldType => $fieldDefinitionInterface));
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophesize(AccountInterface::class)->reveal();
    $access_result = EntityAccessHelper::nodeAccessCheck($node, $op, $account);
    $this->assertEquals(0, $access_result);
    $this->assertNotEquals(2, $access_result);
    $this->assertNotEquals(1, $access_result);
  }

  /**
   * Tests the EntityAccessHelper::nodeAccessCheck for Forbidden Access.
   */
  public function testForbiddenAccess() {

    $node = $this->prophesize(NodeInterface::class);

    $this->fieldId = 'node.article.field_content_visibility';
    $this->fieldValue = 'public';
    $this->fieldType = 'entity_access_field';
    $fieldDefinitionInterface = $this->getMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->once())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->any())
      ->method('id')
      ->willReturn('node.article.field_content_visibility');

    $fieldItemListInterface = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $fieldItemListInterface->expects($this->any())
      ->method('getValue')
      ->willReturn(array(0 => array('value' => 'public')));

    $node->get('entity_access_field')->willReturn($fieldItemListInterface);
    $node->getFieldDefinitions()->willReturn(array($this->fieldType => $fieldDefinitionInterface));
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId  . ':' . $this->fieldValue . ' content', $account)
    ->willReturn(FALSE);
    $account = $account->reveal();

    $access_result = EntityAccessHelper::nodeAccessCheck($node, $op, $account);
    $this->assertEquals(1, $access_result);
    $this->assertNotEquals(0, $access_result);
    $this->assertNotEquals(2, $access_result);

  }

  /**
   * Tests the EntityAccessHelper::nodeAccessCheck for Allowed Access.
   */
  public function testAllowedAccess() {

    $node = $this->prophesize(NodeInterface::class);

    $this->fieldId = 'node.article.field_content_visibility';
    $this->fieldValue = 'public';
    $this->fieldType = 'entity_access_field';
    $fieldDefinitionInterface = $this->getMock('Drupal\Core\Field\FieldConfigInterface');
    $fieldDefinitionInterface->expects($this->once())
      ->method('getType')
      ->willReturn($this->fieldType);
    $fieldDefinitionInterface->expects($this->any())
      ->method('id')
      ->willReturn($this->fieldId);

    $fieldItemListInterface = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $fieldItemListInterface->expects($this->any())
      ->method('getValue')
      ->willReturn(array(0 => array('value' => $this->fieldValue)));

    $node->get('entity_access_field')->willReturn($fieldItemListInterface);
    $node->getFieldDefinitions()->willReturn(array($this->fieldType => $fieldDefinitionInterface));
    $node->getCacheContexts()->willReturn(NULL);
    $node = $node->reveal();

    $op = 'view';

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('view ' . $this->fieldId  . ':' . $this->fieldValue . ' content', $account)
      ->willReturn(TRUE);
    $account = $account->reveal();

    $access_result = EntityAccessHelper::nodeAccessCheck($node, $op, $account);
    $this->assertEquals(2, $access_result);
    $this->assertNotEquals(0, $access_result);
    $this->assertNotEquals(1, $access_result);
  }

}
