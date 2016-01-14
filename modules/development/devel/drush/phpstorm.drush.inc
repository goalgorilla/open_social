<?php

/**
 * @file
 * Generate PhpStorm metadata file.
 */

/**
 * Implements of hook_drush_command().
 */
function phpstorm_drush_command() {
  $items = array();

  $items['phpstorm-metadata'] = array(
    'description' => 'Save the PhpStorm Metadata file to Drupal root.',
    'core' => array('8+'),
    'aliases' => array('phpm'),
    'category' => 'devel',
  );

  return $items;
}

/**
 * Implements hook_drush_help_alter().
 */
function phpstorm_drush_help_alter(&$command) {
  if ($command['command'] == 'cache-rebuild') {
    $command['options']['storm'] = 'Write a new PHPstorm metadata file to Drupal root.';
  }
}

/*
 * Implements drush_hook_post_COMMAND().
 */
function drush_phpstorm_post_cache_rebuild() {
  if (drush_get_option('storm')) {
    drush_invoke_process('@self', 'phpstorm-metadata');
  }
}

/**
 * Generate PhpStorm Metadata file.
 *
 * @see http://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 */
function drush_phpstorm_metadata() {
  $container = \Drupal::getContainer();

  $reflectedClass = new ReflectionClass($container);

  $map = array();

  // Map for all services of the container.
  // @see \Symfony\Component\DependencyInjection\Container::getServiceIds().
  foreach ($reflectedClass->getMethods() as $method) {
    if (preg_match('/^get(.+)Service$/', $method->name, $match)) {
      $id = strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($match[1], '_', '.')));
      $service = \Drupal::service($id);
      if (is_object($service)) {
        $map["\\Drupal::service('')"][$id] = '\\' . get_class($service);
      }
    }
  }

  // Entity Manager - getStorage
  foreach (\Drupal::entityManager()->getDefinitions() as $type => $definition) {
    $class = Drupal::entityManager()->getStorage($type);
    $map["\\Drupal::entityManager()->getStorage('')"][$type] = '\\' . get_class($class);
  }

  $content = _drush_phpstorm_metadata_phpstorm_metadata_template($map);
  file_put_contents(DRUPAL_ROOT . '/.phpstorm.meta.php', $content);
}

function _drush_phpstorm_metadata_phpstorm_metadata_template($data) {
  $file = '<?php

namespace PHPSTORM_META {

  /** @noinspection PhpUnusedLocalVariableInspection */
  /** @noinspection PhpIllegalArrayKeyTypeInspection */
  $STATIC_METHOD_TYPES = [
';

  foreach ($data as $method => $map) {
    $file .= "\n";
    $file .= "    {$method} => [\n";

    foreach ($map as $argument => $class) {
      $file .= "      '{$argument}' instanceof {$class},\n";
    }

    $file .= "    ],";
    $file .= "\n";
  }

  $file .= '
    ];
  }
  ';

  return $file;
}
