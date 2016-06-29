<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\General\Buttons\ButtonSize.
 */

namespace Drupal\bootstrap\Plugin\Setting\General\Buttons;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "button_size" theme setting.
 *
 * @BootstrapSetting(
 *   id = "button_size",
 *   type = "select",
 *   title = @Translation("Default button size"),
 *   defaultValue = "",
 *   empty_option = @Translation("Normal"),
 *   groups = {
 *     "general" = @Translation("General"),
 *     "button" = @Translation("Buttons"),
 *   },
 *   options = {
 *     "btn-xs" = @Translation("Extra Small"),
 *     "btn-sm" = @Translation("Small"),
 *     "btn-lg" = @Translation("Large"),
 *   },
 * )
 */
class ButtonSize extends SettingBase {}
