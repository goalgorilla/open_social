<?php

namespace Drupal\search_api_db\DatabaseCompatibility;

/**
 * Bundles methods for resolving DBMS-specific differences.
 *
 * @internal This interface and all implementing classes are just used by the
 *   search_api_db module for internal purposes. They should not be relied upon
 *   in other modules.
 */
interface DatabaseCompatibilityHandlerInterface {

  /**
   * Retrieves the database connection this compatibility handler is based upon.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase();

  /**
   * Reacts to a new table being created.
   *
   * @param string $table
   *   The name of the table.
   * @param string $type
   *   (optional) The type of table. One of "index" (for the denormalized table
   *   for an entire index), "text" (for an index's fulltext data table) and
   *   "field" (for field-specific tables).
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if any error occurs that should abort the current action. Internal
   *   errors that can be ignored should just be logged.
   */
  public function alterNewTable($table, $type = 'text');

  /**
   * Determines the canonical base form of a value.
   *
   * For example, when the table is case-insensitive, the value should always be
   * lowercased (or always uppercased) to arrive at the canonical base form.
   *
   * If tables of the given type use binary comparison in this database, the
   * value should not be changed.
   *
   * @param string $value
   *   A string to be indexed or searched for.
   * @param string $type
   *   (optional) The type of table. One of "index" (for the denormalized table
   *   for an entire index), "text" (for an index's fulltext data table) and
   *   "field" (for field-specific tables).
   *
   * @return string
   *   The value in its canonical base form, which won't clash with any other
   *   canonical base form when inserted into a table of the given type.
   */
  public function preprocessIndexValue($value, $type = 'text');

}
