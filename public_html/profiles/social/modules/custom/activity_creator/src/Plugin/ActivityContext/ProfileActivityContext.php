<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContext\ProfileActivityContext.
 */

namespace Drupal\activity_creator\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\Entity;
use Drupal\social_post\Entity\Post;

/**
 * Provides a 'ProfileActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "profile_activity_context",
 *  label = @Translation("Profile activity context"),
 * )
 */
class ProfileActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $referenced_entity = $data['related_object']['0'];

      if ($referenced_entity['target_type'] === 'post') {
        $post = Post::load($referenced_entity['target_id']);

        $recipient_user = $post->get('field_recipient_user')->getValue();
        if (!empty($recipient_user)) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $recipient_user['0']['target_id'],
          ];
        }


      }

    }

    return $recipients;
  }

}
