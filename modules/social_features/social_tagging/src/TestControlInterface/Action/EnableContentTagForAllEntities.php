<?php

declare(strict_types=1);

namespace Drupal\social_tagging\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_tagging\SocialTaggingServiceInterface;
use Drupal\social_tagging\TestControlInterface\Error\TermNotFound;
use Drupal\social_tagging\TestControlInterface\Success\ContentTagEnabledForAllEntitiesSuccess;
use Drupal\taxonomy\TermInterface;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * TCI endpoint for the tag category-usage config.
 *
 * Replaces TaggingContext::enableContentTagForAllEntities().
 */
final class EnableContentTagForAllEntities {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'social_tagging.tag_service')]
    private readonly SocialTaggingServiceInterface $socialTaggingService,
  ) {
  }

  /**
   * Marks a social_tagging term as usable on every entity type it supports.
   *
   * @return \Drupal\social_tagging\TestControlInterface\Success\ContentTagEnabledForAllEntitiesSuccess|\Drupal\social_tagging\TestControlInterface\Error\TermNotFound
   *   The operation result.
   */
  #[Operation(
    id: 'content_tag_enable_for_all_entities',
    patterns: ['I enable content tag :term_name for all entities'],
    type: OperationType::Write,
  )]
  public function enableContentTagForAllEntities(
    #[Example('Tag1')]
    string $term_name,
  ): ContentTagEnabledForAllEntitiesSuccess|TermNotFound {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $term_name]);
    $term = reset($terms);
    if (!$term instanceof TermInterface) {
      return new TermNotFound($term_name);
    }

    // Option contains a key => value array where values are labels; keys are
    // the usage values that TaggingUsageWidget serializes onto the term.
    $options = $this->socialTaggingService->getKeyValueOptions();
    $values = array_keys($options);
    $term->set('field_category_usage', serialize($values))->save();

    return new ContentTagEnabledForAllEntitiesSuccess(termId: (int) $term->id());
  }

}
