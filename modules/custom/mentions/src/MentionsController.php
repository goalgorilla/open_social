<?php

namespace Drupal\mentions;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the mentions entity.
 *
 * @see \Drupal\mentions\Entity.
 */
class MentionsController extends ControllerBase {

  /**
   * Redirects mention links to the correct page depending on entity context.
   *
   * Taken from: commentPermalink.
   *
   * @param \Drupal\mentions\MentionsInterface $mentions
   *   A mention entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The mention listing set to the page on which the mention appears.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function mentionPermalink(MentionsInterface $mentions): RedirectResponse {
    if ($entity = $mentions->getMentionedEntity()) {
      // Check access permissions for the entity.
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      $entity_url = $entity->toUrl('canonical');
      return new RedirectResponse($entity_url->setAbsolute()->toString());
    }
    throw new NotFoundHttpException();
  }

}
