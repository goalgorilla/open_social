<?php

namespace Drupal\config_update_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verify the config revert report and its links.
 *
 * @group config
 */
class ConfigUpdateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Use the Search module because it has two included config items in its
   * config/install, assuming node and user are also enabled.
   *
   * @var array.
   */
  public static $modules = ['config', 'config_update', 'config_update_ui', 'search', 'node', 'user', 'block', 'text', 'field', 'filter'];

  /**
   * The admin user that will be created.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user and log in.
    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer search', 'view config updates report', 'synchronize configuration', 'export configuration', 'import configuration', 'revert configuration', 'delete configuration']);
    $this->drupalLogin($this->adminUser);

    // Make sure local tasks and page title are showing.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the config report and its linked pages.
   */
  public function testConfigReport() {
    // Test links to report page.
    $this->drupalGet('admin/config/development/configuration');
    $this->clickLink('Updates report');
    $this->assertNoReport();

    // Verify some empty reports.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);
    // Module, theme, and profile reports have no 'added' section.
    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], [], [], ['added']);
    $this->drupalGet('admin/config/development/configuration/report/theme/classy');
    $this->assertReport('Classy theme', [], [], [], [], ['added']);

    $inactive = ['locale.settings' => 'Simple configuration'];
    $this->drupalGet('admin/config/development/configuration/report/profile');
    $this->assertReport('Testing profile', [], [], [], $inactive, ['added']);

    // Delete the user search page from the search UI and verify report for
    // both the search page config type and user module.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $inactive = ['search.page.user_search' => 'Users'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], $inactive);
    $this->drupalGet('admin/config/development/configuration/report/module/user');
    $this->assertReport('User module', [], [], [], $inactive, ['added', 'changed']);

    // Use the import link to get it back. Do this from the search page
    // report to make sure we are importing the right config.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Import from source');
    $this->assertText('The configuration was imported');
    $this->assertNoReport();
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Edit the node search page from the search UI and verify report.
    $this->drupalGet('admin/config/search/pages');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'New label',
      'path'  => 'new_path',
    ], 'Save search page');
    $changed = ['search.page.node_search' => 'New label'];
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], $changed, []);

    // Test the show differences link.
    $this->clickLink('Show differences');
    $this->assertText('Content');
    $this->assertText('New label');
    $this->assertText('node');
    $this->assertText('new_path');

    // Test the Back link.
    $this->clickLink("Back to 'Updates report' page.");
    $this->assertNoReport();

    // Test the export link.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Export');
    $this->assertText('Here is your configuration:');
    $this->assertText('id: node_search');
    $this->assertText('New label');
    $this->assertText('path: new_path');
    $this->assertText('search.page.node_search.yml');

    // Test reverting.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Revert to source');
    $this->assertText('Are you sure you want to revert');
    $this->assertText('Search page');
    $this->assertText('node_search');
    $this->assertText('Customizations will be lost. This action cannot be undone');
    $this->drupalPostForm(NULL, [], 'Revert');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Add a new search page from the search UI and verify report.
    $this->drupalPostForm('admin/config/search/pages', [
      'search_type' => 'node_search',
    ], 'Add new page');
    $this->drupalPostForm(NULL, [
      'label' => 'test',
      'id'    => 'test',
      'path'  => 'test',
    ], 'Add search page');
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $added = ['search.page.test' => 'test'];
    $this->assertReport('Search page', [], $added, [], []);

    // Test the export link.
    $this->clickLink('Export');
    $this->assertText('Here is your configuration:');
    $this->assertText('id: test');
    $this->assertText('label: test');
    $this->assertText('path: test');
    $this->assertText('search.page.test.yml');

    // Test the delete link.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->clickLink('Delete');
    $this->assertText('Are you sure');
    $this->assertText('cannot be undone');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertText('The configuration was deleted');
    // And verify the report again.
    $this->drupalGet('admin/config/development/configuration/report/type/search_page');
    $this->assertReport('Search page', [], [], [], []);

    // Change the search module config and verify the actions work for
    // simple config.
    $this->drupalPostForm('admin/config/search/pages', [
      'minimum_word_size' => 4,
    ], 'Save configuration');
    $changed = ['search.settings' => 'search.settings'];
    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], $changed, [], ['added']);

    $this->clickLink('Show differences');
    $this->assertText('Config difference for Simple configuration search.settings');
    $this->assertText('index::minimum_word_size');
    $this->assertText('4');

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->clickLink('Export');
    $this->assertText('minimum_word_size: 4');

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->clickLink('Revert to source');
    $this->drupalPostForm(NULL, [], 'Revert');

    $this->drupalGet('admin/config/development/configuration/report/module/search');
    $this->assertReport('Search module', [], [], [], [], ['added']);
  }

  /**
   * Asserts that the report page has the correct content.
   *
   * Assumes you are already on the report page.
   *
   * @param string $title
   *   Report title to check for.
   * @param string[] $missing
   *   Array of items that should be listed as missing, name => label.
   * @param string[] $added
   *   Array of items that should be listed as missing, name => label.
   * @param string[] $changed
   *   Array of items that should be listed as changed, name => label.
   * @param string[] $inactive
   *   Array of items that should be listed as inactive, name => label.
   * @param string[] $skip
   *   Array of report sections to skip checking.
   */
  protected function assertReport($title, $missing, $added, $changed, $inactive, $skip = []) {
    $this->assertText('Configuration updates report for ' . $title);
    $this->assertText('Generate new report');

    if (!in_array('missing', $skip)) {
      $this->assertText('Missing configuration items');
      if (count($missing)) {
        foreach ($missing as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: all provided configuration items are in your active configuration.');
      }
      else {
        $this->assertText('None: all provided configuration items are in your active configuration.');
      }
    }

    if (!in_array('inactive', $skip)) {
      $this->assertText('Inactive optional items');
      if (count($inactive)) {
        foreach ($inactive as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: all optional configuration items are in your active configuration.');
      }
      else {
        $this->assertText('None: all optional configuration items are in your active configuration.');
      }
    }

    if (!in_array('added', $skip)) {
      $this->assertText('Added configuration items');
      if (count($added)) {
        foreach ($added as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: all active configuration items of this type were provided by modules, themes, or install profile.');
      }
      else {
        $this->assertText('None: all active configuration items of this type were provided by modules, themes, or install profile.');
      }
    }

    if (!in_array('changed', $skip)) {
      $this->assertText('Changed configuration items');
      if (count($changed)) {
        foreach ($changed as $name => $label) {
          $this->assertText($name);
          $this->assertText($label);
        }
        $this->assertNoText('None: no active configuration items differ from their current provided versions.');
      }
      else {
        $this->assertText('None: no active configuration items differ from their current provided versions.');
      }
    }
  }

  /**
   * Asserts that the report is not shown.
   *
   * Assumes you are already on the report form page.
   */
  protected function assertNoReport() {
    $this->assertText('Report type');
    $this->assertText('Configuration type');
    $this->assertText('Module');
    $this->assertText('Theme');
    $this->assertText('Installation profile');
    $this->assertText('Updates report');
    $this->assertNoText('Missing configuration items');
    $this->assertNoText('Added configuration items');
    $this->assertNoText('Changed configuration items');
    $this->assertNoText('Unchanged configuration items');
  }

}
