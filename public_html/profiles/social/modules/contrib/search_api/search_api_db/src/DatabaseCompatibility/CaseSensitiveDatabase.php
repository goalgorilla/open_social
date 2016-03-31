<?php

/**
 * @file
 * Contains Drupal\search_api_db\DatabaseCompatibility\CaseSensitiveDatabase.
 */

namespace Drupal\search_api_db\DatabaseCompatibility;

/**
 * Represents a database whose tables are, by default, case-sensitive.
 */
class CaseSensitiveDatabase extends GenericDatabase {

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexValue($value, $type = 'text') {
    return $value;
  }

}
