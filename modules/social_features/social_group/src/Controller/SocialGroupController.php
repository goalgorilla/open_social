<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Social Group routes.
 */
class SocialGroupController extends ControllerBase {

  use ViewsBulkOperationsFormTrait;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Views Bulk Operations action processor.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface
   */
  protected $actionProcessor;


  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * SocialGroupController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   Private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   */
  public function __construct(RequestStack $requestStack, PrivateTempStoreFactory $tempStoreFactory, ViewsBulkOperationsActionProcessorInterface $actionProcessor) {
    $this->requestStack = $requestStack;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->actionProcessor = $actionProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('tempstore.private'),
      $container->get('views_bulk_operations.processor')
    );
  }

  /**
   * The _title_callback for the view.group_members.page_group_members route.
   *
   * @param object $group
   *   The group ID.
   *
   * @return string
   *   The page title.
   */
  public function groupMembersTitle($group) {
    // If it's not a group then it's a gid.
    if (!$group instanceof Group) {
      $group = Group::load($group);
    }
    return $this->t('Members of @name', ['@name' => $group->label()]);
  }

  /**
   * The _title_callback for the view.posts.block_stream_group route.
   *
   * @param object $group
   *   The group ID.
   *
   * @return string
   *   The page title.
   */
  public function groupStreamTitle($group) {
    $group_label = $group->label();
    return $group_label;
  }

  /**
   * Callback function of the stream page of a group.
   *
   * @return array
   *   A renderable array.
   */
  public function groupStream() {
    return [
      '#markup' => '',
    ];
  }

  /**
   * The title callback for the entity.group_content.add_form.
   *
   * @return string
   *   The page title.
   */
  public function groupAddMemberTitle() {
    return $this->t('Add members');
  }

  /**
   * The title callback for the entity.group_content.delete-form.
   *
   * @return string
   *   The page title.
   */
  public function groupRemoveContentTitle() {
    return $this->t('Remove a member');
  }

  /**
   * Function that checks access on the my topic pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account we need to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If access is allowed.
   */
  public function myGroupAccess(AccountInterface $account) {
    // Fetch user from url.
    $user = $this->requestStack->getCurrentRequest()->get('user');
    // If we don't have a user in the request, assume it's my own profile.
    if (is_null($user)) {
      // Usecase is the user menu, which is generated on all LU pages.
      $user = User::load($account->id());
    }

    // If not a user then just return neutral.
    if (!$user instanceof User) {
      $user = User::load($user);

      if (!$user instanceof User) {
        return AccessResult::neutral();
      }
    }

    // Own profile?
    if ($user->id() === $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'view groups on my profile');
    }
    return AccessResult::allowedIfHasPermission($account, 'view groups on other profiles');
  }

  /**
   * OtherGroupPage.
   *
   * @return RedirectResponse
   *   Return Redirect to the group account.
   */
  public function otherGroupPage($group) {
    return $this->redirect('entity.group.canonical', ['group' => $group]);
  }

  /**
   * AJAX callback to update selection (multipage).
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function updateSelection($view_id, $display_id, Request $request) {
    $view_data = $this->getTempstoreData($view_id, $display_id);
    if (empty($view_data)) {
      throw new NotFoundHttpException();
    }

    // If the group id doesn't match.
    // We reset the selection and update the group.
    if ($view_id === 'group_manage_members') {
      $group_id = $request->attributes->get('group');
      if (!empty($group_id) && !empty($view_data['group_id'])) {
        if ($group_id !== $view_data['group_id']) {
          $view_data['list'] = [];
          $view_data['group_id'] = $group_id;
          $view_data['total_results'] = 0;
        }
      }
    }

    // All borrowed from ViewsBulkOperationsController.php.
    $list = $request->request->get('list');

    $op = $request->request->get('op', 'check');
    // Reverse operation when in exclude mode.
    if (!empty($view_data['exclude_mode'])) {
      if ($op === 'add') {
        $op = 'remove';
      }
      elseif ($op === 'remove') {
        $op = 'add';
      }
    }

    switch ($op) {
      case 'add':
        foreach ($list as $bulkFormKey) {
          if (!isset($view_data['list'][$bulkFormKey])) {
            $view_data['list'][$bulkFormKey] = $this->getListItem($bulkFormKey);
          }
        }
        break;

      case 'remove':
        foreach ($list as $bulkFormKey) {
          if (isset($view_data['list'][$bulkFormKey])) {
            unset($view_data['list'][$bulkFormKey]);
          }
        }
        break;

      case 'method_include':
        unset($view_data['exclude_mode']);
        $view_data['list'] = [];
        break;

      case 'method_exclude':
        $view_data['exclude_mode'] = TRUE;
        $view_data['list'] = [];
        break;
    }

    $this->setTempstoreData($view_data);

    $count = empty($view_data['exclude_mode']) ? count($view_data['list']) : $view_data['total_results'] - count($view_data['list']);

    $response = new AjaxResponse();
    $response->setData(['count' => $count]);
    return $response;
  }

}
