<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Container\ContainerInterface;

class ThemeBridge {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
    );
  }

  /**
   * Set the theme style.
   *
   * @param string $style
   *   The style to use.
   *
   * @return string[]
   *   The result.
   */
  #[Command(name: 'theme-set-style')]
  public function setThemeStyle(string $style) : array {
    $this->configFactory
      ->getEditable('socialblue.settings')
      ->set('style', $style)
      ->save();

    return ['status' => 'ok'];
  }

}
