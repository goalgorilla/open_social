<?php

declare(strict_types=1);

namespace Drupal\secret_file_system\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite secret file URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a parameter on the request.
 *
 * See https://www.drupal.org/project/drupal/issues/2741939.
 */
class SecretFiles implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (str_starts_with($path, '/system/file/') && !$request->attributes->has('filepath')) {
      $matches = [];
      if (!preg_match('|^(\/system\/file\/[\w-]+\/\d+)\/(.+)|', $path, $matches)) {
        return $path;
      }
      [$_, $new_path, $file_path] = $matches;
      // We add the filepath as attribute, so we don't need to update the
      // controller in the future.
      $request->attributes->set('filepath', $file_path);
      return $new_path;
    }
    return $path;
  }

}
