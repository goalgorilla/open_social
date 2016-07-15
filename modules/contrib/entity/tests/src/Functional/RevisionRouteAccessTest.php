<?php

namespace Drupal\Tests\entity\Functional;

use Drupal\entity_module_test\Entity\EnhancedEntity;
use Drupal\entity_module_test\Entity\EnhancedEntityBundle;
use Drupal\simpletest\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the revision route access check.
 *
 * @group entity
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class RevisionRouteAccessTest extends BrowserTestBase {

  use BlockCreationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface;
   */
  protected $account;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_module_test', 'user', 'entity', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    EnhancedEntityBundle::create([
      'id' => 'default',
      'label' => 'Default',
    ])->save();

    $this->placeBlock('local_tasks_block');
    $this->placeBlock('system_breadcrumb_block');

    $this->account = $this->drupalCreateUser([
      'administer entity_test_enhanced',
      'view all entity_test_enhanced revisions',
    ]);

    $this->drupalLogin($this->account);
  }

  /**
   * Test enhanced entity revision routes access.
   */
  public function testRevisionRouteAccess() {
    $entity = EnhancedEntity::create([
      'name' => 'rev 1',
      'type' => 'default',
    ]);
    $entity->save();

    $revision = clone $entity;
    $revision->name->value = 'rev 2';
    $revision->setNewRevision(TRUE);
    $revision->isDefaultRevision(FALSE);
    $revision->save();

    $this->drupalGet('/entity_test_enhanced/1/revisions');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Revisions');
    $collection_link = $this->getSession()->getPage()->findLink('Entity test with enhancements');
    $collection_link->click();
    $this->assertSession()->addressEquals('/entity_test_enhanced');
    $this->assertSession()->responseContains('Edit');
    $edit_link = $this->getSession()->getPage()->findLink('Edit');
    $edit_link->click();
    $this->assertSession()->addressEquals('/entity_test_enhanced/1/edit');
    // Check if we have revision tab link on edit page.
    $this->getSession()->getPage()->findLink('Revisions')->click();
    $this->assertSession()->addressEquals('/entity_test_enhanced/1/revisions');
    $this->drupalGet('/entity_test_enhanced/1/revisions/2/view');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('rev 2');
    $revisions_link = $this->getSession()->getPage()->findLink('Revisions');
    $revisions_link->click();
    $this->assertSession()->addressEquals('/entity_test_enhanced/1/revisions');
    $this->assertSession()->statusCodeEquals(200);
  }

}
