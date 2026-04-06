<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Provides help in generating a simple signed upload destination for tests.
 */
trait SignedUploadEntityDestinationTrait {

  use ContentTypeCreationTrait;

  /**
   * Set up the default image destination.
   *
   * Call this from your test's setUp method.
   */
  protected function setUpDefaultDestination() : void {
    $this->createContentType([
      'type' => 'article',
    ]);

    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Default Destination Image',
      'required' => FALSE,
      'settings' => [],
    ])->save();
  }

  /**
   * The default entity destination.
   *
   * @return \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination
   *   The entity destination for the image set up from setUpDefaultDestination.
   */
  protected function entityDestination(): EntityFieldUploadDestination {
    return new EntityFieldUploadDestination('node', 'article', 'field_image');
  }

}
