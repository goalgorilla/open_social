<?php

declare(strict_types=1);

namespace Drupal\social_node\QueryAccess;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Contains helper functions for node entity query alters.
 */
trait SocialNodeEnsureTablesTrait {

  /**
   * Attach a query as object property.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query object.
   */
  protected function attachQuery(SelectInterface $query): void {
    $this->query = $query;
  }

  /**
   * Ensures the query is joined with a given node field table.
   *
   * @return string
   *   The node field join alias.
   */
  public function ensureNodeFieldTableJoin(string $field_name): string {
    $field_table = "node__$field_name";

    $tables = $this->query->getTables();
    foreach ($tables as $join_info) {
      if ($join_info['table'] === $field_table) {
        return $join_info['alias'];
      }
    }

    try {
      $node_data_table = $this->ensureNodeDataTable();
    }
    catch (\Exception $e) {
      return '';
    }

    return $this->query->leftJoin(
      $field_table,
      NULL,
      "$node_data_table.nid = %alias.entity_id"
    );
  }

  /**
   * Ensures the query is joined against the node data table.
   *
   * @return string
   *   The node data table alias.
   *
   * @throws \Exception
   */
  public function ensureNodeDataTable(): string {
    $tables = $this->query->getTables();
    foreach ($tables as $join_info) {
      if ($join_info['table'] === 'node_field_data') {
        return $join_info['alias'];
      }
    }

    // Ensure the base_table is joined.
    foreach ($tables as $join_info) {
      if ($join_info['table'] === 'node') {
        $base_table = $join_info['alias'];
        break;
      }
    }

    // @todo Investigate where from this table is appearing.
    foreach ($tables as $join_info) {
      if ($join_info['table'] === 'book') {
        $base_table = $join_info['alias'];
        break;
      }
    }

    if (empty($base_table)) {
      throw new \Exception('Base table "node" not found. Check your query.');
    }

    return $this->query->leftJoin(
      'node_field_data',
      NULL,
      "$base_table.nid = %alias.nid"
    );
  }

}
