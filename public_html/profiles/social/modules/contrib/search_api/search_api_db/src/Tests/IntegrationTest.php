<?php

namespace Drupal\search_api_db\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that using the DB backend via the UI works as expected.
 *
 * @group search_api
 */
class IntegrationTest extends WebTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'search_api',
    'search_api_db',
  );

  /**
   * Tests that adding a server works.
   */
  public function testAddingServer() {
    $admin_user = $this->drupalCreateUser(array('administer search_api', 'access administration pages'));
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/search/search-api/add-server');
    $this->assertResponse(200);

    $edit = array('name' => ' ~`Test Server', 'id' => '_test');
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertUrl('admin/config/search/search-api/server/_test');
  }

}
