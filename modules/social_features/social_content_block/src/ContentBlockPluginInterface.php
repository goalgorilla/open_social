<?php

namespace Drupal\social_content_block;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Provides an interface for content block plugins.
 *
 * @package Drupal\social_content_block
 */
interface ContentBlockPluginInterface extends PluginInspectionInterface {

  /**
   * Create filtering query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query.
   * @param array $fields
   *   The fields.
   */
  public function query(SelectInterface $query, array $fields): void;

  /**
   * The sort options that are supported for this content block type.
   *
   * Used to configure the sorting field storage as well as the content block
   * form.
   *
   * @return array
   *   An array with sorting option's system name as key and a human-readable
   *   label as value or value is an associative array with the following keys:
   *   - label: The human-readable label.
   *   - description: (optional) The human-readable description.
   *   - limit: (optional) Whether the limitation by creation date is required.
   *     Defaults to TRUE.
   */
  public function supportedSortOptions(): array;

}
