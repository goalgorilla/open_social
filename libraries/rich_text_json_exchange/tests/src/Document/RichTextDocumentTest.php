<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Document;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Exception\InvalidDocumentException;
use OpenSocial\RichTextJson\Exception\JsonDecodeException;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\UnknownNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RichTextDocument parsing and serialization.
 */
#[CoversClass(RichTextDocument::class)]
#[CoversClass(RootNode::class)]
#[CoversClass(UnknownNode::class)]
#[CoversClass(JsonDecodeException::class)]
#[CoversClass(InvalidDocumentException::class)]
class RichTextDocumentTest extends TestCase {

  /**
   * Tests that a minimal valid document parses successfully.
   */
  #[Test]
  public function minimalValidDocumentParses(): void {
    $json = '{"root": {"type": "root", "version": 1}}';

    $document = RichTextDocument::fromJson($json);

    $this->assertSame('root', $document->getRoot()->getType());
    $this->assertSame(1, $document->getRoot()->getVersion());
  }

  /**
   * Tests that root with empty children array parses.
   */
  #[Test]
  public function rootWithEmptyChildrenParses(): void {
    $json = '{"root": {"type": "root", "version": 1, "children": []}}';

    $document = RichTextDocument::fromJson($json);

    $this->assertSame([], $document->getRoot()->getChildren());
  }

  /**
   * Tests that fromArray works with valid array input.
   */
  #[Test]
  public function fromArrayWithValidInput(): void {
    $data = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [],
      ],
    ];

    $document = RichTextDocument::fromArray($data);

    $this->assertSame('root', $document->getRoot()->getType());
  }

  /**
   * Tests that invalid JSON throws JsonDecodeException.
   */
  #[Test]
  public function invalidJsonThrowsJsonDecodeException(): void {
    $this->expectException(JsonDecodeException::class);

    RichTextDocument::fromJson('{invalid json}');
  }

  /**
   * Tests that empty JSON object throws InvalidDocumentException.
   */
  #[Test]
  public function missingRootThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);
    $this->expectExceptionMessage('root');

    RichTextDocument::fromJson('{}');
  }

  /**
   * Tests that root missing type throws InvalidDocumentException.
   */
  #[Test]
  public function rootMissingTypeThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);
    $this->expectExceptionMessage('type');

    RichTextDocument::fromJson('{"root": {"version": 1}}');
  }

  /**
   * Tests that root missing version throws InvalidDocumentException.
   */
  #[Test]
  public function rootMissingVersionThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);
    $this->expectExceptionMessage('version');

    RichTextDocument::fromJson('{"root": {"type": "root"}}');
  }

  /**
   * Tests that root with wrong type value throws InvalidDocumentException.
   */
  #[Test]
  public function rootWithWrongTypeThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);

    RichTextDocument::fromJson('{"root": {"type": "paragraph", "version": 1}}');
  }

  /**
   * Tests that root with non-positive version throws InvalidDocumentException.
   */
  #[Test]
  public function rootWithZeroVersionThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);
    $this->expectExceptionMessage('version');

    RichTextDocument::fromJson('{"root": {"type": "root", "version": 0}}');
  }

  /**
   * Tests that root with negative version throws InvalidDocumentException.
   */
  #[Test]
  public function rootWithNegativeVersionThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);
    $this->expectExceptionMessage('version');

    RichTextDocument::fromJson('{"root": {"type": "root", "version": -1}}');
  }

  /**
   * Tests that unknown node types in children are preserved as UnknownNode.
   */
  #[Test]
  public function unknownNodeTypeIsPreserved(): void {
    $json = '{"root": {"type": "root", "version": 1, "children": [{"type": "custom-widget", "version": 1, "foo": "bar"}]}}';

    $document = RichTextDocument::fromJson($json);
    $children = $document->getRoot()->getChildren();

    $this->assertCount(1, $children);
    $this->assertInstanceOf(UnknownNode::class, $children[0]);
    $this->assertSame('custom-widget', $children[0]->getType());
    $this->assertSame(1, $children[0]->getVersion());
  }

  /**
   * Tests that unknown fields on root node are preserved.
   */
  #[Test]
  public function unknownFieldsOnRootArePreserved(): void {
    $json = '{"root": {"type": "root", "version": 1, "customField": 123, "anotherField": "test"}}';

    $document = RichTextDocument::fromJson($json);
    $result = $document->toArray();

    $this->assertArrayHasKey('customField', $result['root']);
    $this->assertSame(123, $result['root']['customField']);
    $this->assertArrayHasKey('anotherField', $result['root']);
    $this->assertSame('test', $result['root']['anotherField']);
  }

  /**
   * Tests that unknown node with all its fields round-trips correctly.
   */
  #[Test]
  public function unknownNodeRoundTripsWithAllFields(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'custom-widget',
            'version' => 2,
            'foo' => 'bar',
            'nested' => ['a' => 1, 'b' => 2],
          ],
        ],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that toArray returns the original structure for valid documents.
   */
  #[Test]
  public function toArrayReturnsOriginalStructure(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame($original, $result);
  }

  /**
   * Tests that toJson produces equivalent JSON.
   */
  #[Test]
  public function toJsonProducesEquivalentJson(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [],
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $json = $document->toJson();

    $this->assertJson($json);
    $this->assertSame($original, json_decode($json, TRUE));
  }

  /**
   * Tests that toJson accepts JSON encoding flags.
   */
  #[Test]
  public function toJsonAcceptsFlags(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 1,
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $json = $document->toJson(JSON_PRETTY_PRINT);

    $this->assertStringContainsString("\n", $json);
  }

  /**
   * Tests round-trip with higher version number on root (forward compat).
   */
  #[Test]
  public function higherVersionRootNodeIsPreserved(): void {
    $original = [
      'root' => [
        'type' => 'root',
        'version' => 99,
        'children' => [],
        'futureField' => 'value',
      ],
    ];

    $document = RichTextDocument::fromArray($original);
    $result = $document->toArray();

    $this->assertSame(99, $result['root']['version']);
    $this->assertSame('value', $result['root']['futureField']);
  }

  /**
   * Tests that root with non-integer version throws InvalidDocumentException.
   */
  #[Test]
  public function rootWithNonIntegerVersionThrowsException(): void {
    $this->expectException(InvalidDocumentException::class);

    RichTextDocument::fromJson('{"root": {"type": "root", "version": "1"}}');
  }

  /**
   * Tests that root with non-string type throws InvalidDocumentException.
   */
  #[Test]
  public function rootWithNonStringTypeThrowsException(): void {
    $this->expectException(InvalidDocumentException::class);

    RichTextDocument::fromJson('{"root": {"type": 123, "version": 1}}');
  }

  /**
   * Tests that null root throws InvalidDocumentException.
   */
  #[Test]
  public function nullRootThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);

    RichTextDocument::fromJson('{"root": null}');
  }

  /**
   * Tests that non-array root throws InvalidDocumentException.
   */
  #[Test]
  public function nonArrayRootThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);

    RichTextDocument::fromJson('{"root": "not an object"}');
  }

  /**
   * Tests that children must be an array if present.
   */
  #[Test]
  public function nonArrayChildrenThrowsInvalidDocumentException(): void {
    $this->expectException(InvalidDocumentException::class);

    RichTextDocument::fromJson('{"root": {"type": "root", "version": 1, "children": "not an array"}}');
  }

}
