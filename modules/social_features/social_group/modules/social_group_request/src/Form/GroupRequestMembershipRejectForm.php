<?php

namespace Drupal\social_group_request\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\social_group_request\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the examples.
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
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   Redirect destination.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   Cache tags invalidator.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->redirectDestination = $redirect_destination;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('cache_tags.invalidator')
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
  private function getCancelUrl() {
    return Url::fromUserInput($this->redirectDestination->get());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL, GroupContentInterface $group_content = NULL) {
    $this->group = $group;
    $this->groupContent = $group_content;

    $form['#attributes']['class'][] = 'form--default';

    $user_name = $group_content->entity_id->entity->getDisplayName();
    $form['question'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure to reject the membership request for the @name?', ['@name' => $user_name]),
      '#weight' => 1,
      '#prefix' => '<div class="card"><div class="card__block">',
      '#suffix' => '</div></div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
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
      '#url' => $this->getCancelUrl(),
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
      ->set('grequest_updated_by', $this->currentUser()->id());
    $result = $this->groupContent->save();

    if ($result) {
      $this->messenger()->addStatus($this->t('Membership Request rejected'));
    }
    else {
      $this->messenger()->addError($this->t('Error updating Request'));
    }

    _activity_basics_entity_action($this->groupContent, 'change_status_group_membership_request_entity_action');

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $this->group->id()]);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
