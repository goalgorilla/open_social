<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupType;
use Drupal\Tests\system\Kernel\Token\TokenReplaceKernelTestBase;

/**
 * Generates text using placeholders for dummy content to check group token
 * replacement.
 *
 * @group group
 */
class GroupTokenReplaceTest extends TokenReplaceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['group'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['group']);
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');

    $group_type = GroupType::create([
      'id' => 'my_group',
      'label' => 'My Group!',
    ]);
    $group_type->save();
  }

  /**
   * Tests the tokens replacement for group.
   */
  function testGroupTokenReplacement() {
    $url_options = [
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    ];

    // Create a user and a group.
    $account = $this->createUser();

    /* @var \Drupal\group\Entity\GroupInterface $group */
    $group = Group::create([
      'type' => 'my_group',
      'id' => 0,
      'uid' => $account->id(),
    ]);
    $group->save();

    // Generate and test tokens.
    $tests = [];
    $tests['[group:id]'] = $group->id();
    $tests['[group:type]'] = 'my_group';
    $tests['[group:type-name]'] = 'My Group!';
    $tests['[group:langcode]'] = $group->language()->getId();
    $tests['[group:url]'] = $group->url('canonical', $url_options);
    $tests['[group:edit-url]'] = $group->url('edit-form', $url_options);
    $tests['[group:author]'] = $account->getUsername();
    $tests['[group:author:uid]'] = $group->getOwnerId();
    $tests['[group:author:name]'] = $account->getUsername();
    $tests['[group:created:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($group->getCreatedTime(), ['langcode' => $this->interfaceLanguage->getId()]);
    $tests['[group:changed:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($group->getChangedTime(), ['langcode' => $this->interfaceLanguage->getId()]);

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($group);

    $metadata_tests = [];
    $metadata_tests['[group:id]'] = $base_bubbleable_metadata;
    $metadata_tests['[group:type]'] = $base_bubbleable_metadata;
    $metadata_tests['[group:type-name]'] = $base_bubbleable_metadata;
    $metadata_tests['[group:langcode]'] = $base_bubbleable_metadata;
    $metadata_tests['[group:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[group:edit-url]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[group:author]'] = $bubbleable_metadata->addCacheTags(['user:1']);
    $metadata_tests['[group:author:uid]'] = $bubbleable_metadata;
    $metadata_tests['[group:author:name]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[group:created:since]'] = $bubbleable_metadata->setCacheMaxAge(0);
    $metadata_tests['[group:changed:since]'] = $bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $token => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($token, ['group' => $group], ['langcode' => $this->interfaceLanguage->getId()], $bubbleable_metadata);
      $this->assertEquals($output, $expected, new FormattableMarkup('Group token %token replaced.', ['%token' => $token]));
      $this->assertEquals($bubbleable_metadata, $metadata_tests[$token]);
    }
  }

}
