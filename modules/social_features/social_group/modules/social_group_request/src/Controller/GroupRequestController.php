<?php

namespace Drupal\social_group_request\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\Entity\Group;
use Drupal\social_group_request\Form\GroupRequestMembershipRequestAnonymousForm;
use Drupal\social_group_request\Form\GroupRequestMembershipRequestForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group request routes.
 */
class GroupRequestController extends ControllerBase {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * GroupRequestController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    EntityFormBuilderInterface $entity_form_builder,
    MessengerInterface $messenger,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    TranslationInterface $string_translation,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user
  ) {
    $this->formBuilder = $form_builder;
    $this->entityFormBuilder = $entity_form_builder;
    $this->setMessenger($messenger);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->setStringTranslation($string_translation);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.form_builder'),
      $container->get('messenger'),
      $container->get('cache_tags.invalidator'),
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Return the title for approve request confirmation page.
   */
  public function getTitleApproveRequest(GroupInterface $group, GroupContentInterface $group_content) {
    return $this->t('Approve membership request for the group @group_title', ['@group_title' => $group->label()]);
  }

  /**
   * Return the title for reject request confirmation page.
   */
  public function getTitleRejectRequest(GroupInterface $group, GroupContentInterface $group_content) {
    return $this->t('Reject membership request for the group @group_title', ['@group_title' => $group->label()]);
  }

  /**
   * Builds the form to create new membership on membership request approve.
   */
  public function approveRequest(GroupInterface $group, GroupContentInterface $group_content) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');

    // Pre-populate a group membership from Membership request.
    $group_content = $this->entityTypeManager()->getStorage('group_content')->create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $group_content->getEntity()->id(),
    ]);

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $group->id()]);

    return $this->entityFormBuilder()->getForm($group_content, 'add');
  }

  /**
   * Callback to request membership.
   */
  public function requestMembership(GroupInterface $group) {
    $response = new AjaxResponse();

    $contentTypeConfigId = $group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $request = $this->entityTypeManager()->getStorage('group_content')->getQuery()
      ->condition('type', $contentTypeConfigId)
      ->condition('gid', $group->id())
      ->condition('entity_id', $this->currentUser()->id())
      ->condition('grequest_status', GroupMembershipRequest::REQUEST_PENDING)
      ->count()
      ->execute();

    if ($request == 0) {
      $request_form = $this->formBuilder()->getForm(GroupRequestMembershipRequestForm::class, $group);
      $response->addCommand(new OpenModalDialogCommand($this->t('Request to join'), $request_form, [
        'width' => '582px',
        'dialogClass' => 'social_group-popup',
      ]));
    }

    return $response;
  }

  /**
   * Callback to request membership for anonymous.
   */
  public function anonymousRequestMembership(GroupInterface $group) {
    $request_form = $this->formBuilder()->getForm(GroupRequestMembershipRequestAnonymousForm::class, $group);

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Request to join'), $request_form, [
      'width' => '337px',
      'dialogClass' => 'social_group-popup social_group-popup--anonymous',
    ]));

    return $response;
  }

  /**
   * Callback to cancel the request of membership.
   */
  public function cancelRequest(GroupInterface $group) {
    $content_type_config_id = $group
      ->getGroupType()
      ->getContentPlugin('group_membership_request')
      ->getContentTypeConfigId();

    $requests = $this->entityTypeManager()->getStorage('group_content')->loadByProperties([
      'type' => $content_type_config_id,
      'gid' => $group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
    ]);

    foreach ($requests as $request) {
      $request->delete();
    }

    $this->messenger()->addMessage($this->t('Membership has been successfully denied.'));

    $this->cacheTagsInvalidator->invalidateTags(['request-membership:' . $group->id()]);

    return $this->redirect('social_group.stream', ['group' => $group->id()]);
  }

  /**
   * Checks access for a specific route request to see if user can see requests.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function routeAccess(AccountInterface $account) {
    // @todo refactor this when Group entity query access lands.
    $has_administer_users = $account->hasPermission('administer members');
    if ($has_administer_users) {
      return AccessResult::allowed();
    }
    $group = _social_group_get_current_group();
    if (!$group instanceof Group) {
      $group_id = \Drupal::routeMatch()->getParameter('group');
      // Views upcasting is lame.
      if (!isset($group_id)) {
        $group_id = \Drupal::routeMatch()->getParameter('arg_0');
      }
      $group = Group::load($group_id);
    }
    $is_group_page = isset($group);
    $is_group_manager = $group->hasPermission('administer members', $account);
    return AccessResult::allowedIf($is_group_page && $is_group_manager);
  }

}
