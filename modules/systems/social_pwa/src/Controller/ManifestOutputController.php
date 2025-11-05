<?php

namespace Drupal\social_pwa\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ManifestOutputController.
 *
 * @package Drupal\social_pwa\Controller
 */
class ManifestOutputController extends ControllerBase {

  /**
   * This will convert the social_pwa.settings.yml array to json format.
   */
  public function generateManifest() {
    $pwa_enabled = \Drupal::config('social_pwa.settings')->get('status.all');
    if (!$pwa_enabled) {
      return new JsonResponse([]);
    }

    // Get all the current settings stored in social_pwa.settings.
    $config = \Drupal::config('social_pwa.settings')->get();

    // Array filter used to filter the "_core:" key from the output.
    $allowed = [
      'name',
      'short_name',
      'icons',
      'start_url',
      'background_color',
      'theme_color',
      'display',
      'orientation',
    ];

    $filtered = [];

    foreach ($config as $config_key => $config_value) {
      if (!in_array($config_key, $allowed)) {
        continue;
      }

      if ($config_key == 'icons') {
        // Get the specific icons. Needed to get the correct path of the file.
        $icon = \Drupal::config('social_pwa.settings')->get('icons.icon');

        // Get the file id and path.
        $fid = $icon[0];
        /** @var \Drupal\file\Entity\File $file */
        $file = File::load($fid);
        $path = $file->getFileUri();

        $image_styles = [
          'social_pwa_icon_128' => '128x128',
          'social_pwa_icon_144' => '144x144',
          'social_pwa_icon_152' => '152x152',
          'social_pwa_icon_180' => '180x180',
          'social_pwa_icon_192' => '192x192',
          'social_pwa_icon_256' => '256x256',
          'social_pwa_icon_512' => '512x512',
        ];

        $config_value = [];

        foreach ($image_styles as $key => $value) {
          $config_value[] = [
            'src' => \Drupal::service('file_url_generator')->transformRelative(ImageStyle::load($key)->buildUrl($path)),
            'sizes' => $value,
            'type' => 'image/png',
          ];
        }
      }

      $filtered[$config_key] = $config_value;
    }

    // Finally, after all the magic went down we return a manipulated and
    // filtered array of our social_pwa.settings and output it to JSON format.
    $response = new CacheableJsonResponse($filtered);
    $response->getCacheableMetadata()->addCacheTags(['manifestjson']);
    return $response;
  }

}
