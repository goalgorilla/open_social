<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Validation\Constraint\GroupContentCardinality.
 */

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the cardinality limits for a piece of group content.
 *
 * Content enabler plugins may limit the amount of times a single content entity
 * can be added to a group as well as the amount of groups that single entity
 * can be added to. This constraint will enforce that behavior on entity
 * reference fields.
 *
 * @Constraint(
 *   id = "GroupContentCardinality",
 *   label = @Translation("Group content cardinality check", context = "Validation")
 * )
 */
class GroupContentCardinality extends Constraint {

  /**
   * The message to show when an entity has reached the group cardinality.
   *
   * @var string
   */
  public $groupMessage = '@field: %content has reached the maximum amount of groups it can be added to';

  /**
   * The message to show when an entity has reached the entity cardinality.
   *
   * @var string
   */
  public $entityMessage = '@field: %content has reached the maximum amount of times it can be added to %group';

}
