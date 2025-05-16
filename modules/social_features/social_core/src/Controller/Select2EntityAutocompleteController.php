<?php

namespace Drupal\social_core\Controller;

use Drupal\select2\Controller\EntityAutocompleteController as EntityAutocompleteControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class Select2EntityAutocompleteController.
 *
 * @package Drupal\social_core\Controller
 */
class Select2EntityAutocompleteController extends EntityAutocompleteControllerBase {

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete(Request $request, string $target_type, string $selection_handler, string $selection_settings_key): JsonResponse {
    if ($selection_handler === "social" && !$this->currentUser()->hasPermission('use select2 autocomplete')) {
      throw new AccessDeniedHttpException();
    }

    return parent::handleAutocomplete($request, $target_type, $selection_handler, $selection_settings_key);
  }

}
