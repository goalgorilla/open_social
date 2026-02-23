<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ListItemNode;
use OpenSocial\RichTextJson\Node\ListNode;
use OpenSocial\RichTextJson\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ListNode class.
 */
#[CoversClass(ListNode::class)]
class ListNodeTest extends TestCase {

  /**
   * Tests that list with default listType (bullet) parses.
   */
  #[Test]
  public function listWithDefaultListTypeParses(): void {
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

    $children = $document->getRoot()->getChildren();
    $this->assertCount(1, $children);
    $this->assertInstanceOf(ListNode::class, $children[0]);

    /** @var \OpenSocial\RichTextJson\Node\ListNode $list */
    $list = $children[0];
    $this->assertSame('bullet', $list->getListType());
  }

  /**
   * Tests that list with explicit listType parses.
   */
  #[Test]
  public function listWithExplicitListTypeParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'number',
            'children' => [],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ListNode $list */
    $list = $document->getRoot()->getChildren()[0];
    $this->assertSame('number', $list->getListType());
  }

  /**
   * Tests that numbered list with start value parses.
   */
  #[Test]
  public function numberedListWithStartValueParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'number',
            'start' => 5,
            'children' => [],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ListNode $list */
    $list = $document->getRoot()->getChildren()[0];
    $this->assertSame('number', $list->getListType());
    $this->assertSame(5, $list->getStart());
  }

  /**
   * Tests that list without start returns null.
   */
  #[Test]
  public function listWithoutStartReturnsNull(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'number',
            'children' => [],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ListNode $list */
    $list = $document->getRoot()->getChildren()[0];
    $this->assertNull($list->getStart());
  }

  /**
   * Tests that list round-trips correctly preserving listType.
   */
  #[Test]
  public function listRoundTripsPreservingListType(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'bullet',
            'children' => [
              ['type' => 'list-item', 'version' => 1, 'children' => []],
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
   * Tests that numbered list with start round-trips correctly.
   */
  #[Test]
  public function numberedListWithStartRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'number',
            'start' => 10,
            'children' => [],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that list without explicit listType round-trips without adding it.
   */
  #[Test]
  public function listWithoutListTypeRoundTripsWithoutIt(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'children' => [],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that unknown fields on list are preserved.
   */
  #[Test]
  public function unknownFieldsOnListArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
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
   * Tests that list children are list-item nodes.
   */
  #[Test]
  public function listChildrenAreListItemNodes(): void {
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
              ['type' => 'list-item', 'version' => 1, 'children' => []],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\ListNode $list */
    $list = $document->getRoot()->getChildren()[0];
    $this->assertCount(2, $list->getChildren());
    $this->assertInstanceOf(ListItemNode::class, $list->getChildren()[0]);
    $this->assertInstanceOf(ListItemNode::class, $list->getChildren()[1]);
  }

  /**
   * Tests that non-list-item children in list cause validation error.
   */
  #[Test]
  public function nonListItemChildrenInListCauseValidationError(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
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
    $this->assertStringContainsString('list-item', strtolower($error->getMessage()));
  }

}
