<?php

/**
 * @file
 * User page callbacks for the token module.
 */
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\DiffArray;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;

/**
 * Theme a link to a token tree shown as a dialog.
 */
function template_preprocess_token_tree_link(&$variables) {
  if (empty($variables['text'])) {
    $variables['text'] = t('Browse available tokens.');
  }

  $variables['#attached']['library'][] = 'core/drupal.dialog.ajax';
  $variables['#attached']['library'][] = 'token/token';
  $variables['options']['attributes']['class'][] = 'token-dialog';
  $variables['options']['attributes']['class'][] = 'use-ajax';

  $tree_variables = [
    'token_types' => [],
    'global_types' => TRUE,
    'click_insert' => TRUE,
    'show_restricted' => FALSE,
    'recursion_limit' => 3,
  ];
  $query_options = array_intersect_key($variables, $tree_variables);
  $query_options = DiffArray::diffAssocRecursive($query_options, $tree_variables);
  if (!isset($variables['options']['query']['options'])) {
    $variables['options']['query']['options'] = [];
  }
  $variables['options']['query']['options'] += $query_options;

  // Because PHP converts query strings with arrays into a different syntax on
  // the next request, the options have to be encoded with JSON in the query
  // string so that we can reliably decode it for token comparison.
  $variables['options']['query']['options'] = Json::encode($variables['options']['query']['options']);

  // Set the token tree to open in a separate window.
  $variables['options']['attributes'] += [
    'data-dialog-type' => 'dialog',
    'data-dialog-options' => json_encode([
      'dialogClass' => 'token-tree-dialog',
      'width' => 600,
      'height' => 400,
      'position' => ['my' => 'right bottom', 'at' => 'right-10 bottom-10'],
      'draggable' => TRUE,
      'autoResize' => FALSE,
    ]),
   ];

  $variables['link'] = Link::createFromRoute($variables['text'], 'token.tree', [], $variables['options'])->toRenderable();
  $variables['url'] = new Url('token.tree', [], $variables['options']);
  $variables['attributes'] = $variables['options']['attributes'];
}
