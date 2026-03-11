<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_tagging\ValueObject\ContentTagInputValidationResult;
use Drupal\taxonomy\TermInterface;

/**
 * Service for validating content tag input in API.
 */
class ContentTagInputValidationService implements ContentTagInputValidationServiceInterface {


  /**
   * Maximum number of content tags allowed per content.
   *
   * This limit prevents Denial of Service attacks where an attacker could
   * send a large number of tag UUIDs to overload the database.
   */
  private const MAX_CONTENT_TAGS = 50;

  /**
   * Constructs a ContentTagInputValidationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Validates content tags input for a given content bundle.
   *
   * Performs all necessary validation:
   * - Validates input format (must be an array)
   * - Checks maximum number of tags limit
   * - Loads terms by UUID (vocabulary social_tagging, published)
   * - Checks if each tag can be used for the given content bundle via
   *   field_category_usage (explicit or inherited from parent).
   *
   * @param mixed $content_tags_input
   *   The content tags input (should be an array of tag UUIDs).
   * @param string $content_bundle
   *   The content bundle to validate against (e.g. 'node_topic', 'node_event').
   *
   * @return \Drupal\social_tagging\ValueObject\ContentTagInputValidationResult
   *   The validation result. When some tags fail (e.g. not found or invalid
   *   usage), the result still contains both: errors for the failed tags and
   *   the list of valid tags that passed. Callers can use the valid tags for
   *   partial application or treat any non-empty errors as a full failure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function validateContentTagsForContent(
    mixed $content_tags_input,
    string $content_bundle,
  ): ContentTagInputValidationResult {
    $errors = [];
    $valid_tags = [];

    // Validate input format - must be an array.
    if (!is_array($content_tags_input)) {
      return new ContentTagInputValidationResult(
        ['CONTENT_TAG_INVALID_INPUT'],
        [],
      );
    }

    // Ensure each element is a non-empty string.
    foreach ($content_tags_input as $tag_uuid) {
      if (!is_string($tag_uuid) || $tag_uuid === '') {
        return new ContentTagInputValidationResult(
          ['CONTENT_TAG_INVALID_INPUT'],
          [],
        );
      }
    }

    // Check maximum number of tags limit to prevent DoS attacks.
    if (count($content_tags_input) > self::MAX_CONTENT_TAGS) {
      return new ContentTagInputValidationResult(
        ['CONTENT_TAGS_LIMIT_EXCEEDED'],
        [],
      );
    }

    // Empty array is valid (no tags to assign).
    if (empty($content_tags_input)) {
      return new ContentTagInputValidationResult([], []);
    }

    // Validate each UUID only once; duplicates would otherwise repeat in
    // errors and/or valid_tags.
    $tag_uuids = array_values(array_unique($content_tags_input));

    // Load all tags in a single query to avoid N+1 problem.
    $tags_by_uuid = $this->loadTermsByUuids($tag_uuids);

    foreach ($tag_uuids as $tag_uuid) {
      // Check if tag exists and is from the correct vocabulary.
      if (!isset($tags_by_uuid[$tag_uuid])) {
        $errors[] = 'CONTENT_TAG_NOT_FOUND:' . $tag_uuid;
        continue;
      }

      $tag = $tags_by_uuid[$tag_uuid];

      // Check if tag can be used for the content bundle.
      if (!$this->tagHasUsageForBundle($tag, $content_bundle)) {
        $errors[] = 'CONTENT_TAG_INVALID_USAGE:' . $tag_uuid;
        continue;
      }

      $valid_tags[] = $tag;
    }

    // Both $errors and $valid_tags can be non-empty: failed tags are reported,
    // valid tags are still returned for partial success handling by the caller.
    return new ContentTagInputValidationResult($errors, $valid_tags);
  }

  /**
   * Checks if a tag can be used for the given content bundle.
   *
   * Uses field_category_usage on the tag; if not set, inherits from the first
   * parent only (multiple parents are not supported for inheritance).
   *
   * @param \Drupal\taxonomy\TermInterface $tag
   *   The taxonomy term (tag).
   * @param string $content_bundle
   *   The content bundle (e.g. 'node_topic', 'node_event').
   *
   * @return bool
   *   TRUE if the tag can be used for the bundle.
   */
  private function tagHasUsageForBundle(TermInterface $tag, string $content_bundle): bool {
    $has_field = $tag->hasField('field_category_usage') && !$tag->get('field_category_usage')->isEmpty();
    $usage = $has_field ? unserialize($tag->get('field_category_usage')->value, ['allowed_classes' => FALSE]) : NULL;

    if ($has_field && is_array($usage)) {
      return in_array($content_bundle, $usage, TRUE);
    }

    // Tag doesn't have field_category_usage - check parent for inheritance.
    $parent_ids = [];
    foreach ($tag->get('parent') as $parent_item) {
      $parent_value = $parent_item->getValue();
      $parent_id = $parent_value['target_id'] ?? NULL;
      if ($parent_id !== NULL && $parent_id > 0) {
        $parent_ids[] = $parent_id;
      }
    }

    if (empty($parent_ids)) {
      return FALSE;
    }

    // Only the first parent is used for usage inheritance; multiple parents
    // are not supported.
    $parent = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->load(reset($parent_ids));

    if (!$parent || !$parent->hasField('field_category_usage') || $parent->get('field_category_usage')->isEmpty()) {
      return FALSE;
    }

    $parent_usage = unserialize($parent->get('field_category_usage')->value, ['allowed_classes' => FALSE]);
    return is_array($parent_usage) && in_array($content_bundle, $parent_usage, TRUE);
  }

  /**
   * Loads taxonomy terms by their UUIDs in a single query.
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
  private function loadTermsByUuids(array $uuids): array {
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
