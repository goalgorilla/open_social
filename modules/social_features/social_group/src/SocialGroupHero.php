<?php

namespace Drupal\social_group;

use Drupal\Core\Config\ConfigFactory;
use Drupal\group\Entity\GroupInterface;

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
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return string
   *   Of the image / crop style.
   */
  public function getGroupHeroCropType(GroupInterface $group) :string {
    $settings = $this->configFactory->get('social_group.settings');
    // Check if this is a group and check if hero selection is allowed.
    if (!$group instanceof GroupInterface || $settings->get('allow_hero_selection') !== FALSE) {
      return $settings->get('default_hero') ?? 'hero';
    }
    // Return the selected style, or the default one.
    return $group->get('field_group_image_style')->value ?? 'hero';
  }

  /**
   * Function that determines the group hero croptype.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return string
   *   The crop style.
   */
  public function getGroupHeroImageStyle(GroupInterface $group) :string {
    return $this->cropToStyle($this->getGroupHeroCropType($group));
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

    return $values[$cropType] ?? 'social_xx_large';
  }

}
