<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\FileLink as BaseFileLink;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "file_link" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("file_link",
 *   replace = "template_preprocess_file_link"
 * )
 */
class FileLink extends BaseFileLink implements ContainerFactoryPluginInterface {

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The storage handler class for files.
   *
   * @var \Drupal\file\FileStorage
   */
  protected $fileStorage;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ThemeManagerInterface $theme_manager,
    EntityTypeManagerInterface $entity
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeManager = $theme_manager;
    $this->fileStorage = $entity->getStorage('file');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info): void {
    parent::preprocess($variables, $hook, $info);

    // Find out what the active theme is first.
    $theme = $this->themeManager->getActiveTheme();

    // Check if socialbase is one of the base themes.
    // Then get the path to socialbase theme and provide a variable
    // that can be used in the template for a path to the icons.
    if (array_key_exists('socialbase', $theme->getBaseThemeExtensions())) {
      $basethemes = $theme->getBaseThemeExtensions();
      $variables['path_to_socialbase'] = $basethemes['socialbase']->getPath();
    }

    $file = ($variables['file'] instanceof File) ? $variables['file'] : $this->fileStorage->load($variables['file']->fid);

    if ($file instanceof File) {
      $mime_type = $file->getMimeType();
      $generic_mime_type = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.3.0', fn() => \Drupal\file\IconMimeTypes::getIconClass($mime_type), fn() => file_icon_class($mime_type));
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

      // Set a new variable to be used in the template file.
      $variables['node_icon'] = $node_icon;
    }

  }

}
