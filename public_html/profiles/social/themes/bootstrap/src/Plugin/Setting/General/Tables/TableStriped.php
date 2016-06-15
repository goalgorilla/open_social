<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\General\Tables\TableStriped.
 */

namespace Drupal\bootstrap\Plugin\Setting\General\Tables;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "table_striped" theme setting.
 *
 * @BootstrapSetting(
 *   id = "table_striped",
 *   type = "checkbox",
 *   title = @Translation("Striped rows"),
 *   description = @Translation("Add zebra-striping to any table row within the <code>&lt;tbody&gt;</code>."),
 *   defaultValue = 1,
 *   groups = {
 *     "general" = @Translation("General"),
 *     "tables" = @Translation("Tables"),
 *   },
 * )
 */
class TableStriped extends SettingBase {}
