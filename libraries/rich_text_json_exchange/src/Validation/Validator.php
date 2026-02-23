<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Validation;

use OpenSocial\RichTextJson\Document\RichTextDocument;
use OpenSocial\RichTextJson\Node\CodeNode;
use OpenSocial\RichTextJson\Node\HeadingNode;
use OpenSocial\RichTextJson\Node\InlineCodeNode;
use OpenSocial\RichTextJson\Node\LinkNode;
use OpenSocial\RichTextJson\Node\ListItemNode;
use OpenSocial\RichTextJson\Node\ListNode;
use OpenSocial\RichTextJson\Node\NodeFactory;
use OpenSocial\RichTextJson\Node\NodeInterface;
use OpenSocial\RichTextJson\Node\ParagraphNode;
use OpenSocial\RichTextJson\Node\QuoteNode;
use OpenSocial\RichTextJson\Node\RootNode;
use OpenSocial\RichTextJson\Node\TextNode;

/**
 * Validates rich text documents against structural rules.
 */
final class Validator {

  /**
   * The node factory for type checking.
   *
   * @var \OpenSocial\RichTextJson\Node\NodeFactory
   */
  private NodeFactory $nodeFactory;

  /**
   * Node types that must not have children.
   *
   * @var array<string, true>
   */
  private const CHILDLESS_TYPES = [
    'text' => TRUE,
    'linebreak' => TRUE,
    'inline-code' => TRUE,
  ];

  /**
   * Creates a new Validator.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeFactory|null $nodeFactory
   *   Optional node factory for type checking.
   */
  public function __construct(?NodeFactory $nodeFactory = NULL) {
    $this->nodeFactory = $nodeFactory ?? new NodeFactory();
  }

  /**
   * Validates a document.
   *
   * @param \OpenSocial\RichTextJson\Document\RichTextDocument $document
   *   The document to validate.
   *
   * @return \OpenSocial\RichTextJson\Validation\ValidationResult
   *   The validation result.
   */
  public function validateDocument(RichTextDocument $document): ValidationResult {
    $errors = [];
    $this->validateNode($document->getRoot(), '/root', 'block', $errors);
    return new ValidationResult($errors);
  }

  /**
   * Validates a node and its children.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node to validate.
   * @param string $path
   *   The JSON pointer path.
   * @param string $expectedContext
   *   The expected context: 'block' or 'inline'.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateNode(
    NodeInterface $node,
    string $path,
    string $expectedContext,
    array &$errors,
  ): void {
    $type = $node->getType();

    // Validate required fields based on node type.
    $this->validateRequiredFields($node, $path, $errors);

    // Check structural rules based on context.
    if ($node instanceof RootNode) {
      $this->validateRootChildren($node, $path, $errors);
    }
    elseif ($node instanceof ParagraphNode) {
      $this->validateInlineContainer($node->getChildren(), $path, $errors);
    }
    elseif ($node instanceof HeadingNode) {
      $this->validateInlineContainer($node->getChildren(), $path, $errors);
    }
    elseif ($node instanceof LinkNode) {
      $this->validateInlineContainer($node->getChildren(), $path, $errors);
    }
    elseif ($node instanceof ListNode) {
      $this->validateListChildren($node, $path, $errors);
    }
    elseif ($node instanceof ListItemNode) {
      $this->validateAnyChildren($node->getChildren(), $path, $errors);
    }
    elseif ($node instanceof QuoteNode) {
      $this->validateBlockContainer($node->getChildren(), $path, $errors);
    }
    elseif (isset(self::CHILDLESS_TYPES[$type])) {
      $this->validateChildlessNode($node, $path, $errors);
    }
  }

  /**
   * Validates required fields for known node types.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node to validate.
   * @param string $path
   *   The JSON pointer path.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateRequiredFields(
    NodeInterface $node,
    string $path,
    array &$errors,
  ): void {
    $type = $node->getType();

    // Check type field.
    if ($type === '') {
      $errors[] = new ValidationError('Missing required field "type"', $path);
      return;
    }

    // Check version field - required on all nodes per specification.
    if (!$node->hasVersion()) {
      $errors[] = new ValidationError('Missing required field "version"', $path);
      return;
    }

    // Check type-specific required fields.
    if ($node instanceof TextNode && !$node->hasText()) {
      $errors[] = new ValidationError('Text node missing required "text" field', $path);
    }

    if ($node instanceof LinkNode && !$node->hasUrl()) {
      $errors[] = new ValidationError('Link node missing required "url" field', $path);
    }

    if ($node instanceof HeadingNode && !$node->hasLevel()) {
      $errors[] = new ValidationError('Heading node missing required "level" field', $path);
    }

    if ($node instanceof CodeNode && !$node->hasCode()) {
      $errors[] = new ValidationError('Code node missing required "code" field', $path);
    }

    if ($node instanceof InlineCodeNode && !$node->hasCode()) {
      $errors[] = new ValidationError('Inline-code node missing required "code" field', $path);
    }
  }

  /**
   * Validates children of the root node.
   *
   * @param \OpenSocial\RichTextJson\Node\RootNode $root
   *   The root node.
   * @param string $path
   *   The JSON pointer path.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateRootChildren(
    RootNode $root,
    string $path,
    array &$errors,
  ): void {
    foreach ($root->getChildren() as $index => $child) {
      $childPath = sprintf('%s/children/%d', $path, $index);
      $childType = $child->getType();

      // Check if child is a known inline type (not allowed under root).
      if ($this->nodeFactory->isInlineType($childType)) {
        $errors[] = new ValidationError(
          sprintf('Inline node "%s" not allowed under root; expected block node', $childType),
          $childPath,
        );
      }

      // Recursively validate the child.
      $this->validateNode($child, $childPath, 'block', $errors);
    }
  }

  /**
   * Validates children of an inline container (paragraph, link).
   *
   * @param array<int, NodeInterface> $children
   *   The children to validate.
   * @param string $path
   *   The JSON pointer path of the container.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateInlineContainer(
    array $children,
    string $path,
    array &$errors,
  ): void {
    foreach ($children as $index => $child) {
      $childPath = sprintf('%s/children/%d', $path, $index);
      $childType = $child->getType();

      // Check if child is a known block type (not allowed in inline context).
      if ($this->nodeFactory->isBlockType($childType)) {
        $errors[] = new ValidationError(
          sprintf('Block node "%s" not allowed in inline context; expected inline node', $childType),
          $childPath,
        );
      }

      // Recursively validate the child.
      $this->validateNode($child, $childPath, 'inline', $errors);
    }
  }

  /**
   * Validates a node that must not have children.
   *
   * @param \OpenSocial\RichTextJson\Node\NodeInterface $node
   *   The node to validate.
   * @param string $path
   *   The JSON pointer path.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateChildlessNode(
    NodeInterface $node,
    string $path,
    array &$errors,
  ): void {
    $nodeArray = $node->toArray();

    // Ignore until https://github.com/phpstan/phpstan/issues/8438.
    /** @phpstan-ignore-next-line isset.offset booleanAnd.alwaysFalse */
    if (isset($nodeArray['children']) && is_array($nodeArray['children']) && $nodeArray['children'] !== []) {
      $errors[] = new ValidationError(
        sprintf('Node type "%s" must not have children', $node->getType()),
        $path,
      );
    }
  }

  /**
   * Validates children of a list node (must be list-item).
   *
   * @param \OpenSocial\RichTextJson\Node\ListNode $list
   *   The list node.
   * @param string $path
   *   The JSON pointer path.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateListChildren(
    ListNode $list,
    string $path,
    array &$errors,
  ): void {
    foreach ($list->getChildren() as $index => $child) {
      $childPath = sprintf('%s/children/%d', $path, $index);
      $childType = $child->getType();

      // List can only contain list-item nodes.
      if ($childType !== 'list-item') {
        $errors[] = new ValidationError(
          sprintf('List can only contain list-item nodes, found "%s"', $childType),
          $childPath,
        );
      }

      // Recursively validate the child.
      $this->validateNode($child, $childPath, 'block', $errors);
    }
  }

  /**
   * Validates children of a block container (quote, list-item).
   *
   * @param array<int, NodeInterface> $children
   *   The children to validate.
   * @param string $path
   *   The JSON pointer path of the container.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateBlockContainer(
    array $children,
    string $path,
    array &$errors,
  ): void {
    foreach ($children as $index => $child) {
      $childPath = sprintf('%s/children/%d', $path, $index);
      $childType = $child->getType();

      // Check if child is a known inline type (not allowed in block context).
      if ($this->nodeFactory->isInlineType($childType)) {
        $errors[] = new ValidationError(
          sprintf('Inline node "%s" not allowed in block context; expected block node', $childType),
          $childPath,
        );
      }

      // Recursively validate the child.
      $this->validateNode($child, $childPath, 'block', $errors);
    }
  }

  /**
   * Validates children without type restrictions (for flow content).
   *
   * @param array<int, NodeInterface> $children
   *   The children to validate.
   * @param string $path
   *   The JSON pointer path of the container.
   * @param array<int, ValidationError> $errors
   *   The errors array to append to.
   */
  private function validateAnyChildren(
    array $children,
    string $path,
    array &$errors,
  ): void {
    foreach ($children as $index => $child) {
      $childPath = sprintf('%s/children/%d', $path, $index);
      $childType = $child->getType();

      // Determine context based on child-type for proper nested validation.
      $expectedContext = $this->nodeFactory->isInlineType($childType) ? 'inline' : 'block';

      // Recursively validate the child without type restrictions.
      $this->validateNode($child, $childPath, $expectedContext, $errors);
    }
  }

}
