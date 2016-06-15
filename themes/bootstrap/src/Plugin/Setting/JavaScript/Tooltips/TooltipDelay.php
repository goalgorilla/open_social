<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips\TooltipDelay.
 */

namespace Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "tooltip_delay" theme setting.
 *
 * @BootstrapSetting(
 *   id = "tooltip_delay",
 *   type = "textfield",
 *   title = @Translation("delay"),
 *   description = @Translation("The amount of time to delay showing and hiding the tooltip (in milliseconds). Does not apply to manual trigger type."),
 *   defaultValue = "0",
 *   groups = {
 *     "javascript" = @Translation("JavaScript"),
 *     "tooltips" = @Translation("Tooltips"),
 *     "options" = @Translation("Options"),
 *   },
 * )
 */
class TooltipDelay extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return !!$this->theme->getSetting('tooltip_enabled');
  }

}
