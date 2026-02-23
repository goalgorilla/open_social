<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Document;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ValidatedDocument class.
 */
#[CoversClass(ValidatedDocument::class)]
#[CoversClass(ValidationException::class)]
class ValidatedDocumentTest extends TestCase {

  /**
   * Tests that a valid document can be created from an array.
   */
  #[Test]
  public function validDocumentFromArray(): void {
    $data = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
            ],
          ],
        ],
      ],
    ];

    $validated = ValidatedDocument::fromArray($data);

    $this->assertSame($data, $validated->toArray());
  }

  /**
   * Tests that a valid document can be created from JSON.
   */
  #[Test]
  public function validDocumentFromJson(): void {
    $json = '{"root":{"type":"root","version":1,"children":[{"type":"paragraph","version":1,"children":[{"type":"text","version":1,"text":"Hello"}]}]}}';

    $validated = ValidatedDocument::fromJson($json);

    $this->assertSame($json, $validated->toJson());
  }

  /**
   * Tests that a valid document can be created from a RichTextDocument.
   */
  #[Test]
  public function validDocumentFromDocument(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
            ],
          ],
        ],
      ],
    ]);

    $validated = ValidatedDocument::fromDocument($document);

    $this->assertSame($document, $validated->getDocument());
  }

  /**
   * Tests that an invalid document throws ValidationException.
   */
  #[Test]
  public function invalidDocumentThrowsException(): void {
    $data = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'text' => 'Missing version'],
            ],
          ],
        ],
      ],
    ];

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('version');

    ValidatedDocument::fromArray($data);
  }

  /**
   * Tests that ValidationException contains the errors.
   */
  #[Test]
  public function validationExceptionContainsErrors(): void {
    $data = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'text' => 'Missing version'],
            ],
          ],
        ],
      ],
    ];

    try {
      ValidatedDocument::fromArray($data);
      $this->fail('Expected ValidationException to be thrown');
    }
    catch (ValidationException $e) {
      $errors = $e->getErrors();
      $this->assertCount(1, $errors);
      $this->assertStringContainsString('version', strtolower($errors[0]->getMessage()));
      $this->assertSame('/root/children/0/children/0', $errors[0]->getPath());
    }
  }

  /**
   * Tests that ValidationException with multiple errors formats correctly.
   */
  #[Test]
  public function validationExceptionWithMultipleErrors(): void {
    $data = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          ['type' => 'text', 'version' => 1, 'text' => 'inline at root'],
          ['type' => 'linebreak'],
        ],
      ],
    ];

    try {
      ValidatedDocument::fromArray($data);
      $this->fail('Expected ValidationException to be thrown');
    }
    catch (ValidationException $e) {
      $errors = $e->getErrors();
      $this->assertGreaterThanOrEqual(2, count($errors));
      $this->assertStringContainsString('errors', $e->getMessage());
    }
  }

  /**
   * Tests that toJson works correctly on validated document.
   */
  #[Test]
  public function toJsonWithFlags(): void {
    $data = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [],
      ],
    ];

    $validated = ValidatedDocument::fromArray($data);
    $json = $validated->toJson(JSON_PRETTY_PRINT);

    $this->assertStringContainsString("\n", $json);
  }

}
