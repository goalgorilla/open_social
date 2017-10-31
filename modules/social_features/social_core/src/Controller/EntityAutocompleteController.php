<?php

namespace Drupal\social_core\Controller;

use Drupal\system\Controller\EntityAutocompleteController as EntityAutocompleteControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class EntityAutocompleteController.
 *
 * @package Drupal\social_core\Controller
 */
class EntityAutocompleteController extends EntityAutocompleteControllerBase {

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete(Request $request, $target_type, $selection_handler, $selection_settings_key) {
    $matches = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));

      // Selection settings are passed in as a hashed key of a serialized array
      // stored in the key/value store.
      $selection_settings = $this->keyValue->get($selection_settings_key, FALSE);

      if ($selection_settings !== FALSE) {
        $selection_settings_hash = Crypt::hmacBase64(serialize($selection_settings) . $target_type . $selection_handler, Settings::getHashSalt());

        if ($selection_settings_hash !== $selection_settings_key) {
          // Disallow access when the selection settings hash does not match the
          // passed-in key.
          throw new AccessDeniedHttpException('Invalid selection settings key.');
        }
      }
      else {
        // Disallow access when the selection settings key is not found in the
        // key/value store.
        throw new AccessDeniedHttpException();
      }

      $matches = $this->matcher->getMatches($target_type, $selection_handler, $selection_settings, $typed_string);
    }

    return new JsonResponse($matches);
  }

}
