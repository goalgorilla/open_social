<?php

namespace Drupal\Tests\social_group\Kernel;

use Drupal\flag\Entity\Flag;
use Drupal\Tests\flag\Traits\FlagCreateTrait;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * Tests the GroupMuteNotify service.
 *
 * @group social_group
 */
class GroupMuteNotifyServiceTest extends GroupKernelTestBase {

  use FlagCreateTrait;

  /**
   * The Group Mute Notify service.
   *
   * @var \Drupal\social_group\GroupMuteNotify
   */
  protected $groupMuteNotify;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'filter',
    'flag',
    'text',
    'user',
    'system',
    'entity',
    'variationcache',
    'entity_access_by_field',
    'comment',
    'message',
    'options',
    'group_test_config',
    'role_delegation',
    'dynamic_entity_reference',
    'social_group',
    'address',
    'better_exposed_filters',
    'block',
    'block_content',
    'datetime',
    'field',
    'field_group',
    'file',
    'gnode',
    'group',
    'gvbo',
    'image',
    'image_widget_crop',
    'image_effects',
    'file_mdm',
    'crop',
    'node',
    'path',
    'profile',
    'activity_creator',
    'social_node',
    'social_core',
    'social_editor',
    'social_event',
    'social_profile',
    'social_topic',
    'social_user',
    'taxonomy',
    'text',
    'user',
    'link',
    'views',
    'views_bulk_operations',
    'select2',
    'datetime',
    'group_core_comments',
    'activity_logger',
    'better_exposed_filters',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('activity');
    $this->installEntitySchema('user');
    $this->installEntitySchema('flagging');
    $this->installEntitySchema('crop');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('message');
    $this->installSchema('flag', ['flag_counts']);
    $this->installSchema('node', ['node_access']);

    $this->installConfig(['filter', 'flag', 'better_exposed_filters', 'node', 'block_content', 'image_effects', 'activity_creator', 'activity_logger', 'social_core', 'social_node', 'social_event', 'social_topic', 'social_group']);

    $this->groupMuteNotify = \Drupal::service('social_group.group_mute_notify');
    $this->flagService = \Drupal::service('flag');
  }

  /**
   * Test exceptions are not thrown for AN users.
   */
  public function testFlagServiceFlagExceptions() {
    // First user created has uid == 0, the anonymous user.
    $account = $this->createUser();
    $group = $this->createGroup();

    // Test flagging.
    // This AN user threw a logic exception, as the FlagService's method
    // getAllEntityFlaggings is flagged. This expects a session id being
    // provided if the AN user calls it. This could occur when cron is running.
    try {
      $this->groupMuteNotify->groupNotifyIsMuted($group, $account);
    }
    catch (\LogicException $e) {
      $this->fail("The exception was thrown while it should not.");
    }
  }


  /**
   * Tests retrieval of all group mute notifications.
   */
  public function testCorrectFlaggingRetrieval() {
    $account = $this->createUser();
    $account2 = $this->createUser();
    $group = $this->createGroup();

    $flag = Flag::load('mute_group_notifications');

    // Flag the global flag as account 1.
    $this->flagService->flag($flag, $group, $account);

    // Verify the muting of groups.
    $flaggings = $this->groupMuteNotify->groupNotifyIsMuted($group, $account);
    $this->assertEquals(TRUE, $flaggings);

    // Verify it's not muted if not flagged.
    $flaggings = $this->groupMuteNotify->groupNotifyIsMuted($group, $account2);
    $this->assertEquals(FALSE, $flaggings);

    // Create a flag that is not mute group notifications.
    $random_flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'group',
      'bundles' => [],
      'flag_type' => 'entity:group',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $random_flag->save();

    $account3 = $this->createUser();

    // Flag the global flag as account 1.
    $this->flagService->flag($random_flag, $group, $account3);

    // Verify the groups are not muted if a group is flagged by another flag
    // that isn't mute group notifications for the user.
    $flaggings = $this->groupMuteNotify->groupNotifyIsMuted($group, $account3);
    $this->assertEquals(FALSE, $flaggings);
  }

}
