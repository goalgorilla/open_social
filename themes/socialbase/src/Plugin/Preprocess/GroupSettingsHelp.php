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
    $icon = Bootstrap::glyphicon('cog');
    $build['toggle'] = [
      '#type' => 'link',
      '#title' => $this->t('Access permissions'),
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
      '#theme_wrappers' => ['container__group_settings_help'],
    ];
    if (!empty($variables['group_visibility'])) {
      $build['settings']['group_visibility'] = [
        '#theme' => 'item_list__group_settings_help',
        '#items' => $variables['group_visibility'],
        '#title' => $this->t('Group visibility'),
      ];
    }
    if (!empty($variables['join_method'])) {
      $build['settings']['join_method'] = [
        '#theme' => 'item_list__group_settings_help',
        '#items' => $variables['join_method'],
        '#title' => $this->t('Join method'),
      ];
    }
    if (!empty($variables['allowed_visibility'])) {
      $build['settings']['allowed_visibility'] = [
        '#theme' => 'item_list__group_settings_help',
        '#items' => $variables['allowed_visibility'],
        '#title' => $this->t('Group content visibility'),
      ];
    }
    $variables['popover'] = $build;
    $variables['popover_id'] = $unique_id;
    $variables['popover_toggle'] = $build['toggle'];
    $variables['popover_info'] = $build['settings'];
  }

}
