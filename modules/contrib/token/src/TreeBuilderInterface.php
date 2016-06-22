<?php

/**
 * @file
 * Contains \Drupal\token\TreeBuilderInterface.
 */

namespace Drupal\token;

interface TreeBuilderInterface {

  /**
   * The maximum depth for token tree recursion.
   */
  const MAX_DEPTH = 9;

  /**
   * Build a tree array of tokens used for themeing or information.
   *
   * @param string $token_type
   *   The token type.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'flat' (defaults to FALSE): Set to true to generate a flat list of
   *     token information. Otherwise, child tokens will be inside the
   *     'children' parameter of a token.
   *   - 'restricted' (defaults to FALSE): Set to true to how restricted tokens.
   *   - 'depth' (defaults to 4): Maximum number of token levels to recurse.
   *
   * @return array
   *   The token information constructed in a tree or flat list form depending
   *   on $options['flat'].
   */
  public function buildTree($token_type, array $options = []);

  /**
   * Flatten a token tree.
   *
   * @param array $tree
   *   The tree array as returned by TreeBuilderInterface::buildTree().
   *
   * @return array
   *   The flattened version of the tree.
   */
  public function flattenTree(array $tree);

  /**
   * Build a render array with token tree built as per specified options.
   *
   * @param array $token_types
   *   An array containing token types that should be shown in the tree.
   * @param array $options
   *   (optional) An associative array to control which tokens are shown and
   *   how. The properties available are:
   *   - 'global_types' (defaults to TRUE): Show all global token types along
   *     with the specified types.
   *   - 'click_insert' (defaults to TRUE): Include classes and caption to show
   *     allow inserting tokens in fields by clicking on them.
   *   - 'show_restricted' (defaults to FALSE): Show restricted tokens in the
   *     tree.
   *   - 'recursion_limit' (defaults to 3): Only show tokens up to the specified
   *     depth.
   *
   * @return array
   *   Render array for the token tree.
   */
  public function buildRenderable(array $token_types, array $options = []);

  /**
   * Build a render array with token tree containing all possible tokens.
   *
   * @param array $options
   *   (optional) An associative array to control which tokens are shown and
   *   how. The properties available are: See
   *   \Drupal\token\TreeBuilderInterface::buildRenderable() for details.
   *
   * @return array
   *   Render array for the token tree.
   */
  public function buildAllRenderable(array $options = []);
}
