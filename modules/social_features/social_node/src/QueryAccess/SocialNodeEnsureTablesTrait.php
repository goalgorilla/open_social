<?php

declare(strict_types=1);

namespace Drupal\social_node\QueryAccess;

use Drupal\Core\Database\Query\SelectInterface;

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
   * Ensures the query is joined with the node visibility table.
   *
   * @return string
   *   The visibility join alias.
   */
  public function ensureJoinNodeField(string $field_name): string {
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
   * Ensures the query is joined against the data table.
   *
   * @return string
   *   The data table alias.
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

    if (empty($base_table)) {
      throw new \Exception('Base table "node" not found.');
    }

    return $this->query->leftJoin(
      'node_field_data',
      NULL,
      "$base_table.nid = %alias.nid"
    );
  }
}