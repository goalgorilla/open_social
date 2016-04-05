<?php

namespace Drupal\facets\Tests;

/**
 * Tests the processor functionality.
 *
 * @group facets
 */
class ProcessorIntegrationTest extends WebTestBase {

  /**
   * The url of the edit form.
   *
   * @var string
   */
  protected $editForm;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    // Set up example content types and insert 10 new content items.
    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->assertEqual($this->indexItems($this->indexId), 5, '5 items were indexed.');
    $this->insertExampleContent();
    $this->assertEqual($this->indexItems($this->indexId), 5, '5 items were indexed.');
  }

  /**
   * Tests for the processors behavior in the backend.
   */
  public function testProcessorAdmin() {
    $facet_name = "Guanaco";
    $facet_id = "guanaco";

    $this->createFacet($facet_name, $facet_id);

    // Go to the processors form and check that the count limit processor is not
    // checked.
    $this->drupalGet('admin/config/search/facets/' . $facet_id . '/display');
    $this->assertNoFieldChecked('edit-facet-settings-count-limit-status');

    $form = ['facet_settings[count_limit][status]' => TRUE];
    $this->drupalPostForm(NULL, $form, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertFieldChecked('edit-facet-settings-count-limit-status');

    // Enable the sort processor and change sort direction, check that the
    // change is persisted.
    $form = [
      'facet_sorting[active_widget_order][status]' => TRUE,
      'facet_sorting[active_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm(NULL, $form, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertFieldChecked('edit-facet-sorting-active-widget-order-status');
    $this->assertFieldChecked('edit-facet-sorting-active-widget-order-settings-sort-desc');

    // Add an extra processor so we can test the weights as well.
    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $form, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertFieldChecked('edit-facet-settings-count-limit-status');
    $this->assertFieldChecked('edit-facet-settings-hide-non-narrowing-result-processor-status');
    $this->assertOptionSelected('edit-processors-count-limit-weights-build', -10);
    $this->assertOptionSelected('edit-processors-hide-non-narrowing-result-processor-weights-build', -10);

    // Change the weight of one of the processors and test that the weight
    // change persisted.
    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
      'processors[hide_non_narrowing_result_processor][weights][build]' => 5,
    ];
    $this->drupalPostForm(NULL, $form, $this->t('Save'));
    $this->assertFieldChecked('edit-facet-settings-count-limit-status');
    $this->assertFieldChecked('edit-facet-settings-hide-non-narrowing-result-processor-status');
    $this->assertOptionSelected('edit-processors-count-limit-weights-build', -10);
    $this->assertOptionSelected('edit-processors-hide-non-narrowing-result-processor-weights-build', 5);
  }

  /**
   * Tests the for processors in the frontend with a 'keywords' facet.
   */
  public function testProcessorIntegration() {
    $facet_name = "Vicuña";
    $facet_id = "vicuna";
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/display';

    $this->createFacet($facet_name, $facet_id, 'keywords');
    $this->createFacetBlock($facet_id);

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertText('grape');
    $this->assertText('orange');
    $this->assertText('apple');
    $this->assertText('strawberry');
    $this->assertText('banana');

    $this->checkCountLimitProcessor();
    $this->checkExcludeItems();
    $this->checkHideNonNarrowingProcessor();
    $this->checkHideActiveItems();
  }

  /**
   * Tests the for sorting processors in the frontend with a 'keywords' facet.
   */
  public function testSortingWidgets() {
    $facet_name = "Huacaya alpaca";
    $facet_id = "huacaya_alpaca";
    $this->editForm = 'admin/config/search/facets/' . $facet_id . '/display';

    $this->createFacet($facet_name, $facet_id, 'keywords');
    $this->createFacetBlock($facet_id);

    $this->checkSortByActive();
    $this->checkSortByCount();
    $this->checkSortByDisplay();
    $this->checkSortByRaw();
  }

  /**
   * Tests the count limit processor.
   */
  private function checkCountLimitProcessor() {
    $this->drupalGet($this->editForm);

    $form = [
      'widget_configs[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertFieldChecked('edit-facet-settings-count-limit-status');
    $form = [
      'widget_configs[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $form = [
      'widget_configs[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
      'facet_settings[count_limit][settings][minimum_items]' => 5,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertText('grape (6)');
    $this->assertNoText('apple (4)');

    $form = [
      'widget_configs[show_numbers]' => TRUE,
      'facet_settings[count_limit][status]' => TRUE,
      'facet_settings[count_limit][settings][minimum_items]' => 1,
      'facet_settings[count_limit][settings][maximum_items]' => 5,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertNoText('grape (6)');
    $this->assertText('apple (4)');

    $form = [
      'widget_configs[show_numbers]' => FALSE,
      'facet_settings[count_limit][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Tests the exclude items.
   */
  private function checkExcludeItems() {
    $form = [
      'facet_settings[exclude_specified_items][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $form = [
      'facet_settings[exclude_specified_items][status]' => TRUE,
      'facet_settings[exclude_specified_items][settings][exclude]' => 'banana',
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertText('grape');
    $this->assertNoText('banana');

    $form = [
      'facet_settings[exclude_specified_items][status]' => TRUE,
      'facet_settings[exclude_specified_items][settings][exclude]' => '(.*)berry',
      'facet_settings[exclude_specified_items][settings][regex]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertNoText('strawberry');
    $this->assertText('grape');

    $form = [
      'facet_settings[exclude_specified_items][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Tests hiding non-narrowing results.
   */
  private function checkHideNonNarrowingProcessor() {
    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertLink('grape');

    $this->clickLink('grape');
    $this->assertText('Displaying 6 search results');
    $this->assertLink('orange');

    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertLink('grape');

    $this->clickLink('grape');
    $this->assertText('Displaying 6 search results');
    $this->assertNoLink('orange');

    $form = [
      'facet_settings[hide_non_narrowing_result_processor][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Tests hiding active results.
   */
  private function checkHideActiveItems() {
    $form = [
      'facet_settings[hide_active_items_processor][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertText('Displaying 10 search results');
    $this->assertLink('grape');
    $this->assertLink('banana');

    $this->clickLink('grape');
    $this->assertText('Displaying 6 search results');
    $this->assertNoLink('grape');
    $this->assertLink('banana');

    $form = [
      'facet_settings[hide_active_items_processor][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Tests the active widget order.
   */
  protected function checkSortByActive() {
    $form = [
      'facet_sorting[active_widget_order][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('strawberry');
    $this->assertStringPosition('(-) strawberry', 'grape');

    $form = [
      'facet_sorting[active_widget_order][status]' => TRUE,
      'facet_sorting[active_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->clickLink('strawberry');
    $this->assertStringPosition('grape', '(-) strawberry');

    $form = [
      'facet_sorting[active_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Tests the active widget order.
   */
  protected function checkSortByCount() {
    $form = [
      'widget_configs[show_numbers]' => TRUE,
      'facet_sorting[count_widget_order][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('banana', 'apple');
    $this->assertStringPosition('banana', 'strawberry');
    $this->assertStringPosition('apple', 'orange');

    $form = [
      'facet_sorting[count_widget_order][status]' => TRUE,
      'facet_sorting[count_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('apple', 'banana');
    $this->assertStringPosition('strawberry', 'banana');
    $this->assertStringPosition('orange', 'apple');

    $form = [
      'widget_configs[show_numbers]' => FALSE,
      'facet_sorting[count_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Tests the display order.
   */
  public function checkSortByDisplay() {
    $form = [
      'facet_sorting[display_value_widget_order][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('grape', 'strawberry');
    $this->assertStringPosition('apple', 'banana');

    $form = [
      'facet_sorting[display_value_widget_order][status]' => TRUE,
      'facet_sorting[display_value_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('strawberry', 'grape');
    $this->assertStringPosition('banana', 'apple');

    $form = [
      'facet_sorting[display_value_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Tests the display order.
   */
  public function checkSortByRaw() {
    $form = [
      'facet_sorting[raw_value_widget_order][status]' => TRUE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('grape', 'strawberry');
    $this->assertStringPosition('apple', 'banana');

    $form = [
      'facet_sorting[raw_value_widget_order][status]' => TRUE,
      'facet_sorting[raw_value_widget_order][settings][sort]' => 'DESC',
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));

    $this->drupalGet('search-api-test-fulltext');
    $this->assertStringPosition('strawberry', 'grape');
    $this->assertStringPosition('banana', 'apple');

    $form = [
      'facet_sorting[raw_value_widget_order][status]' => FALSE,
    ];
    $this->drupalPostForm($this->editForm, $form, $this->t('Save'));
  }

  /**
   * Creates a facet block by id.
   *
   * @param string $id
   *   The id of the block.
   */
  protected function createFacetBlock($id) {
    $plugin_id = 'facet_block:' . $id;
    $settings = [
      'region' => 'footer',
      'id' => str_replace('_', '-', $id),
    ];
    $this->blocks[$id] = $this->drupalPlaceBlock($plugin_id, $settings);
  }

}
