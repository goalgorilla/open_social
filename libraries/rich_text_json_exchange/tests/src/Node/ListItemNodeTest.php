<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ListItemNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ListItemNode class.
 */
#[CoversClass(ListItemNode::class)]
class ListItemNodeTest extends TestCase {

  /**
   * Tests that list-item node parses correctly.
   */
  #[Test]
  public function listItemNodeParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'children' => [
              ['type' => 'list-item', 'version' => 1, 'children' => []],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ListNode $list */
    $list = $document->getRoot()->getChildren()[0];
    $children = $list->getChildren();

    $this->assertCount(1, $children);
    $this->assertInstanceOf(ListItemNode::class, $children[0]);
    $this->assertSame('list-item', $children[0]->getType());
    $this->assertSame(1, $children[0]->getVersion());
  }

  /**
   * Tests that list-item with block children parses.
   */
  #[Test]
  public function listItemWithBlockChildrenParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => 'Item text'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ListNode $list */
    $list = $document->getRoot()->getChildren()[0];
    /** @var \OpenSocial\RichTextJson\Node\ListItemNode $listItem */
    $listItem = $list->getChildren()[0];

    $this->assertCount(1, $listItem->getChildren());
    $this->assertInstanceOf(ParagraphNode::class, $listItem->getChildren()[0]);
  }

  /**
   * Tests that list-item round-trips correctly.
   */
  #[Test]
  public function listItemRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => 'Item'],
                    ],
                  ],
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
   * Tests that unknown fields on list-item are preserved.
   */
  #[Test]
  public function unknownFieldsOnListItemArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [],
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
   * Tests nested lists inside list-item.
   */
  #[Test]
  public function nestedListInsideListItem(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'list',
                    'version' => 1,
                    'children' => [
                      ['type' => 'list-item', 'version' => 1, 'children' => []],
                    ],
                  ],
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

}
