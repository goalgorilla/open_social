<?php

namespace Drupal\facets\Tests;

/**
 * Tests the functionality of the facet source config entity.
 *
 * @group facets
 */
class FacetSourceTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'search_api',
    'search_api_test_backend',
    'facets',
    'facets_search_api_dependency',
    'facets_query_processor',
  ];

  /**
   * Tests the facet source editing.
   */
  public function testEditFilterKey() {
    // Make sure we're logged in with a user that has sufficient permissions.
    $this->drupalLogin($this->adminUser);

    // Test the overview.
    $this->drupalGet('admin/config/search/facets');
    $this->assertLink($this->t('Configure'));
    $this->clickLink($this->t('Configure'));

    // Test the edit page.
    $edit = array(
      'filter_key' => 'fq',
    );
    $this->assertField('filter_key');
    $this->assertField('url_processor');
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertResponse(200);

    // Test that saving worked filter_key has the new value.
    $this->assertField('filter_key');
    $this->assertField('url_processor');
    $this->assertRaw('fq');
  }

  /**
   * Tests editing the url processor.
   */
  public function testEditUrlProcessor() {
    // Make sure we're logged in with a user that has sufficient permissions.
    $this->drupalLogin($this->adminUser);

    // Test the overview.
    $this->drupalGet('admin/config/search/facets');
    $this->assertLink($this->t('Configure'));
    $this->clickLink($this->t('Configure'));

    // Test the edit page.
    $edit = array(
      'url_processor' => 'dummy_query',
    );
    $this->assertField('filter_key');
    $this->assertField('url_processor');
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertResponse(200);

    // Test that saving worked and that the url processor has the new value.
    $this->assertField('filter_key');
    $this->assertField('url_processor');
    $elements = $this->xpath('//input[@id=:id]', [':id' => 'edit-url-processor-dummy-query']);
    $this->assertEqual('dummy_query', $elements[0]['value']);
  }

}
