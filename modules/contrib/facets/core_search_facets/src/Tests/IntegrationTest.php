<?php

namespace Drupal\core_search_facets\Tests;

use Drupal\facets\Tests\ExampleContentTrait;

/**
 * Tests the admin UI with the core search facet source.
 *
 * @group core_search_facets
 */
class IntegrationTest extends WebTestBase {

  use ExampleContentTrait;

  /**
   * The block entities used by this test.
   *
   * @var \Drupal\block\BlockInterface[]
   */
  protected $blocks;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    // Index the content.
    \Drupal::service('plugin.manager.search')->createInstance('node_search')->updateIndex();
    search_update_totals();

    // Make absolutely sure the ::$blocks variable doesn't pass information
    // along between tests.
    $this->blocks = [];
  }

  /**
   * Tests various operations via the Facets' admin UI.
   */
  public function testFramework() {

    $facet_id = 'test_facet_name';
    $facet_name = 'Test Facet Name';

    // Check if the overview is empty.
    $this->checkEmptyOverview();

    // Add a new facet and edit it.
    $this->addFacet($facet_id, $facet_name, 'type');

    // Create and place a block for "Test Facet name" facet.
    $this->createFacetBlock($facet_id);

    // Verify that the facet results are correct.
    $this->drupalGet('search/node', ['query' => ['keys' => 'test']]);
    $this->assertLink('page');
    $this->assertLink('article');

    // Verify that facet blocks appear as expected.
    $this->assertFacetBlocksAppear();

    $this->setShowAmountOfResults($facet_id, TRUE);

    // Verify that the number of results per item.
    $this->drupalGet('search/node', ['query' => ['keys' => 'test']]);
    $this->assertLink('page (19)');
    $this->assertLink('article (10)');

    // Verify that the label is correct for a clicked link.
    $this->clickLink('page (19)');
    $this->assertLink('(-) page (19)');

    // Do not show the block on empty behaviors.
    // Truncate the search_index table because, for the moment, we don't have
    // the possibility to clear the index from the API.
    // See https://www.drupal.org/node/326062
    \Drupal::database()->truncate('search_index')->execute();

    // Verify that no facet blocks appear. Empty behavior "None" is selected by
    // default.
    $this->drupalGet('search/node', ['query' => ['keys' => 'test']]);
    $this->assertNoFacetBlocksAppear();

    // Verify that the "empty_text" appears as expected.
    $this->setEmptyBehaviorFacetText($facet_name);
    $this->drupalGet('search/node', ['query' => ['keys' => 'test']]);
    $this->assertRaw('block-test-facet-name');
    $this->assertRaw('No results found for this block!');

    // Delete the block.
    $this->deleteBlock($facet_id);

    // Delete the facet and make sure the overview is empty again.
    $this->deleteUnusedFacet($facet_id, $facet_name);
    $this->checkEmptyOverview();

  }

  /**
   * Tests the date integration.
   */
  public function testDate() {
    $facet_name = 'Tardigrade';
    $facet_id = 'tardigrade';

    $this->addFacet($facet_id, $facet_name, 'created');
    $this->createFacetBlock($facet_id);
    $this->setShowAmountOfResults($facet_id, TRUE);

    // Assert date facets.
    $this->drupalGet('search/node', ['query' => ['keys' => 'test']]);
    $this->assertLink('February 2016 (9)');
    $this->assertLink('March 2016 (10)');
    $this->assertLink('April 2016 (10)');
    $this->assertResponse(200);

    $this->clickLink('March 2016 (10)');
    $this->assertResponse(200);
    $this->assertLink('March 8, 2016 (1)');
    $this->assertLink('March 9, 2016 (2)');

    $this->clickLink('March 9, 2016 (2)');
    $this->assertResponse(200);
    $this->assertLink('10 AM (1)');
    $this->assertLink('12 PM (1)');

    $this->drupalGet('search/node', ['query' => ['keys' => 'test']]);
    $this->assertLink('April 2016 (10)');
    $this->clickLink('April 2016 (10)');
    $this->assertResponse(200);
    $this->assertLink('April 1, 2016 (1)');
    $this->assertLink('April 2, 2016 (1)');
  }

  /**
   * Tests for CRUD operations.
   */
  public function testCrudFacet() {
    $facet_name = "Test Facet name";
    $facet_id = 'test_facet_name';

    // Check if the overview is empty.
    $this->checkEmptyOverview();

    // Add a new facet and edit it.
    $this->addFacetCheck($facet_id, $facet_name, 'type');
    $this->editFacetCheck($facet_id, $facet_name);

    // Create and place a block.
    $this->createFacetBlock($facet_id);

    // Delete the block.
    $this->deleteBlock($facet_id);

    // Delete the facet.
    $this->deleteUnusedFacet($facet_id, $facet_name);
  }

  /**
   * Creates a new facet.
   *
   * @param string $id
   *   The facet's id.
   * @param string $name
   *   The facet's name.
   * @param string $type
   *   The field type.
   */
  public function addFacet($id, $name, $type) {
    $this->drupalGet('admin/config/search/facets/add-facet');
    $form_values = [
      'id' => $id,
      'status' => 1,
      'url_alias' => $id,
      'name' => $name,
      'weight' => 2,
      'facet_source_id' => 'core_node_search:node_search',
      'facet_source_configs[core_node_search:node_search][field_identifier]' => $type,
    ];
    $this->drupalPostForm(NULL, ['facet_source_id' => 'core_node_search:node_search'], $this->t('Configure facet source'));
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
  }

  /**
   * Configures the possibility to show the amount of results for facet blocks.
   *
   * @param string $facet_id
   *   The id of the facet.
   * @param bool|TRUE $show
   *   Boolean to determine if we want to show the amount of results.
   */
  protected function setShowAmountOfResults($facet_id, $show = TRUE) {

    $facet_display_page = '/admin/config/search/facets/' . $facet_id . '/display';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_display_page);
    $this->assertResponse(200);

    // Configure the text for empty results behavior.
    $edit = [
      'widget_configs[show_numbers]' => $show,
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
  }

  /**
   * Deletes a facet block by id.
   *
   * @param string $id
   *   The id of the block.
   */
  protected function deleteBlock($id) {
    $this->drupalGet('admin/structure/block/manage/' . $this->blocks[$id]->id(), array('query' => array('destination' => 'admin')));
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertRaw(t('The block %name has been deleted.', array('%name' => $this->blocks[$id]->label())));
  }

  /**
   * Asserts that a facet block does not appear.
   */
  protected function assertNoFacetBlocksAppear() {
    foreach ($this->blocks as $block) {
      $this->assertNoBlockAppears($block);
    }
  }

  /**
   * Asserts that a facet block appears.
   */
  protected function assertFacetBlocksAppear() {
    foreach ($this->blocks as $block) {
      $this->assertBlockAppears($block);
    }
  }

  /**
   * Creates a facet block by id.
   *
   * @param string $id
   *   The id of the block.
   */
  protected function createFacetBlock($id) {
    $block_values = ['region' => 'footer', 'id' => str_replace('_', '-', $id)];
    $this->blocks[$id] = $this->drupalPlaceBlock('facet_block:' . $id, $block_values);
  }

  /**
   * Configures empty behavior option to show a text on empty results.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function setEmptyBehaviorFacetText($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_display_page = '/admin/config/search/facets/' . $facet_id . '/display';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_display_page);
    $this->assertResponse(200);

    // Configure the text for empty results behavior.
    $edit = [
      'facet_settings[empty_behavior]' => 'text',
      'facet_settings[empty_behavior_container][empty_behavior_text][value]' => 'No results found for this block!',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
  }

  /**
   * Configures a facet to only be visible when accessing to the facet source.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function setOptionShowOnlyWhenFacetSourceVisible($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_display_page = '/admin/config/search/facets/' . $facet_id . '/display';
    $this->drupalGet($facet_display_page);
    $this->assertResponse(200);

    $edit = [
      'facet_settings[only_visible_when_facet_source_is_visible]' => TRUE,
      'widget' => 'links',
      'widget_configs[show_numbers]' => '0',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
  }

  /**
   * Get the facet overview page and make sure the overview is empty.
   */
  protected function checkEmptyOverview() {
    $facet_overview = '/admin/config/search/facets';
    $this->drupalGet($facet_overview);
    $this->assertResponse(200);

    // The list overview has Field: field_name as description. This tests on the
    // absence of that.
    $this->assertNoText('Field:');
  }

  /**
   * Tests adding a facet trough the interface.
   *
   * @param string $facet_id
   *   The id of the facet.
   * @param string $facet_name
   *   The name of the facet.
   * @param string $type
   *   The field type.
   */
  protected function addFacetCheck($facet_id, $facet_name, $type) {
    // Go to the Add facet page and make sure that returns a 200.
    $facet_add_page = '/admin/config/search/facets/add-facet';
    $this->drupalGet($facet_add_page);
    $this->assertResponse(200);

    $form_values = [
      'name' => '',
      'id' => $facet_id,
      'status' => 1,
      'url_alias' => $facet_id,
      'weight' => 4,
    ];

    // Try filling out the form, but without having filled in a name for the
    // facet to test for form errors.
    $this->drupalPostForm($facet_add_page, $form_values, $this->t('Save'));
    $this->assertText($this->t('Facet name field is required.'));
    $this->assertText($this->t('Facet source field is required.'));

    // Make sure that when filling out the name, the form error disappears.
    $form_values['name'] = $facet_name;
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertNoText($this->t('Facet name field is required.'));

    // Configure the facet source by selecting one of the Search API views.
    $this->drupalGet($facet_add_page);
    $this->drupalPostForm(NULL, ['facet_source_id' => 'core_node_search:node_search'], $this->t('Configure facet source'));

    // The facet field is still required.
    $this->drupalPostForm(NULL, $form_values, $this->t('Save'));
    $this->assertText($this->t('Facet field field is required.'));

    // Fill in all fields and make sure the 'field is required' message is no
    // longer shown.
    $facet_source_form = [
      'facet_source_configs[core_node_search:node_search][field_identifier]' => $type,
    ];
    $this->drupalPostForm(NULL, $form_values + $facet_source_form, $this->t('Save'));
    $this->assertNoText('field is required.');

    // Make sure that the redirection to the display page is correct.
    $this->assertRaw(t('Facet %name has been created.', ['%name' => $facet_name]));
    $this->assertUrl('admin/config/search/facets/' . $facet_id . '/display');

    $this->drupalGet('admin/config/search/facets');
  }

  /**
   * Tests editing of a facet through the UI.
   *
   * @param string $facet_id
   *   The id of the facet.
   * @param string $facet_name
   *   The name of the facet.
   */
  public function editFacetCheck($facet_id, $facet_name) {
    $facet_edit_page = '/admin/config/search/facets/' . $facet_id . '/edit';

    // Go to the facet edit page and make sure "edit facet %facet" is present.
    $this->drupalGet($facet_edit_page);
    $this->assertResponse(200);
    $this->assertRaw($this->t('Edit facet @facet', ['@facet' => $facet_name]));

    // Change the facet name to add in "-2" to test editing of a facet works.
    $form_values = ['name' => $facet_name . ' - 2'];
    $this->drupalPostForm($facet_edit_page, $form_values, $this->t('Save'));

    // Make sure that the redirection back to the overview was successful and
    // the edited facet is shown on the overview page.
    $this->assertRaw(t('Facet %name has been updated.', ['%name' => $facet_name . ' - 2']));

    // Make sure the "-2" suffix is still on the facet when editing a facet.
    $this->drupalGet($facet_edit_page);
    $this->assertRaw($this->t('Edit facet @facet', ['@facet' => $facet_name . ' - 2']));

    // Edit the form and change the facet's name back to the initial name.
    $form_values = ['name' => $facet_name];
    $this->drupalPostForm($facet_edit_page, $form_values, $this->t('Save'));

    // Make sure that the redirection back to the overview was successful and
    // the edited facet is shown on the overview page.
    $this->assertRaw(t('Facet %name has been updated.', ['%name' => $facet_name]));
  }

  /**
   * This deletes an unused facet through the UI.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function deleteUsedFacet($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertResponse(200);

    // Check that the facet by testing for the message and the absence of the
    // facet name on the overview.
    $this->assertRaw($this->t("The facet is currently used in a block and thus can't be removed. Remove the block first."));
  }

  /**
   * This deletes a facet through the UI.
   *
   * @param string $facet_id
   *   The id of the facet.
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function deleteUnusedFacet($facet_id, $facet_name) {
    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertResponse(200);
    $this->assertText($this->t('This action cannot be undone'));
    // Actually submit the confirmation form.
    $this->drupalPostForm(NULL, [], $this->t('Delete'));

    // Check that the facet by testing for the message and the absence of the
    // facet name on the overview.
    $this->assertRaw($this->t('The facet %facet has been deleted.', ['%facet' => $facet_name]));

    // Refresh the page because on the previous page the $facet_name is still
    // visible (in the message).
    $facet_overview = '/admin/config/search/facets';
    $this->drupalGet($facet_overview);
    $this->assertResponse(200);
    $this->assertNoText($facet_name);
  }

  /**
   * Convert facet name to machine name.
   *
   * @param string $facet_name
   *   The name of the facet.
   *
   * @return string
   *   The facet name changed to a machine name.
   */
  protected function convertNameToMachineName($facet_name) {
    return preg_replace('@[^a-zA-Z0-9_]+@', '_', strtolower($facet_name));
  }

  /**
   * Go to the Delete Facet Page using the facet name.
   *
   * @param string $facet_name
   *   The name of the facet.
   */
  protected function goToDeleteFacetPage($facet_name) {
    $facet_id = $this->convertNameToMachineName($facet_name);

    $facet_delete_page = '/admin/config/search/facets/' . $facet_id . '/delete';

    // Go to the facet delete page and make the warning is shown.
    $this->drupalGet($facet_delete_page);
    $this->assertResponse(200);
  }

}
