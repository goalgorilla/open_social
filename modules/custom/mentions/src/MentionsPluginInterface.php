<?php

namespace Drupal\mentions;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface MentionsPluginInterface extends ContainerFactoryPluginInterface
{
  public function targetCallback($value, $settings);
  public function outputCallback($mention, $settings);
  public function patternCallback($settings, $regex);
  public function settingsCallback($form, $form_state, $type);
  public function settingsSubmitCallback($form, $form_state, $type);
  public function mentionPresaveCallback($entity);
}

