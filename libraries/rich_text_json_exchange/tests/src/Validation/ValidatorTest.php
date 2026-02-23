<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Validation;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Validation\ValidationError;
use OpenSocial\RichTextJson\Validation\ValidationResult;
use OpenSocial\RichTextJson\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for document structural validation.
 */
#[CoversClass(Validator::class)]
#[CoversClass(ValidationResult::class)]
#[CoversClass(ValidationError::class)]
class ValidatorTest extends TestCase {

  /**
   * Tests that a valid document passes validation.
   */
  #[Test]
  public function validDocumentPassesValidation(): void {
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

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertTrue($result->isValid());
    $this->assertSame([], $result->getErrors());
  }

  /**
   * Tests that inline node directly under root is invalid.
   */
  #[Test]
  public function inlineNodeUnderRootIsInvalid(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertFalse($result->isValid());
    $this->assertCount(1, $result->getErrors());

    $error = $result->getErrors()[0];
    $this->assertSame('/root/children/0', $error->getPath());
    $this->assertStringContainsString('block', strtolower($error->getMessage()));
  }

  /**
   * Tests that block node inside paragraph is invalid.
   */
  #[Test]
  public function blockNodeInsideParagraphIsInvalid(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
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
    $this->assertSame('/root/children/0/children/0', $error->getPath());
    $this->assertStringContainsString('inline', strtolower($error->getMessage()));
  }

  /**
   * Tests that block node inside link is invalid.
   */
  #[Test]
  public function blockNodeInsideLinkIsInvalid(): void {
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
                  ['type' => 'paragraph', 'version' => 1, 'children' => []],
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
    $this->assertSame('/root/children/0/children/0/children/0', $error->getPath());
    $this->assertStringContainsString('inline', strtolower($error->getMessage()));
  }

  /**
   * Tests that text node with children is invalid.
   */
  #[Test]
  public function textNodeWithChildrenIsInvalid(): void {
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
                'type' => 'text',
                'version' => 1,
                'text' => 'Hello',
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'World'],
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
    $this->assertSame('/root/children/0/children/0', $error->getPath());
    $this->assertStringContainsString('children', strtolower($error->getMessage()));
  }

  /**
   * Tests that linebreak node with children is invalid.
   */
  #[Test]
  public function linebreakNodeWithChildrenIsInvalid(): void {
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
                'type' => 'linebreak',
                'version' => 1,
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
    $this->assertSame('/root/children/0/children/0', $error->getPath());
    $this->assertStringContainsString('children', strtolower($error->getMessage()));
  }

  /**
   * Tests that missing version on a node is invalid.
   */
  #[Test]
  public function missingVersionIsInvalid(): void {
    $document = RichTextDocument::fromArray([
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
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertFalse($result->isValid());
    $this->assertCount(1, $result->getErrors());

    $error = $result->getErrors()[0];
    $this->assertSame('/root/children/0/children/0', $error->getPath());
    $this->assertStringContainsString('version', strtolower($error->getMessage()));
  }

  /**
   * Data provider for testing missing version validation on all node types.
   *
   * @return array<string, array{0: array<string, mixed>, 1: string}>
   *   Test cases with node data (missing version) and expected error path.
   */
  public static function nodeTypesMissingVersionProvider(): array {
    // Inline nodes: placed inside a paragraph.
    $inlineNodes = [
      'text node' => [
        ['type' => 'text', 'text' => 'Hello'],
        '/root/children/0/children/0',
      ],
      'linebreak node' => [
        ['type' => 'linebreak'],
        '/root/children/0/children/0',
      ],
      'link node' => [
        ['type' => 'link', 'url' => 'https://example.com', 'children' => []],
        '/root/children/0/children/0',
      ],
      'inline-code node' => [
        ['type' => 'inline-code', 'text' => 'code'],
        '/root/children/0/children/0',
      ],
    ];

    // Block nodes: placed directly under root.
    $blockNodes = [
      'paragraph node' => [
        ['type' => 'paragraph', 'children' => []],
        '/root/children/0',
      ],
      'heading node' => [
        ['type' => 'heading', 'tag' => 'h1', 'children' => []],
        '/root/children/0',
      ],
      'quote node' => [
        ['type' => 'quote', 'children' => []],
        '/root/children/0',
      ],
      'code node' => [
        ['type' => 'code', 'code' => 'echo "hi";'],
        '/root/children/0',
      ],
      'list node' => [
        ['type' => 'list', 'listType' => 'bullet', 'children' => []],
        '/root/children/0',
      ],
      'list-item node' => [
        ['type' => 'list-item', 'children' => []],
        '/root/children/0',
      ],
    ];

    return array_merge($inlineNodes, $blockNodes);
  }

  /**
   * Tests that missing version on any node type is invalid.
   *
   * @param array<string, mixed> $nodeData
   *   The node data without version field.
   * @param string $expectedPath
   *   The expected error path.
   */
  #[Test]
  #[DataProvider('nodeTypesMissingVersionProvider')]
  public function missingVersionIsInvalidForAllNodeTypes(array $nodeData, string $expectedPath): void {
    // Determine if this is a block or inline node based on expected path.
    $isBlockNode = $expectedPath === '/root/children/0';

    if ($isBlockNode) {
      $document = RichTextDocument::fromArray([
        'root' => [
          'type' => 'root',
          'version' => 1,
          'children' => [$nodeData],
        ],
      ]);
    }
    else {
      $document = RichTextDocument::fromArray([
        'root' => [
          'type' => 'root',
          'version' => 1,
          'children' => [
            [
              'type' => 'paragraph',
              'version' => 1,
              'children' => [$nodeData],
            ],
          ],
        ],
      ]);
    }

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    assert(is_string($nodeData['type']));
    $this->assertFalse(
      $result->isValid(),
      sprintf('Node type "%s" without version should be invalid', $nodeData['type'])
    );
    $this->assertCount(1, $result->getErrors());

    $error = $result->getErrors()[0];
    $this->assertSame($expectedPath, $error->getPath());
    $this->assertStringContainsString('version', strtolower($error->getMessage()));
  }

  /**
   * Tests that missing type on a node is invalid.
   */
  #[Test]
  public function missingTypeIsInvalid(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['version' => 1, 'text' => 'Missing type'],
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
    $this->assertStringContainsString('type', strtolower($error->getMessage()));
  }

  /**
   * Tests that multiple validation errors are collected.
   */
  #[Test]
  public function multipleErrorsAreCollected(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          ['type' => 'text', 'version' => 1, 'text' => 'inline at root'],
          ['type' => 'linebreak', 'version' => 1],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertFalse($result->isValid());
    $this->assertCount(2, $result->getErrors());
  }

  /**
   * Tests that unknown node types are allowed but flagged appropriately.
   *
   * Unknown nodes under root are treated as block nodes for validation
   * purposes (forward compatibility).
   */
  #[Test]
  public function unknownNodeTypeUnderRootIsAllowed(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          ['type' => 'future-block', 'version' => 1],
        ],
      ],
    ]);

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    // Unknown nodes under root are assumed to be block nodes (forward compat).
    $this->assertTrue($result->isValid());
  }

  /**
   * Tests validation of nested link content.
   */
  #[Test]
  public function validLinkWithInlineChildrenPasses(): void {
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

    $validator = new Validator();
    $result = $validator->validateDocument($document);

    $this->assertTrue($result->isValid());
  }

  /**
   * Tests that text node missing required 'text' field is invalid.
   */
  #[Test]
  public function textNodeMissingTextFieldIsInvalid(): void {
    $document = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1],
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
    $this->assertStringContainsString('text', strtolower($error->getMessage()));
  }

  /**
   * Tests that link node missing required 'url' field is invalid.
   */
  #[Test]
  public function linkNodeMissingUrlFieldIsInvalid(): void {
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
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'link text'],
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
    $this->assertStringContainsString('url', strtolower($error->getMessage()));
  }

  /**
   * Tests that list-item children are recursively validated.
   *
   * This test demonstrates a bug: currently, ListItemNode children are never
   * validated because validateNode() has no branch for ListItemNode.
   */
  #[Test]
  public function testListItemWithInvalidChildIsDetected(): void {
    $data = [
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
                  // Missing 'text' field.
                  ['type' => 'text', 'version' => 1],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($data);
    $result = (new Validator())->validateDocument($document);

    $this->assertFalse($result->isValid());
  }

}
