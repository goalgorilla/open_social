<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\search_api\Plugin\search_api\processor\AddURL;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "URL field" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\AddURL
 */
class AddURLTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\search_api\processor\AddURL
   */
  protected $processor;

  /**
   * A search index mock for the tests.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    // Create a mock for the URL to be returned.
    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();
    $url->expects($this->any())
      ->method('toString')
      ->will($this->returnValue('http://www.example.com/node/example'));

    // Mock the data source of the indexer to return the mocked url object.
    $datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $datasource->expects($this->any())
      ->method('getItemUrl')
      ->withAnyParameters()
      ->will($this->returnValue($url));

    // Create a mock for the index to return the datasource mock.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->index = $this->getMock('Drupal\search_api\IndexInterface');
    $this->index->expects($this->any())
      ->method('getDatasource')
      ->with('entity:node')
      ->will($this->returnValue($datasource));

    // Create the tested processor and set the mocked indexer.
    $this->processor = new AddURL(array(), 'add_url', array());
    $this->processor->setIndex($index);
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $this->getStringTranslationStub();
    $this->processor->setStringTranslation($translation);
  }

  /**
   * Tests whether indexed items are correctly preprocessed.
   */
  public function testProcessIndexItems() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getMockBuilder('Drupal\node\Entity\Node')
      ->disableOriginalConstructor()
      ->getMock();

    $body_value = array('Some text value');
    $fields = array(
      'search_api_url' => array(
        'type' => 'string',
      ),
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_value,
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, EntityAdapter::createFromEntity($node));

    // Process the items.
    $this->processor->preprocessIndexItems($items);

    // Check the valid item.
    $field = $items[$this->itemIds[0]]->getField('search_api_url');
    $this->assertEquals(array('http://www.example.com/node/example'), $field->getValues(), 'Valid URL added as value to the field.');

    // Check that no other fields were changed.
    $field = $items[$this->itemIds[0]]->getField('body');
    $this->assertEquals($body_value, $field->getValues(), 'Body field was not changed.');

    // Check the second item to be sure that all are processed.
    $field = $items[$this->itemIds[1]]->getField('search_api_url');
    $this->assertEquals(array('http://www.example.com/node/example'), $field->getValues(), 'Valid URL added as value to the field in the second item.');
  }

  /**
   * Tests whether the properties are correctly altered.
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AddURL::alterPropertyDefinitions()
   */
  public function testAlterPropertyDefinitions() {
    $properties = array();

    // Check for modified properties when no data source is given.
    $this->processor->alterPropertyDefinitions($properties, NULL);
    $property_added = array_key_exists('search_api_url', $properties);
    $this->assertTrue($property_added, 'The "search_api_url" property was added to the properties.');
    if ($property_added) {
      $this->assertInstanceOf('Drupal\Core\TypedData\DataDefinitionInterface', $properties['search_api_url'], 'The "search_api_url" property contains a valid data definition.');
      if ($properties['search_api_url'] instanceof DataDefinitionInterface) {
        $this->assertEquals('string', $properties['search_api_url']->getDataType(), 'Correct data type set in the data definition.');
        $this->assertEquals('URI', $properties['search_api_url']->getLabel(), 'Correct label set in the data definition.');
        $this->assertEquals('A URI where the item can be accessed', $properties['search_api_url']->getDescription(), 'Correct description set in the data definition.');
      }
    }

    // Test whether the properties of specific datasources stay untouched.
    $properties = array();
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $this->processor->alterPropertyDefinitions($properties, $datasource);
    $this->assertEmpty($properties, 'Datasource-specific properties did not get changed.');
  }

}
