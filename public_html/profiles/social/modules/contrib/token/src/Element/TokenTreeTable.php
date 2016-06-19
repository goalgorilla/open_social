<?php

/**
 * @file
 * Contains \Drupal\token\Element\TokenTreeTable.
 */

namespace Drupal\token\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\Table;

/**
 * Provides a render element for a token tree table.
 *
 * @RenderElement("token_tree_table")
 */
class TokenTreeTable extends Table {

  protected static $cssFilter = [' ' => '-', '_' => '-', '/' => '-', '[' => '-', ']' => '', ':' => '--', '?' => '', '<' => '-', '>' => '-'];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#header' => [],
      '#rows' => [],
      '#token_tree' => [],
      '#columns' => ['name', 'token', 'description'],
      '#empty' => '',
      '#show_restricted' => FALSE,
      '#skip_empty_values' => FALSE,
      '#click_insert' => TRUE,
      '#sticky' => FALSE,
      '#responsive' => TRUE,
      '#input' => FALSE,
      '#pre_render' => [
        [$class, 'preRenderTokenTree'],
        [$class, 'preRenderTable'],
      ],
      '#theme' => 'table__token_tree',
      '#attached' => [
        'library' => [
          'token/token',
        ],
      ],
    ];
  }

  /**
   * Pre-render the token tree to transform rows in the token tree.
   *
   * @param array $element
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderTokenTree($element) {
    $multiple_token_types = count($element['#token_tree']) > 1;
    foreach ($element['#token_tree'] as $token_type => $type_info) {
      if ($multiple_token_types) {
        $row = static::formatRow($token_type, $type_info, $element['#columns'], TRUE);
        $element['#rows'][] = $row;
      }

      foreach ($type_info['tokens'] as $token => $token_info) {
        if (!empty($token_info['restricted']) && empty($element['#show_restricted'])) {
          continue;
        }
        if ($element['#skip_empty_values'] && empty($token_info['value']) && !empty($token_info['parent']) && !isset($tree[$token_info['parent']]['value'])) {
          continue;
        }
        if ($multiple_token_types && !isset($token_info['parent'])) {
          $token_info['parent'] = $token_type;
        }
        $row = static::formatRow($token, $token_info, $element['#columns']);
        $element['#rows'][] = $row;
      }
    }

    if (!empty($element['#rows'])) {
      $element['#attached']['library'][] = 'token/jquery.treeTable';
    }

    // Fill headers if one is not specified.
    if (empty($element['#header'])) {
      $column_map = [
        'name' => t('Name'),
        'token' => t('Token'),
        'value' => t('Value'),
        'description' => t('Description'),
      ];
      foreach ($element['#columns'] as $col) {
        $element['#header'][] = $column_map[$col];
      }
    }

    $element['#attributes']['class'][] = 'token-tree';

    if ($element['#click_insert']) {
      $element['#caption'] = t('Click a token to insert it into the field you\'ve last clicked.');
      $element['#attributes']['class'][] = 'token-click-insert';
    }

    return $element;
  }

  protected static function cleanCssIdentifier($id) {
    return 'token-' . Html::cleanCssIdentifier(trim($id, '[]'), static::$cssFilter);
  }

  protected static function formatRow($token, $token_info, $columns, $is_group = FALSE) {
    $row = [
      'id' => static::cleanCssIdentifier($token),
      'class' => [],
      'data' => [],
    ];

    foreach ($columns as $col) {
      switch ($col) {
        case 'name':
          $row['data'][$col] = $token_info['name'];
          break;

        case 'token':
          $row['data'][$col]['data'] = $token;
          $row['data'][$col]['class'][] = 'token-key';
          break;

        case 'description':
          $row['data'][$col] = isset($token_info['description']) ? $token_info['description'] : '';
          break;

        case 'value':
          $row['data'][$col] = !$is_group && isset($token_info['value']) ? $token_info['value'] : '';
          break;
      }
    }

    if ($is_group) {
      // This is a token type/group.
      $row['class'][] = 'token-group';
    }
    elseif (!empty($token_info['parent'])) {
      $row['class'][] = 'child-of-' . static::cleanCssIdentifier($token_info['parent']);
      unset($row['parent']);
    }

    return $row;
  }
}
