<?php

namespace Drupal\social_comment\Cache;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Caches node IDs with hidden comment fields (nid => field names).
 */
class HiddenCommentFieldMapCache {

  public const CACHE_ID = 'social_comment:hidden_comment_field_map';

  public function __construct(
    protected CacheBackendInterface $cache,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Returns hidden comment fields keyed by node ID.
   *
   * @return array<int, string[]>
   *   Node ID to comment field machine names with Hidden status.
   */
  public function getMap(): array {
    $cached = $this->cache->get(self::CACHE_ID);
    if ($cached !== FALSE) {
      return $cached->data;
    }

    $map = $this->buildMap();
    $tags = [];
    foreach (array_keys($map) as $nid) {
      $tags[] = 'node:' . $nid;
    }
    $this->cache->set(self::CACHE_ID, $map, Cache::PERMANENT, $tags);
    return $map;
  }

  /**
   * Clears the hidden comment field map.
   */
  public function reset(): void {
    $this->cache->delete(self::CACHE_ID);
  }

  /**
   * Builds the hidden comment field map from storage.
   *
   * @return array<int, string[]>
   *   Node ID to comment field machine names with Hidden status.
   */
  protected function buildMap(): array {
    $hidden_fields_by_nid = [];
    $node_storage = $this->entityTypeManager->getStorage('node');
    foreach ($this->getNodeCommentFieldNames() as $field_name) {
      $hidden_nids = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition("{$field_name}.status", CommentItemInterface::HIDDEN)
        ->execute();

      foreach ($hidden_nids as $nid) {
        $hidden_fields_by_nid[(int) $nid][] = $field_name;
      }
    }
    return $hidden_fields_by_nid;
  }

  /**
   * Returns comment field machine names defined on node entities.
   *
   * @return string[]
   *   Field names, e.g. field_topic_comments.
   */
  protected function getNodeCommentFieldNames(): array {
    $field_names = [];
    foreach ($this->entityFieldManager->getFieldStorageDefinitions('node') as $field_name => $definition) {
      if ($definition->getType() === 'comment') {
        $field_names[] = (string) $field_name;
      }
    }
    return $field_names;
  }

}
