<?php

namespace Drupal\social_group\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\views\Views;

/**
 * Filter to search users by combining username, first name and last name.
 *
 * This filter works with entity-rendered views without requiring field
 * handlers. It directly joins the necessary tables and builds a combined
 * search expression.
 */
#[ViewsFilter("combine_user_name_profile_name")]
class CombineUserNameProfileName extends StringFilter {

  /**
   * Table alias for the profile table.
   *
   * @var string|null
   */
  protected ?string $profileTableAlias = NULL;

  /**
   * Table alias for the first name field table.
   *
   * @var string|null
   */
  protected ?string $firstNameTableAlias = NULL;

  /**
   * Table alias for the last name field table.
   *
   * @var string|null
   */
  protected ?string $lastNameTableAlias = NULL;

  /**
   * Table alias for the user's field data table.
   *
   * @var string|null
   */
  protected ?string $usersTableAlias = NULL;

  /**
   * Where the $query object will reside.
   *
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query = NULL;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function query(): void {
    // Don't filter on empty strings.
    if (empty($this->value)) {
      return;
    }

    $this->ensureRequiredTables();

    // Build the combined expression for searching across all name fields.
    $expression = $this->buildCombinedExpression();

    if ($expression) {
      $info = $this->operators();
      if (!empty($info[$this->operator]['method'])) {
        $this->{$info[$this->operator]['method']}($expression);
      }
    }
  }

  /**
   * Ensures all required tables are joined.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function ensureRequiredTables(): void {
    // If a relationship to the user exists, use it.
    // Otherwise, join from the base table.
    if (!empty($this->relationship) && isset($this->view->relationship[$this->relationship])) {
      $relationship = $this->view->relationship[$this->relationship];
      $this->usersTableAlias = $relationship->alias;
    }
    elseif (isset($this->view->relationship['gc__user'])) {
      $this->usersTableAlias = $this->view->relationship['gc__user']->alias;
    }
    else {
      // Join users_field_data directly.
      $this->usersTableAlias = $this->query->ensureTable('users_field_data');
    }

    // Join the profile table.
    $configuration = [
      'table' => 'profile',
      'field' => 'uid',
      'left_table' => $this->usersTableAlias,
      'left_field' => 'uid',
      'type' => 'LEFT',
      'extra' => [
        [
          'field' => 'type',
          'value' => 'profile',
        ],
      ],
    ];
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->profileTableAlias = $this->query->addRelationship(
      'profile_combined_filter',
      $join,
      'profile'
    );

    // Join the first name field table.
    $configuration = [
      'table' => 'profile__field_profile_first_name',
      'field' => 'entity_id',
      'left_table' => $this->profileTableAlias,
      'left_field' => 'profile_id',
      'type' => 'LEFT',
    ];
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->firstNameTableAlias = $this->query->addRelationship(
      'profile_first_name_combined_filter',
      $join,
      'profile__field_profile_first_name'
    );

    // Join the last name field table.
    $configuration = [
      'table' => 'profile__field_profile_last_name',
      'field' => 'entity_id',
      'left_table' => $this->profileTableAlias,
      'left_field' => 'profile_id',
      'type' => 'LEFT',
    ];
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->lastNameTableAlias = $this->query->addRelationship(
      'profile_last_name_combined_filter',
      $join,
      'profile__field_profile_last_name'
    );
  }

  /**
   * Builds the combined expression for searching.
   *
   * @return string
   *   The SQL expression combining all name fields.
   */
  protected function buildCombinedExpression(): string {
    $fields = [];

    // Add the username field.
    if ($this->usersTableAlias) {
      $fields[] = "COALESCE($this->usersTableAlias.name, '')";
    }

    // Add the first name field.
    if ($this->firstNameTableAlias) {
      $fields[] = "COALESCE($this->firstNameTableAlias.field_profile_first_name_value, '')";
    }

    // Add the last name field.
    if ($this->lastNameTableAlias) {
      $fields[] = "COALESCE($this->lastNameTableAlias.field_profile_last_name_value, '')";
    }

    if (empty($fields)) {
      return '';
    }

    // Use CONCAT_WS to combine the fields with spaces.
    // This allows searching across any combination of the fields.
    if (count($fields) === 1) {
      return reset($fields);
    }

    return "CONCAT(" . implode(", ' ', ", $fields) . ")";
  }

  /**
   * Operation to filter for values that are equal to a given string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  public function opEqual($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator($this->operator());
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => $this->value]
    );
  }

  /**
   * Operation to filter for values that contains a given string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opContains($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator('LIKE');
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => '%' . $this->connection->escapeLike($this->value) . '%']
    );
  }

  /**
   * Operation to match any or all words in the string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opContainsWord($field): void {
    $placeholder = $this->placeholder();

    // Don't filter on empty strings.
    if (empty($this->value)) {
      return;
    }

    // Match all words separated by spaces or sentences encapsulated by double
    // quotes.
    preg_match_all(static::WORDS_PATTERN, ' ' . $this->value, $matches, PREG_SET_ORDER);

    // Switch between the 'word' and 'allwords' operator.
    $type = $this->operator === 'word' ? 'OR' : 'AND';
    $group = $this->query->setWhereGroup($type);
    $operator = $this->getConditionOperator('LIKE');

    foreach ($matches as $match_key => $match) {
      $temp_placeholder = $placeholder . '_' . $match_key;
      // Clean up the user input and remove the sentence delimiters.
      $word = trim($match[2], ',?!();:-"');
      $this->query->addWhereExpression(
        $group,
        "$field $operator $temp_placeholder",
        [$temp_placeholder => '%' . $this->connection->escapeLike($word) . '%']
      );
    }
  }

  /**
   * Operation to filter for values that start with a given string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opStartsWith($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator('LIKE');
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => $this->connection->escapeLike($this->value) . '%']
    );
  }

  /**
   * Operation to filter for values that do not start with a given string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opNotStartsWith($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator('NOT LIKE');
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => $this->connection->escapeLike($this->value) . '%']
    );
  }

  /**
   * Operation to filter for values that end with a given string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opEndsWith($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator('LIKE');
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => '%' . $this->connection->escapeLike($this->value)]
    );
  }

  /**
   * Operation to filter for values that do not end with a given string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opNotEndsWith($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator('NOT LIKE');
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => '%' . $this->connection->escapeLike($this->value)]
    );
  }

  /**
   * Operation to exclude values containing the specified string.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opNotLike($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator('NOT LIKE');
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => '%' . $this->connection->escapeLike($this->value) . '%']
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function opRegex($field): void {
    $placeholder = $this->placeholder();
    $operator = $this->getConditionOperator('REGEXP');
    $this->query->addWhereExpression(
      $this->options['group'],
      "$field $operator $placeholder",
      [$placeholder => $this->value]
    );
  }

  /**
   * Operation to check for empty or non-empty values.
   *
   * @param string $field
   *   The SQL expression to filter on.
   */
  protected function opEmpty($field): void {
    if ($this->operator === 'empty') {
      $this->query->addWhereExpression(
        $this->options['group'],
        "($field IS NULL OR $field = '')"
      );
    }
    else {
      $this->query->addWhereExpression(
        $this->options['group'],
        "($field IS NOT NULL AND $field != '')"
      );
    }

  }

}
