<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Renderer;

use OpenSocial\RichTextJson\Bitmask\TextDetail;
use OpenSocial\RichTextJson\Bitmask\TextFormat;
use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\CodeNode;
use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\InlineCodeNode;
use OpenSocial\RichTextJson\Node\LinebreakNode;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\ListItemNode;
use OpenSocial\RichTextJson\Node\ListNode;
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\QuoteNode;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\TextNode;

/**
 * Renders a RichTextDocument to HTML.
 *
 * Escapes all text content to prevent XSS attacks and sanitizes URLs
 * to block dangerous schemes like javascript:, data:, and vbscript:.
 */
final class HtmlRenderer {

  /**
   * The URL sanitizer.
   *
   * @var \OpenSocial\RichTextJson\Renderer\UrlSanitizer
   */
  private UrlSanitizer $urlSanitizer;

  /**
   * Creates a new HtmlRenderer.
   *
   * @param \OpenSocial\RichTextJson\Renderer\UrlSanitizer|null $urlSanitizer
   *   Optional URL sanitizer. If not provided, a default one will be created.
   */
  public function __construct(?UrlSanitizer $urlSanitizer = NULL) {
    $this->urlSanitizer = $urlSanitizer ?? new UrlSanitizer();
  }

  /**
   * Renders a RichTextDocument to HTML.
   *
   * @param \OpenSocial\RichTextJson\Document\RichTextDocument $document
   *   The document to render.
   *
   * @return string
   *   The rendered HTML.
   */
  public function renderDocument(RichTextDocument $document): string {
    return $this->renderNode($document->getRoot());
  }

  /**
   * Renders a node to HTML.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node to render.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderNode(NodeInterface $node): string {
    if ($node instanceof RootNode) {
      return $this->renderRootNode($node);
    }
    if ($node instanceof ParagraphNode) {
      return $this->renderParagraphNode($node);
    }
    if ($node instanceof HeadingNode) {
      return $this->renderHeadingNode($node);
    }
    if ($node instanceof TextNode) {
      return $this->renderTextNode($node);
    }
    if ($node instanceof LinkNode) {
      return $this->renderLinkNode($node);
    }
    if ($node instanceof ListNode) {
      return $this->renderListNode($node);
    }
    if ($node instanceof ListItemNode) {
      return $this->renderListItemNode($node);
    }
    if ($node instanceof QuoteNode) {
      return $this->renderQuoteNode($node);
    }
    if ($node instanceof CodeNode) {
      return $this->renderCodeNode($node);
    }
    if ($node instanceof InlineCodeNode) {
      return $this->renderInlineCodeNode($node);
    }
    if ($node instanceof LinebreakNode) {
      return '<br>';
    }

    // Unknown nodes are silently omitted.
    return '';
  }

  /**
   * Renders the children of a node.
   *
   * @param array<int, NodeInterface> $children
   *   The child nodes.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderChildren(array $children): string {
    $html = '';
    foreach ($children as $child) {
      $html .= $this->renderNode($child);
    }
    return $html;
  }

  /**
   * Renders a root node.
   *
   * @param \OpenSocial\RichTextJson\Node\RootNode $node
   *   The root node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderRootNode(RootNode $node): string {
    return $this->renderChildren($node->getChildren());
  }

  /**
   * Renders a paragraph node.
   *
   * @param \OpenSocial\RichTextJson\Node\ParagraphNode $node
   *   The paragraph node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderParagraphNode(ParagraphNode $node): string {
    return '<p>' . $this->renderChildren($node->getChildren()) . '</p>';
  }

  /**
   * Renders a heading node.
   *
   * @param \OpenSocial\RichTextJson\Node\HeadingNode $node
   *   The heading node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderHeadingNode(HeadingNode $node): string {
    // Clamp heading level to 1-6.
    $level = max(1, min(6, $node->getLevel()));
    return "<h$level>" . $this->renderChildren($node->getChildren()) . "</h$level>";
  }

  /**
   * Renders a text node.
   *
   * @param \OpenSocial\RichTextJson\Node\TextNode $node
   *   The text node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderTextNode(TextNode $node): string {
    $text = $this->escape($node->getText());

    // Apply format tags.
    $format = TextFormat::fromNullable($node->getFormat());
    if ($format !== NULL) {
      $text = $this->applyFormatting($text, $format);
    }

    // Apply detail tags.
    $detail = TextDetail::fromNullable($node->getDetail());
    if ($detail !== NULL) {
      $text = $this->applyDetail($text, $detail);
    }

    return $text;
  }

  /**
   * Applies format tags to text.
   *
   * @param string $text
   *   The text to format.
   * @param \OpenSocial\RichTextJson\Bitmask\TextFormat $format
   *   The format bitmask.
   *
   * @return string
   *   The formatted text.
   */
  private function applyFormatting(string $text, TextFormat $format): string {
    // Apply tags from innermost to outermost.
    if ($format->isStrikethrough()) {
      $text = '<s>' . $text . '</s>';
    }
    if ($format->isUnderline()) {
      $text = '<u>' . $text . '</u>';
    }
    if ($format->isItalic()) {
      $text = '<em>' . $text . '</em>';
    }
    if ($format->isBold()) {
      $text = '<strong>' . $text . '</strong>';
    }

    return $text;
  }

  /**
   * Applies detail tags to text.
   *
   * @param string $text
   *   The text to format.
   * @param \OpenSocial\RichTextJson\Bitmask\TextDetail $detail
   *   The detail bitmask.
   *
   * @return string
   *   The formatted text.
   */
  private function applyDetail(string $text, TextDetail $detail): string {
    if ($detail->isSuperscript()) {
      $text = '<sup>' . $text . '</sup>';
    }
    if ($detail->isSubscript()) {
      $text = '<sub>' . $text . '</sub>';
    }

    return $text;
  }

  /**
   * Renders a link node.
   *
   * @param \OpenSocial\RichTextJson\Node\LinkNode $node
   *   The link node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderLinkNode(LinkNode $node): string {
    $url = $this->urlSanitizer->sanitize($node->getUrl());
    $children = $this->renderChildren($node->getChildren());

    if ($url === '') {
      // Dangerous URL: render as span to preserve content.
      return '<span>' . $children . '</span>';
    }

    $href = $this->escape($url);
    $attrs = 'href="' . $href . '"';

    $title = $node->getTitle();
    if ($title !== NULL) {
      $attrs .= ' title="' . $this->escape($title) . '"';
    }

    return '<a ' . $attrs . '>' . $children . '</a>';
  }

  /**
   * Renders a list node.
   *
   * @param \OpenSocial\RichTextJson\Node\ListNode $node
   *   The list node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderListNode(ListNode $node): string {
    $children = $this->renderChildren($node->getChildren());

    if ($node->getListType() === 'number') {
      $start = $node->getStart();
      if ($start !== NULL && $start !== 1) {
        return '<ol start="' . $start . '">' . $children . '</ol>';
      }
      return '<ol>' . $children . '</ol>';
    }

    return '<ul>' . $children . '</ul>';
  }

  /**
   * Renders a list item node.
   *
   * @param \OpenSocial\RichTextJson\Node\ListItemNode $node
   *   The list item node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderListItemNode(ListItemNode $node): string {
    return '<li>' . $this->renderChildren($node->getChildren()) . '</li>';
  }

  /**
   * Renders a quote node.
   *
   * @param \OpenSocial\RichTextJson\Node\QuoteNode $node
   *   The quote node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderQuoteNode(QuoteNode $node): string {
    return '<blockquote>' . $this->renderChildren($node->getChildren()) . '</blockquote>';
  }

  /**
   * Renders a code node.
   *
   * @param \OpenSocial\RichTextJson\Node\CodeNode $node
   *   The code node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderCodeNode(CodeNode $node): string {
    $code = $this->escape($node->getCode());
    $language = $node->getLanguage();

    if ($language !== NULL) {
      $class = $this->escape('language-' . $language);
      return '<pre><code class="' . $class . '">' . $code . '</code></pre>';
    }

    return '<pre><code>' . $code . '</code></pre>';
  }

  /**
   * Renders an inline code node.
   *
   * @param \OpenSocial\RichTextJson\Node\InlineCodeNode $node
   *   The inline code node.
   *
   * @return string
   *   The rendered HTML.
   */
  private function renderInlineCodeNode(InlineCodeNode $node): string {
    return '<code>' . $this->escape($node->getCode()) . '</code>';
  }

  /**
   * Escapes HTML special characters.
   *
   * @param string $text
   *   The text to escape.
   *
   * @return string
   *   The escaped text.
   */
  private function escape(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }

}
