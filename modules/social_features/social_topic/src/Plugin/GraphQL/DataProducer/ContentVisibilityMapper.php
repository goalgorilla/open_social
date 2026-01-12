<?php

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Maps Drupal visibility values to GraphQL ContentVisibility enum.
 *
 * @DataProducer(
 *   id = "content_visibility_mapper",
 *   name = @Translation("Content Visibility Mapper"),
 *   description = @Translation("Maps Drupal visibility field values to GraphQL ContentVisibility enum."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("ContentVisibility enum value")
 *   ),
 *   consumes = {
 *     "value" = @ContextDefinition("string",
 *       label = @Translation("Drupal field value"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class ContentVisibilityMapper extends DataProducerPluginBase {

  /**
   * Maps Drupal visibility values to GraphQL enum values.
   *
   * @param string|null $value
   *   The Drupal field value ('public', 'community', 'group').
   *
   * @return string
   *   The GraphQL enum value ('PUBLIC', 'COMMUNITY', 'GROUP_MEMBER').
   *
   * @throws \RuntimeException
   *   When the value is NULL or not found in the mapping.
   */
  public function resolve(?string $value): string {
    if ($value === NULL) {
      throw new \RuntimeException('Visibility value cannot be NULL.');
    }

    $map = [
      'public' => 'PUBLIC',
      'community' => 'COMMUNITY',
      'group' => 'GROUP_MEMBER',
    ];

    if (!isset($map[$value])) {
      throw new \RuntimeException("Invalid visibility value: '{$value}'. Expected one of: 'public', 'community', 'group'.");
    }

    return $map[$value];
  }

}
