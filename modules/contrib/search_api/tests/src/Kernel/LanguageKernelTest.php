<?php

namespace Drupal\Tests\search_api\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;

/**
 * Tests translation handling of the content entity datasource.
 *
 * @group search_api
 */
class LanguageKernelTest extends KernelTestBase {

  /**
   * The test entity type used in the test.
   *
   * @var string
   */
  protected $testEntityTypeId = 'entity_test_mul';

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array(
    'search_api',
    'search_api_test_backend',
    'language',
    'user',
    'system',
    'entity_test',
  );

  /**
   * An array of langcodes.
   *
   * @var string[]
   */
  protected $langcodes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Enable translation for the entity_test module.
    \Drupal::state()->set('entity_test.translation', TRUE);

    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));
    $this->installEntitySchema('entity_test_mul');

    // Create the default languages.
    $this->installConfig(array('language'));
    $this->langcodes = array();
    for ($i = 0; $i < 3; ++$i) {
      /** @var \Drupal\language\Entity\ConfigurableLanguage $language */
      $language = ConfigurableLanguage::create(array(
        'id' => 'l' . $i,
        'label' => 'language - ' . $i,
        'weight' => $i,
      ));
      $this->langcodes[$i] = $language->getId();
      $language->save();
    }

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    // Create a test server.
    $this->server = Server::create(array(
      'name' => 'Test Server',
      'id' => 'test_server',
      'status' => 1,
      'backend' => 'search_api_test_backend',
    ));
    $this->server->save();

    // Create a test index.
    $this->index = Index::create(array(
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => 1,
      'datasource_settings' => array(
        'entity:' . $this->testEntityTypeId => array(
          'plugin_id' => 'entity:' . $this->testEntityTypeId,
          'settings' => array(),
        ),
      ),
      'tracker_settings' => array(
        'default' => array(
          'plugin_id' => 'default',
          'settings' => array(),
        ),
      ),
      'server' => $this->server->id(),
      'options' => array('index_directly' => FALSE),
    ));
    $this->index->save();
  }

  /**
   * Tests translation handling of the content entity datasource.
   */
  public function testItemTranslations() {
    // Test retrieving language and translations when no translations are
    // available.
    /** @var \Drupal\entity_test\Entity\EntityTestMul $entity_1 */
    $entity_1 = EntityTestMul::create(array(
      'id' => 1,
      'name' => 'test 1',
      'user_id' => $this->container->get('current_user')->id(),
    ));
    $entity_1->save();
    $this->assertEquals('en', $entity_1->language()->getId(), new FormattableMarkup('%entity_type: Entity language set to site default.', array('%entity_type' => $this->testEntityTypeId)));
    $this->assertFalse($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', array('%entity_type' => $this->testEntityTypeId)));

    /** @var \Drupal\entity_test\Entity\EntityTestMul $entity_2 */
    $entity_2 = EntityTestMul::create(array(
      'id' => 2,
      'name' => 'test 2',
      'user_id' => $this->container->get('current_user')->id(),
    ));
    $entity_2->save();
    $this->assertEquals('en', $entity_2->language()->getId(), new FormattableMarkup('%entity_type: Entity language set to site default.', array('%entity_type' => $this->testEntityTypeId)));
    $this->assertFalse($entity_2->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', array('%entity_type' => $this->testEntityTypeId)));

    // Test that the datasource returns the correct item IDs.
    $datasource = $this->index->getDatasource('entity:' . $this->testEntityTypeId);
    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:en',
      '2:en',
    );
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids.');

    // Test indexing the new entity.
    $this->assertEquals(0, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The index is empty.');
    $this->assertEquals(2, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are two items to be indexed.');
    $this->index->indexItems();
    $this->assertEquals(2, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'Two items have been indexed.');

    // Now, make the first entity language-specific by assigning a language.
    $default_langcode = $this->langcodes[0];
    $entity_1->get('langcode')->setValue($default_langcode);
    $entity_1->save();
    $this->assertEquals(\Drupal::languageManager()->getLanguage($this->langcodes[0]), $entity_1->language(), new FormattableMarkup('%entity_type: Entity language retrieved.', array('%entity_type' => $this->testEntityTypeId)));
    $this->assertFalse($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: No translations are available', array('%entity_type' => $this->testEntityTypeId)));

    // Test that the datasource returns the correct item IDs.
    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:' . $this->langcodes[0],
      '2:en',
    );
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids.');

    // Test that the index needs to be updated.
    $this->assertEquals(1, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The updated item needs to be reindexed.');
    $this->assertEquals(2, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are two items in total.');

    // Set two translations for the first entity and test that the datasource
    // returns three separate item IDs, one for each translation.
    $translation = $entity_1->addTranslation($this->langcodes[1]);
    $translation->save();
    $translation = $entity_1->addTranslation($this->langcodes[2]);
    $translation->save();
    $this->assertTrue($entity_1->getTranslationLanguages(FALSE), new FormattableMarkup('%entity_type: Translations are available', array('%entity_type' => $this->testEntityTypeId)));

    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:' . $this->langcodes[0],
      '1:' . $this->langcodes[1],
      '1:' . $this->langcodes[2],
      '2:en',
    );
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids for a translated entity.');

    // Test that the index needs to be updated.
    $this->assertEquals(1, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The updated items needs to be reindexed.');
    $this->assertEquals(4, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are four items in total.');

    // Delete one translation and test that the datasource returns only three
    // items.
    $entity_1->removeTranslation($this->langcodes[2]);
    $entity_1->save();

    $datasource_item_ids = $datasource->getItemIds();
    sort($datasource_item_ids);
    $expected = array(
      '1:' . $this->langcodes[0],
      '1:' . $this->langcodes[1],
      '2:en',
    );
    $this->assertEquals($expected, $datasource_item_ids, 'Datasource returns correct item ids for a translated entity.');

    // Test reindexing.
    $this->assertEquals(3, $this->index->getTrackerInstance()->getTotalItemsCount(), 'There are three items in total.');
    $this->assertEquals(1, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'The updated items needs to be reindexed.');
    $this->index->indexItems();
    $this->assertEquals(3, $this->index->getTrackerInstance()->getIndexedItemsCount(), 'Three items are indexed.');
  }

}
