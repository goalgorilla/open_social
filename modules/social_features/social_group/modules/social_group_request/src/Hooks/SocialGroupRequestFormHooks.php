<?php

namespace Drupal\social_group_request\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\social_group\JoinManagerInterface;

/**
 * Form alter hooks for Social Group Request.
 *
 * @internal
 */
class SocialGroupRequestFormHooks {

  /**
   * Constructs the form hooks.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\social_group\JoinManagerInterface $joinManager
   *   The join manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected JoinManagerInterface $joinManager,
  ) {}

  /**
   * Returns resolved allow_customize per group type (includes legacy config).
   *
   * @return array<string, bool>
   *   Resolved allow_customize keyed by group type id.
   */
  public function getAllowCustomizeByGroupType(): array {
    $config = $this->configFactory->get('social_group_request.settings');
    $allow_customize = (array) ($config->get('allow_customize') ?? []);
    $allow_customize['flexible_group'] = $allow_customize['flexible_group']
      ?? $config->get('allow_group_managers_customize')
      ?? FALSE;
    $allow_customize['organization'] = $allow_customize['organization']
      ?? $config->get('allow_organization_managers_customize')
      ?? FALSE;
    return $allow_customize;
  }

  /**
   * Returns group types that use the group_membership_request content plugin.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface[]
   *   Group types keyed by id.
   */
  public function getGroupTypesWithMembershipRequest(): array {
    $relationship_type_ids = (array) $this->entityTypeManager
      ->getStorage('group_content_type')
      ->getQuery()
      ->condition('content_plugin', 'group_membership_request')
      ->condition('status', TRUE)
      ->accessCheck(FALSE)
      ->execute();

    if ($relationship_type_ids === []) {
      return [];
    }

    $group_type_ids = [];
    foreach ($relationship_type_ids as $relationship_type_id) {
      $relation_config = $this->configFactory->get("group.content_type.{$relationship_type_id}");
      $group_type_id = $relation_config->get('group_type');
      if ($group_type_id !== NULL) {
        $group_type_ids[$group_type_id] = TRUE;
      }
    }

    if ($group_type_ids === []) {
      return [];
    }

    $group_types = $this->entityTypeManager
      ->getStorage('group_type')
      ->loadMultiple(array_keys($group_type_ids));

    return $group_types;
  }

  /**
   * Implements hook_form_alter().
   *
   * Customizes group add/edit forms for the request-to-join flow.
   */
  #[Alter('form')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $this->alterAllowRequestForGroupForms($form, $form_state, $form_id);
    $this->addRequestFormCustomizationSection($form, $form_state, $form_id);
  }

  /**
   * Hides allow_request when the group type does not support request to join.
   *
   * @param array &$form
   *   The form array (passed by reference).
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  public function alterAllowRequestForGroupForms(array &$form, FormStateInterface $form_state, string $form_id): void {
    $social_group_types = [
      'flexible_group',
      'sc',
      'cc',
    ];
    $this->moduleHandler->alter('social_group_types', $social_group_types);
    if (!preg_match(
      '/^group_(' . implode('|', $social_group_types) . ')_(add|edit)_form$/',
      $form_id,
    )) {
      return;
    }

    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return;
    }
    $group = $form_object->getEntity();
    if (!$group instanceof GroupInterface) {
      return;
    }
    $group_type = $group->getGroupType();
    $prohibit = FALSE;

    if ($group_type->hasPlugin('group_membership_request')) {
      $bundle = $group_type->id();
      $found = FALSE;

      foreach ($this->joinManager->relations() as $relation) {
        if (
          $relation['entity_type'] === 'group' &&
          isset($relation['bundle'], $relation['method']) &&
          in_array($bundle, (array) $relation['bundle']) &&
          in_array('request', (array) $relation['method'])
        ) {
          $found = TRUE;
          break;
        }
      }

      if (!$found) {
        $prohibit = TRUE;
      }
    }
    else {
      $prohibit = TRUE;
    }

    if ($prohibit) {
      unset($form['allow_request']);
    }
  }

  /**
   * After-build callback set a fixed ID on the "Request to join" widget option.
   *
   * Targets the option with value "request" so #states can rely on it.
   */
  public static function groupJoinMethodWidgetAfterBuild(array $element, FormStateInterface $form_state): array {
    if (empty($element['widget'])) {
      return $element;
    }
    foreach (array_keys($element['widget']) as $key) {
      if (!is_array($element['widget'][$key] ?? NULL)) {
        continue;
      }
      $option = &$element['widget'][$key];
      $value = $option['#return_value'] ?? $key;
      if ($value === 'request') {
        $option['#id'] = 'group-join-method-request';
        break;
      }
    }
    return $element;
  }

  /**
   * Adds the "Request to join" customization section to group entity forms.
   */
  public function addRequestFormCustomizationSection(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!preg_match('/^group_([a-z_]+)_(add|edit)_form$/', $form_id, $matches)) {
      return;
    }

    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return;
    }
    $group = $form_object->getEntity();
    if (!$group instanceof GroupInterface) {
      return;
    }

    $bundle = $group->bundle();
    if ($bundle !== $matches[1]) {
      return;
    }

    $group_types_with_request = $this->getGroupTypesWithMembershipRequest();
    if (!isset($group_types_with_request[$bundle])) {
      return;
    }

    $allow_customize = $this->getAllowCustomizeByGroupType();
    if (empty($allow_customize[$bundle])) {
      return;
    }

    if (!$group->hasField('field_grequest_form_required') ||
      !$group->hasField('field_group_allowed_join_method')) {
      return;
    }

    // Give the "Request to join" option a fixed ID so #states can target it.
    if (isset($form['field_group_allowed_join_method']['widget'])) {
      $form['field_group_allowed_join_method']['#after_build'][] = [
        self::class,
        'groupJoinMethodWidgetAfterBuild',
      ];
    }

    // Visible only when "Request to join" is selected. Value state works for
    // radios (single name); ID works for checkboxes (per-option names).
    // phpcs:disable Squiz.Arrays.ArrayDeclaration.NoKeySpecified -- #states requires 'or' and unkeyed entry
    $request_selected = [
      ':input[name="field_group_allowed_join_method"]' => ['value' => 'request'],
      'or',
      ':input#group-join-method-request' => ['checked' => TRUE],
    ];
    $visible_states = $request_selected;
    if ($bundle === 'flexible_group' && !empty($form['field_flexible_group_visibility'])) {
      $visible_states = [
        ':input[name="field_flexible_group_visibility"]' => [
          ['value' => 'public'],
          ['value' => 'community'],
          ['value' => 'members'],
        ],
        $request_selected,
      ];
    }
    // phpcs:enable Squiz.Arrays.ArrayDeclaration.NoKeySpecified

    $form['request_to_join_form_wrapper'] = [
      '#type' => 'container',
      '#weight' => 103,
      '#group' => 'group_access_permissions',
      '#attributes' => [
        'style' => 'display:none',
        'data-request-join-fields' => 'wrapper',
      ],
      '#states' => [
        'visible' => $visible_states,
      ],
    ];

    // Reuse the same panel structure and class as Location address.
    $form['request_to_join_form_wrapper']['request_to_join_form'] = [
      '#type' => 'container',
      '#prefix' => '<div class="panel field--type-address"><div class="panel-body">',
      '#markup' => '<div class="help-block">' . t('Personalize request to join form:') . '</div>',
      '#suffix' => '</div></div>',
    ];

    if ($group->hasField('field_grequest_form_description')) {
      $form['request_to_join_form_wrapper']['request_to_join_form']['field_grequest_form_description'] = [
        '#type' => 'textarea',
        '#title' => t('Description'),
        '#description' => t('Describe the information users are expected to provide.'),
        '#default_value' => !$group->get('field_grequest_form_description')->isEmpty()
          ? $group->get('field_grequest_form_description')->value
          : 'You can leave a message in your request. Only when your request is approved, you will receive a notification via email and notification center.',
        '#weight' => 2,
        '#rows' => 5,
      ];
    }

    if ($group->hasField('field_grequest_form_default')) {
      $form['request_to_join_form_wrapper']['request_to_join_form']['field_grequest_form_default'] = [
        '#type' => 'textarea',
        '#title' => t('Message'),
        '#description' => t('Help users with an example of the information they are expected to provide.'),
        '#default_value' => !$group->get('field_grequest_form_default')->isEmpty() ? $group->get('field_grequest_form_default')->value : '',
        '#weight' => 4,
        '#rows' => 10,
      ];
    }

    $form['request_to_join_form_wrapper']['request_to_join_form']['field_grequest_form_required'] = [
      '#type' => 'checkbox',
      '#title' => t('Make the message required when requesting to join'),
      '#description' => t('Users are required to provide a message.'),
      '#default_value' => !$group->get('field_grequest_form_required')->isEmpty()
      && $group->get('field_grequest_form_required')->value,
      '#weight' => 6,
    ];

    if (isset($form['#fieldgroups']['group_access_permissions'])) {
      $form['#fieldgroups']['group_access_permissions']->children[] = 'request_to_join_form_wrapper';
    }

    array_unshift($form['#submit'], [self::class, 'groupFormSubmitRequestFormSettingsStatic']);
  }

  /**
   * Static submit handler wrapper for form cache serialization safety.
   *
   * Using [$this, 'method'] in $form['#submit'] stores the service instance
   * in the form array. When the form is cached (e.g. during AJAX file uploads),
   * PHP tries to serialize the service and its injected dependencies, which
   * fails. This static wrapper resolves the service from the container instead.
   *
   * @param array &$form
   *   The form array (passed by reference).
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function groupFormSubmitRequestFormSettingsStatic(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    \Drupal::service('social_group_request.form_hooks')
      ->groupFormSubmitRequestFormSettings($form, $form_state);
  }

  /**
   * Save request-to-join form customization to the group entity.
   *
   * @param array &$form
   *   The form array (passed by reference).
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function groupFormSubmitRequestFormSettings(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    $values = $form_state->getValue(
      ['request_to_join_form_wrapper', 'request_to_join_form'],
      [],
    );
    if ($values === []) {
      return;
    }

    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return;
    }
    $group = $form_object->getEntity();
    if (!$group instanceof GroupInterface
      || !$group->hasField('field_grequest_form_required')) {
      return;
    }

    $group->set('field_grequest_form_required', !empty($values['field_grequest_form_required']));

    if ($group->hasField('field_grequest_form_description')) {
      $description = isset($values['field_grequest_form_description'])
        ? trim((string) $values['field_grequest_form_description'])
        : '';
      $group->set('field_grequest_form_description', $description);
    }

    if ($group->hasField('field_grequest_form_default')) {
      $default = isset($values['field_grequest_form_default'])
        ? trim((string) $values['field_grequest_form_default'])
        : '';
      $group->set('field_grequest_form_default', $default);
    }
  }

}
