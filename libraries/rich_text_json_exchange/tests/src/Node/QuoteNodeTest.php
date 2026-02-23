<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\QuoteNode;
use OpenSocial\RichTextJson\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the QuoteNode class.
 */
#[CoversClass(QuoteNode::class)]
class QuoteNodeTest extends TestCase {

  /**
   * Tests that quote node parses correctly.
   */
  #[Test]
  public function quoteNodeParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'quote',
            'version' => 1,
            'children' => [],
          ],
        ],
      ],
    ]);

    $children = $document->getRoot()->getChildren();
    $this->assertCount(1, $children);
    $this->assertInstanceOf(QuoteNode::class, $children[0]);
    $this->assertSame('quote', $children[0]->getType());
    $this->assertSame(1, $children[0]->getVersion());
  }

  /**
   * Tests that quote with block children parses.
   */
  #[Test]
  public function quoteWithBlockChildrenParses(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'quote',
            'version' => 1,
            'children' => [
              [
                'type' => 'paragraph',
                'version' => 1,
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Quoted text'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    /** @var \OpenSocial\RichTextJson\Node\QuoteNode $quote */
    $quote = $document->getRoot()->getChildren()[0];
    $this->assertCount(1, $quote->getChildren());
    $this->assertInstanceOf(ParagraphNode::class, $quote->getChildren()[0]);
  }

  /**
   * Tests that quote round-trips correctly.
   */
  #[Test]
  public function quoteRoundTrips(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'quote',
            'version' => 1,
            'children' => [
              [
                'type' => 'paragraph',
                'version' => 1,
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Famous quote'],
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
   * Tests that unknown fields on quote are preserved.
   */
  #[Test]
  public function unknownFieldsOnQuoteArePreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'quote',
            'version' => 1,
            'children' => [],
            'citation' => 'Author Name',
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that quote can only contain block nodes (not inline directly).
   */
  #[Test]
  public function quoteCanOnlyContainBlockNodes(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'quote',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Direct text'],
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
    $this->assertStringContainsString('block', strtolower($error->getMessage()));
  }

  /**
   * Tests nested quotes.
   */
  #[Test]
  public function nestedQuotes(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'quote',
            'version' => 1,
            'children' => [
              [
                'type' => 'quote',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => 'Nested'],
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
