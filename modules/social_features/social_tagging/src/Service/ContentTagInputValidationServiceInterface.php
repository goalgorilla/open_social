<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Service;

use Drupal\social_tagging\ValueObject\ContentTagInputValidationResult;

/**
 * Interface for validating content tag input in API.
 */
interface ContentTagInputValidationServiceInterface {

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
  ): ContentTagInputValidationResult;

}
