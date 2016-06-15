<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\General\Tables\TableResponsive.
 */

namespace Drupal\bootstrap\Plugin\Setting\General\Tables;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "table_responsive" theme setting.
 *
 * @BootstrapSetting(
 *   id = "table_responsive",
 *   type = "checkbox",
 *   title = @Translation("Responsive tables"),
 *   description = @Translation("Makes tables responsive by wrapping them in <code>.table-responsive</code> to make them scroll horizontally up to small devices (under 768px). When viewing on anything larger than 768px wide, you will not see any difference in these tables."),
 *   defaultValue = 1,
 *   groups = {
 *     "general" = @Translation("General"),
 *     "tables" = @Translation("Tables"),
 *   },
 * )
 */
class TableResponsive extends SettingBase {}
