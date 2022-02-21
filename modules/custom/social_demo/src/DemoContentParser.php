<?php

namespace Drupal\social_demo;

use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\Yaml\Yaml;

/**
 * Plugin manager for DemoContentParser plugin.
 *
 * @package Drupal\social_demo
 */
class DemoContentParser extends Yaml implements DemoContentParserInterface {

  /**
   * Module list services.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Constructor for Demo content parser.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   Module list services.
   */
  public function __construct(ModuleExtensionList $module_extension_list) {
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($file, $module, $profile) {
    if ($profile != '' && strpos($profile, DIRECTORY_SEPARATOR) === FALSE) {
      $profile .= DIRECTORY_SEPARATOR;
    }
    return $this->moduleExtensionList->getPath($module) . DIRECTORY_SEPARATOR . $profile . $file;
  }

  /**
   * Parses YAML file into a PHP value.
   *
   * @param string $file
   *   The filename.
   * @param string $module
   *   The module where the Yaml file is placed.
   * @param string $profile
   *   The profile used.
   *
   * @return mixed
   *   The YAML converted to a PHP value.
   */
  public function parseFileFromModule($file, $module, $profile) {
    return $this->parse(file_get_contents($this->getPath($file, $module, $profile)));
  }

}
