<?php

namespace Drupal\social_course_basic_request;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides config overrides in social_course_basic_request module.
 */
class SocialCourseBasicRequestOverrides implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SocialCourseBasicRequestOverrides object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_name = 'core.entity_form_display.group.course_basic.default';

    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);

      $children = $config->get('third_party_settings.field_group.group_settings.children');
      $children[] = 'field_group_allowed_join_method';

      $content = $config->get('content');
      $content['field_group_allowed_join_method'] = [
        'weight' => 100,
        'settings' => [
          'display_label' => TRUE,
        ],
        'third_party_settings' => [],
        'type' => 'options_select',
        'region' => 'content',
      ];

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => [
            'group_settings' => [
              'children' => $children,
            ],
          ],
        ],
        'content' => $content,
      ];
    }
    $config_name = 'block.block.membershiprequestsnotification';

    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name] = [
        'visibility' => [
          'group_type' => [
            'group_types' => [
              'course_basic' => 'course_basic',
            ],
          ],
        ],
      ];
    }

    $config_name = 'block.block.membershiprequestsnotification_2';

    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name] = [
        'visibility' => [
          'group_type' => [
            'group_types' => [
              'course_basic' => 'course_basic',
            ],
          ],
        ],
      ];
    }

    $config_name = 'message.template.request_to_join_a_group';
    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
        [
          'group_content-group_content_type_aed76be1b2aaf' => 'group_content-group_content_type_aed76be1b2aaf',
        ];
    }

    $config_name = 'message.template.approve_request_join_group';
    if (in_array($config_name, $names, FALSE)) {
      $overrides[$config_name]['third_party_settings']['activity_logger']['activity_bundle_entities'] =
        [
          'group_content-course_basic-group_membership' => 'group_content-course_basic-group_membership',
        ];
    }

    $config_name = 'core.entity_form_display.group.course_basic.default';

    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);

      if ($config->get('third_party_settings.field_group.group_access_permissions')) {
        $children = $config->get('third_party_settings.field_group.group_access_permissions.children');
        $children[] = 'field_group_allowed_join_method';
      }

      $content = $config->get('content');
      $content['field_group_allowed_join_method'] = [
        'weight' => 100,
        'settings' => [
          'display_label' => TRUE,
        ],
        'third_party_settings' => [],
        'type' => 'options_buttons',
        'region' => 'content',
      ];

      $overrides[$config_name] = [
        'third_party_settings' => [
          'field_group' => [
            'group_access_permissions' => [
              'children' => $children ?? [],
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
    return 'SocialCourseBasicRequestOverrides';
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
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

}
