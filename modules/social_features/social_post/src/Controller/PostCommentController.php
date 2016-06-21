<?php

namespace Drupal\social_post\Controller;

use Drupal\social_comment\Controller\SocialCommentController;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routine override to change relevant bits in the password reset.
 */
class PostCommentController extends SocialCommentController {

  /**
   * @inheritdoc
   */
  public function getReplyForm(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {
    $account = $this->currentUser();

    // The user is not just previewing a comment.
    if ($request->request->get('op') != $this->t('Preview')) {
      // $pid indicates that this is a reply to a comment.
      if ($pid) {
        // Load the parent comment.
        $comment = $this->entityManager()->getStorage('comment')->load($pid);
      }
    }

    if ($entity->getEntityTypeId() === 'post') {
      // Check if the post has been posted in a group.
      /** @var @var \Drupal\social_post\Entity\Post $entity */
      $group_id = $entity->field_recipient_group->target_id;
      if ($group_id) {
        /** @var \Drupal\group\Entity\Group $group */
        $group = entity_load('group', $group_id);
        if (!$group->hasPermission('access posts in group', $account)|| !$group->hasPermission('add post entities in group', $account)) {
          if (!isset($comment)) {
            $comment = NULL;
          }
          /** @var \Drupal\Core\Url $url*/
          if ($url = $entity->urlInfo('canonical')) {
            // Redirect the user to the correct entity.
            return $this->redirectToOriginalEntity($url, $comment, $entity);
          }
        }
      }
    }

    return parent::getReplyForm($request, $entity, $field_name, $pid);
  }

}
