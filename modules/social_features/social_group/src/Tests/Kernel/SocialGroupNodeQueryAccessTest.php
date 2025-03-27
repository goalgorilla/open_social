<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Kernel;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\group\PermissionScopeInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\group\Traits\GroupTestTrait;
use Drupal\Tests\social_node\Kernel\NodeQueryAccessTestBase;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Tests access control for querying nodes with group visibility settings.
 *
 * This test class extends NodeQueryAccessTestBase and is responsible for
 * verifying that access settings related to group visibility are correctly
 * applied to content queries. It ensures that different user roles and
 * permissions are respected in determining whether nodes with specific
 * visibility settings can be viewed.
 *
 * @group social_group
 * @group social_node_query_access
 */
class SocialGroupNodeQueryAccessTest extends NodeQueryAccessTestBase {

  use GroupTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'group',
    'options',
    'entity',
    'variationcache',
    'node',
    'gnode',
    'social_group',
    'flag',
    'address',
    'image',
    'file',
    'entity_access_by_field',
    'flexible_permissions',
  ];

  /**
   * A stub group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $group;

  /**
   * User to check access to "group" content.
   */
  protected UserInterface $groupVisibilityUser;

  /**
   * A node with "group" visibility.
   */
  protected NodeInterface $groupNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    $this->installConfig(['group']);

    // To see a content with "group" visibility, users don't need to have
    // `view node.{$bundle}.field_content_visibility:group content` permission.
    // Enough to be just a member.
    // This causes one disadvantage that we can use in some cases.
    // There could be a case when we want to restrict access to all content
    // with "group" visibilities for a certain global role. When a user lost
    // a global role (for example, when a user lost "verified" role, user lost
    // access to any group user is a member, but still has access to content.
    $this->groupVisibilityUser = $this->createUser();

    $group_type = GroupType::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);

    $group_type->save();

    // "Group" module creates an additional access layer to the content that
    // belongs to groups.
    // So, we need to create an "insider scope" role to make sure members have
    // access to the content.
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ["view group_node:{$this->nodeTypeWithVisibility->id()} entity"],
    ]);

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'group_node:' . $this->nodeTypeWithVisibility->id())
      ->save();

    $this->group = $this->createGroup([
      'type' => $group_type->id(),
      'label' => $this->randomString(),
    ]);

    $this->groupNode = Node::create([
      'type' => $this->nodeTypeWithVisibility->id(),
      'title' => $this->randomString(),
      'field_content_visibility' => 'group',
      'status' => TRUE,
    ]);
    $this->groupNode->save();

    $this->group->addRelationship($this->groupNode, 'group_node:' . $this->nodeTypeWithVisibility->id());
    $this->group->addMember($this->groupVisibilityUser);
  }

  /**
   * Tests the access control for node queries with altered access settings.
   *
   *   This method verifies whether users with different roles and permissions
   *   have appropriate access to content nodes categorized
   *   by visibility settings such as "public", "community", or no visibility.
   *
   * @covers \Drupal\social_group\EventSubscriber\NodeQueryAccessAlterSubscriber::alterQueryAccess()
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testGroupNodeAlterQueryAccess(): void {
    // Check access to content for anonymous users.
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertNotContains($this->groupNode->id(), $query_results, 'Anonymous user does not have access to content with "group" visibility.');

    // Check access to "group" content.
    $this->setCurrentUser($this->groupVisibilityUser);
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertContains($this->groupNode->id(), $query_results, 'GroupUser has access to content with "group" visibility.');
    $this->assertNotContains($this->publicNode->id(), $query_results, 'GroupUser does not have access to content with "public" visibility.');
    $this->assertNotContains($this->communityNode->id(), $query_results, 'GroupUser does not have access to content with "community" visibility.');
    $this->assertNotContains($this->nodeWithoutVisibility->id(), $query_results, 'GroupUser does not have access to content without visibility.');

    // Check access to any content.
    $this->setCurrentUser($this->privilegedUser);
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertContains($this->publicNode->id(), array_values($query_results), 'PrivilegedUser can see "group" content.');
  }

}
