<?php

declare(strict_types=1);

namespace Drupal\social_tagging\TestControlInterface\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\social_tagging\TestControlInterface\Error\TagNotFound;
use Drupal\social_tagging\TestControlInterface\Error\TopicNotFound;
use Drupal\social_tagging\TestControlInterface\Success\TagAddedToTopicSuccess;
use Drupal\taxonomy\TermInterface;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoint for tagging a topic (replaces TaggingContext::addTagToTopic()).
 */
final class AddTagToTopic {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Adds an existing social_tagging term to an existing topic.
   *
   * @return \Drupal\social_tagging\TestControlInterface\Success\TagAddedToTopicSuccess|\Drupal\social_tagging\TestControlInterface\Error\TopicNotFound|\Drupal\social_tagging\TestControlInterface\Error\TagNotFound
   *   The operation result.
   */
  #[Operation(
    id: 'topic_tag_add',
    patterns: ['I add the tag :tag_name to the topic :topic_title'],
    type: OperationType::Write,
  )]
  public function addTagToTopic(
    #[Example('Tag1')]
    string $tag_name,
    #[Example('Great Topic')]
    string $topic_title,
  ): TagAddedToTopicSuccess|TopicNotFound|TagNotFound {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'title' => $topic_title,
      'type' => 'topic',
    ]);
    $node = reset($nodes);
    if (!$node instanceof NodeInterface) {
      return new TopicNotFound($topic_title);
    }

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'name' => $tag_name,
      'vid' => 'social_tagging',
    ]);
    $term = reset($terms);
    if (!$term instanceof TermInterface) {
      return new TagNotFound($tag_name);
    }

    if (!$node->hasField('social_tagging')) {
      throw new BadRequestHttpException('Topic does not have social_tagging field.');
    }

    $current_tags = $node->get('social_tagging')->getValue();
    $current_tags[] = ['target_id' => $term->id()];
    $node->set('social_tagging', $current_tags);
    $node->save();

    return new TagAddedToTopicSuccess(nid: (int) $node->id(), termId: (int) $term->id());
  }

}
