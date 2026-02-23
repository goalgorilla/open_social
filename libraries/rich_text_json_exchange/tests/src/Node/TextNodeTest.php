<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\TextNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the TextNode class.
 */
#[CoversClass(TextNode::class)]
class TextNodeTest extends TestCase {

  /**
   * Tests that a text node parses correctly.
   */
  #[Test]
  public function textNodeParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello World'],
            ],
          ],
        ],
      ],
    ]);

    $paragraph = $document->getRoot()->getChildren()[0];
    $this->assertInstanceOf(ParagraphNode::class, $paragraph);

    $textNodes = $paragraph->getChildren();
    $this->assertCount(1, $textNodes);
    $this->assertInstanceOf(TextNode::class, $textNodes[0]);
    $this->assertSame('text', $textNodes[0]->getType());
    $this->assertSame(1, $textNodes[0]->getVersion());
  }

  /**
   * Tests that text node getText() returns the text content.
   */
  #[Test]
  public function textNodeReturnsTextContent(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello World'],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\TextNode $textNode */
    $textNode = $paragraph->getChildren()[0];

    $this->assertSame('Hello World', $textNode->getText());
  }

  /**
   * Tests that text node with format field parses correctly.
   */
  #[Test]
  public function textNodeWithFormatParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Bold', 'format' => 1],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\TextNode $textNode */
    $textNode = $paragraph->getChildren()[0];

    $this->assertSame(1, $textNode->getFormat());
  }

  /**
   * Tests that text node with detail field parses correctly.
   */
  #[Test]
  public function textNodeWithDetailParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Super', 'detail' => 1],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\TextNode $textNode */
    $textNode = $paragraph->getChildren()[0];

    $this->assertSame(1, $textNode->getDetail());
  }

  /**
   * Tests that text node without format returns null or 0.
   */
  #[Test]
  public function textNodeWithoutFormatReturnsNull(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Plain'],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\TextNode $textNode */
    $textNode = $paragraph->getChildren()[0];

    $this->assertNull($textNode->getFormat());
  }

  /**
   * Tests that text node round-trips correctly.
   */
  #[Test]
  public function textNodeRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello', 'format' => 3, 'detail' => 1],
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
   * Tests that text node without optional fields round-trips correctly.
   */
  #[Test]
  public function textNodeWithoutOptionalFieldsRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Plain text'],
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
   * Tests that unknown fields on text node are preserved.
   */
  #[Test]
  public function unknownFieldsOnTextNodeArePreserved(): void {
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
                'type' => 'text',
                'version' => 1,
                'text' => 'Hello',
                'customField' => 'preserved',
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

}
