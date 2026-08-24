<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupType;
use Drupal\group\PermissionScopeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageTemplate;
use Drupal\profile\Entity\Profile;
use Drupal\social_profile\GroupAffiliation;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Tests the primary affiliation tokens.
 *
 * @group social_profile
 */
class AffiliationTokenTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'profile',
    'entity',
    'flexible_permissions',
    'group',
    'file',
    'image',
    'address',
    'telephone',
    'taxonomy',
    'field_group',
    'crop',
    'image_widget_crop',
    'hux',
    'paragraphs',
    'entity_reference_revisions',
    'field',
    'text',
    'options',
    'filter',
    'smart_trim',
    'token',
    'social_profile',
    'config_update',
    'update_helper',
    'config_modify',
    'social_profile_fields',
    'message',
    'language',
    'dynamic_entity_reference',
    'views',
    'activity_creator',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // Views are not needed here, so the modules providing them are not enabled
    // and their schema cannot be validated.
    "views.view.newest_users",
    "views.view.user_information",
    // config_modify tracks applied modifications in its own config object which
    // does not ship a schema definition.
    "config_modify.applied",
    // The dynamic_entity_reference formatter settings have no schema, this
    // display is only installed so the activity fields exist.
    "core.entity_view_display.activity.activity.default",
    "core.entity_form_display.activity.activity.default",
  ];

  /**
   * The user the affiliation belongs to.
   */
  protected User $author;

  /**
   * The group the author is affiliated with.
   */
  protected Group $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('profile_type');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('paragraphs_type');
    $this->installEntitySchema('message');
    $this->installEntitySchema('activity');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('activity_creator', ['activity_notification_status']);
    // config_modify must come first: its config installer reads
    // config_modify.applied while installing any other module's config.
    $this->installConfig(['config_modify', 'system', 'filter', 'language', 'group', 'social_profile', 'social_profile_fields', 'activity_creator']);

    // Rendering the affiliation link needs a theme and the group routes.
    $this->container->get('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    $this->container->get('router.builder')->rebuild();

    $this->config('social_profile.settings')
      ->set('group_affiliation_status', TRUE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);

    $this->createAffiliationGroupType('affiliated_group', TRUE);

    $this->group = Group::create([
      'type' => 'affiliated_group',
      'label' => 'Acme Corp',
    ]);
    $this->group->save();

    $author = $this->createUser();
    $this->assertNotFalse($author);
    $this->author = $author;
    $this->group->addMember($this->author);

    // Joining the group already creates the default profile, so reuse it
    // rather than adding a second, non-default one the token would not load.
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    $profile = $profile_storage->loadByUser($this->author, 'profile') ?? Profile::create([
      'type' => 'profile',
      'uid' => $this->author->id(),
      'status' => 1,
    ]);
    $profile->set('field_group_affiliation', $this->group->id());
    $profile->save();

    $this->setCurrentUser($this->author);
  }

  /**
   * Both token variants and the chaining type are declared.
   */
  public function testTokenInfoDeclaresBothVariants(): void {
    $info = \Drupal::token()->getInfo();

    $this->assertSame('primary-affiliation', $info['tokens']['user']['primary-affiliation']['type']);
    $this->assertArrayHasKey('primary-affiliation', $info['types']);
    $this->assertArrayHasKey('link', $info['tokens']['primary-affiliation']);
    $this->assertArrayHasKey('raw', $info['tokens']['primary-affiliation']);
  }

  /**
   * The link variant renders an anchor to the affiliated group.
   */
  public function testLinkTokenRendersAnchor(): void {
    $output = $this->replace('[user:primary-affiliation:link]', 'default');

    $this->assertStringContainsString('>Acme Corp</a>', $output);
    $this->assertStringStartsWith(' @<a', $output);
    // No wrapping element: basic_html only allows a class attribute on <a>,
    // and the activity text is downgraded to basic_html when it is stored.
    $this->assertStringNotContainsString('<span', $output);
  }

  /**
   * The raw variant returns plain text, with the same separator and no markup.
   */
  public function testRawTokenRendersPlainText(): void {
    $this->assertSame(' @Acme Corp', $this->replace('[user:primary-affiliation:raw]', 'default'));
  }

  /**
   * The bare token behaves as the link variant.
   */
  public function testBareTokenAliasesToLink(): void {
    $this->assertSame(
      $this->replace('[user:primary-affiliation:link]', 'default'),
      $this->replace('[user:primary-affiliation]', 'default'),
    );
  }

  /**
   * The bare and the suffixed token can be used side by side.
   */
  public function testBareAndSuffixedTokensCoexist(): void {
    $output = $this->replace('[user:primary-affiliation][user:primary-affiliation:raw]', 'default');

    $this->assertStringContainsString('</a>', $output);
    $this->assertStringEndsWith(' @Acme Corp', $output);
  }

  /**
   * The dashboard stream block uses the featured view mode.
   */
  public function testFeaturedViewModeShowsAffiliation(): void {
    $this->assertStringContainsString('Acme Corp', $this->replace('[user:primary-affiliation:link]', 'featured'));
  }

  /**
   * Without an activity view mode nothing is emitted, for any variant.
   *
   * This is the email, digest and push path: they never render an activity.
   */
  public function testNoViewModeEmitsNothing(): void {
    $this->assertSame('', $this->replace('[user:primary-affiliation:link]', NULL));
    $this->assertSame('', $this->replace('[user:primary-affiliation:raw]', NULL));
    $this->assertSame('', $this->replace('[user:primary-affiliation]', NULL));
  }

  /**
   * The notification centre view modes are not on the allowlist.
   */
  public function testNotificationViewModesEmitNothing(): void {
    $this->assertSame('', $this->replace('[user:primary-affiliation:link]', 'notification'));
    $this->assertSame('', $this->replace('[user:primary-affiliation:link]', 'notification_archive'));
  }

  /**
   * An unresolved token must never leak its literal string.
   *
   * Every message template ships 'token options': {clear: FALSE}, so a token
   * hook_tokens() does not answer for ends up verbatim in the mail body.
   */
  public function testTokenNeverLeaksItsLiteralString(): void {
    foreach ([NULL, 'notification'] as $view_mode) {
      $output = $this->replace('[user:primary-affiliation][user:primary-affiliation:link][user:primary-affiliation:raw]', $view_mode, FALSE);
      $this->assertStringNotContainsString('[user:primary-affiliation', $output);
    }
  }

  /**
   * A token variant that does not exist is left to other implementations.
   */
  public function testUnknownVariantIsNotHandled(): void {
    $output = $this->replace('[user:primary-affiliation:bogus]', 'default', FALSE);

    $this->assertSame('[user:primary-affiliation:bogus]', $output);
  }

  /**
   * A user token without an affiliation suffix is left alone.
   */
  public function testOtherUserTokensAreUntouched(): void {
    $output = \Drupal::token()->replace(
      '[user:display-name]',
      ['user' => $this->author],
      ['activity_view_mode' => 'default'],
    );

    $this->assertSame($this->author->getDisplayName(), $output);
  }

  /**
   * Without a user object in the data there is nothing to resolve.
   */
  public function testMissingUserDataEmitsNothing(): void {
    $output = \Drupal::token()->replace(
      '[user:primary-affiliation:link]',
      [],
      ['activity_view_mode' => 'default', 'clear' => TRUE],
    );

    $this->assertSame('', $output);
  }

  /**
   * A user without a profile has no affiliation to show.
   */
  public function testUserWithoutProfileEmitsNothing(): void {
    $stranger = $this->createUser();
    $this->assertNotFalse($stranger);

    $output = \Drupal::token()->replace(
      '[user:primary-affiliation:link]',
      ['user' => $stranger],
      ['activity_view_mode' => 'default', 'clear' => TRUE],
    );

    $this->assertSame('', $output);
  }

  /**
   * Disabling the affiliation field hides it even though it still has a value.
   */
  public function testDisabledFieldEmitsNothing(): void {
    $this->config('social_profile_fields.settings')
      ->set('profile_profile_field_group_affiliation', FALSE)
      ->save();
    drupal_static_reset();

    $this->assertSame('', $this->replace('[user:primary-affiliation:link]', 'default'));
    $this->assertSame('', $this->replace('[user:primary-affiliation:raw]', 'default'));
  }

  /**
   * Switching the affiliation feature off hides both variants.
   *
   * The master switch is read by ::isAffiliationFeatureEnabled() behind its
   * own cache bin entry, so the tag has to be invalidated for the new value
   * to be seen.
   *
   * @see \Drupal\social_profile\GroupAffiliation::isAffiliationFeatureEnabled()
   */
  public function testFeatureDisabledEmitsNothing(): void {
    $this->config('social_profile.settings')
      ->set('group_affiliation_status', FALSE)
      ->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    drupal_static_reset();

    $this->assertSame('', $this->replace('[user:primary-affiliation:link]', 'default'));
    $this->assertSame('', $this->replace('[user:primary-affiliation:raw]', 'default'));
  }

  /**
   * Affiliation switched off for the group's type hides it on every stream.
   */
  public function testGroupTypeWithoutAffiliationEmitsNothing(): void {
    $group_type = GroupType::load('affiliated_group');
    $this->assertNotNull($group_type);
    $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, FALSE);
    $group_type->save();
    Cache::invalidateTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    drupal_static_reset();

    $this->assertSame('', $this->replace('[user:primary-affiliation:link]', 'default'));
  }

  /**
   * The token bubbles the cacheability of the decision it made.
   */
  public function testTokenBubblesCacheability(): void {
    $bubbleable_metadata = new BubbleableMetadata();
    \Drupal::token()->replace(
      '[user:primary-affiliation:link]',
      ['user' => $this->author],
      ['activity_view_mode' => 'default'],
      $bubbleable_metadata,
    );

    $this->assertContains('user', $bubbleable_metadata->getCacheContexts());
    $this->assertContains(GroupAffiliation::GENERAL_CACHE_TAG, $bubbleable_metadata->getCacheTags());
    $this->assertContains('group:' . $this->group->id(), $bubbleable_metadata->getCacheTags());
  }

  /**
   * The affiliation name is escaped, it can never inject markup.
   */
  public function testAffiliationNameIsEscaped(): void {
    $this->group->set('label', 'Acme & Co <script>alert(1)</script>');
    $this->group->save();

    $link = $this->replace('[user:primary-affiliation:link]', 'default');
    $this->assertStringNotContainsString('<script>', $link);
    $this->assertStringContainsString('&amp;', $link);

    $raw = $this->replace('[user:primary-affiliation:raw]', 'default');
    $this->assertStringNotContainsString('<script>', $raw);
  }

  /**
   * The stream text carries the affiliation as a sibling of the author link.
   */
  public function testActivityFactoryPassesTheViewModeOn(): void {
    $message = $this->createAffiliationMessage();
    $factory = \Drupal::service('activity_creator.activity_factory');

    $stream = $factory->getMessageText($message, '', 'default');
    $this->assertStringContainsString('</a> @<a', $stream[0]);
    $this->assertStringContainsString('>Acme Corp</a>', $stream[0]);

    // The notification centre shares the very same message text.
    $notification = $factory->getMessageText($message, '', 'notification');
    $this->assertStringNotContainsString('Acme Corp', $notification[0]);
    $this->assertStringNotContainsString('[message:author:primary-affiliation', $notification[0]);

    // Email, digest and push do not pass a view mode at all.
    $mail = $factory->getMessageText($message);
    $this->assertStringNotContainsString('Acme Corp', $mail[0]);
    $this->assertStringNotContainsString('[message:author:primary-affiliation', $mail[0]);
  }

  /**
   * The update hook only patches the stream delta, and only once.
   *
   * Delta 1 is the aggregated stream text, delta 2 is the mail body. Patching
   * either of them would put the affiliation back into the email.
   */
  public function testUpdateHookPatchesTheStreamDeltaOnly(): void {
    $stream = '<p><a href="[message:author:url:absolute]">[message:author:display-name]</a> posted</p>';
    $mail = '<p><a href="[message:author:url:absolute]">[message:author:display-name]</a> posted</p><p>[message:preview]</p>';
    MessageTemplate::create([
      'template' => 'create_post_community',
      'label' => 'Create post in the community',
      'text' => [
        ['value' => $stream, 'format' => 'full_html'],
        ['value' => $mail, 'format' => 'full_html'],
      ],
      'settings' => ['token options' => ['clear' => FALSE, 'token replace' => TRUE]],
    ])->save();

    $this->runAffiliationTokenUpdate();
    $text = $this->config('message.template.create_post_community')->get('text');

    $this->assertStringContainsString('</a>[message:author:primary-affiliation:link] posted', $text[0]['value']);
    $this->assertSame($mail, $text[1]['value']);

    // A second deployment must not add the token twice.
    $this->runAffiliationTokenUpdate();
    $this->assertSame($text, $this->config('message.template.create_post_community')->get('text'));
  }

  /**
   * The revision_author prefix is patched too.
   *
   * The event, topic and book templates build their author link from
   * [message:revision_author:...]. A wrong prefix string there patches nothing
   * and raises no error, so the branch needs a case of its own.
   */
  public function testUpdateHookPatchesTheRevisionAuthorPrefix(): void {
    $stream = '<p><a href="[message:revision_author:url:absolute]">[message:revision_author:display-name]</a> created a topic</p>';
    MessageTemplate::create([
      'template' => 'create_topic_group',
      'label' => 'Create topic in a group',
      'text' => [['value' => $stream, 'format' => 'full_html']],
      'settings' => ['token options' => ['clear' => FALSE, 'token replace' => TRUE]],
    ])->save();

    $this->runAffiliationTokenUpdate();
    $text = $this->config('message.template.create_topic_group')->get('text');

    $this->assertStringContainsString(
      '</a>[message:revision_author:primary-affiliation:link] created a topic',
      $text[0]['value']
    );
  }

  /**
   * Translated overrides are patched too.
   *
   * ActivityFactory::getMessageText() prefers the language override whenever it
   * exists, so a multilingual site would otherwise never show the affiliation.
   */
  public function testUpdateHookPatchesLanguageOverrides(): void {
    ConfigurableLanguage::createFromLangcode('nl')->save();
    $this->createPostGroupTemplate('<p><a href="[message:author:url:absolute]">[message:author:display-name]</a> posted</p>');

    $language_manager = \Drupal::languageManager();
    assert($language_manager instanceof ConfigurableLanguageManagerInterface);
    $translated = '<p><a href="[message:author:url:absolute]">[message:author:display-name]</a> plaatste</p>';
    $language_manager->getLanguageConfigOverride('nl', 'message.template.create_post_group')
      ->set('text', [['value' => $translated, 'format' => 'full_html']])
      ->save();

    $this->runAffiliationTokenUpdate();

    $text = $language_manager->getLanguageConfigOverride('nl', 'message.template.create_post_group')->get('text');
    $this->assertStringContainsString('</a>[message:author:primary-affiliation:link] plaatste', $text[0]['value']);
  }

  /**
   * A locally rewritten template is reported instead of silently ignored.
   *
   * Both the base config and the default language override are reported.
   * MessageTemplate::preSave() mirrors the text into the override of the
   * default language on every save, so with the language module installed a
   * template always has one, and ::getText() reads it whenever the template
   * langcode differs from the language being rendered. Support has to fix both.
   *
   * @see \Drupal\message\Entity\MessageTemplate::preSave()
   */
  public function testUpdateHookReportsTemplatesWithoutTheAnchor(): void {
    $this->createPostGroupTemplate('<p>Someone posted something</p>');

    $output = $this->runAffiliationTokenUpdate();

    $this->assertStringContainsString('Skipped 2 template', $output);
    $this->assertStringContainsString('message.template.create_post_group,', $output);
    $this->assertStringContainsString('message.template.create_post_group (en)', $output);
  }

  /**
   * Creates the create_post_group template with the given delta 0 text.
   *
   * @param string $value
   *   The text of the first delta.
   */
  protected function createPostGroupTemplate(string $value): void {
    MessageTemplate::create([
      'template' => 'create_post_group',
      'label' => 'Create post in a group',
      'text' => [['value' => $value, 'format' => 'full_html']],
      'settings' => ['token options' => ['clear' => FALSE, 'token replace' => TRUE]],
    ])->save();
  }

  /**
   * A template whose module is not installed is not resurrected as empty.
   *
   * Create_book_group ships in config/optional.
   */
  public function testUpdateHookSkipsAbsentOptionalTemplates(): void {
    $this->runAffiliationTokenUpdate();

    $this->assertTrue(\Drupal::configFactory()->get('message.template.create_book_group')->isNew());
  }

  /**
   * Runs the affiliation token update hook.
   *
   * @return string
   *   The summary the update hook reports back to the operator.
   */
  protected function runAffiliationTokenUpdate(): string {
    \Drupal::moduleHandler()->loadInclude('social_profile', 'install');
    return social_profile_update_260001();
  }

  /**
   * Replaces an affiliation token for the author.
   *
   * @param string $text
   *   The text to run the token replacement on.
   * @param string|null $view_mode
   *   The activity view mode to pretend to render for.
   * @param bool $clear
   *   Whether unresolved tokens are cleared. Defaults to TRUE so assertions
   *   read as "what does this token produce".
   *
   * @return string
   *   The replaced text.
   */
  protected function replace(string $text, ?string $view_mode, bool $clear = TRUE): string {
    return (string) \Drupal::token()->replace(
      $text,
      ['user' => $this->author],
      ['activity_view_mode' => $view_mode, 'clear' => $clear],
    );
  }

  /**
   * Creates a message template carrying the affiliation token and a message.
   *
   * @return \Drupal\message\Entity\Message
   *   The message.
   */
  protected function createAffiliationMessage(): Message {
    MessageTemplate::create([
      'template' => 'affiliation_test',
      'label' => 'Affiliation test',
      'text' => [
        [
          'value' => '<p><a href="[message:author:url:absolute]">[message:author:display-name]</a>[message:author:primary-affiliation:link] posted</p>',
          'format' => 'full_html',
        ],
      ],
      'settings' => [
        'token options' => [
          'clear' => FALSE,
          'token replace' => TRUE,
        ],
      ],
    ])->save();

    $message = Message::create([
      'template' => 'affiliation_test',
      'uid' => $this->author->id(),
    ]);
    $message->save();

    return $message;
  }

  /**
   * Creates a group type that is an affiliation candidate.
   *
   * @param string $group_type_id
   *   The group type id.
   * @param bool $affiliation_enabled
   *   Whether affiliation is enabled for the type.
   */
  protected function createAffiliationGroupType(string $group_type_id, bool $affiliation_enabled): void {
    $group_type = GroupType::create([
      'id' => $group_type_id,
      'label' => 'Affiliated group',
    ]);
    $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY, TRUE);
    $group_type->setThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_ENABLED_CONFIG_KEY, $affiliation_enabled);
    $group_type->save();

    // Members must be able to view the group, the affiliation is only shown to
    // users who may see it.
    GroupRole::create([
      'id' => $group_type_id . '-member',
      'label' => 'Member',
      'group_type' => $group_type_id,
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view group'],
    ])->save();
  }

}
