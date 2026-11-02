<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the group field lock and messages of the group selector widget.
 *
 * Every lock is asserted together with a positive control on the same
 * fixture, so a widget that always disables the field cannot pass.
 *
 * @coversDefaultClass \Drupal\social_group\Plugin\Field\FieldWidget\SocialGroupSelectorWidget
 *
 * @group social_group
 */
class SocialGroupSelectorWidgetMessageTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'flexible_permissions',
    'group',
    'gnode',
    'node',
    'options',
    'flag',
    'select2',
    'social_group',
  ];

  /**
   * The message shown when moving content after creation is switched off.
   */
  protected const SETTING_OFF = 'Moving content after creation function has been disabled. In order to move this content, please contact a site manager.';

  /**
   * The message shown when the content is in more than one group.
   */
  protected const MULTIPLE_GROUPS = 'This content is posted to multiple groups. In order to move it, please contact a site manager.';

  /**
   * The message shown when a member may not create the bundle in the group.
   */
  protected const NOT_ALLOWED_BUNDLE = 'You are not allowed to post this type of content in this group. In order to move it, please contact a site manager.';

  /**
   * The message shown when the editor is not a member of a locked group.
   */
  protected const NOT_A_MEMBER = 'This content is posted in a group that you are no longer a member of. In order to move it, please contact a site manager.';

  /**
   * The message on the visibility when the editor is not a member.
   */
  protected const VISIBILITY_NOT_A_MEMBER = 'This content is posted in a group that you are no longer a member of. In order to change its visibility, please contact a site manager.';

  /**
   * Visibility radios as the field widget alter leaves them.
   *
   * The alter also clears #default_value when the group does not allow the
   * stored value; alterNodeForm() adds that.
   */
  protected const VISIBILITY_RADIOS = [
    'public' => ['#disabled' => FALSE],
    'community' => ['#disabled' => FALSE],
    'group' => ['#disabled' => TRUE],
  ];

  /**
   * The group the node lives in.
   */
  protected GroupInterface $group;

  /**
   * A second group, for the cross-posting cases.
   */
  protected GroupInterface $otherGroup;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('flagging');
    $this->installSchema('flag', ['flag_counts']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    NodeType::create(['type' => 'discussion', 'name' => 'Discussion'])->save();

    $group_type = $this->createGroupType(['id' => 'example']);

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $relationship_type_storage */
    $relationship_type_storage = $this->entityTypeManager()->getStorage('group_content_type');
    $relationship_type_storage->createFromPlugin($group_type, 'group_node:article')->save();
    // Members may not create discussions, as social_discussion_group ships it.
    $relationship_type_storage->createFromPlugin($group_type, 'group_node:discussion')->save();

    $this->createGroupRole([
      'id' => 'example-insider',
      'group_type' => 'example',
      'scope' => 'insider',
      'global_role' => 'authenticated',
      'permissions' => ['view group', 'create group_node:article entity'],
    ]);
    $this->createGroupRole([
      'id' => 'example-outsider',
      'group_type' => 'example',
      'scope' => 'outsider',
      'global_role' => 'authenticated',
      'permissions' => ['view group'],
    ]);

    $this->group = $this->createGroup(['type' => 'example', 'label' => 'Group A']);
    $this->otherGroup = $this->createGroup(['type' => 'example', 'label' => 'Group B']);

    $this->setSettings(TRUE, TRUE);
  }

  /**
   * A content or site manager can always change the groups.
   *
   * @covers ::formElement
   */
  public function testManagerIsNeverLocked(): void {
    $node = $this->createNodeInGroups([$this->group, $this->otherGroup]);
    $this->setSettings(FALSE, TRUE);

    [$element, $form_state] = $this->buildElement($node, $this->createUser(['manage all groups', 'access cross-group posting']));

    $this->assertEmpty($element['#disabled'] ?? FALSE);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * An entity admin without cross-group posting gets only the multi-group lock.
   *
   * @covers ::formElement
   */
  public function testEntityAdminGetsMultipleGroupsLock(): void {
    $node = $this->createNodeInGroups([$this->group, $this->otherGroup]);

    [$element, $form_state] = $this->buildElement($node, $this->createUser(['administer nodes']));

    $this->assertLocked(self::MULTIPLE_GROUPS, $element);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * Existing content without a group follows the setting only.
   *
   * @covers ::formElement
   */
  public function testContentWithoutGroupFollowsSetting(): void {
    $node = $this->createNodeInGroups([]);
    $member = $this->createMember([$this->group]);

    [$element, $form_state] = $this->buildElement($node, $member);
    $this->assertEmpty($element['#disabled'] ?? FALSE);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));

    $this->setSettings(FALSE, TRUE);
    [$element] = $this->buildElement($node, $member);
    $this->assertLocked(self::SETTING_OFF, $element);
  }

  /**
   * A member may change the group when the setting is on.
   *
   * @covers ::formElement
   */
  public function testMemberWithSettingOnCanChangeGroup(): void {
    $node = $this->createNodeInGroups([$this->group]);

    [$element, $form_state] = $this->buildElement($node, $this->createMember([$this->group]));

    $this->assertEmpty($element['#disabled'] ?? FALSE);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * A group manager without a site role behaves as a regular member.
   *
   * @covers ::formElement
   */
  public function testGroupManagerBehavesAsMember(): void {
    $this->createGroupRole([
      'id' => 'example-manager',
      'group_type' => 'example',
      'scope' => 'individual',
      'permissions' => ['view group', 'create group_node:article entity', 'administer members'],
    ]);
    $node = $this->createNodeInGroups([$this->group]);
    $manager = $this->createUser();
    $this->group->addMember($manager, ['group_roles' => ['example-manager']]);

    [$element] = $this->buildElement($node, $manager);
    $this->assertEmpty($element['#disabled'] ?? FALSE);

    $this->setSettings(FALSE, TRUE);
    [$element] = $this->buildElement($node, $manager);
    $this->assertLocked(self::SETTING_OFF, $element);
  }

  /**
   * A member cannot change the group when the setting is off.
   *
   * @covers ::formElement
   */
  public function testMemberWithSettingOffIsLocked(): void {
    $node = $this->createNodeInGroups([$this->group]);
    $this->setSettings(FALSE, TRUE);

    [$element] = $this->buildElement($node, $this->createMember([$this->group]));

    $this->assertLocked(self::SETTING_OFF, $element);
  }

  /**
   * Cross-posted content is locked whatever the settings say.
   *
   * @param bool $allow_change
   *   Whether verified users may change the group of their content.
   * @param bool $cross_posting
   *   Whether cross-posting is enabled.
   *
   * @covers ::formElement
   *
   * @dataProvider providerSettings
   */
  public function testCrossPostedContentIsLocked(bool $allow_change, bool $cross_posting): void {
    $node = $this->createNodeInGroups([$this->group, $this->otherGroup]);
    $this->setSettings($allow_change, $cross_posting);

    [$element] = $this->buildElement($node, $this->createMember([$this->group, $this->otherGroup]));

    $this->assertLocked(self::MULTIPLE_GROUPS, $element);
  }

  /**
   * Provides every combination of the two settings.
   *
   * @return array<string, array{bool, bool}>
   *   The allow-change and cross-posting settings.
   */
  public static function providerSettings(): array {
    return [
      'change on, cross-posting on' => [TRUE, TRUE],
      'change off, cross-posting on' => [FALSE, TRUE],
      'change on, cross-posting off' => [TRUE, FALSE],
      'change off, cross-posting off' => [FALSE, FALSE],
    ];
  }

  /**
   * A cross-posting editor may still change cross-posted content.
   *
   * @covers ::formElement
   */
  public function testCrossPostingPermissionUnlocksCrossPostedContent(): void {
    $node = $this->createNodeInGroups([$this->group, $this->otherGroup]);
    $editor = $this->createUser(['access cross-group posting']);
    $this->group->addMember($editor);
    $this->otherGroup->addMember($editor);

    [$element, $form_state] = $this->buildElement($node, $editor);

    $this->assertEmpty($element['#disabled'] ?? FALSE);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * A non-member is locked, and re-joining the group unlocks it.
   *
   * @param bool $allow_change
   *   Whether verified users may change the group of their content.
   *
   * @covers ::formElement
   * @covers ::getOptions
   *
   * @dataProvider providerAllowChange
   */
  public function testNonMemberIsLocked(bool $allow_change): void {
    $node = $this->createNodeInGroups([$this->group]);
    $this->setSettings($allow_change, TRUE);
    $editor = $this->createUser();

    [$element, $form_state] = $this->buildElement($node, $editor);

    $this->assertLocked(self::NOT_A_MEMBER, $element);
    $this->assertSame([(int) $this->group->id()], $form_state->get('social_group_non_member_group_ids'));
    $this->assertSame([$this->group->id()], array_map('strval', $element['#default_value']), 'the locked group is still submitted');

    $this->group->addMember($editor);
    [$element, $form_state] = $this->buildElement($node, $editor);

    if ($allow_change) {
      $this->assertEmpty($element['#disabled'] ?? FALSE);
    }
    else {
      $this->assertLocked(self::SETTING_OFF, $element);
    }
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * Provides both values of the allow-change setting.
   *
   * @return array<string, array{bool}>
   *   The allow-change setting.
   */
  public static function providerAllowChange(): array {
    return [
      'setting on' => [TRUE],
      'setting off' => [FALSE],
    ];
  }

  /**
   * The non-member message wins over the multiple groups message.
   *
   * @covers ::formElement
   */
  public function testNonMemberWinsOverMultipleGroups(): void {
    $node = $this->createNodeInGroups([$this->group, $this->otherGroup]);

    [$element, $form_state] = $this->buildElement($node, $this->createMember([$this->group]));

    $this->assertCount(2, $element['#default_value'], 'the multiple groups lock applies too');
    $this->assertLocked(self::NOT_A_MEMBER, $element);
    $this->assertSame([(int) $this->otherGroup->id()], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * A member without create permission is locked but not told they left.
   *
   * @covers ::formElement
   * @covers ::getOptions
   * @covers \social_group_form_node_form_alter
   */
  public function testMemberWithoutStandingIsLockedButKeepsVisibility(): void {
    $member = $this->createMember([$this->group]);

    [$element] = $this->buildElement($this->createNodeInGroups([$this->group]), $member);
    $this->assertEmpty($element['#disabled'] ?? FALSE, 'the same member may move an article');

    [$element, $form_state] = $this->buildElement($this->createNodeInGroups([$this->group], 'discussion'), $member);
    $this->assertLocked(self::NOT_ALLOWED_BUNDLE, $element);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
    $form = $this->alterNodeForm($form_state, TRUE);
    $this->assertSame(['#default_value' => NULL] + self::VISIBILITY_RADIOS, $form['field_content_visibility']['widget'], 'the visibility stays editable');
  }

  /**
   * A member without create permission keeps the acceptance criteria messages.
   *
   * @param bool $allow_change
   *   Whether verified users may change the group of their content.
   * @param bool $cross_posted
   *   Whether the discussion is in both groups.
   * @param string $message
   *   The expected description.
   *
   * @covers ::formElement
   *
   * @dataProvider providerMemberWithoutStandingMessages
   */
  public function testMemberWithoutStandingKeepsMessages(bool $allow_change, bool $cross_posted, string $message): void {
    $this->setSettings($allow_change, TRUE);
    $groups = $cross_posted ? [$this->group, $this->otherGroup] : [$this->group];
    $node = $this->createNodeInGroups($groups, 'discussion');

    [$element, $form_state] = $this->buildElement($node, $this->createMember($groups));

    $this->assertLocked($message, $element);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * Provides the settings that already lock the field with a message.
   *
   * @return array<string, array{bool, bool, string}>
   *   The allow-change setting, whether cross-posted, the expected message.
   */
  public static function providerMemberWithoutStandingMessages(): array {
    return [
      'setting off' => [FALSE, FALSE, self::SETTING_OFF],
      'multiple groups' => [TRUE, TRUE, self::MULTIPLE_GROUPS],
    ];
  }

  /**
   * A non-member of a discussion's group is told so, until they join.
   *
   * @covers ::formElement
   * @covers ::getOptions
   */
  public function testNonMemberOfDiscussionIsLocked(): void {
    $node = $this->createNodeInGroups([$this->group], 'discussion');
    $editor = $this->createUser();

    [$element, $form_state] = $this->buildElement($node, $editor);
    $this->assertLocked(self::NOT_A_MEMBER, $element);
    $this->assertSame([(int) $this->group->id()], $form_state->get('social_group_non_member_group_ids'));

    $this->group->addMember($editor);
    [$element, $form_state] = $this->buildElement($node, $editor);
    $this->assertLocked(self::NOT_ALLOWED_BUNDLE, $element);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * Only the groups the editor is not a member of are handed over.
   *
   * @covers ::formElement
   * @covers ::getOptions
   */
  public function testOnlyNonMemberGroupsAreHandedOver(): void {
    $node = $this->createNodeInGroups([$this->group, $this->otherGroup], 'discussion');

    [$element, $form_state] = $this->buildElement($node, $this->createMember([$this->group]));

    $this->assertLocked(self::NOT_A_MEMBER, $element);
    $this->assertSame([(int) $this->otherGroup->id()], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * New content is never locked.
   *
   * @covers ::formElement
   */
  public function testNewContentIsNotLocked(): void {
    $node = Node::create(['type' => 'article', 'title' => $this->randomMachineName()]);
    $this->setSettings(FALSE, TRUE);

    [$element, $form_state] = $this->buildElement($node, $this->createMember([$this->group]));

    $this->assertEmpty($element['#disabled'] ?? FALSE);
    $this->assertSame([], $form_state->get('social_group_non_member_group_ids'));
  }

  /**
   * The node form locks the visibility only for a non-member.
   *
   * @covers \social_group_form_node_form_alter
   */
  public function testVisibilityIsLockedForNonMember(): void {
    $node = $this->createNodeInGroups([$this->group]);
    $editor = $this->createUser();

    [, $form_state] = $this->buildElement($node, $editor);
    $form = $this->alterNodeForm($form_state, TRUE);
    $this->assertTrue($form['field_content_visibility']['widget']['#disabled']);
    $this->assertSame(self::VISIBILITY_NOT_A_MEMBER, (string) $form['field_content_visibility']['widget']['#description']);
    $this->assertNull($form['field_content_visibility']['widget']['#default_value'], 'a bundle without the field has no stored value to restore');
    foreach (array_keys(self::VISIBILITY_RADIOS) as $key) {
      $this->assertTrue($form['field_content_visibility']['widget'][$key]['#disabled'], "the $key radio is disabled");
    }

    $form = $this->alterNodeForm($form_state, FALSE);
    $this->assertArrayNotHasKey('field_content_visibility', $form, 'a form without visibility gets none added');

    $this->group->addMember($editor);
    [, $form_state] = $this->buildElement($node, $editor);
    $form = $this->alterNodeForm($form_state, TRUE);
    $this->assertArrayNotHasKey('#disabled', $form['field_content_visibility']['widget']);
    $this->assertSame(['#default_value' => NULL] + self::VISIBILITY_RADIOS, $form['field_content_visibility']['widget']);

    $form = $this->alterNodeForm($form_state, FALSE);
    $this->assertArrayNotHasKey('field_content_visibility', $form);
  }

  /**
   * A locked visibility keeps the stored value the widget alter cleared.
   *
   * @covers \social_group_form_node_form_alter
   */
  public function testLockedVisibilityKeepsStoredValue(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_content_visibility',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_content_visibility',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    $node = $this->createNodeInGroups([$this->group]);
    $node->set('field_content_visibility', 'public')->save();
    $editor = $this->createUser();

    [, $form_state] = $this->buildElement($node, $editor);
    $form = $this->alterNodeForm($form_state, TRUE);
    $this->assertTrue($form['field_content_visibility']['widget']['#disabled']);
    $this->assertSame('public', $form['field_content_visibility']['widget']['#default_value']);

    $this->group->addMember($editor);
    [, $form_state] = $this->buildElement($node, $editor);
    $form = $this->alterNodeForm($form_state, TRUE);
    $this->assertNull($form['field_content_visibility']['widget']['#default_value'], 'an editable visibility keeps the widget default');
  }

  /**
   * Asserts the group field is disabled with the given message.
   *
   * @param string $message
   *   The expected description.
   * @param array $element
   *   The widget form element.
   */
  protected function assertLocked(string $message, array $element): void {
    $this->assertTrue($element['#disabled'] ?? FALSE, 'the group field is disabled');
    $this->assertSame($message, (string) $element['#description']);
  }

  /**
   * Builds the group widget element for the node as the given editor.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being edited.
   * @param \Drupal\user\UserInterface $editor
   *   The editor.
   *
   * @return array{array, \Drupal\Core\Form\FormStateInterface}
   *   The form element and the form state it wrote to.
   */
  protected function buildElement(NodeInterface $node, UserInterface $editor): array {
    $this->setCurrentUser($editor);

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $this->entityTypeManager()->getFormObject('node', 'default');
    $form_object->setEntity($node);
    $form_state = (new FormState())->setFormObject($form_object);

    /** @var \Drupal\Core\Field\WidgetInterface $widget */
    $widget = $this->container->get('plugin.manager.field.widget')->getInstance([
      'field_definition' => $node->getFieldDefinition('groups'),
      'form_mode' => 'default',
      'configuration' => ['type' => 'social_group_selector_widget', 'settings' => []],
    ]);

    $form = [];
    $element = $widget->formElement(
      $node->get('groups'),
      0,
      ['#required' => FALSE, '#title' => 'Group', '#description' => ''],
      $form,
      $form_state,
    );

    return [$element, $form_state];
  }

  /**
   * Runs the node form alter on a minimal node form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state the widget wrote to.
   * @param bool $with_visibility
   *   Whether the form has a visibility field.
   *
   * @return array
   *   The altered form.
   */
  protected function alterNodeForm(FormStateInterface $form_state, bool $with_visibility): array {
    $form = [
      '#entity_type' => 'node',
      'groups' => ['widget' => []],
      'actions' => ['submit' => ['#submit' => []]],
    ];

    if ($with_visibility) {
      $form['field_content_visibility'] = ['widget' => ['#default_value' => NULL] + self::VISIBILITY_RADIOS];
    }

    social_group_form_node_form_alter($form, $form_state);

    return $form;
  }

  /**
   * Creates a user who is a member of the given groups.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   The groups to join.
   *
   * @return \Drupal\user\UserInterface
   *   The member.
   */
  protected function createMember(array $groups): UserInterface {
    $account = $this->createUser();

    foreach ($groups as $group) {
      $group->addMember($account);
    }

    return $account;
  }

  /**
   * Creates a published node related to the given groups.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   The groups the node must belong to.
   * @param string $bundle
   *   The node bundle.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved node.
   */
  protected function createNodeInGroups(array $groups, string $bundle = 'article'): NodeInterface {
    $node = Node::create([
      'type' => $bundle,
      'title' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $node->save();

    foreach ($groups as $group) {
      $group->addRelationship($node, 'group_node:' . $bundle);
    }

    $node = Node::load($node->id());
    $this->assertInstanceOf(NodeInterface::class, $node);

    return $node;
  }

  /**
   * Writes the two group settings under test.
   *
   * @param bool $allow_change
   *   Whether verified users may change the group of their content.
   * @param bool $cross_posting
   *   Whether cross-posting is enabled.
   */
  protected function setSettings(bool $allow_change, bool $cross_posting): void {
    $this->config('social_group.settings')
      ->set('allow_group_selection_in_node', $allow_change)
      ->set('cross_posting.status', $cross_posting)
      ->set('cross_posting.content_types', ['article'])
      ->save();
  }

}
