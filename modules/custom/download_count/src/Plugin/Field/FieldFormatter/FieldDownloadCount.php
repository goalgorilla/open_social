<?php

namespace Drupal\download_count\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * The FieldDownloadCount class.
 *
 * @FieldFormatter(
 *  id = "FieldDownloadCount",
 *  label = @Translation("Generic file with download count"),
 *  field_types = {"file"}
 * )
 */
class FieldDownloadCount extends GenericFileFormatter implements ContainerFactoryPluginInterface {

  /**
   * Serialization services.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Database services.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * Cache services.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The user services.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * The theme manager services.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $theme;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    Serializer $serializer,
    Connection $connection,
    CacheBackendInterface $cacheBackend,
    AccountProxyInterface $accountProxy,
  ThemeManagerInterface $theme,
  TranslationManager $translation) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->serializer = $serializer;
    $this->database = $connection;
    $this->cache = $cacheBackend;
    $this->user = $accountProxy;
    $this->theme = $theme;
    $this->translation = $translation;
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
      $container->get('serializer'),
      $container->get('database'),
      $container->get('cache.default'),
      $container->get('current_user'),
      $container->get('theme.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $access = $this->user->hasPermission('view download counts');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;

      if ($access) {
        // Try to fetch the count from cache.
        $cache_data = $this->cache->get('download_count');

        // Decode the cache json data.
        $cache_download_count_array = $cache_data ? $this->serializer->decode($cache_data->data, 'json') : NULL;

        // Get the count from cached array.
        if ($cache_download_count_array && array_key_exists($file->id(), $cache_download_count_array)) {
          $download_count = $cache_download_count_array[$file->id()];
        }
        else {
          // If there is no data in cache, we find it via a query.
          $download_count = $this->database->select('download_count', 'du')
            ->fields('fu', ['fid'])
            ->condition('type', $entity_type)
            ->condition('id', $entity->id())
            ->countQuery()
            ->execute()
            ->fetchField();

          // Update the array.
          $cache_download_counts[$file->id()] = $download_count;
          // And then we set the data in cache again after encoding.
          // This cache should automatically expire after an hour.
          $this->cache->set('download_count', $this->serializer->encode($cache_download_counts, 'json'), time() + 3600);
        }

        if ($download_count > 0) {
          $count_string = $this->translation
            ->formatPlural($download_count, '1 download', '@count downloads');
        }
        else {
          $count_string = $this->t('0 downloads');
        }
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

      $theme = $this->theme->getActiveTheme();

      // Check if socialbase is one of the base themes.
      // Then get the path to socialbase theme and provide a variable
      // that can be used in the template for a path to the icons.
      if (array_key_exists('socialbase', $theme->getBaseThemes())) {
        $basethemes = $theme->getBaseThemes();
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
        '#count' => $count_string,
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
