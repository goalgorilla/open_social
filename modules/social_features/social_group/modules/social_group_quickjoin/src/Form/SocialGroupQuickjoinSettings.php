<?php

namespace Drupal\social_group_quickjoin\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialEventTypeSettings.
 *
 * @package Drupal\social_group_quickjoin\Form
 */
class SocialGroupQuickjoinSettings extends ConfigFormBase {

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_group_quickjoin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_group_quickjoin_form';
  }

  /**
   * SocialGroupQuickjoinSettings constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_group_quickjoin.settings');

    $form['help'] = [
      '#type' => 'item',
      '#markup' => $this->t("Enabling this feature gives site builders the possibility to create group 'quick join' links (ex., /group/1/quickjoin). Furthermore, it's possible to skip the confirmation step on a group type basis."),
    ];

    $form['social_group_quickjoin_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Enable quickjoin"),
      '#description' => $this->t("Allow users to join groups with a single click."),
      '#default_value' => $config->get('social_group_quickjoin_enabled'),
    ];

    $form['grouptypes'] = [
      '#type' => 'details',
      '#title' => $this->t('Group types'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="social_group_quickjoin_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    /** @var \Drupal\group\Entity\GroupType $group_type */
    foreach ($this->getGroups() as $group_type) {
      // The setting name.
      $setting_name = 'social_group_quickjoin_' . $group_type->id();

      $form['grouptypes'][$setting_name] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Skip confirmation for type @grouptype', [
          '@grouptype' => $group_type->label(),
        ]),
        '#description' => $this->t('Allow users to skip the confirmation step when joining any @grouptype.', [
          '@grouptype' => $group_type->label(),
        ]),
        '#default_value' => $config->get($setting_name),
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Set the value for the general setting.
    $this->config('social_group_quickjoin.settings')
      ->set('social_group_quickjoin_enabled', $form_state->getValue('social_group_quickjoin_enabled'))
      ->save();

    /** @var \Drupal\group\Entity\GroupType $group_type */
    foreach ($this->getGroups() as $group_type) {
      // The setting name.
      $setting_name = 'social_group_quickjoin_' . $group_type->id();
      $setting_value = $form_state->getValue('social_group_quickjoin_enabled') ? $form_state->getValue($setting_name) : FALSE;
      // Set the value in the config.
      $this->config('social_group_quickjoin.settings')
        ->set($setting_name, $setting_value);
    }
    // Save the config.
    $this->config('social_group_quickjoin.settings')->save();
  }

  /**
   * Function that returns all groups that outsider can become a member of.
   *
   * @return array
   *   Joinable groups.
   */
  protected function getGroups() {
    $types = [];

    $group_roles = $this->entityTypeManager
      ->getStorage('group_role')
      ->loadMultiple();

    // Instead of loading by group type, we load all group roles.
    // So we can find those with a scope outsider, and permission
    // join group. As we can only configure those group types. If
    // there aren't any with that permission it means nobody
    // is able to join the group directly, so configuring this
    // doesn't make sense.
    if ($group_roles) {
      /** @var \Drupal\group\Entity\GroupRole $group_role */
      foreach ($group_roles as $group_role) {
        if ($group_role->isOutsider() && $group_role->hasPermission('join_group')) {
          if (!in_array($group_role->getGroupType(), $types)) {
            $types[] = $group_role->getGroupType();
          }
        }
      }
    }
    return $types;
  }

}
