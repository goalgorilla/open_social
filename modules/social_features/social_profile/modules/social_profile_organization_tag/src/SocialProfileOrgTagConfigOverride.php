<?php

namespace Drupal\social_profile_organization_tag;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class SocialProfileOrgTagConfigOverride.
 *
 * @package Drupal\social_profile_organization_tag
 */
class SocialProfileOrgTagConfigOverride implements ConfigFactoryOverrideInterface {
  use StringTranslationTrait;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * Constructs for SocialGroupSelectorWidgetConfigOverride class.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Override profile form display.
    $config_name = 'core.entity_form_display.profile.profile.default';
    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);

      $children = $config->get('third_party_settings.field_group.group_profile_self_intro.children');
      $children[] = 'field_profile_organization_tag';

      $content = $config->get('content');
      $content['field_profile_organization_tag'] = [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
        'region' => 'content',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'third_party_settings' => [],

      ];

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => [
            'group_tags' => [
              'children' => [
                'field_profile_organization_tag',
              ],
              'parent_name' => '',
              'weight' => 99,
              'label' => $this->t('Tags')->render(),
              'format_type' => 'fieldset',
              'format_settings' => [
                'label' => $this->t('Tags')->render(),
                'required_fields' => FALSE,
                'id' => 'group_tags',
                'classes' => 'scrollspy',
              ],
            ],
          ],
        ],
        'content' => $content,
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialProfileOrgTagConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
