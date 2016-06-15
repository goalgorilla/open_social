<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\Components\Navbar\NavbarPosition.
 */

namespace Drupal\bootstrap\Plugin\Setting\Components\Navbar;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "navbar_position" theme setting.
 *
 * @BootstrapSetting(
 *   id = "navbar_position",
 *   type = "select",
 *   title = @Translation("Navbar Position"),
 *   defaultValue = "",
 *   groups = {
 *     "components" = @Translation("Components"),
 *     "navbar" = @Translation("Navbar"),
 *   },
 *   empty_option = @Translation("Normal"),
 *   options = {
 *     "static-top" = @Translation("Static Top"),
 *     "fixed-top" = @Translation("Fixed Top"),
 *     "fixed-bottom" = @Translation("Fixed Bottom"),
 *   },
 * )
 */
class NavbarPosition extends SettingBase {}
