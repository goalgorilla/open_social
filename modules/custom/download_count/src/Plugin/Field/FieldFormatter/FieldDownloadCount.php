<?php

namespace Drupal\download_count\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The FieldDownloadCount class.
 *
 * @FieldFormatter(
 *  id = "FieldDownloadCount",
 *  label = @Translation("Generic file with download count"),
 *  field_types = {"file"}
 * )
 */
class FieldDownloadCount extends GenericFileFormatter {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  private $themeManager;

  /**
   * FieldDownloadCount constructor.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The settings array.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct($plugin_id, array $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountProxyInterface $current_user, ThemeManagerInterface $theme_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currentUser = $current_user;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $access = $this->currentUser->hasPermission('view download counts');
    $download = 0;

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;

      if ($access) {
        $download = Database::getConnection()
          ->query('SELECT COUNT(fid) from {download_count} where fid = :fid AND type = :type AND id = :id', [
            ':fid' => $file->id(),
            ':type' => $entity_type,
            ':id' => $entity->id(),
          ])
          ->fetchField();
        $file->download = (int) $download;
      }

      $link_url = file_create_url($file->getFileUri());
      $file_size = $file->getSize();

      $options = [
        'attributes' => [
          'type' => $file->getMimeType() . '; length=' . $file->getSize(),
        ],
      ];

      // Use the description as the link text if available.
      if (empty($item->description)) {
        $link_text = $file->getFilename();
      }
      else {
        $link_text = $item->description;
        $options['attributes']['title'] = Html::escape($file->getFilename());
      }

      // Classes to add to the file field for icons.
      $classes = [
        'file',
        // Add a specific class for each and every mime type.
        'file--mime-' . strtr($file->getMimeType(), [
          '/' => '-',
          '.' => '-',
        ]),
        // Add a more general class for groups of well known mime types.
        'file--' . file_icon_class($file->getMimeType()),
      ];

      $attributes = new Attribute(['class' => $classes]);
      $link = Link::fromTextAndUrl($link_text, Url::fromUri($link_url, $options))
        ->toString();

      if (isset($file->download) && $file->download > 0 && $file->download !== NULL) {
        $count = $this->formatPlural($download, '1 download', '@count downloads');
      }
      else {
        $count = $this->t('0 downloads');
      }

      $theme = $this->themeManager->getActiveTheme();

      // Check if socialbase is one of the base themes.
      // Then get the path to socialbase theme and provide a variable
      // that can be used in the template for a path to the icons.
      if (array_key_exists('socialbase', $theme->getBaseThemeExtensions())) {
        $basethemes = $theme->getBaseThemeExtensions();
        $path_to_socialbase = $basethemes['socialbase']->getPath();
      }

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

      $element[$delta] = [
        '#theme' => !$access ? 'file_link' : 'download_count_file_field_formatter',
        '#file' => $file,
        '#link' => $link,
        '#link_url' => $link_url,
        '#link_text' => $link_text,
        '#classes' => $attributes['class'],
        '#count' => $count,
        '#file_size' => format_size($file_size),
        '#path_to_socialbase' => $path_to_socialbase,
        '#node_icon' => $node_icon,
        '#attached' => [
          'library' => [
            'classy/file',
          ],
        ],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];

      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $element[$delta] += ['#attributes' => []];
        $element[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $element;
  }

}
