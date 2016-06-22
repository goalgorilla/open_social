<?php

namespace Drupal\search_api\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;

/**
 * Tests the overall functionality of indexing specific logic.
 *
 * @group search_api
 */
class LanguageIntegrationTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'node',
    'search_api',
    'search_api_test_backend',
    'language',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add extra languages.
    ConfigurableLanguage::createFromLangcode('nl')->save();
    ConfigurableLanguage::createFromLangcode('xx-lolspeak')->save();

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    // Create an index and server to work with.
    $this->getTestServer();
    $this->getTestIndex();

    // Log in, so we can test all the things.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests indexing with different language settings trough the UI.
   */
  public function testIndexSettings() {
    // Create 2 articles.
    $article1 = $this->drupalCreateNode(array('type' => 'article'));
    $article2 = $this->drupalCreateNode(array('type' => 'article'));

    // Those 2 new nodes should be added to the tracking table immediately.
    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 2, 'Two items are tracked.');

    // Add translations.
    $translation = array('title' => 'test NL', 'body' => 'NL body');
    $article1->addTranslation('nl', $translation)->save();
    $translation = array('title' => 'test2 NL', 'body' => 'NL body2');
    $article2->addTranslation('nl', $translation)->save();
    $translation = array('title' => 'cats', 'body' => 'Cats test');
    $article1->addTranslation('xx-lolspeak', $translation)->save();

    // The translations should be tracked as well, so we have a total of 5
    // indexed items.
    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 5, 'Five items are tracked.');

    // Clear index.
    $this->drupalGet($this->getIndexPath());
    $this->drupalPostForm(NULL, array(), $this->t('Clear all indexed data'));
    $this->drupalPostForm(NULL, array(), $this->t('Confirm'));

    // Make sure all 5 items are successfully indexed.
    $this->drupalGet($this->getIndexPath());
    $this->drupalPostForm(NULL, array(), $this->t('Index now'));
    $this->assertText($this->t('Successfully indexed 5 items'));

    // Change the datasource to disallow indexing of dutch.
    $form_values = array('datasource_configs[entity:node][languages][nl]' => 1);
    $this->drupalGet($this->getIndexPath('edit'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertText('The index was successfully saved.');

    // Make sure that we only have 3 indexed items now. The 2 original nodes
    // + 1 translation in lolspeak, the 2 dutch translations should be ignored.
    $this->drupalGet($this->getIndexPath());
    $this->drupalPostForm(NULL, array(), $this->t('Index now'));
    $this->assertText($this->t('Successfully indexed 3 items'));

    // Change the datasource to only allow indexing of dutch.
    $form_values = array(
      'datasource_configs[entity:node][default]' => 0,
      'datasource_configs[entity:node][bundles][article]' => 1,
      'datasource_configs[entity:node][languages][nl]' => 1,
    );
    $this->drupalGet($this->getIndexPath('edit'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertText('The index was successfully saved.');

    // Make sure that we only have 2 index items. The only indexed items should
    // be the dutch translations.
    $this->drupalGet($this->getIndexPath());
    $this->drupalPostForm(NULL, array(), $this->t('Index now'));
    $this->assertText($this->t('Successfully indexed 2 items'));
  }

  /**
   * Counts the number of tracked items in the test index.
   *
   * @return int
   *   The number of tracked items in the test index.
   */
  protected function countTrackedItems() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($this->indexId);
    return $index->getTrackerInstance()->getTotalItemsCount();
  }

}
