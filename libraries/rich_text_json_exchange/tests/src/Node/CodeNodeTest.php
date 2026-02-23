<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\CodeNode;
use OpenSocial\RichTextJson\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the CodeNode class.
 */
#[CoversClass(CodeNode::class)]
class CodeNodeTest extends TestCase {

  /**
   * Tests that code block parses with code and language.
   */
  #[Test]
  public function codeBlockParsesWithCodeAndLanguage(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => 'echo "Hello";',
            'language' => 'php',
          ],
        ],
      ],
    ]);

    $children = $document->getRoot()->getChildren();
    $this->assertCount(1, $children);
    $this->assertInstanceOf(CodeNode::class, $children[0]);

    /** @var \OpenSocial\RichTextJson\Node\CodeNode $codeBlock */
    $codeBlock = $children[0];
    $this->assertSame('code', $codeBlock->getType());
    $this->assertSame('echo "Hello";', $codeBlock->getCode());
    $this->assertSame('php', $codeBlock->getLanguage());
  }

  /**
   * Tests that code block without language parses.
   */
  #[Test]
  public function codeBlockWithoutLanguageParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => 'some code',
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\CodeNode $codeBlock */
    $codeBlock = $document->getRoot()->getChildren()[0];
    $this->assertSame('some code', $codeBlock->getCode());
    $this->assertNull($codeBlock->getLanguage());
  }

  /**
   * Tests that code block requires code field.
   */
  #[Test]
  public function codeBlockRequiresCodeField(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'language' => 'php',
          ],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertFalse($result->isValid());
    $this->assertCount(1, $result->getErrors());

    $error = $result->getErrors()[0];
    $this->assertStringContainsString('code', strtolower($error->getMessage()));
  }

  /**
   * Tests that code block round-trips correctly.
   */
  #[Test]
  public function codeBlockRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => "function test() {\n  return true;\n}",
            'language' => 'javascript',
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that code block without language round-trips correctly.
   */
  #[Test]
  public function codeBlockWithoutLanguageRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => 'plain code',
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that unknown fields on code block are preserved.
   */
  #[Test]
  public function unknownFieldsOnCodeBlockArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => 'test',
            'highlightLines' => [1, 3, 5],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that code block with empty code string is valid.
   */
  #[Test]
  public function codeBlockWithEmptyCodeStringIsValid(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => '',
          ],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertTrue($result->isValid());
  }

}
