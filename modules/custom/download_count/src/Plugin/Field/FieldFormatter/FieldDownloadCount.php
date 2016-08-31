<?php

namespace Drupal\download_count\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;

/**
 * @FieldFormatter(
 *  id = "FieldDownloadCount",
 *  label = @Translation("Generic file with download count"),
 *  field_types = {"file"}
 * )
 */
class FieldDownloadCount extends GenericFileFormatter {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $access = \Drupal::currentUser()->hasPermission('view download counts');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;

      if ($access) {
        $download = Database::getConnection()
          ->query('SELECT COUNT(fid) from {download_count} where fid = :fid AND type = :type AND id = :id', array(
            ':fid' => $file->id(),
            ':type' => $entity_type,
            ':id' => $entity->id()
          ))
          ->fetchField();
        $file->download = $download;
      }

      $url = file_create_url($file->getFileUri());
      $file_size = $file->getSize();

      $options = array(
        'attributes' => array(
          'type' => $file->getMimeType() . '; length=' . $file->getSize(),
        ),
      );

      // Use the description as the link text if available.
      if (empty($item->description)) {
        $link_text = $file->getFilename();
      }
      else {
        $link_text = $item->description;
        $options['attributes']['title'] = Html::escape($file->getFilename());
      }

      // Classes to add to the file field for icons.
      $classes = array(
        'file',
        // Add a specific class for each and every mime type.
        'file--mime-' . strtr($file->getMimeType(), array(
          '/' => '-',
          '.' => '-'
        )),
        // Add a more general class for groups of well known mime types.
        'file--' . file_icon_class($file->getMimeType()),
      );

      $attributes = new Attribute(array('class' => $classes));
      $url = Link::fromTextAndUrl(t($link_text), Url::fromUri($url, $options))
        ->toString();

      if (isset($file->download) && $file->download > 0) {
        $count = \Drupal::translation()
          ->formatPlural($file->download, '1 download', '@count downloads');
      }
      else {
        $count = $this->t('0 downloads');
      }

      $element[$delta] = array(
        '#theme' => !$access ? 'file_link' : 'download_count_file_field_formatter',
        '#file' => $file,
        '#url' => $url,
        '#classes' => $attributes['class'],
        '#count' => $count,
        '#file_size' => format_size($file_size),
        '#attached' => array(
          'library' => array(
            'classy/file',
          ),
        ),
        '#cache' => array(
          'tags' => $file->getCacheTags(),
        ),
      );

      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $element[$delta] += array('#attributes' => array());
        $element[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $element;
  }
}
