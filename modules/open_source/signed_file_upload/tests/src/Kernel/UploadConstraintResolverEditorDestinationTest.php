<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\Exception\InvalidDestinationException;
use Drupal\signed_file_upload\UploadConstraintResolver;
use Drupal\signed_file_upload\UploadConstraintResolverInterface;

/**
 * Test the upload constraint resolver for editor destinations.
 *
 * @group signed_file_upload
 */
class UploadConstraintResolverEditorDestinationTest extends SignedFileUploadTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
  ];

  /**
   * The constraint resolver under test.
   */
  private UploadConstraintResolver $constraintResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    $this->constraintResolver = $this->container->get(UploadConstraintResolverInterface::class);
  }

  /**
   * Make developers aware of this test class if they start implementing.
   */
  public function testThrowsExceptionUntilImplemented() : void {
    $this->expectException(InvalidDestinationException::class);

    $this->constraintResolver->resolve(new EditorUploadDestination('full_html', 'full_html'));
  }

}
