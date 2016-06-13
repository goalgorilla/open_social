<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\Components\Breadcrumbs\Breadcrumb.
 */

namespace Drupal\bootstrap\Plugin\Setting\Components\Breadcrumbs;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "breadcrumb" theme setting.
 *
 * @BootstrapSetting(
 *   id = "breadcrumb",
 *   type = "select",
 *   title = @Translation("Breadcrumb visibility"),
 *   defaultValue = "1",
 *   groups = {
 *     "components" = @Translation("Components"),
 *     "breadcrumbs" = @Translation("Breadcrumbs"),
 *   },
 *   options = {
 *     0 = @Translation("Hidden"),
 *     1 = @Translation("Visible"),
 *     2 = @Translation("Only in admin areas"),
 *   },
 * )
 */
class Breadcrumb extends SettingBase {}
