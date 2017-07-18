<?php

namespace Drupal\social_group;

use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Drupal\social_post\Entity\Post;

/**
 * Class SocialGroupHelperService.
 *
 * @package Drupal\social_group
 */
class SocialGroupHelperService {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Returns a group id from a entity (post, node).
   */
  public static function getGroupFromEntity($entity) {
    $gid = NULL;

    // Special cases for comments.
    // Returns the entity to which the comment is attached.
    if ($entity['target_type'] === 'comment') {
      $comment = \Drupal::entityTypeManager()
        ->getStorage('comment')
        ->load($entity['target_id']);
      $commented_entity = $comment->getCommentedEntity();
      $entity['target_type'] = $commented_entity->getEntityTypeId();
      $entity['target_id'] = $commented_entity->id();
    }

    if ($entity['target_type'] === 'post') {
      /* @var /Drupal/social_post/Entity/Post $post */
      $post = Post::load($entity['target_id']);
      $recipient_group = $post->get('field_recipient_group')->getValue();
      if (!empty($recipient_group)) {
        $gid = $recipient_group['0']['target_id'];
      }
    }
    elseif ($entity['target_type'] === 'node') {
      // Try to load the entity.
      if ($node = Node::load($entity['target_id'])) {
        // Try to load group content from entity.
        if ($groupcontent = GroupContent::loadByEntity($node)) {
          // Potentially there are more than one.
          $groupcontent = reset($groupcontent);
          // Set the group id.
          $gid = $groupcontent->getGroup()->id();
        }
      }
    }
    return $gid;
  }

}
