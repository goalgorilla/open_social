<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\TextNode;
use OpenSocial\RichTextJson\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the HeadingNode class.
 */
#[CoversClass(HeadingNode::class)]
class HeadingNodeTest extends TestCase {

  /**
   * Tests that a heading node parses with level.
   */
  #[Test]
  public function headingNodeParsesWithLevel(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 2,
          ],
        ],
      ],
    ]);

    $children = $document->getRoot()->getChildren();
    $this->assertCount(1, $children);
    $this->assertInstanceOf(HeadingNode::class, $children[0]);
    $this->assertSame('heading', $children[0]->getType());
    $this->assertSame(1, $children[0]->getVersion());

    /** @var \OpenSocial\RichTextJson\Node\HeadingNode $heading */
    $heading = $children[0];
    $this->assertSame(2, $heading->getLevel());
  }

  /**
   * Tests that heading requires level field.
   */
  #[Test]
  public function headingRequiresLevelField(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
          ],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertFalse($result->isValid());
    $this->assertCount(1, $result->getErrors());

    $error = $result->getErrors()[0];
    $this->assertStringContainsString('level', strtolower($error->getMessage()));
  }

  /**
   * Tests that heading with inline children parses.
   */
  #[Test]
  public function headingWithInlineChildrenParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\HeadingNode $heading */
    $heading = $document->getRoot()->getChildren()[0];
    $this->assertCount(1, $heading->getChildren());
    $this->assertInstanceOf(TextNode::class, $heading->getChildren()[0]);
  }

  /**
   * Tests that heading round-trips correctly.
   */
  #[Test]
  public function headingRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 3,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Title'],
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
   * Tests that unknown fields on heading are preserved.
   */
  #[Test]
  public function unknownFieldsOnHeadingArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 2,
            'children' => [],
            'customAttr' => 'preserved',
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that heading can only contain inline nodes.
   */
  #[Test]
  public function headingCanOnlyContainInlineNodes(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 1,
            'children' => [
              ['type' => 'paragraph', 'version' => 1, 'children' => []],
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
    $this->assertSame('/root/children/0/children/0', $error->getPath());
    $this->assertStringContainsString('inline', strtolower($error->getMessage()));
  }

}
