<?php

namespace Drupal\social_language;

use Drupal\Core\Render\MainContent\ModalRenderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Social Language main content renderer for modal dialog requests.
 */
class SocialLanguageModalRenderer extends ModalRenderer {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $sub_request = $request->request->all();
    $sub_request['dialogOptions']['closeText'] = $this->t('Close');

    $request = new Request(
      $request->query->all(),
      $sub_request,
      $request->attributes->all(),
      $request->cookies->all(),
      $request->files->all(),
      $request->server->all(),
      $request->getContent()
    );

    return parent::renderResponse($main_content, $request, $route_match);
  }

}
