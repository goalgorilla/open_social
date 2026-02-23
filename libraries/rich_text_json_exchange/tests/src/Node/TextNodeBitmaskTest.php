<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Node;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\TextNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for bitmask round-trip preservation in TextNode.
 *
 * @phpstan-import-type TextNodeArray from \OpenSocial\RichTextJson\Node\TextNode
 */
#[CoversClass(TextNode::class)]
#[CoversClass(RichTextDocument::class)]
final class TextNodeBitmaskTest extends TestCase {

  /**
   * Tests format bitmask with only known bits round-trips correctly.
   */
  public function testFormatKnownBitsRoundTrip(): void {
    $json = json_encode([
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
                'text' => 'Bold and italic',
                // Bold (1) + italic (2) = 3.
                'format' => 3,
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);
    $output = $doc->toArray();

    /** @phpstan-var TextNodeArray $textNode */
    // We trust the shape of the other arrays, thus we PHPStan ignore them.
    // This keeps the test readable.
    /** @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
    $textNode = $output['root']['children'][0]['children'][0];
    self::assertArrayHasKey('format', $textNode);
    self::assertSame(3, $textNode['format']);
  }

  /**
   * Tests format bitmask with unknown bits round-trips correctly.
   */
  public function testFormatUnknownBitsRoundTrip(): void {
    $json = json_encode([
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
                'text' => 'Text with unknown format',
                // Bold (1) + unknown bit 16 + unknown bit 32 = 49.
                'format' => 49,
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);
    $output = $doc->toArray();

    /** @phpstan-var TextNodeArray $textNode */
    // We trust the shape of the other arrays, thus we PHPStan ignore them.
    // This keeps the test readable.
    /** @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
    $textNode = $output['root']['children'][0]['children'][0];
    self::assertArrayHasKey('format', $textNode);
    self::assertSame(49, $textNode['format']);
  }

  /**
   * Tests detail bitmask with only known bits round-trips correctly.
   */
  public function testDetailKnownBitsRoundTrip(): void {
    $json = json_encode([
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
                'text' => 'Superscript',
                'detail' => 1,
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);
    $output = $doc->toArray();

    /** @phpstan-var TextNodeArray $textNode */
    // We trust the shape of the other arrays, thus we PHPStan ignore them.
    // This keeps the test readable.
    /** @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
    $textNode = $output['root']['children'][0]['children'][0];
    self::assertArrayHasKey('detail', $textNode);
    self::assertSame(1, $textNode['detail']);
  }

  /**
   * Tests detail bitmask with unknown bits round-trips correctly.
   */
  public function testDetailUnknownBitsRoundTrip(): void {
    $json = json_encode([
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
                'text' => 'Text with unknown detail',
                // Subscript (2) + unknown bit 4 + unknown bit 8 = 14.
                'detail' => 14,
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);
    $output = $doc->toArray();

    /** @phpstan-var TextNodeArray $textNode */
    /** @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
    $textNode = $output['root']['children'][0]['children'][0];
    self::assertArrayHasKey('detail', $textNode);
    self::assertSame(14, $textNode['detail']);
  }

  /**
   * Tests combined format and detail with unknown bits.
   */
  public function testCombinedFormatAndDetailWithUnknownBits(): void {
    $json = json_encode([
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
                'text' => 'Complex formatting',
                // All known format bits (15) + unknown bit 64 = 79.
                'format' => 79,
                // Superscript (1) + unknown bit 16 = 17.
                'detail' => 17,
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);
    $output = $doc->toArray();

    /** @phpstan-var TextNodeArray $textNode */
    // We trust the shape of the other arrays, thus we PHPStan ignore them.
    // This keeps the test readable.
    /** @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
    $textNode = $output['root']['children'][0]['children'][0];
    self::assertArrayHasKey('format', $textNode);
    self::assertSame(79, $textNode['format']);
    self::assertArrayHasKey('detail', $textNode);
    self::assertSame(17, $textNode['detail']);
  }

  /**
   * Tests that absent format/detail stays absent after round-trip.
   */
  public function testAbsentBitmasksStayAbsent(): void {
    $json = json_encode([
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
                'text' => 'Plain text',
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);
    $output = $doc->toArray();

    /** @phpstan-var TextNodeArray $textNode */
    // We trust the shape of the other arrays, thus we PHPStan ignore them.
    // This keeps the test readable.
    /** @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
    $textNode = $output['root']['children'][0]['children'][0];
    self::assertArrayNotHasKey('format', $textNode);
    self::assertArrayNotHasKey('detail', $textNode);
  }

  /**
   * Tests that zero format/detail round-trips as zero (not absent).
   */
  public function testZeroBitmasksRoundTrip(): void {
    $json = json_encode([
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
                'text' => 'Plain text',
                'format' => 0,
                'detail' => 0,
              ],
            ],
          ],
        ],
      ],
    ], JSON_THROW_ON_ERROR);

    $doc = RichTextDocument::fromJson($json);
    $output = $doc->toArray();

    /** @phpstan-var TextNodeArray $textNode */
    /** @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible */
    $textNode = $output['root']['children'][0]['children'][0];
    self::assertArrayHasKey('format', $textNode);
    self::assertArrayHasKey('detail', $textNode);
    self::assertSame(0, $textNode['format']);
    self::assertSame(0, $textNode['detail']);
  }

  /**
   * Tests TextNode getFormat and getDetail methods.
   */
  public function testTextNodeGetters(): void {
    $data = [
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
      'format' => 15,
      'detail' => 3,
    ];

    $node = TextNode::fromArray($data);

    self::assertSame(15, $node->getFormat());
    self::assertSame(3, $node->getDetail());
  }

  /**
   * Tests TextNode with null format and detail.
   */
  public function testTextNodeNullBitmasks(): void {
    $data = [
      'type' => 'text',
      'version' => 1,
      'text' => 'Hello',
    ];

    $node = TextNode::fromArray($data);

    self::assertNull($node->getFormat());
    self::assertNull($node->getDetail());
  }

}
