<?php

declare(strict_types=1);

namespace Drupal\Tests\social_course\Kernel;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\group\PermissionScopeInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\group\Traits\GroupTestTrait;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Testing access to "course_section" content in courses.
 *
 * Provides test coverage for access to nodes of type "course_section" within
 * the "social_course" context, particularly in the context of group
 * membership and the role-based restrictions enforced by the "group" module.
 */
class SocialCourseNodeQueryAccessTest extends EntityKernelTestBase {

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
    'social_course',
  ];

  /**
   * A stub group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $group;

  /**
   * User to check access to "course_section" content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $groupMember;

  /**
   * A course section node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $courseSectionNode;

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
    $this->installSchema('node', ['node_access']);

    // The "course_section" node can be visible only for group members.
    $this->groupMember = $this->createUser();

    // Create node types.
    $course_section_node_type = NodeType::create([
      'type' => 'course_section',
      'name' => $this->randomString(),
    ]);
    $course_section_node_type->save();

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
      'permissions' => ["view group_node:{$course_section_node_type->id()} entity"],
    ]);

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'group_node:' . $course_section_node_type->id())
      ->save();

    $this->group = $this->createGroup([
      'type' => $group_type->id(),
      'label' => $this->randomString(),
    ]);

    $this->courseSectionNode = Node::create([
      'type' => $course_section_node_type->id(),
      'title' => $this->randomString(),
      'status' => TRUE,
    ]);
    $this->courseSectionNode->save();

    $this->group->addRelationship($this->courseSectionNode, 'group_node:' . $course_section_node_type->id());
    $this->group->addMember($this->groupMember);
  }

  /**
   * Tests the access to "course_section" content in courses.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGroupNodeAlterQueryAccess(): void {
    // Check access to content for anonymous users.
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertNotContains($this->courseSectionNode->id(), $query_results, 'Anonymous user does not have access to course sections.');

    // Check access content by authenticated user.
    $this->setUpCurrentUser();
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertNotContains($this->courseSectionNode->id(), $query_results, 'Authenticated user does not have access to course sections.');

    // Check access content by group member.
    $this->setCurrentUser($this->groupMember);
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertContains($this->courseSectionNode->id(), $query_results, 'GroupMember user has access to course sections.');
  }

}
