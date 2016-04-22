<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\search_api\Plugin\search_api\processor\RoleFilter;
use Drupal\search_api\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Role filter" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\RoleFilter
 */
class RoleFilterTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\search_api\processor\RoleFilter
   */
  protected $processor;

  /**
   * The test items to use.
   *
   * @var \Drupal\search_api\Item\ItemInterface[]
   */
  protected $items = array();

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->processor = new RoleFilter(array(), 'role_filter', array());

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $node_datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $node_datasource->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('node'));
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $node_datasource */
    $user_datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $user_datasource->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue('user'));
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $user_datasource */

    $item = Utility::createItem($index, Utility::createCombinedId('entity:node', '1:en'), $node_datasource);
    $node = $this->getMockBuilder('Drupal\Tests\search_api\TestNodeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    /** @var \Drupal\node\NodeInterface $node */
    $item->setOriginalObject(EntityAdapter::createFromEntity($node));
    $this->items[$item->getId()] = $item;

    $item = Utility::createItem($index, Utility::createCombinedId('entity:user', '1:en'), $user_datasource);
    $account1 = $this->getMockBuilder('Drupal\Tests\search_api\TestUserInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $account1->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue(array('authenticated' => 'authenticated', 'editor' => 'editor')));
    /** @var \Drupal\user\UserInterface $account1 */
    $item->setOriginalObject(EntityAdapter::createFromEntity($account1));
    $this->items[$item->getId()] = $item;

    $item = Utility::createItem($index, Utility::createCombinedId('entity:user', '2:en'), $user_datasource);
    $account2 = $this->getMockBuilder('Drupal\Tests\search_api\TestUserInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $account2->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue(array('authenticated' => 'authenticated')));
    /** @var \Drupal\user\UserInterface $account2 */
    $item->setOriginalObject(EntityAdapter::createFromEntity($account2));
    $this->items[$item->getId()] = $item;
  }

  /**
   * Tests preprocessing search items with an inclusive filter.
   */
  public function testFilterInclusive() {
    $configuration['roles'] = array('authenticated' => 'authenticated');
    $configuration['default'] = 0;
    $this->processor->setConfiguration($configuration);

    $this->processor->preprocessIndexItems($this->items);

    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:user', '1:en')]), 'User with two roles was not removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:user', '2:en')]), 'User with only the authenticated role was not removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:node', '1:en')]), 'Node item was not removed.');
  }

  /**
   * Tests preprocessing search items with an exclusive filter.
   */
  public function testFilterExclusive() {
    $configuration['roles'] = array('editor' => 'editor');
    $configuration['default'] = 1;
    $this->processor->setConfiguration($configuration);

    $this->processor->preprocessIndexItems($this->items);

    $this->assertTrue(empty($this->items[Utility::createCombinedId('entity:user', '1:en')]), 'User with editor role was successfully removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:user', '2:en')]), 'User without the editor role was not removed.');
    $this->assertTrue(!empty($this->items[Utility::createCombinedId('entity:node', '1:en')]), 'Node item was not removed.');
  }

}
