<?php

declare(strict_types=1);

namespace Drupal\social_media_system\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\FieldConfigInterface;
use Drupal\media\Attribute\MediaSource;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;

/**
 * Media source wrapping around link media entity fields.
 */
#[MediaSource(
  id: 'link',
  label: new TranslatableMarkup('Link'),
  description: new TranslatableMarkup('Use link for reusable media.'),
  allowed_field_types: ['link'],
  default_thumbnail_filename: 'no-thumbnail.png',
)]
class Link extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
    return [
      'label' => $this->t('Title'),
      'url' => $this->t('URL'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type): FieldConfigInterface {
    return parent::createSourceField($type)->set('label', 'Link');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display): void {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'link',
      'label' => 'hidden',
    ]);
  }

}
