<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips\TooltipSelector.
 */

namespace Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "tooltip_selector" theme setting.
 *
 * @BootstrapSetting(
 *   id = "tooltip_selector",
 *   type = "textfield",
 *   title = @Translation("selector"),
 *   description = @Translation("If a selector is provided, tooltip objects will be delegated to the specified targets."),
 *   defaultValue = "",
 *   groups = {
 *     "javascript" = @Translation("JavaScript"),
 *     "tooltips" = @Translation("Tooltips"),
 *     "options" = @Translation("Options"),
 *   },
 * )
 */
class TooltipSelector extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return !!$this->theme->getSetting('tooltip_enabled');
  }

}
