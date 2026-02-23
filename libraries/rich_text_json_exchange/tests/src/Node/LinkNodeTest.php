<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\TextNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the LinkNode class.
 */
#[CoversClass(LinkNode::class)]
class LinkNodeTest extends TestCase {

  /**
   * Tests that a link node parses correctly.
   */
  #[Test]
  public function linkNodeParses(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com',
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    $linkNodes = $paragraph->getChildren();

    $this->assertCount(1, $linkNodes);
    $this->assertInstanceOf(LinkNode::class, $linkNodes[0]);
    $this->assertSame('link', $linkNodes[0]->getType());
    $this->assertSame(1, $linkNodes[0]->getVersion());
  }

  /**
   * Tests that link node getUrl() returns the URL.
   */
  #[Test]
  public function linkNodeReturnsUrl(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com/page',
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\LinkNode $linkNode */
    $linkNode = $paragraph->getChildren()[0];

    $this->assertSame('https://example.com/page', $linkNode->getUrl());
  }

  /**
   * Tests that link node with title parses correctly.
   */
  #[Test]
  public function linkNodeWithTitleParses(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com',
                'title' => 'Example Site',
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\LinkNode $linkNode */
    $linkNode = $paragraph->getChildren()[0];

    $this->assertSame('Example Site', $linkNode->getTitle());
  }

  /**
   * Tests that link node without title returns null.
   */
  #[Test]
  public function linkNodeWithoutTitleReturnsNull(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com',
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\LinkNode $linkNode */
    $linkNode = $paragraph->getChildren()[0];

    $this->assertNull($linkNode->getTitle());
  }

  /**
   * Tests that link node with children parses correctly.
   */
  #[Test]
  public function linkNodeWithChildrenParses(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com',
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Click here'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ParagraphNode $paragraph */
    $paragraph = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\LinkNode $linkNode */
    $linkNode = $paragraph->getChildren()[0];

    $this->assertCount(1, $linkNode->getChildren());
    $this->assertInstanceOf(TextNode::class, $linkNode->getChildren()[0]);
  }

  /**
   * Tests that link node round-trips correctly.
   */
  #[Test]
  public function linkNodeRoundTrips(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com',
                'title' => 'Example',
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Click'],
                ],
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
   * Tests that link node without optional fields round-trips correctly.
   */
  #[Test]
  public function linkNodeWithoutOptionalFieldsRoundTrips(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com',
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
   * Tests that unknown fields on link node are preserved.
   */
  #[Test]
  public function unknownFieldsOnLinkNodeArePreserved(): void {
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
                'type' => 'link',
                'version' => 1,
                'url' => 'https://example.com',
                'rel' => 'noopener',
                'target' => '_blank',
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
