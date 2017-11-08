<?php

namespace Drupal\social_private_message\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialPrivateMessageMemberFormatter.
 *
 * @FieldFormatter(
 *   id = "social_private_message_member_formatter",
 *   label = @Translation("Social Private Message Members"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class SocialPrivateMessageMemberFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Construct a PrivateMessageThreadFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current logged in user.
   *
   * @internal param $ |Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityManagerInterface $entityManager, AccountProxyInterface $currentUser) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId() == 'private_message_thread' && $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'user');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    if ($this->getSetting('display_type') == 'label') {
      $format = t('Displays members using their username, linked to the user account if the viewer has permission to access user profiles');
    }
    elseif ($this->getSetting('display_type') == 'entity') {
      $format = t('Displays members using the %display_mode display mode of the user entity', ['%display_mode' => $this->getSetting('entity_display_mode')]);
    }

    $summary[] = $format;

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_type' => 'label',
      'entity_display_mode' => 'private_message_author',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['display_type'] = [
      '#title' => t('Display Type'),
      '#type' => 'select',
      '#options' => [
        'label' => $this->t('Label'),
        'entity' => $this->t('Entity'),
      ],
      '#default_value' => $this->getSetting('display_type'),
      '#ajax' => [
        'wrapper' => 'private_message_thread_member_formatter_settings_wrapper',
        'callback' => [$this, 'ajaxCallback'],
      ],
    ];

    $element['entity_display_mode'] = [
      '#prefix' => '<div id="private_message_thread_member_formatter_settings_wrapper">',
      '#suffix' => '</div>',
    ];

    foreach ($this->entityManager->getViewModes('user') as $display_mode_id => $display_mode) {
      $options[$display_mode_id] = $display_mode['label'];
    }

    $setting_key = 'display_type';
    if ($value = $form_state->getValue([
      'fields',
      $this->getFieldName(),
      'settings_edit_form',
      'settings',
      $setting_key,
    ])) {
      $display_type = $value;
    }
    else {
      $display_type = $this->getSetting('display_type');
    }

    if ($display_type == 'entity') {
      $element['entity_display_mode']['#type'] = 'select';
      $element['entity_display_mode']['#title'] = $this->t('View mode');
      $element['entity_display_mode']['#options'] = $options;
      $element['entity_display_mode']['#default_value'] = $this->getSetting('entity_display_mode');
    }
    else {
      $element['entity_display_mode']['#markup'] = '';
    }

    return $element;
  }

  /**
   * Ajax callback for settings form.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['fields'][$this->getFieldName()]['plugin']['settings_edit_form']['settings']['entity_display_mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $users = [];

    $view_builder = $this->entityManager->getViewBuilder('user');
    foreach ($items as $delta => $item) {
      $user = $item->entity;
      if ($user->id() != $this->currentUser->id()) {
        if ($this->getSetting('display_type') == 'label') {
          $users[$user->id()] = $user->getDisplayName();
        }
        elseif ($this->getSetting('display_type') == 'entity') {
          $renderable = $view_builder->view($user, $this->getSetting('entity_display_mode'));
          $users[$user->id()] = render($renderable);
        }
      }
    }
    $separator = $this->getSetting('display_type') == 'label' ? ', ' : '';

    if (count($users) == 1) {
      $recipient = User::load(key($users));
      // Load compact notification view mode of the attached profile.
      if ($recipient instanceof User) {
        $storage = \Drupal::entityTypeManager()->getStorage('profile');
        if (!empty($storage)) {
          $user_profile = $storage->loadByUser($recipient, 'profile');
          if ($user_profile) {
            $content = \Drupal::entityTypeManager()
              ->getViewBuilder('profile')
              ->view($user_profile, 'compact_private_message');
            // Add to a new field, so twig can render it.
            $profile_picture = $content;
          }
        }
      }
      $participants = $profile_picture;
    }
    else {
      $participants['#markup'] = '<div class="media-left avatar"><span class="avatar-icon avatar-group-icon avatar-group-icon--small"></span></div><div class="media-body">' . implode($separator, $users) . '</div>';
    }

    $element = [
      '#prefix' => '<div class="media message__recipients">',
      '#suffix' => '</div>',
    ];
    $element += $participants;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldName() {
    return $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
  }

}
