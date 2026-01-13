<?php

declare(strict_types=1);

namespace Drupal\social_topic\Wrappers\Input;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_graphql\Wrappers\InputBase;

/**
 * Base class for topic input wrappers.
 *
 * Provides shared validation logic for creating and updating topics.
 */
abstract class TopicInputBase extends InputBase {

  /**
   * Maximum number of content tags allowed per topic.
   *
   * This limit prevents Denial of Service attacks where an attacker could
   * send a large number of tag UUIDs to overload the database.
   */
  const MAX_CONTENT_TAGS = 50;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * Validate and load content tags.
   *
   * This method performs all necessary validation for content tags:
   * - Validates input format (must be an array)
   * - Checks maximum number of tags limit
   * - Checks if the tag exists and is of the correct vocabulary
   * - Checks if the tag is published
   * - Checks if the tag can be used for node_topic.
   *
   * @param mixed $content_tags_input
   *   The content tags input (should be an array of tag UUIDs).
   *
   * @return array
   *   An associative array with two keys:
   *   - 'valid_tags': Array of validated TermInterface objects.
   *   - 'violations': Array of Violation objects for any validation errors.
   */
  protected function validateContentTags($content_tags_input): array {
    $valid_tags = [];
    $violations = [];

    // Validate input format - must be an array.
    if (!is_array($content_tags_input)) {
      $violations[] = new Violation("CONTENT_TAG_INVALID_INPUT");
      return [
        'valid_tags' => $valid_tags,
        'violations' => $violations,
      ];
    }

    // Check maximum number of tags limit to prevent DoS attacks.
    if (count($content_tags_input) > self::MAX_CONTENT_TAGS) {
      $violations[] = new Violation("CONTENT_TAGS_LIMIT_EXCEEDED");
      return [
        'valid_tags' => $valid_tags,
        'violations' => $violations,
      ];
    }

    // Empty array is valid (no tags to assign).
    if (empty($content_tags_input)) {
      return [
        'valid_tags' => $valid_tags,
        'violations' => $violations,
      ];
    }

    // Load all tags in a single query to avoid N+1 problem.
    $tags_by_uuid = $this->loadTermsByUuids($content_tags_input);

    foreach ($content_tags_input as $tag_uuid) {
      // Check if tag exists and is from the correct vocabulary.
      if (!isset($tags_by_uuid[$tag_uuid])) {
        $violations[] = new Violation("CONTENT_TAG_NOT_FOUND:" . $tag_uuid);
        continue;
      }

      $tag = $tags_by_uuid[$tag_uuid];

      // Check if tag can be used for node_topic.
      $has_field = $tag->hasField('field_category_usage') && !$tag->get('field_category_usage')->isEmpty();
      $usage = $has_field ? unserialize($tag->get('field_category_usage')->value, ['allowed_classes' => FALSE]) : NULL;

      if (!$has_field || !is_array($usage) || !in_array('node_topic', $usage, TRUE)) {
        $violations[] = new Violation("CONTENT_TAG_INVALID_USAGE:" . $tag_uuid);
        continue;
      }

      // Tag is valid - add to the list.
      $valid_tags[] = $tag;
    }

    return [
      'valid_tags' => $valid_tags,
      'violations' => $violations,
    ];
  }

  /**
   * Load taxonomy terms by their UUIDs in a single query.
   *
   * @param array $uuids
   *   The UUIDs of the terms to load.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of terms indexed by their UUIDs. Returns an empty array
   *   if no matching entities are found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo Remove when https://www.drupal.org/project/drupal/issues/3214923 lands.
   */
  protected function loadTermsByUuids(array $uuids): array {
    /** @var \Drupal\taxonomy\TermInterface[] $terms_by_id */
    $terms_by_id = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'uuid' => $uuids,
        'vid' => 'social_tagging',
        'status' => 1,
      ]);
    $terms = [];
    foreach ($terms_by_id as $term) {
      $terms[$term->uuid()] = $term;
    }

    return $terms;
  }

}
