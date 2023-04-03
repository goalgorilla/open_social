<?php

namespace Drupal\Tests\entity_access_by_field\Kernel;

use Drupal\user\UserInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Test base for testing access records and grants for group nodes.
 */
abstract class EntityAccessNodeAccessTestBase extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['group', 'options', 'entity', 'variationcache', 'node', 'gnode', 'social_group', 'flag', 'address', 'image', 'file', 'entity_access_by_field'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account to use for retrieving the grants.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $account;

  /**
   * A dummy group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $group1;

  /**
   * Another dummy group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $group2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');
    $this->installEntitySchema('flag');
    $this->installEntitySchema('flagging');
    $this->installConfig(['group', 'node']);

    // Create the test user account.
    $this->account = $this->createUser(['uid' => 2]);

    // Create a group type.
    $storage = $this->entityTypeManager->getStorage('group_type');
    $values = ['label' => 'foo', 'description' => 'bar'];
    /** @var \Drupal\group\Entity\GroupTypeInterface $groupTypeA */
    $groupTypeA = $storage->create(['id' => 'a'] + $values);
    $groupTypeA->save();

    // Create some node types.
    $storage = $this->entityTypeManager->getStorage('node_type');
    $values = ['name' => 'foo', 'description' => 'bar'];
    $storage->create(['type' => 'a'] + $values)->save();
    $storage->create(['type' => 'b'] + $values)->save();

    // Install some node types on the group type.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($groupTypeA, 'group_node:a')->save();
    $storage->createFromPlugin($groupTypeA, 'group_node:b')->save();

    // Create some groups.
    $storage = $this->entityTypeManager->getStorage('group');
    $values = ['uid' => $this->account->id(), 'label' => 'foo'];
    $this->group1 = $storage->create(['type' => 'a'] + $values);
    $this->group2 = $storage->create(['type' => 'a'] + $values);
    $this->group1->save();
    $this->group2->save();
  }

}
