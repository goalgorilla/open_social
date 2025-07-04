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
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\Entity\Group;
use Drupal\social_group\GroupMembershipRequestableInterface;
use Drupal\social_group_request\Form\GroupRequestMembershipRequestAnonymousForm;
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
    AccountInterface $current_user,
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
   * Get the title for the membership request page/dialog.
   *
   * This requests the membership request title from the group's bundle class if
   * the group type supports it. Otherwise, it'll provide a default title.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to get the title for.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function requestMembershipTitle(GroupInterface $group) : TranslatableMarkup {
    if ($group instanceof GroupMembershipRequestableInterface) {
      $title = $group->requestMembershipTitle($group);
      if ($title !== NULL) {
        return $title;
      }
    }

    return $this->t(
      "Request to join: %group_label",
      ['%group_label' => $group->label()],
      ['context' => 'join request page title'],
    );
  }

  /**
   * Return the title for approve request confirmation page.
   */
  public function getTitleApproveRequest(GroupInterface $group, GroupRelationshipInterface $group_content) {
    return $this->t('Approve membership request for the @group_title', ['@group_title' => $group->label()]);
  }

  /**
   * Return the title for reject request confirmation page.
   */
  public function getTitleRejectRequest(GroupInterface $group, GroupRelationshipInterface $group_content) {
    return $this->t('Reject membership request for the @group_title', ['@group_title' => $group->label()]);
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
    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('group_content_type');
    $group_type_id = (string) $group->getGroupType()->id();
    $relation_type_id = $storage->getRelationshipTypeId($group_type_id, 'group_membership_request');

    $requests = $this->entityTypeManager()->getStorage('group_content')->loadByProperties([
      'type' => $relation_type_id,
      'gid' => $group->id(),
      'entity_id' => $this->currentUser()->id(),
      'grequest_status' => GroupMembershipRequest::REQUEST_PENDING,
    ]);

    foreach ($requests as $request) {
      $request->delete();
    }

    $this->messenger()->addMessage($this->t('Membership has been successfully denied.'));

    $this->cacheTagsInvalidator->invalidateTags(['group:' . $group->id()]);

    return $this->redirect('entity.group.canonical', ['group' => $group->id()]);
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
