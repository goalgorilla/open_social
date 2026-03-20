<?php

namespace Drupal\social_group_request\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\Entity\Form\GroupMembershipRequestForm;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\grequest\MembershipRequestManager;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to request group membership.
 */
class GroupRequestMembershipRequestForm extends GroupMembershipRequestForm {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new GroupRequestMembershipRequestForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, MembershipRequestManager $membership_request_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time, $membership_request_manager);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager'),
      $container->get('cache_tags.invalidator'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->getEntity();
    if (!$entity instanceof GroupRelationshipInterface) {
      return $form;
    }
    $group = $entity->getGroup();

    $message_required = $this->isMessageRequired($group);
    $custom_description = $this->getCustomMessageDescription($group);
    $default_message = $this->getDefaultMessageText($group);

    $message_field_visible = $this->configureMessageField($form, $message_required, $default_message);

    $default_description = $message_field_visible
      ? ($message_required
        ? t("Please provide a message with your request. Only when your request is approved, you will receive a notification via email and notification center.")
        : t("You can leave a message in your request. Only when your request is approved, you will receive a notification via email and notification center."))
      : t("Only when your request is approved, you will receive a notification via email and notification center.");

    $form['description'] = [
      '#type' => 'inline_template',
      '#template' => '<p>{{ description }}</p>',
      '#context' => [
        'description' => $custom_description !== '' ? $custom_description : $default_description,
      ],
      '#weight' => ($form['field_grequest_message']['#weight'] ?? 0) - 10,
    ];

    unset($form['actions']['cancel']);

    assert(
      isset($form['actions']['submit']),
      "The grequest module has removed the 'submit' action from its form.",
    );
    $form['actions']['submit']['#value'] = t('Send request');

    // When message is required, use AJAX so validation is shown without reload.
    if ($this->getRequest()->isXmlHttpRequest() && $message_required) {
      $form['#id'] = 'group-request-membership-form';
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $form['actions']['submit']['#attributes']['class'][] = 'use-ajax';
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::submitFormAjax',
        'wrapper' => 'group-request-membership-form',
        'disable-refocus' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * AJAX submit: redirect to the group page on success, rebuild on error.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Rebuilt form if there are errors, or an AJAX redirect to the group page.
   */
  public function submitFormAjax(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return $form;
    }
    $response = new AjaxResponse();
    $group_relationship = $this->getEntity();
    if ($group_relationship instanceof GroupRelationshipInterface) {
      $url = $group_relationship->getGroup()->toUrl()->toString();
      $response->addCommand(new RedirectCommand($url));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ContentEntityInterface {
    $entity = parent::validateForm($form, $form_state);

    $group_entity = $this->getEntity();
    if (!$group_entity instanceof GroupRelationshipInterface) {
      return $entity;
    }

    $message_value = $form_state->getValue(['field_grequest_message', 0, 'value']);
    if (!is_string($message_value)) {
      return $entity;
    }
    // Do not set an error here when required and empty.
    // Adding another would show "2 errors have been found:Message,Message".
    $trimmed = trim($message_value);
    $form_state->setValue(['field_grequest_message', 0, 'value'], $trimmed);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    $group_relationship = $this->getEntity();
    if ($group_relationship instanceof GroupRelationshipInterface) {
      $group = $group_relationship->getGroup();
      $this->cacheTagsInvalidator->invalidateTags($group->getCacheTagsToInvalidate());
    }

    return $return;
  }

  /**
   * Configures the message field (required state and default value).
   *
   * @param array &$form
   *   The form array (passed by reference).
   * @param bool $message_required
   *   Whether the message is required.
   * @param string $default_message
   *   Default message text, or empty string.
   *
   * @return bool
   *   TRUE if the message field is visible, FALSE otherwise.
   */
  private function configureMessageField(array &$form, bool $message_required, string $default_message): bool {
    if (!isset($form['field_grequest_message']) || ($form['field_grequest_message']['#access'] ?? TRUE) === FALSE) {
      return FALSE;
    }

    if ($message_required) {
      if (isset($form['field_grequest_message']['widget'][0]['value'])) {
        $form['field_grequest_message']['widget'][0]['value']['#required'] = TRUE;
      }
      else {
        $form['field_grequest_message']['#required'] = TRUE;
      }
    }

    if ($default_message !== '') {
      $this->setMessageDefaultValue($form['field_grequest_message'], $default_message);
    }

    return TRUE;
  }

  /**
   * Whether the message field is required for this group.
   */
  private function isMessageRequired(GroupInterface $group): bool {
    if (!$this->isCustomizationAllowed($group)) {
      return FALSE;
    }
    if (!$group->hasField('field_grequest_form_required')) {
      return FALSE;
    }
    $field = $group->get('field_grequest_form_required');
    return !$field->isEmpty() && filter_var($field->value, FILTER_VALIDATE_BOOLEAN);
  }

  /**
   * Gets the custom message form description from the group, or empty string.
   */
  private function getCustomMessageDescription(GroupInterface $group): string {
    if (!$this->isCustomizationAllowed($group)) {
      return '';
    }
    if (!$group->hasField('field_grequest_form_description')) {
      return '';
    }
    $field = $group->get('field_grequest_form_description');
    return $field->isEmpty() ? '' : trim((string) $field->value);
  }

  /**
   * Gets the default message text from the group, or empty string.
   */
  private function getDefaultMessageText(GroupInterface $group): string {
    if (!$this->isCustomizationAllowed($group)) {
      return '';
    }
    if (!$group->hasField('field_grequest_form_default')) {
      return '';
    }
    $field = $group->get('field_grequest_form_default');

    return $field->isEmpty()
      ? ''
      : trim((string) $field->value);
  }

  /**
   * Checks if customization is allowed for the group type.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   *
   * @return bool
   *   TRUE if customization is allowed, FALSE otherwise.
   */
  private function isCustomizationAllowed(GroupInterface $group): bool {
    $config = $this->configFactory->get('social_group_request.settings');
    $allow_customize = (array) ($config->get('allow_customize') ?? []);
    $bundle = $group->bundle();

    return !empty($allow_customize[$bundle]);
  }

  /**
   * Sets the default value for the message field.
   *
   * Supports common widget structures (e.g. widget[0][value]).
   *
   * @param array &$element
   *   The field_grequest_message form element (passed by reference).
   * @param string $default
   *   The default text to set.
   */
  private function setMessageDefaultValue(array &$element, string $default): void {
    if (isset($element['widget'][0]['value'])) {
      $element['widget'][0]['value']['#default_value'] = $default;
      $element['widget'][0]['value']['#placeholder'] = '';
    }
    elseif (isset($element['widget'][0])) {
      $element['widget'][0]['#default_value'] = $default;
      if (isset($element['widget'][0]['#placeholder'])) {
        $element['widget'][0]['#placeholder'] = '';
      }
    }
  }

}
