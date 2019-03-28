<?php

namespace Drupal\social_group;

use Drupal\Core\Config\ConfigFactory;

/**
 * Class SocialGroupHero.
 *
 * @package Drupal\social_group
 */
class SocialGroupHero {
  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  protected $isSmall = FALSE;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The injected configfactory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Function that determines the group hero imagestyle.
   *
   * @return string
   *   Of the image / crop style.
   */
  public function getGroupHeroCropType() :string {
    $settings = $this->configFactory->get('social_group.settings');
    // Return the selected style, or the default one.
    return $settings->get('default_hero') ?? 'hero';
  }

  /**
   * Function that determines the group hero croptype.
   *
   * @return string
   *   The crop style.
   */
  public function getGroupHeroImageStyle() :string {
    return $this->cropToStyle($this->getGroupHeroCropType());
  }

  /**
   * Small or not.
   *
   * @return bool
   *   Is this considered small.
   */
  public function isSmall() :bool {
    // Invoke cropToStyle, to get info about the size.
    $this->cropToStyle($this->configFactory->get('social_group.settings')->get('default_hero'));
    // Return info about the size.
    return $this->isSmall;
  }

  /**
   * Function that converts crop type to image style.
   *
   * @param string $cropType
   *   The croptype.
   *
   * @return string
   *   The associated image style.
   */
  protected function cropToStyle($cropType) :string {
    $values = [
      'hero' => 'social_xx_large',
      'hero_small' => 'social_hero_small',
    ];

    switch ($cropType) {
      case 'hero_small':
        $this->isSmall = TRUE;
      default:
    }

    return $values[$cropType] ?? 'social_xx_large';
  }

}
