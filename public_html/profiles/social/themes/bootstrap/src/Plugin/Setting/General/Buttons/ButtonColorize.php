<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\General\Buttons\ButtonColorize.
 */

namespace Drupal\bootstrap\Plugin\Setting\General\Buttons;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "button_colorize" theme setting.
 *
 * @BootstrapSetting(
 *   id = "button_colorize",
 *   type = "checkbox",
 *   title = @Translation("Colorize Buttons"),
 *   defaultValue = 1,
 *   description = @Translation("Adds classes to buttons based on their text value."),
 *   groups = {
 *     "general" = @Translation("General"),
 *     "button" = @Translation("Buttons"),
 *   },
 *   see = {
 *     "http://getbootstrap.com/css/#buttons" = @Translation("Buttons"),
 *     "http://drupal-bootstrap.org/apis/hook_bootstrap_colorize_text_alter" = @Translation("hook_bootstrap_colorize_text_alter()"),
 *   },
 * )
 */
class ButtonColorize extends SettingBase {}
