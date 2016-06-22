<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\search_api\Plugin\search_api\processor\Highlight;
use Drupal\search_api\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Highlight" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Highlight
 */
class HighlightTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\search_api\processor\Highlight
   */
  protected $processor;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->processor = new Highlight(array(), 'highlight', array());

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $this->getStringTranslationStub();
    $this->processor->setStringTranslation($translation);
  }

  /**
   * Tests postprocessing with an empty result set.
   */
  public function testPostprocessSearchResultsWithEmptyResult() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');

    $results = $this->getMockBuilder('\Drupal\search_api\Query\ResultSet')
      ->setMethods(array('getResultCount'))
      ->setConstructorArgs(array($query))
      ->getMock();

    $results->expects($this->once())
      ->method('getResultCount')
      ->will($this->returnValue(0));
    $results->expects($this->never())
      ->method('getQuery');
    $results->expects($this->never())
      ->method('setExtraData');
    /** @var \Drupal\search_api\Query\ResultSet $results */

    $this->processor->postprocessSearchResults($results);
    $output = $results->getExtraData('highlighted_fields');
    $this->assertEmpty($output, 'No highlighting data added to result set.');
  }

  /**
   * Tests postprocessing on a query without keywords.
   */
  public function testPostprocessSearchResultsWithoutKeywords() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');

    $results = $this->getMockBuilder('Drupal\search_api\Query\ResultSet')
      ->setMethods(array('getResultCount', 'getQuery'))
      ->setConstructorArgs(array($query))
      ->getMock();

    $query->expects($this->once())
      ->method('getKeys')
      ->will($this->returnValue(array()));

    $results->expects($this->once())
      ->method('getResultCount')
      ->will($this->returnValue(1));
    $results->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($query));
    $results->expects($this->never())
      ->method('setExtraData');
    /** @var \Drupal\search_api\Query\ResultSet $results */

    $this->processor->postprocessSearchResults($results);
    $output = $results->getExtraData('highlighted_fields');
    $this->assertEmpty($output, 'No highlighting data added to result set.');
  }

  /**
   * Tests field highlighting with a normal result set.
   */
  public function testPostprocessSearchResultsWithResults() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue('foo'));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array('Some foo value');
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getExtraData('highlighted_fields');
    $this->assertEquals('Some <strong>foo</strong> value', $output[$this->itemIds[0]]['body'][0], 'Highlighting is correctly applied to body field.');
  }

  /**
   * Tests changing the prefix and suffix used for highlighting.
   */
  public function testPostprocessSearchResultsWithChangedPrefixSuffix() {
    $this->processor->setConfiguration(array('prefix' => '<em>', 'suffix' => '</em>'));

    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'AND', 'foo')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array('Some foo value');
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getExtraData('highlighted_fields');
    $this->assertEquals('Some <em>foo</em> value', $output[$this->itemIds[0]]['body'][0], 'Highlighting is correctly applied');
  }

  /**
   * Tests whether field highlighting can be disabled.
   */
  public function testPostprocessSearchResultsWithoutHighlight() {
    $this->processor->setConfiguration(array('highlight' => 'never'));

    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'AND', 'foo')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array('Some foo value');
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getExtraData('highlighted_fields');
    $this->assertEmpty($output, 'Highlighting is not applied when disabled.');
  }

  /**
   * Tests field highlighting when previous highlighting is present.
   */
  public function testPostprocessSearchResultsWithPreviousHighlighting() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'AND', 'foo')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array('Some foo value');
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);
    $highlighted_fields[$this->itemIds[0]]["body_2"][0] = 'Old highlighting text';
    $highlighted_fields[$this->itemIds[0]]["body_2"][1] = 'More highlighting text';
    $results->setExtraData('highlighted_fields', $highlighted_fields);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getExtraData('highlighted_fields');
    $this->assertEquals('Some <strong>foo</strong> value', $output[$this->itemIds[0]]['body'][0], 'Highlighting correctly applied to body field.');
    $this->assertEquals('Old highlighting text', $output[$this->itemIds[0]]["body_2"][0], 'Old highlighting data is preserved.');
    $this->assertEquals('More highlighting text', $output[$this->itemIds[0]]["body_2"][1], 'Old highlighting data is preserved.');
  }

  /**
   * Tests whether highlighting works on a longer text.
   */
  public function testPostprocessSearchResultsExcerpt() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'AND', 'congue')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array($this->getFieldBody());
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getResultItems();
    $excerpt = $output[$this->itemIds[0]]->getExcerpt();
    $correct_output = '… tristique, ligula sit amet condimentum dapibus, lorem nunc <strong>congue</strong> velit, et dictum augue leo sodales augue. Maecenas …';
    $this->assertEquals($correct_output, $excerpt, 'Excerpt was added.');
  }

  /**
   * Tests whether highlighting works on a longer text matching near the end.
   */
  public function testPostprocessSearchResultsExerptMatchNearEnd() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'AND', 'diam')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array($this->getFieldBody());
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getResultItems();
    $excerpt = $output[$this->itemIds[0]]->getExcerpt();
    $correct_output = '… Fusce in mauris eu leo fermentum feugiat. Proin varius <strong>diam</strong> ante, non eleifend ipsum luctus sed. …';
    $this->assertEquals($correct_output, $excerpt, 'Excerpt was added.');
  }

  /**
   * Tests whether highlighting works with a changed excerpt length.
   */
  public function testPostprocessSearchResultsWithChangedExcerptLength() {
    $this->processor->setConfiguration(array('excerpt_length' => 64));

    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue('congue'));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array($this->getFieldBody());
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getResultItems();
    $excerpt = $output[$this->itemIds[0]]->getExcerpt();
    $correct_output = '… dapibus, lorem nunc <strong>congue</strong> velit, et dictum augue …';
    $this->assertEquals($correct_output, $excerpt, 'Excerpt has correct reduced length.');
  }

  /**
   * Tests whether adding an excerpt can be successfully disabled.
   */
  public function testPostprocessSearchResultsWithoutExcerpt() {
    $this->processor->setConfiguration(array('excerpt' => FALSE));

    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'AND', 'congue')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $field = Utility::createField($index, 'body');
    $field->setType('text');
    $field->setDatasourceId('entity:node');
    $field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $field)));

    $this->processor->setIndex($index);

    $body_values = array($this->getFieldBody());
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $excerpt = $items[$this->itemIds[0]]->getExcerpt();

    $this->assertEmpty($excerpt, 'No excerpt added when disabled.');
  }

  /**
   * Tests whether highlighting works on a longer text.
   */
  public function testPostprocessSearchResultsWithComplexKeys() {
    $keys = array(
      '#conjunction' => 'AND',
      array(
        '#conjunction' => 'OR',
        'foo',
        'bar',
      ),
      'baz',
      array(
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'text',
        'will',
      ),
    );
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue($keys));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $body_field = Utility::createField($index, 'body');
    $body_field->setType('text');
    $body_field->setDatasourceId('entity:node');
    $body_field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $body_field)));

    $this->processor->setIndex($index);

    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => array(
          'This foo text bar will get baz riddled with &lt;strong&gt; tags.',
        ),
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $highlighted_fields = $results->getExtraData('highlighted_fields');
    $this->assertEquals('This <strong>foo</strong> text <strong>bar</strong> will get <strong>baz</strong> riddled with &lt;strong&gt; tags.', $highlighted_fields[$this->itemIds[0]]['body'][0], 'Highlighting is correctly applied when keys are complex.');
    $correct_output = '… This <strong>foo</strong> text <strong>bar</strong> will get <strong>baz</strong> riddled with &lt;strong&gt; tags. …';
    $excerpt = $items[$this->itemIds[0]]->getExcerpt();
    $this->assertEquals($correct_output, $excerpt, 'Excerpt was added.');
  }

  /**
   * Tests field highlighting and excerpts for two fields.
   */
  public function testPostprocessSearchResultsWithTwoFields() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'AND', 'foo')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $body_field = Utility::createField($index, 'body');
    $body_field->setType('text');
    $body_field->setDatasourceId('entity:node');
    $body_field->setPropertyPath('body');
    $title_field = Utility::createField($index, 'title');
    $title_field->setType('text');
    $title_field->setDatasourceId('entity:node');
    $title_field->setPropertyPath('title');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $body_field, 'title' => $title_field)));

    $this->processor->setIndex($index);

    $body_values = array('Some foo value', 'foo bar');
    $title_values = array('Title foo');
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
      'title' => array(
        'type' => 'text',
        'values' => $title_values,
      ),
    );

    $items = $this->createItems($index, 1, $fields);

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getExtraData('highlighted_fields');
    $this->assertEquals('Some <strong>foo</strong> value', $output[$this->itemIds[0]]['body'][0], 'Highlighting is correctly applied to first body field value.');
    $this->assertEquals('<strong>foo</strong> bar', $output[$this->itemIds[0]]['body'][1], 'Highlighting is correctly applied to second body field value.');
    $this->assertEquals('Title <strong>foo</strong>', $output[$this->itemIds[0]]['title'][0], 'Highlighting is correctly applied to title field.');

    $excerpt = '… Some <strong>foo</strong> value … <strong>foo</strong> bar … Title <strong>foo</strong> …';
    $this->assertEquals($excerpt, $items[$this->itemIds[0]]->getExcerpt(), 'Correct excerpt created from two text fields.');
  }

  /**
   * Tests field highlighting and excerpts with two items.
   */
  public function testPostprocessSearchResultsWithTwoItems() {
    $query = $this->getMock('Drupal\search_api\Query\QueryInterface');
    $query->expects($this->atLeastOnce())
      ->method('getKeys')
      ->will($this->returnValue(array('#conjunction' => 'OR', 'foo')));
    /** @var \Drupal\search_api\Query\QueryInterface $query */

    /** @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject $index */
    $index = $this->getMock('Drupal\search_api\IndexInterface');

    $body_field = Utility::createField($index, 'body');
    $body_field->setType('text');
    $body_field->setDatasourceId('entity:node');
    $body_field->setPropertyPath('body');

    $index->expects($this->atLeastOnce())
      ->method('getFields')
      ->will($this->returnValue(array('body' => $body_field)));

    $this->processor->setIndex($index);

    $body_values = array('Some foo value', 'foo bar');
    $fields = array(
      'entity:node/body' => array(
        'type' => 'text',
        'values' => $body_values,
      ),
    );

    $items = $this->createItems($index, 2, $fields);

    $items[$this->itemIds[1]]->getField('body')->setValues(array('The second item also contains foo in its body.'));

    $results = Utility::createSearchResultSet($query);
    $results->setResultItems($items);
    $results->setResultCount(1);

    $this->processor->postprocessSearchResults($results);

    $output = $results->getExtraData('highlighted_fields');
    $this->assertEquals('Some <strong>foo</strong> value', $output[$this->itemIds[0]]['body'][0], 'Highlighting is correctly applied to first body field value.');
    $this->assertEquals('<strong>foo</strong> bar', $output[$this->itemIds[0]]['body'][1], 'Highlighting is correctly applied to second body field value.');
    $this->assertEquals('The second item also contains <strong>foo</strong> in its body.', $output[$this->itemIds[1]]['body'][0], 'Highlighting is correctly applied to second item.');

    $excerpt1 = '… Some <strong>foo</strong> value … <strong>foo</strong> bar …';
    $excerpt2 = '… The second item also contains <strong>foo</strong> in its body. …';
    $this->assertEquals($excerpt1, $items[$this->itemIds[0]]->getExcerpt(), 'Correct excerpt created from two text fields.');
    $this->assertEquals($excerpt2, $items[$this->itemIds[1]]->getExcerpt(), 'Correct excerpt created for second item.');
  }

  /**
   * Returns a long text to use for highlighting tests.
   *
   * @return string
   *   A Lorem Ipsum text.
   */
  protected function getFieldBody() {
    return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris dictum ultricies sapien id consequat.
Fusce tristique erat at dui ultricies, eu rhoncus odio rutrum. Praesent viverra mollis mauris a cursus.
Curabitur at condimentum orci. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.
Praesent suscipit massa non pretium volutpat. Suspendisse id lacus facilisis, fringilla mauris vitae, tincidunt turpis.
Proin a euismod libero. Nam aliquet neque nulla, nec placerat libero accumsan id. Quisque sit amet consequat lacus.
Donec mauris erat, iaculis id nisl nec, dapibus posuere lectus. Sed ultrices libero id elit volutpat sagittis.
Donec a tortor ullamcorper, tempus lectus at, ultrices felis. Nam nibh magna, dictum in massa ut, ornare venenatis enim.
Phasellus enim massa, condimentum eu sem vel, consectetur fermentum erat. Cras porttitor ut dolor interdum vehicula.
Vestibulum erat arcu, placerat quis gravida quis, venenatis vel magna. Pellentesque pellentesque lacus ut feugiat auctor.
Mauris libero magna, dictum in fermentum nec, blandit non augue.
Morbi sed viverra libero.Phasellus sem velit, sollicitudin in felis lacinia, suscipit auctor dolor.
Praesent dignissim dolor sed lobortis mattis.
Ut tristique, ligula sit amet condimentum dapibus, lorem nunc congue velit, et dictum augue leo sodales augue.
Maecenas eget mi ac massa sagittis malesuada. Fusce ac purus vel ipsum imperdiet vulputate.
Mauris vestibulum sapien sit amet elementum tincidunt. Aenean sollicitudin tortor pulvinar ante commodo sagittis.
Integer in nisi consequat, elementum felis in, consequat purus. Maecenas blandit ipsum id tellus accumsan, sit amet venenatis orci vestibulum.
Ut id erat venenatis, vehicula mi eget, gravida odio. Etiam dapibus purus in massa condimentum, vitae lobortis est aliquam.
Morbi tristique velit et sem varius rhoncus. In tincidunt sagittis libero. Integer interdum sit amet sem sit amet sodales.
Donec sit amet arcu sit amet leo tristique dignissim vel ut enim. Nulla faucibus lacus eu adipiscing semper. Sed ut sodales erat.
Sed mauris purus, tempor non eleifend et, mollis ut lacus. Etiam interdum velit justo, nec imperdiet nunc pulvinar sit amet.
Sed eu lacus eget augue laoreet vehicula id sed sem. Maecenas at condimentum massa, et pretium nulla. Aliquam sed nibh velit.
Quisque turpis lacus, sodales nec malesuada nec, commodo non purus.
Cras pellentesque, lectus ut imperdiet euismod, purus sem convallis tortor, ut fermentum elit nulla et quam.
Mauris luctus mattis enim non accumsan. Sed consequat sapien lorem, in ultricies orci posuere nec.
Fusce in mauris eu leo fermentum feugiat. Proin varius diam ante, non eleifend ipsum luctus sed.';
  }

}
