<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Renderer;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HtmlRenderer.
 */
#[CoversClass(HtmlRenderer::class)]
final class HtmlRendererTest extends TestCase {

  /**
   * Tests rendering an empty document.
   */
  public function testRendersEmptyDocument(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('', $html);
  }

  /**
   * Tests rendering a paragraph with text.
   */
  public function testRendersParagraphWithText(): void {
    $doc = RichTextDocument::fromArray([
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
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p>Hello, World!</p>', $html);
  }

  /**
   * Tests that HTML special characters are escaped.
   */
  public function testEscapesHtmlSpecialCharacters(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => '<script>alert("XSS")</script>'],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</p>', $html);
  }

  /**
   * Tests rendering bold text.
   */
  public function testRendersBoldText(): void {
    $doc = RichTextDocument::fromArray([
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

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><strong>Bold</strong></p>', $html);
  }

  /**
   * Tests rendering italic text.
   */
  public function testRendersItalicText(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Italic', 'format' => 2],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><em>Italic</em></p>', $html);
  }

  /**
   * Tests rendering underline text.
   */
  public function testRendersUnderlineText(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Underline', 'format' => 4],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><u>Underline</u></p>', $html);
  }

  /**
   * Tests rendering strikethrough text.
   */
  public function testRendersStrikethroughText(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Strike', 'format' => 8],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><s>Strike</s></p>', $html);
  }

  /**
   * Tests rendering combined formatting.
   */
  public function testRendersCombinedFormatting(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              // Bold (1) + italic (2) = 3.
              ['type' => 'text', 'version' => 1, 'text' => 'Both', 'format' => 3],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><strong><em>Both</em></strong></p>', $html);
  }

  /**
   * Tests rendering superscript.
   */
  public function testRendersSuperscript(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => '2', 'detail' => 1],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><sup>2</sup></p>', $html);
  }

  /**
   * Tests rendering subscript.
   */
  public function testRendersSubscript(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => '2', 'detail' => 2],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><sub>2</sub></p>', $html);
  }

  /**
   * Tests rendering heading levels 1-6.
   */
  public function testRendersHeadingLevels(): void {
    $renderer = new HtmlRenderer();

    for ($level = 1; $level <= 6; $level++) {
      $doc = RichTextDocument::fromArray([
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
      ]);

      $html = $renderer->renderDocument($doc);
      self::assertSame("<h$level>Heading $level</h$level>", $html);
    }
  }

  /**
   * Tests that heading level > 6 is clamped to h6.
   */
  public function testClampsHeadingLevelAbove6(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 10,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Heading'],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<h6>Heading</h6>', $html);
  }

  /**
   * Tests that heading level < 1 is clamped to h1.
   */
  public function testClampsHeadingLevelBelow1(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'heading',
            'version' => 1,
            'level' => 0,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Heading'],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<h1>Heading</h1>', $html);
  }

  /**
   * Tests rendering bullet list.
   */
  public function testRendersBulletList(): void {
    $doc = RichTextDocument::fromArray([
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
                      ['type' => 'text', 'version' => 1, 'text' => 'Item 2'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<ul><li><p>Item 1</p></li><li><p>Item 2</p></li></ul>', $html);
  }

  /**
   * Tests rendering numbered list.
   */
  public function testRendersNumberedList(): void {
    $doc = RichTextDocument::fromArray([
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
                      ['type' => 'text', 'version' => 1, 'text' => 'First'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<ol><li><p>First</p></li></ol>', $html);
  }

  /**
   * Tests rendering numbered list with start attribute.
   */
  public function testRendersNumberedListWithStart(): void {
    $doc = RichTextDocument::fromArray([
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
                      ['type' => 'text', 'version' => 1, 'text' => 'Fifth'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<ol start="5"><li><p>Fifth</p></li></ol>', $html);
  }

  /**
   * Tests rendering blockquote.
   */
  public function testRendersBlockquote(): void {
    $doc = RichTextDocument::fromArray([
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
                  ['type' => 'text', 'version' => 1, 'text' => 'Quote text'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<blockquote><p>Quote text</p></blockquote>', $html);
  }

  /**
   * Tests rendering code block.
   */
  public function testRendersCodeBlock(): void {
    $doc = RichTextDocument::fromArray([
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
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<pre><code>console.log(&quot;hello&quot;);</code></pre>', $html);
  }

  /**
   * Tests rendering code block with language.
   */
  public function testRendersCodeBlockWithLanguage(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => 'echo "hello";',
            'language' => 'php',
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<pre><code class="language-php">echo &quot;hello&quot;;</code></pre>', $html);
  }

  /**
   * Tests that HTML in code blocks is escaped.
   */
  public function testEscapesHtmlInCodeBlocks(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => '<script>alert("XSS")</script>',
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<pre><code>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</code></pre>', $html);
  }

  /**
   * Tests rendering inline code.
   */
  public function testRendersInlineCode(): void {
    $doc = RichTextDocument::fromArray([
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
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p>Use <code>printf()</code> function</p>', $html);
  }

  /**
   * Tests rendering linebreak.
   */
  public function testRendersLinebreak(): void {
    $doc = RichTextDocument::fromArray([
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
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p>Line 1<br>Line 2</p>', $html);
  }

  /**
   * Tests rendering link.
   */
  public function testRendersLink(): void {
    $doc = RichTextDocument::fromArray([
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

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><a href="https://example.com">Click here</a></p>', $html);
  }

  /**
   * Tests rendering link with title.
   */
  public function testRendersLinkWithTitle(): void {
    $doc = RichTextDocument::fromArray([
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
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Click here'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p><a href="https://example.com" title="Example Site">Click here</a></p>', $html);
  }

  /**
   * Tests that javascript: URLs are blocked in links.
   */
  public function testBlocksJavascriptUrls(): void {
    $doc = RichTextDocument::fromArray([
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
                'url' => 'javascript:alert(1)',
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Evil link'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    // Link should be rendered without href or with empty href.
    self::assertStringNotContainsString('javascript:', $html);
    self::assertStringContainsString('Evil link', $html);
  }

  /**
   * Tests that data: URLs are blocked in links.
   */
  public function testBlocksDataUrls(): void {
    $doc = RichTextDocument::fromArray([
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
                'url' => 'data:text/html,<script>alert(1)</script>',
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Data link'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertStringNotContainsString('data:', $html);
  }

  /**
   * Tests that unknown nodes are omitted.
   */
  public function testUnknownNodesAreOmitted(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Before'],
            ],
          ],
          [
            'type' => 'future-block',
            'version' => 1,
            'data' => 'unknown',
          ],
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'After'],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p>Before</p><p>After</p>', $html);
  }

  /**
   * Tests rendering nested structures.
   */
  public function testRendersNestedStructures(): void {
    $doc = RichTextDocument::fromArray([
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
                  ['type' => 'text', 'version' => 1, 'text' => 'A '],
                  ['type' => 'text', 'version' => 1, 'text' => 'bold', 'format' => 1],
                  ['type' => 'text', 'version' => 1, 'text' => ' quote with a '],
                  [
                    'type' => 'link',
                    'version' => 1,
                    'url' => 'https://example.com',
                    'children' => [
                      ['type' => 'text', 'version' => 1, 'text' => 'link'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame(
      '<blockquote><p>A <strong>bold</strong> quote with a <a href="https://example.com">link</a></p></blockquote>',
      $html,
    );
  }

  /**
   * Tests rendering multiple paragraphs.
   */
  public function testRendersMultipleParagraphs(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'First paragraph'],
            ],
          ],
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Second paragraph'],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertSame('<p>First paragraph</p><p>Second paragraph</p>', $html);
  }

  /**
   * Tests that language class is escaped.
   */
  public function testEscapesLanguageClass(): void {
    $doc = RichTextDocument::fromArray([
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'code',
            'version' => 1,
            'code' => 'code',
            'language' => '"><script>alert(1)</script>',
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertStringNotContainsString('<script>', $html);
  }

  /**
   * Tests that link title is escaped.
   */
  public function testEscapesLinkTitle(): void {
    $doc = RichTextDocument::fromArray([
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
                'title' => '"><script>alert(1)</script>',
                'children' => [
                  ['type' => 'text', 'version' => 1, 'text' => 'Link'],
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $renderer = new HtmlRenderer();
    $html = $renderer->renderDocument($doc);

    self::assertStringNotContainsString('<script>', $html);
  }

}
