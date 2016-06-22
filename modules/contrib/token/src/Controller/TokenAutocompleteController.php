<?php

/**
 * @file
 * Contains \Drupal\token\Controller\TokenAutocompleteController.
 */

namespace Drupal\token\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\token\TreeBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for tokens.
 */
class TokenAutocompleteController extends ControllerBase {

  /**
   * @var \Drupal\token\TreeBuilderInterface
   */
  protected $treeBuilder;

  public function __construct(TreeBuilderInterface $tree_builder) {
    $this->treeBuilder = $tree_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token.tree_builder')
    );
  }

  /**
   * Retrieves suggestions for block category autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $token_type
   *   The token type.
   * @param string $filter
   *   The autocomplete filter.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocomplete($token_type, $filter, Request $request) {
    $filter = substr($filter, strrpos($filter, '['));

    $matches = array();

    if (!Unicode::strlen($filter)) {
      $matches["[{$token_type}:"] = 0;
    }
    else {
      $depth = max(1, substr_count($filter, ':'));
      $tree = $this->treeBuilder->buildTree($token_type, ['flat' => TRUE, 'depth' => $depth]);
      foreach (array_keys($tree) as $token) {
        if (strpos($token, $filter) === 0) {
          $matches[$token] = levenshtein($token, $filter);
          if (isset($tree[$token]['children'])) {
            $token = rtrim($token, ':]') . ':';
            $matches[$token] = levenshtein($token, $filter);
          }
        }
      }
    }

    asort($matches);

    $keys = array_keys($matches);
    $matches = array_combine($keys, $keys);

    return new JsonResponse($matches);
  }

}
