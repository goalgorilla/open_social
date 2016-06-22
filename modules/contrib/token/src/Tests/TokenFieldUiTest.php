<?php

/**
 * @file
 * Contains \Drupal\token\Tests\TokenFieldUiTest.
 */

namespace Drupal\token\Tests;

use Drupal\node\Entity\NodeType;

/**
 * Tests field ui.
 *
 * @group token
 */
class TokenFieldUiTest extends TokenTestBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui', 'node', 'image'];

  /**
   * {@inheritdoc}
   */
  public function setUp($modules = []) {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer content types', 'administer node fields']);
    $this->drupalLogin($this->adminUser);

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'description' => "Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.",
    ]);
    $node_type->save();

    entity_create('field_storage_config', array(
      'field_name' => 'field_body',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
    ))->save();
    entity_create('field_config', array(
      'field_name' => 'field_body',
      'label' => 'Body',
      'entity_type' => 'node',
      'bundle' => 'article',
    ))->save();
    entity_create('field_storage_config', array(
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
    ))->save();
    entity_create('field_config', array(
      'field_name' => 'field_image',
      'label' => 'Image',
      'entity_type' => 'node',
      'bundle' => 'article',
    ))->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_body', [
        'type' => 'text_textarea_with_summary',
        'settings' => [
          'rows' => '9',
          'summary_rows' => '3',
        ],
        'weight' => 5,
      ])
      ->save();
  }

  public function testFileFieldUi() {
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_image');

    // Ensure the 'Browse available tokens' link is present and correct.
    $this->assertLink('Browse available tokens.');
    $this->assertLinkByHref('token/tree');

    // Ensure that the default file directory value validates correctly.
    $this->drupalPostForm(NULL, [], t('Save settings'));
    $this->assertText(t('Saved Image configuration.'));
  }

  public function testFieldDescriptionTokens() {
    $edit = [
      'description' => 'The site is called [site:name].',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_body', $edit, 'Save settings');

    $this->drupalGet('node/add/article');
    $this->assertText('The site is called Drupal.');
  }
}
