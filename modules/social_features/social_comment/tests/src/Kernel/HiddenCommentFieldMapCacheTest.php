<?php

declare(strict_types=1);

namespace Drupal\Tests\social_comment\Kernel;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\social_comment\Cache\HiddenCommentFieldMapCache;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests hidden comment field map cache coherence on node saves.
 *
 * @group social_comment
 */
class HiddenCommentFieldMapCacheTest extends EntityKernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'social_comment',
    'hux',
    'comment',
    'entity',
    'social_user',
    'user',
    'role_delegation',
    'node',
    'field',
    'text',
    'filter',
    'views',
    'views_bulk_operations',
    'group',
    'group_core_comments',
    'variationcache',
    'flexible_permissions',
  ];

  /**
   * Hidden comment field map cache.
   *
   * @var \Drupal\social_comment\Cache\HiddenCommentFieldMapCache
   */
  private HiddenCommentFieldMapCache $mapCache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', 'comment_entity_statistics');
    $this->installConfig(['filter', 'comment', 'social_comment']);

    $this->ensurePageCommentField();
    $this->mapCache = $this->container->get('social_comment.hidden_comment_field_map_cache');
  }

  /**
   * Open-to-hidden and hidden-to-open toggles update the warmed map cache.
   */
  public function testHiddenCommentFieldMapUpdatesWhenStatusToggles(): void {
    $node = $this->createNode(['type' => 'page']);
    $nid = (int) $node->id();

    $this->mapCache->getMap();
    self::assertArrayNotHasKey($nid, $this->mapCache->getMap());

    $node->set('field_page_comments', [
      'status' => CommentItemInterface::HIDDEN,
    ]);
    $node->save();

    $map = $this->mapCache->getMap();
    self::assertArrayHasKey($nid, $map);
    self::assertSame(['field_page_comments'], $map[$nid]);

    $node->set('field_page_comments', [
      'status' => CommentItemInterface::OPEN,
    ]);
    $node->save();

    self::assertArrayNotHasKey($nid, $this->mapCache->getMap());
  }

  /**
   * Ownership changes do not alter the hidden comment field map.
   */
  public function testUidChangeDoesNotChangeHiddenCommentFieldMap(): void {
    $owner = $this->createUser();
    $successor = $this->createUser();

    $node = $this->createNode([
      'type' => 'page',
      'uid' => $owner->id(),
      'field_page_comments' => [
        'status' => CommentItemInterface::HIDDEN,
      ],
    ]);
    $nid = (int) $node->id();

    $this->mapCache->getMap();
    $map_before = $this->mapCache->getMap();
    self::assertSame(['field_page_comments'], $map_before[$nid]);

    $node->setOwnerId((int) $successor->id());
    $node->save();

    self::assertSame($map_before, $this->mapCache->getMap());
  }

  /**
   * Ensures the page bundle has a comment field for map cache tests.
   */
  private function ensurePageCommentField(): void {
    if (NodeType::load('page') === NULL) {
      NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    }
    if (FieldStorageConfig::load('node.field_page_comments') === NULL) {
      FieldStorageConfig::create([
        'field_name' => 'field_page_comments',
        'entity_type' => 'node',
        'type' => 'comment',
        'settings' => ['comment_type' => 'comment'],
        'module' => 'comment',
      ])->save();
    }
    if (FieldConfig::load('node.page.field_page_comments') === NULL) {
      FieldConfig::create([
        'field_name' => 'field_page_comments',
        'entity_type' => 'node',
        'bundle' => 'page',
        'label' => 'Comments',
        'settings' => [
          'default_mode' => 1,
          'per_page' => 50,
        ],
      ])->save();
    }
  }

}
