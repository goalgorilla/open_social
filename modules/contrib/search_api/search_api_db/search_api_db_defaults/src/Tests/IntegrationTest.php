<?php

namespace Drupal\search_api_db_defaults\Tests;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the correct installation of the default configs.
 *
 * @group search_api
 */
class IntegrationTest extends WebTestBase {

  use StringTranslationTrait, CommentTestTrait, EntityReferenceTestTrait;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * A non-admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $authenticatedUser;

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create user with content access permission to see if the view is
    // accessible, and an admin to do the setup.
    $this->authenticatedUser = $this->drupalCreateUser();
    $this->adminUser = $this->drupalCreateUser(array(), NULL, TRUE);
  }

  /**
   * Tests whether the default search was correctly installed.
   */
  protected function testInstallAndDefaultSetupWorking() {
    $this->drupalLogin($this->adminUser);

    // Install the search_api_db_defaults module.
    $edit_enable = array(
      'modules[Search][search_api_db_defaults][enable]' => TRUE,
    );
    $this->drupalPostForm('admin/modules', $edit_enable, t('Install'));

    $this->assertText(t('Some required modules must be enabled'), 'Dependencies required.');

    $this->drupalPostForm(NULL, NULL, t('Continue'));
    $args = array(
      '@count' => 3,
      '%names' => 'Database Search Defaults, Database search, Search API',
    );
    $success_text = strip_tags(t('@count modules have been enabled: %names.', $args));
    $this->assertText($success_text, 'Modules have been installed.');

    $this->rebuildContainer();

    $server = Server::load('default_server');
    $this->assertTrue($server, 'Server can be loaded');

    $index = Index::load('default_index');
    $this->assertTrue($index, 'Index can be loaded');

    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('search/content');
    $this->assertResponse(200, 'Authenticated user can access the search view.');
    $this->drupalLogin($this->adminUser);

    // Uninstall the module.
    $edit_disable = array(
      'uninstall[search_api_db_defaults]' => TRUE,
    );
    $this->drupalPostForm('admin/modules/uninstall', $edit_disable, t('Uninstall'));
    $this->drupalPostForm(NULL, array(), t('Uninstall'));
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('search_api_db_defaults'), 'Search API DB Defaults module uninstalled.');

    // Check if the server is found in the Search API admin UI.
    $this->drupalGet('admin/config/search/search-api/server/default_server');
    $this->assertResponse(200, 'Server was not removed.');

    // Check if the index is found in the Search API admin UI.
    $this->drupalGet('admin/config/search/search-api/index/default_index');
    $this->assertResponse(200, 'Index was not removed.');

    $this->drupalLogin($this->authenticatedUser);
    $this->drupalGet('search/content');
    $this->assertResponse(200, 'Authenticated user can access the search view.');
    $this->drupalLogin($this->adminUser);

    // Enable the module again. This should fail because the either the index
    // or the server or the view was found.
    $this->drupalPostForm('admin/modules', $edit_enable, t('Install'));
    $this->assertText(t('It looks like the default setup provided by this module already exists on your site. Cannot re-install module.'));

    // Delete all the entities that we would fail on if they exist.
    $entities_to_remove = array(
      'search_api_index' => 'default_index',
      'search_api_server' => 'default_server',
      'view' => 'search_content',
    );
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    foreach ($entities_to_remove as $entity_type => $entity_id) {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
      $entity_storage = $entity_type_manager->getStorage($entity_type);
      $entity_storage->resetCache();
      $entities = $entity_storage->loadByProperties(array('id' => $entity_id));

      if (!empty($entities[$entity_id])) {
        $entities[$entity_id]->delete();
      }
    }

    // Delete the article content type.
    $this->drupalGet('admin/structure/types/manage/article');
    $this->clickLink(t('Delete'));
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, array(), t('Delete'));

    // Try to install search_api_db_defaults module and test if it failed
    // because there was no content type "article".
    $this->drupalPostForm('admin/modules', $edit_enable, t('Install'));
    $success_text = t('Content type @content_type not found. Database Search Defaults module could not be installed.', array('@content_type' => 'article'));
    $this->assertText($success_text);
  }

}
