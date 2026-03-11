<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Renderer;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\CodeNode;
use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\InlineCodeNode;
use OpenSocial\RichTextJson\Node\LinebreakNode;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\ListItemNode;
use OpenSocial\RichTextJson\Node\ListNode;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\QuoteNode;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\TextNode;

/**
 * Imports HTML and converts it to a RichTextDocument.
 *
 * Best-effort import that handles common HTML elements.
 */
final class HtmlImporter {

  /**
   * Imports HTML and returns a RichTextDocument.
   *
   * @param string $html
   *   The HTML to import.
   *
   * @return \OpenSocial\RichTextJson\Document\RichTextDocument
   *   The imported document.
   */
  public function fromHtml(string $html): RichTextDocument {
    if (trim($html) === '') {
      return new RichTextDocument(new RootNode(1, [], [], TRUE));
    }

    // Parse HTML using DOMDocument.
    $dom = new \DOMDocument();
    // Suppress warnings for malformed HTML.
    $prevErrorState = libxml_use_internal_errors(TRUE);
    // Wrap in div to handle fragments.
    $dom->loadHTML(
      '<?xml encoding="UTF-8"><div>' . $html . '</div>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prevErrorState);

    $blockNodes = [];
    if ($dom->documentElement !== NULL) {
      $body = $dom->getElementsByTagName('div')->item(0);
      if ($body !== NULL) {
        $blockNodes = $this->convertBlockElements($body);
      }
    }

    $root = new RootNode(1, $blockNodes, [], TRUE);
    return new RichTextDocument($root);
  }

  /**
   * Converts block-level child elements to block nodes.
   *
   * @param \DOMNode $parent
   *   The parent DOM node.
   *
   * @return array<int, \OpenSocial\RichTextJson\Node\NodeInterface>
   *   The converted block nodes.
   */
  private function convertBlockElements(\DOMNode $parent): array {
    $blocks = [];

    foreach ($parent->childNodes as $child) {
      if ($child instanceof \DOMText) {
        // Text at block level: wrap in paragraph if not just whitespace.
        $text = trim($child->textContent);
        if ($text !== '') {
          $textNode = new TextNode(1, $text);
          $blocks[] = new ParagraphNode(1, [$textNode]);
        }
        continue;
      }

      if (!($child instanceof \DOMElement)) {
        continue;
      }

      $tagName = strtolower($child->tagName);

      if ($tagName === 'p') {
        $inlineNodes = $this->convertInlineElements($child, 0, 0);
        if ($inlineNodes !== []) {
          $blocks[] = new ParagraphNode(1, $inlineNodes);
        }
      }
      elseif (preg_match('/^h([1-6])$/', $tagName, $matches)) {
        $level = (int) $matches[1];
        $inlineNodes = $this->convertInlineElements($child, 0, 0);
        if ($inlineNodes !== []) {
          $blocks[] = new HeadingNode(1, $level, $inlineNodes);
        }
      }
      elseif ($tagName === 'ul') {
        $listItems = $this->convertListItems($child);
        if ($listItems !== []) {
          $blocks[] = new ListNode(
            1,
            'bullet',
            NULL,
            $listItems,
            [],
            TRUE,
            TRUE,
            FALSE,
          );
        }
      }
      elseif ($tagName === 'ol') {
        $start = $child->getAttribute('start');
        // Match only whole integers (negative and positive).
        $startInt = (preg_match('/^-?\d+$/', $start) === 1) ? (int) $start : NULL;
        $hasStart = $startInt !== NULL && $startInt !== 1;
        if ($startInt === 1) {
          $startInt = NULL;
        }
        $listItems = $this->convertListItems($child);
        if ($listItems !== []) {
          $blocks[] = new ListNode(
            1,
            'number',
            $startInt,
            $listItems,
            [],
            TRUE,
            TRUE,
            $hasStart,
          );
        }
      }
      elseif ($tagName === 'blockquote') {
        $quoteBlocks = $this->convertBlockElements($child);
        if ($quoteBlocks !== []) {
          $blocks[] = new QuoteNode(1, $quoteBlocks);
        }
      }
      elseif ($tagName === 'pre') {
        $codeNode = $this->convertCodeBlock($child);
        if ($codeNode !== NULL) {
          $blocks[] = $codeNode;
        }
      }
      else {
        // Unknown block element: try to extract block content.
        $extracted = $this->convertBlockElements($child);
        foreach ($extracted as $block) {
          $blocks[] = $block;
        }
      }
    }

    return $blocks;
  }

  /**
   * Converts list items.
   *
   * @param \DOMElement $list
   *   The list element (ul or ol).
   *
   * @return array<int, \OpenSocial\RichTextJson\Node\NodeInterface>
   *   The list item nodes.
   */
  private function convertListItems(\DOMElement $list): array {
    $items = [];

    foreach ($list->childNodes as $child) {
      if (!($child instanceof \DOMElement)) {
        continue;
      }

      if (strtolower($child->tagName) === 'li') {
        $itemBlocks = $this->convertBlockElements($child);
        // If no block elements, wrap content in paragraph.
        if ($itemBlocks === []) {
          $inlineNodes = $this->convertInlineElements($child, 0, 0);
          if ($inlineNodes !== []) {
            $itemBlocks = [new ParagraphNode(1, $inlineNodes)];
          }
        }
        if ($itemBlocks !== []) {
          $items[] = new ListItemNode(1, $itemBlocks);
        }
      }
    }

    return $items;
  }

  /**
   * Converts a pre>code block.
   *
   * @param \DOMElement $pre
   *   The pre element.
   *
   * @return \OpenSocial\RichTextJson\Node\CodeNode|null
   *   The code node, or NULL if empty.
   */
  private function convertCodeBlock(\DOMElement $pre): ?CodeNode {
    // Look for code element inside pre.
    $codeElement = NULL;
    foreach ($pre->childNodes as $child) {
      if ($child instanceof \DOMElement && strtolower($child->tagName) === 'code') {
        $codeElement = $child;
        break;
      }
    }

    $code = $codeElement !== NULL ? $codeElement->textContent : $pre->textContent;
    if (trim($code) === '') {
      return NULL;
    }

    $language = NULL;
    if ($codeElement !== NULL) {
      $class = $codeElement->getAttribute('class');
      if (preg_match('/language-(\S+)/', $class, $matches)) {
        $language = $matches[1];
      }
    }

    return new CodeNode(1, $code, $language);
  }

  /**
   * Converts inline elements to inline nodes.
   *
   * @param \DOMNode $parent
   *   The parent DOM node.
   * @param int $format
   *   The current format bitmask.
   * @param int $detail
   *   The current detail bitmask.
   *
   * @return array<int, \OpenSocial\RichTextJson\Node\NodeInterface>
   *   The converted inline nodes.
   */
  private function convertInlineElements(
    \DOMNode $parent,
    int $format,
    int $detail,
  ): array {
    $nodes = [];

    foreach ($parent->childNodes as $child) {
      if ($child instanceof \DOMText) {
        $text = $this->normalizeWhitespace($child->textContent);
        if ($text !== '') {
          $textNode = new TextNode(
            1,
            $text,
            $format !== 0 ? $format : NULL,
            $detail !== 0 ? $detail : NULL,
          );
          $nodes[] = $textNode;
        }
        continue;
      }

      if (!($child instanceof \DOMElement)) {
        continue;
      }

      $tagName = strtolower($child->tagName);

      if ($tagName === 'br') {
        $nodes[] = new LinebreakNode(1);
      }
      elseif ($tagName === 'strong' || $tagName === 'b') {
        $childNodes = $this->convertInlineElements($child, $format | 1, $detail);
        foreach ($childNodes as $node) {
          $nodes[] = $node;
        }
      }
      elseif ($tagName === 'em' || $tagName === 'i') {
        $childNodes = $this->convertInlineElements($child, $format | 2, $detail);
        foreach ($childNodes as $node) {
          $nodes[] = $node;
        }
      }
      elseif ($tagName === 'u') {
        $childNodes = $this->convertInlineElements($child, $format | 4, $detail);
        foreach ($childNodes as $node) {
          $nodes[] = $node;
        }
      }
      elseif ($tagName === 's' || $tagName === 'strike' || $tagName === 'del') {
        $childNodes = $this->convertInlineElements($child, $format | 8, $detail);
        foreach ($childNodes as $node) {
          $nodes[] = $node;
        }
      }
      elseif ($tagName === 'sup') {
        $childNodes = $this->convertInlineElements($child, $format, $detail | 1);
        foreach ($childNodes as $node) {
          $nodes[] = $node;
        }
      }
      elseif ($tagName === 'sub') {
        $childNodes = $this->convertInlineElements($child, $format, $detail | 2);
        foreach ($childNodes as $node) {
          $nodes[] = $node;
        }
      }
      elseif ($tagName === 'a') {
        $href = $child->getAttribute('href');
        $title = $child->getAttribute('title');
        $linkChildren = $this->convertInlineElements($child, $format, $detail);
        $nodes[] = new LinkNode(
          1,
          $href !== '' ? $href : '',
          $title !== '' ? $title : NULL,
          $linkChildren,
        );
      }
      elseif ($tagName === 'code') {
        // Check if parent is pre (code block) - if so, skip.
        if ($parent instanceof \DOMElement && strtolower($parent->tagName) === 'pre') {
          continue;
        }
        $code = $child->textContent;
        if ($code !== '') {
          $nodes[] = new InlineCodeNode(1, $code);
        }
      }
      else {
        // Unknown inline element: extract text with current formatting.
        $childNodes = $this->convertInlineElements($child, $format, $detail);
        foreach ($childNodes as $node) {
          $nodes[] = $node;
        }
      }
    }

    return $this->mergeAdjacentTextNodes($nodes);
  }

  /**
   * Normalizes whitespace in text.
   *
   * @param string $text
   *   The text to normalize.
   *
   * @return string
   *   The normalized text.
   */
  private function normalizeWhitespace(string $text): string {
    // Replace multiple whitespace with single space.
    $normalized = preg_replace('/\s+/', ' ', $text);
    return $normalized ?? $text;
  }

  /**
   * Merges adjacent text nodes with same formatting.
   *
   * @param array<int, \OpenSocial\RichTextJson\Node\NodeInterface> $nodes
   *   The nodes to merge.
   *
   * @return array<int, \OpenSocial\RichTextJson\Node\NodeInterface>
   *   The merged nodes.
   */
  private function mergeAdjacentTextNodes(array $nodes): array {
    if ($nodes === []) {
      return $nodes;
    }

    $merged = [];
    $lastNode = NULL;

    foreach ($nodes as $node) {
      if ($lastNode instanceof TextNode && $node instanceof TextNode) {
        // Check if formatting matches.
        if ($lastNode->getFormat() === $node->getFormat()
            && $lastNode->getDetail() === $node->getDetail()) {
          // Merge text.
          $lastNode = new TextNode(
            1,
            $lastNode->getText() . $node->getText(),
            $lastNode->getFormat(),
            $lastNode->getDetail(),
          );
          $merged[count($merged) - 1] = $lastNode;
          continue;
        }
      }

      $merged[] = $node;
      $lastNode = $node;
    }

    return $merged;
  }

}
