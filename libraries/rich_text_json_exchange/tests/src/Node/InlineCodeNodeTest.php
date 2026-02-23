<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\InlineCodeNode;
use OpenSocial\RichTextJson\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the InlineCodeNode class.
 */
#[CoversClass(InlineCodeNode::class)]
class InlineCodeNodeTest extends TestCase {

  /**
   * Tests that inline-code parses with code.
   */
  #[Test]
  public function inlineCodeParsesWithCode(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => 'console.log()',
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    $children = $paragraph->getChildren();

    $this->assertCount(1, $children);
    $this->assertInstanceOf(InlineCodeNode::class, $children[0]);

    /** @var \OpenSocial\RichTextJson\Node\InlineCodeNode $inlineCode */
    $inlineCode = $children[0];
    $this->assertSame('inline-code', $inlineCode->getType());
    $this->assertSame('console.log()', $inlineCode->getCode());
  }

  /**
   * Tests that inline-code with language parses.
   */
  #[Test]
  public function inlineCodeWithLanguageParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => '$var',
                'language' => 'php',
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\InlineCodeNode $inlineCode */
    $inlineCode = $paragraph->getChildren()[0];

    $this->assertSame('$var', $inlineCode->getCode());
    $this->assertSame('php', $inlineCode->getLanguage());
  }

  /**
   * Tests that inline-code without language returns null.
   */
  #[Test]
  public function inlineCodeWithoutLanguageReturnsNull(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => 'test',
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\InlineCodeNode $inlineCode */
    $inlineCode = $paragraph->getChildren()[0];

    $this->assertNull($inlineCode->getLanguage());
  }

  /**
   * Tests that inline-code requires code field.
   */
  #[Test]
  public function inlineCodeRequiresCodeField(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
              ],
            ],
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
   * Tests that inline-code must not have children.
   */
  #[Test]
  public function inlineCodeMustNotHaveChildren(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => 'test',
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'bad'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertFalse($result->isValid());
    $this->assertCount(1, $result->getErrors());

    $error = $result->getErrors()[0];
    $this->assertStringContainsString('children', strtolower($error->getMessage()));
  }

  /**
   * Tests that inline-code round-trips correctly.
   */
  #[Test]
  public function inlineCodeRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => 'npm install',
                'language' => 'bash',
              ],
            ],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that inline-code without language round-trips correctly.
   */
  #[Test]
  public function inlineCodeWithoutLanguageRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => 'variable',
              ],
            ],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that unknown fields on inline-code are preserved.
   */
  #[Test]
  public function unknownFieldsOnInlineCodeArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => 'test',
                'customAttr' => 'preserved',
              ],
            ],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that inline-code with empty code string is valid.
   */
  #[Test]
  public function inlineCodeWithEmptyCodeStringIsValid(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              [
                'type' => 'inline-code',
                'version' => 1,
                'code' => '',
              ],
            ],
          ],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertTrue($result->isValid());
  }

}
