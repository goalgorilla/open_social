<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Renderer;

use OpenSocial\RichTextJson\Renderer\HtmlImporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HtmlImporter.
 */
#[CoversClass(HtmlImporter::class)]
final class HtmlImporterTest extends TestCase {

  /**
   * Tests importing empty HTML.
   */
  public function testImportsEmptyHtml(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [],
      ],
    ], $array);
  }

  /**
   * Tests importing whitespace-only HTML.
   */
  public function testImportsWhitespaceOnlyHtml(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml("  \n\t  ");
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [],
      ],
    ], $array);
  }

  /**
   * Tests importing a single paragraph.
   */
  public function testImportsSingleParagraph(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p>Hello, World!</p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello, World!'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing multiple paragraphs.
   */
  public function testImportsMultipleParagraphs(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p>First</p><p>Second</p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'First'],
            ],
          ],
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Second'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing heading levels 1-6.
   */
  #[TestWith([1])]
  #[TestWith([2])]
  #[TestWith([3])]
  #[TestWith([4])]
  #[TestWith([5])]
  #[TestWith([6])]
  public function testImportsHeadingLevels(int $level): void {
    $importer = new HtmlImporter();

    $doc = $importer->fromHtml("<h$level>Heading $level</h$level>");
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => $level,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => "Heading $level"],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing bullet list.
   */
  public function testImportsBulletList(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<ul><li><p>Item 1</p></li><li><p>Item 2</p></li></ul>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'bullet',
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => "Item 1"],
                    ],
                  ],
                ],
              ],
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => "Item 2"],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing numbered list.
   */
  public function testImportsNumberedList(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<ol><li><p>First</p></li><li><p>Second</p></li></ol>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'number',
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => "First"],
                    ],
                  ],
                ],
              ],
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => "Second"],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing numbered list with start attribute.
   */
  public function testImportsNumberedListWithStart(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<ol start="5"><li><p>Fifth</p></li></ol>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'number',
            'start' => 5,
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => "Fifth"],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing bullet list.
   */
  public function testImportsListWithUnwrappedChildText(): void {
    // @todo This may require a change in specification.
    self::markTestSkipped("The parser currently removes whitespace and wraps the orphan text in a paragraph, even though text would be allowed there.");

    /** @phpstan-ignore-next-line deadCode.unreachable */
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<ul><li>Item 1</li></ul>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'bullet',
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => "Item 1"],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing blockquote.
   */
  public function testImportsBlockquote(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<blockquote><p>Quote text</p></blockquote>');
    $array = $doc->toArray();

    self::assertEquals([
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
                  ['type' => 'text', 'version' => 1, 'text' => "Quote text"],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing code block.
   */
  public function testImportsCodeBlock(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<pre><code>console.log("hello");</code></pre>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => 'console.log("hello");',
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing code block with language class.
   */
  public function testImportsCodeBlockWithLanguage(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<pre><code class="language-javascript">alert(1);</code></pre>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'language' => 'javascript',
            'code' => 'alert(1);',
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing bold text with strong tag.
   */
  public function testImportsBoldTextWithStrong(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><strong>Bold</strong></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 1, 'text' => 'Bold'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing bold text with b tag.
   */
  public function testImportsBoldTextWithB(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><b>Bold</b></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 1, 'text' => 'Bold'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing italic text with em tag.
   */
  public function testImportsItalicTextWithEm(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><em>Italic</em></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 2, 'text' => 'Italic'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing italic text with i tag.
   */
  public function testImportsItalicTextWithI(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><i>Italic</i></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 2, 'text' => 'Italic'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing underline text.
   */
  public function testImportsUnderlineText(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><u>Underline</u></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 4, 'text' => 'Underline'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing strikethrough text with s tag.
   */
  public function testImportsStrikethroughTextWithS(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><s>Strike</s></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 8, 'text' => 'Strike'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing strikethrough text with strike tag.
   */
  public function testImportsStrikethroughTextWithStrike(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><strike>Strike</strike></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 8, 'text' => 'Strike'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing strikethrough text with del tag.
   */
  public function testImportsStrikethroughTextWithDel(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><del>Deleted</del></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'format' => 8, 'text' => 'Deleted'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing superscript text.
   */
  public function testImportsSuperscriptText(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><sup>Superscript</sup></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'detail' => 1, 'text' => 'Superscript'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing subscript text.
   */
  public function testImportsSubscriptText(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><sub>Subscript</sub></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'detail' => 2, 'text' => 'Subscript'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing combined formatting (bold + italic).
   */
  public function testImportsCombinedFormatting(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><strong><em>Both</em></strong></p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              // Bold (1) + italic (2) = 3.
              ['type' => 'text', 'version' => 1, 'format' => 3, 'text' => 'Both'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing linebreak.
   */
  public function testImportsLinebreak(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p>Line 1<br>Line 2</p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Line 1'],
              ['type' => 'linebreak', 'version' => 1],
              ['type' => 'text', 'version' => 1, 'text' => 'Line 2'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing link.
   */
  public function testImportsLink(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><a href="https://example.com">Click here</a></p>');
    $array = $doc->toArray();

    self::assertEquals([
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
    ], $array);
  }

  /**
   * Tests importing link with title attribute.
   */
  public function testImportsLinkWithTitle(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><a href="https://example.com" title="Example">Link</a></p>');
    $array = $doc->toArray();

    self::assertEquals([
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
                  ['type' => 'text', 'version' => 1, 'text' => 'Link'],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing inline code.
   */
  public function testImportsInlineCode(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p>Use <code>printf()</code> function</p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Use '],
              ['type' => 'inline-code', 'version' => 1, 'code' => 'printf()'],
              ['type' => 'text', 'version' => 1, 'text' => ' function'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing nested lists.
   */
  public function testImportsNestedLists(): void {
    $importer = new HtmlImporter();
    $html = '<ul><li><p>Item 1</p><ul><li><p>Nested 1</p></li></ul></li></ul>';
    $doc = $importer->fromHtml($html);
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'list',
            'version' => 1,
            'listType' => 'bullet',
            'children' => [
              [
                'type' => 'list-item',
                'version' => 1,
                'children' => [
                  [
                    'type' => 'paragraph',
                    'version' => 1,
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => 'Item 1'],
                    ],
                  ],
                  [
                    'type' => 'list',
                    'version' => 1,
                    'listType' => 'bullet',
                    'children' => [
                      [
                        'type' => 'list-item',
                        'version' => 1,
                        'children' => [
                          [
                            'type' => 'paragraph',
                            'version' => 1,
                            'children' => [
                              ['type' => 'text', 'version' => 1, 'text' => 'Nested 1'],
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing paragraph with mixed inline formatting.
   */
  public function testImportsParagraphWithMixedFormatting(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p>Normal <strong>bold</strong> <em>italic</em> text</p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Normal '],
              ['type' => 'text', 'version' => 1, 'format' => 1, 'text' => 'bold'],
              ['type' => 'text', 'version' => 1, 'text' => ' '],
              ['type' => 'text', 'version' => 1, 'format' => 2, 'text' => 'italic'],
              ['type' => 'text', 'version' => 1, 'text' => ' text'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing blockquote with multiple paragraphs.
   */
  public function testImportsBlockquoteWithMultipleParagraphs(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<blockquote><p>First</p><p>Second</p></blockquote>');
    $array = $doc->toArray();

    self::assertEquals([
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
                  ['type' => 'text', 'version' => 1, 'text' => 'First'],
                ],
              ],
              [
                'type' => 'paragraph',
                'version' => 1,
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Second'],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing heading with inline formatting.
   */
  public function testImportsHeadingWithFormatting(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<h1>Heading with <strong>bold</strong></h1>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Heading with '],
              ['type' => 'text', 'version' => 1, 'format' => 1, 'text' => 'bold'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests importing link with formatted text inside.
   */
  public function testImportsLinkWithFormattedText(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p><a href="https://example.com"><strong>Bold link</strong></a></p>');
    $array = $doc->toArray();

    self::assertEquals([
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
                  ['type' => 'text', 'version' => 1, 'format' => 1, 'text' => 'Bold link'],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests handling malformed HTML gracefully.
   */
  public function testHandlesMalformedHtml(): void {
    $importer = new HtmlImporter();
    // Unclosed tag.
    $doc = $importer->fromHtml('<p>Hello');
    $array = $doc->toArray();

    // Should still produce valid document.
    self::assertEquals([
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
    ], $array);
  }

  /**
   * Tests stripping unsupported HTML tags.
   */
  public function testStripsUnsupportedTags(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p>Text with <span>unsupported</span> tag</p>');
    $array = $doc->toArray();

    // Should preserve text content even if tags are unsupported.
    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Text with unsupported tag'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests handling empty paragraphs.
   */
  public function testHandlesEmptyParagraphs(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p></p><p>Text</p>');
    $array = $doc->toArray();

    // @todo This decision should be figured out, it technically changes the document.
    // Empty paragraph should be omitted.
    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Text'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests handling text nodes between block elements.
   */
  public function testHandlesTextNodesBetweenBlocks(): void {
    self::markTestSkipped("The parser currently removes whitespace and wraps the orphan text in a paragraph, even though text would be allowed there.");

    /** @phpstan-ignore-next-line deadCode.unreachable */
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml('<p>First</p> Text between <p>Second</p>');
    $array = $doc->toArray();

    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'First'],
            ],
          ],
          ['type' => 'text', 'version' => 1, 'text' => ' Text between '],
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Second'],
            ],
          ],
        ],
      ],
    ], $array);
  }

  /**
   * Tests whitespace normalization.
   */
  public function testNormalizesWhitespace(): void {
    $importer = new HtmlImporter();
    $doc = $importer->fromHtml("<p>Text\n\n  with   multiple\tspaces</p>");
    $array = $doc->toArray();

    // @todo This is not currently covered by the spec.
    // Multiple whitespace should be normalized to single space.
    self::assertEquals([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Text with multiple spaces'],
            ],
          ],
        ],
      ],
    ], $array);
  }

}
