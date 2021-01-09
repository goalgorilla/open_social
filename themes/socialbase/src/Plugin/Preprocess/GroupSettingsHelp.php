<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Pre-processes variables for the "group_settings_help" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("group_settings_help",
 *   replace = "template_preprocess_group_settings_help"
 * )
 */
class GroupSettingsHelp extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    $build = [];
    $unique_id = Html::getUniqueId('group-settings-help');
    $icon = Bootstrap::glyphicon('info-sign');
    $title = '';

    // Only when it's SKY we want to update titles and icons in hero.
    if (theme_get_setting('style') === 'sky') {
      $title = $this->t('Access permissions');
      $icon = [
        '#type' => "inline_template",
        '#template' => '<svg class="badge__icon badge-info"><use xlink:href="#icon-cog"></use></svg>',
      ];
    }

    $build['toggle'] = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => Url::fromUserInput("#$unique_id"),
      '#icon' => $icon,
      '#attributes' => [
        'class' => ['icon-before'],
        'data-toggle' => 'popover',
        'data-html' => 'true',
        'data-placement' => 'bottom',
        'data-title' => $this->t('Access permissions'),
      ],
    ];

    $build['settings'] = [
      '#type' => 'container',
      '#theme_wrappers' => ['container__file_upload_help'],
      '#attributes' => [
        'id' => $unique_id,
        'class' => ['hidden', 'help-block'],
        'aria-hidden' => 'true',
      ],
    ];

    if (!empty($variables['group_visibility'])) {
      $group_visibility = '';
      // Most likely an array, so we convert all of the values to one string.
      if (is_array($variables['group_visibility'])) {
        foreach ($variables['group_visibility'] as $key => $value) {
          $group_visibility .= $value;
        }
      }

      $build['settings']['group_visibility'] = [
        '#title' => $this->t('Group visibility'),
        '#markup' => $group_visibility,
        '#allowed_tags' => [
          'strong',
          'span',
          'svg',
          'p',
          'div',
          'em',
          'img',
          'a',
          'span',
          'use',
        ],
      ];
    }
    if (!empty($variables['join_method'])) {
      $join_method = '';
      // Most likely an array, so we convert all of the values to one string.
      if (is_array($variables['join_method'])) {
        foreach ($variables['join_method'] as $key => $value) {
          $join_method .= $value;
        }
      }

      $build['settings']['join_method'] = [
        '#title' => $this->t('Join method'),
        '#markup' => $join_method,
        '#allowed_tags' => [
          'strong',
          'span',
          'svg',
          'p',
          'div',
          'em',
          'img',
          'a',
          'span',
          'use',
        ],
      ];
    }
    if (!empty($variables['allowed_visibility'])) {
      $allowed_visibility = '';
      // Most likely an array, so we convert all of the values to one string.
      if (is_array($variables['allowed_visibility'])) {
        foreach ($variables['allowed_visibility'] as $key => $value) {
          $allowed_visibility .= $value;
        }
      }

      $build['settings']['allowed_visibility'] = [
        '#title' => $this->t('Group content visibility'),
        '#markup' => $allowed_visibility,
        '#allowed_tags' => [
          'strong',
          'span',
          'svg',
          'p',
          'div',
          'em',
          'img',
          'a',
          'span',
          'use',
        ],
      ];
    }
    $variables['popover'] = $build;
    $variables['popover_id'] = $unique_id;
    $variables['popover_toggle'] = $build['toggle'];
    $variables['popover_info'] = $build['settings'];
  }

}
