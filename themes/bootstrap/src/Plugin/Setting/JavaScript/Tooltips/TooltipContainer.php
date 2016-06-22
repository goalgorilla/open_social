<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips\TooltipContainer.
 */

namespace Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "tooltip_container" theme setting.
 *
 * @BootstrapSetting(
 *   id = "tooltip_container",
 *   type = "textfield",
 *   title = @Translation("container"),
 *   description = @Translation("Appends the tooltip to a specific element. Example: <code>body</code>."),
 *   defaultValue = "body",
 *   groups = {
 *     "javascript" = @Translation("JavaScript"),
 *     "tooltips" = @Translation("Tooltips"),
 *     "options" = @Translation("Options"),
 *   },
 * )
 */
class TooltipContainer extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return !!$this->theme->getSetting('tooltip_enabled');
  }

}
