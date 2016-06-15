<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContext\GroupActivityContext.
 */

namespace Drupal\activity_creator\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\Entity;
use Drupal\group\Entity\GroupContent;
use Drupal\social_post\Entity\Post;
use \Drupal\node\Entity\Node;

/**
 * Provides a 'GroupActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "group_activity_context",
 *  label = @Translation("Group activity context"),
 * )
 */
class GroupActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {

    // @TODO Retrieve the group members.
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {

      $referenced_entity = $data['related_object']['0'];

      if ($referenced_entity['target_type'] === 'post') {
        $post = Post::load($referenced_entity['target_id']);

        $recipient_group = $post->get('field_recipient_group')->getValue();
        if (!empty($recipient_group)) {
          $recipients[] = [
            'target_type' => 'group',
            'target_id' => $recipient_group['0']['target_id'],
          ];
        }
      }
      elseif ($referenced_entity['target_type'] === 'node') {
        // Try to load the entity.
        if ($node = Node::load($referenced_entity['target_id'])) {
          // Try to load group content from entity.
          if ($groupcontent = GroupContent::loadByEntity($node)) {
            // Potentially there are more than one.
            $groupcontent = reset($groupcontent);
            // Set the group id.
            $recipients[] = [
              'target_type' => 'group',
              'target_id' => $groupcontent->getGroup()->id(),
            ];
          }
        }
      }
    }

    return $recipients;
  }
}
