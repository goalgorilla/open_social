<?php

namespace Drupal\social_group_request\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to request group membership.
 */
class GroupRequestMembershipRequestForm extends FormBase {

  /**
   * Group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Group membership request.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $groupContent;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * GroupRequestMembershipRejectForm constructor.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    AccountInterface $current_user,
    TranslationInterface $string_translation,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->currentUser = $current_user;
    $this->setStringTranslation($string_translation);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_tags.invalidator'),
      $container->get('current_user'),
      $container->get('string_translation'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_group_request_membership_request';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    $this->group = $group;

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You can leave a message in your request. Only when your request is approved, you will receive a notification via email and notification center.'),
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send request'),
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content_type_config_id = $this->group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $group_content = $this->entityTypeManager->getStorage('group_content')->create([
      'type' => $content_type_config_id,
      'gid' => $this->group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
      'field_grequest_message' => $form_state->getValue('message'),
    ]);
    $result = $group_content->save();

    if ($result) {
      $this->messenger()->addMessage($this->t('Your request has been sent successfully'));
    }
    else {
      $this->messenger()->addError($this->t('Error when creating a request to join'));
    }

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $this->group->id()]);

    return $form_state->setRedirect('social_group.stream', ['group' => $this->group->id()]);
  }

}
