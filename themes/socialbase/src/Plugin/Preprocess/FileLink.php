<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\file\Entity\File;
use Drupal\bootstrap\Plugin\Preprocess\FileLink as BaseFileLink;

/**
 * Pre-processes variables for the "file_link" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("file_link",
 *   replace = "template_preprocess_file_link"
 * )
 */
class FileLink extends BaseFileLink {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Find out what the active theme is first.
    $theme = \Drupal::theme()->getActiveTheme();

    // Check if socialbase is one of the base themes.
    // Then get the path to socialbase theme and provide a variable
    // that can be used in the template for a path to the icons.
    if (array_key_exists('socialbase', $theme->getBaseThemes())) {
      $basethemes = $theme->getBaseThemes();
      $variables['path_to_socialbase'] = $basethemes['socialbase']->getPath();
    }

    $file = ($variables['file'] instanceof File) ? $variables['file'] : File::load($variables['file']->fid);

    $mime_type = $file->getMimeType();
    $generic_mime_type = file_icon_class($mime_type);

    if (isset($generic_mime_type)) {

      // Set new icons for the mime types.
      switch ($generic_mime_type) {

        case 'application-pdf':
          $node_icon = 'pdf';
          break;

        case 'x-office-document':
          $node_icon = 'document';
          break;

        case 'x-office-presentation':
          $node_icon = 'presentation';
          break;

        case 'x-office-spreadsheet':
          $node_icon = 'spreadsheet';
          break;

        case 'package-x-generic':
          $node_icon = 'archive';
          break;

        case 'audio':
          $node_icon = 'audio';
          break;

        case 'video':
          $node_icon = 'video';
          break;

        case 'image':
          $node_icon = 'image';
          break;

        default:
          $node_icon = 'text';
      }

    }

    // Set a new variable to be used in the template file.
    $variables['node_icon'] = $node_icon;

  }

}
