<?php

/**
 * @file
 * Contains post-update hooks for the Social Metatag module.
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Correctly import the default topic metatag configuration.
 */
function social_metatag_post_update_0001_fix_node_topic_defaults() {
  $config_yaml = <<<YAML
langcode: en
status: true
dependencies: {  }
id: node__topic
label: 'Content: Topic'
tags:
  title: '[current-page:title]'
  og_description: '[node:body]'
  og_image: '[node:field_topic_image:entity:url]'
  og_site_name: '[site:name]'
  og_title: '[current-page:title]'
YAML;

  $default_config = Yaml::parse($config_yaml);
  $topic_config = \Drupal::configFactory()->getEditable('metatag.metatag_defaults.node__topic');

  $topic_config->setData($default_config)->save(TRUE);
}
