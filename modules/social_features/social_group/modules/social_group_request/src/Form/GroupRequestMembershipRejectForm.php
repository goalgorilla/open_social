<?php

namespace Drupal\social_group_request\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before rejecting membership.
 */
class GroupRequestMembershipRejectForm extends FormBase {

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
   * GroupRequestMembershipRejectForm constructor.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    AccountInterface $current_user,
    TranslationInterface $string_translation
  ) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->currentUser = $current_user;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_tags.invalidator'),
      $container->get('current_user'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grequest_group_request_membership_reject';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL, GroupContentInterface $group_content = NULL) {
    $this->group = $group;
    $this->groupContent = $group_content;

    $form['#attributes']['class'][] = 'form--default';

    $form['question'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure you want to reject the membership request for @name?', [
        '@name' => $group_content->getEntity()->getDisplayName(),
      ]),
      '#weight' => 1,
      '#prefix' => '<div class="card"><div class="card__block">',
      '#suffix' => '</div></div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['_social_group_cancel_join_leave_form'],
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => [
          'button',
          'button--flat',
          'btn',
          'btn-flat',
          'waves-effect',
          'waves-btn',
        ],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->groupContent
      ->set('grequest_status', GroupMembershipRequest::REQUEST_REJECTED)
      // Who created request will become an 'approver' for Membership request.
      ->set('grequest_updated_by', $this->currentUser->id());
    $result = $this->groupContent->save();

    if ($result) {
      $this->messenger()->addStatus($this->t('Membership request rejected'));
    }
    else {
      $this->messenger()->addError($this->t('Error updating Request'));
    }

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $this->group->id()]);

    _social_group_cancel_join_leave_form($form, $form_state);
  }

}
