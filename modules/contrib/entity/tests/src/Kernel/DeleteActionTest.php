<?php

/**
 * @file
 * Contains \Drupal\Tests\entity\Kernel\DeleteActionTest.
 */

namespace Drupal\Tests\entity\Kernel;

use Drupal\entity\Plugin\Action\DeleteAction;
use Drupal\entity_module_test\Entity\EnhancedEntity;
use Drupal\entity_module_test\Entity\EnhancedEntityBundle;
use Drupal\system\Entity\Action;
use Drupal\user\Entity\User;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the delete entity action.
 * @group entity
 */
class DeleteActionTest extends KernelTestBase {

  /**
   * The current user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['action', 'node', 'entity_module_test', 'entity',
                            'user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_enhanced');
    $this->installSchema('system', ['key_value_expire', 'sequences']);

    $bundle = EnhancedEntityBundle::create([
      'id' => 'default',
      'label' => 'Default',
    ]);
    $bundle->save();

    $this->user = User::create([
      'name' => 'username',
      'status' => 1,
    ]);
    $this->user->save();
    \Drupal::service('current_user')->setAccount($this->user);
  }

  public function testAction() {
    /** @var \Drupal\system\ActionConfigEntityInterface $action */
    $action = Action::create([
      'id' => 'enhanced_entity_delete_action',
      'label' => 'Delete enhanced entity',
      'plugin' => 'entity_delete_action:entity_test_enhanced',
    ]);
    $status = $action->save();
    $this->assertEquals(SAVED_NEW, $status);
    $this->assertInstanceOf(DeleteAction::class, $action->getPlugin());

    $entities = [];
    for ($i = 0; $i < 2; $i++) {
      $entity = EnhancedEntity::create([
        'type' => 'default',
      ]);
      $entity->save();
      $entities[$entity->id()] = $entity;
    }

    $action->execute($entities);
    // Confirm that the entity ids and langcodes are now in the tempstore.
    $tempstore = \Drupal::service('user.private_tempstore')->get('entity_delete_multiple_confirm');
    $selection = $tempstore->get($this->user->id());
    $this->assertEquals(array_keys($entities), array_keys($selection));
    $this->assertEquals([['en' => 'en'], ['en' => 'en']], array_values($selection));
  }

}
