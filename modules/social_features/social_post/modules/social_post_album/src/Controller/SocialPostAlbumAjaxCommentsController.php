<?php

namespace Drupal\social_post_album\Controller;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\social_ajax_comments\Controller\AjaxCommentsController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for AJAX comments routes.
 */
class SocialPostAlbumAjaxCommentsController extends AjaxCommentsController {

  /**
   * {@inheritdoc}
   */
  public function socialAdd(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {
    $response = parent::socialAdd($request, $entity, $field_name, $pid);

    $command = $response->getCommands()[0];
    $response->addCommand(new ReplaceCommand($command['selector'] . '-modal', $command['data']), TRUE);

    return $response;
  }

}
