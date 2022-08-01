<?php

namespace Drupal\social_core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\social_core\InviteService;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for social_core module routes.
 */
class SocialCoreController extends ControllerBase {

  use ViewsBulkOperationsFormTrait;

  /**
   * The private temporary storage factory.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The Views Bulk Operations action processor.
   */
  protected ViewsBulkOperationsActionProcessorInterface $actionProcessor;

  /**
   * The currently active route match object.
   */
  private RouteMatchInterface $routeMatch;

  /**
   * The invite service.
   */
  private InviteService $invite;

  /**
   * SocialGroupController constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $action_processor
   *   The Views Bulk Operations action processor.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\social_core\InviteService $invite
   *   The invite service.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    ViewsBulkOperationsActionProcessorInterface $action_processor,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    InviteService $invite
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->actionProcessor = $action_processor;
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->invite = $invite;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('tempstore.private'),
      $container->get('views_bulk_operations.processor'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('social_core.invite'),
    );
  }

  /**
   * Custom function for returning markup on the access denied page.
   */
  public function accessDenied(): array {
    // Get the front page URL.
    $frontpage = $this->config('system.site')->get('page.front');

    // Determine the message we want to set.
    $text = $this->t(
      "<p>You have insufficient permissions to view the page you're trying to access. There could be several reasons for this:</p><ul><li>You are trying to edit content you're not allowed to edit.</li><li>You are trying to view content (from a group) you don't have access to.</li><li>You are trying to access administration pages.</li></ul><p>Click the back button of your browser to go back where you came from or click <a href=\":url\">here</a> to go to the homepage</p>",
      [':url' => $frontpage],
    );

    // Return the message in the render array.
    return ['#markup' => $text];
  }

  /**
   * Empty page for the homepage.
   */
  public function stream(): array {
    return ['#markup' => ''];
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
  public function updateSelection(
    string $view_id,
    string $display_id,
    Request $request
  ): AjaxResponse {
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

    // If the event id doesn't match.
    // We reset the selection and update the group.
    if ($view_id === 'event_manage_enrollments') {
      // Gets overridden by GVBO in to the group argument.
      $event_id = $request->attributes->get('group');
      if (!empty($event_id) && !empty($view_data['event_id'])) {
        if ($event_id !== $view_data['event_id']) {
          $view_data['list'] = [];
          $view_data['event_id'] = $event_id;
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

    $count = empty($view_data['exclude_mode'])
      ? count($view_data['list'])
      : $view_data['total_results'] - count($view_data['list']);

    return (new AjaxResponse())
      ->setData([
        'count' => $count,
        'selection_info' => $this->formatPlural(
          $count,
          '<b><em class="placeholder">1</em> Member</b> is selected',
          '<b><em class="placeholder">@count</em> Members</b> are selected',
        ),
      ]);
  }

  /**
   * Redirects a user to the group or events invite page, or home if empty.
   */
  public function myInvitesUserPage(): RedirectResponse {
    /** @var string $invite */
    $invite = $this->invite->getInviteData('name');

    $route_name = !empty($invite)
      // Only when there are actual Invite plugins enabled.
      ? $invite
      // If there are no invites found or no module enabled lets redirect user
      // to their stream.
      : 'social_user.user_home';

    return $this->redirect($route_name, ['user' => $this->currentUser()->id()]);
  }

  /**
   * The _title_callback for the entity creation route.
   */
  public function addPageTitle(): string {
    $titles = $this->moduleHandler()->invokeAll('social_core_title');
    $this->moduleHandler()->alter('social_core_title', $titles);

    if (!empty($titles['node'])) {
      if (!isset($titles['node']['bundles'])) {
        $titles['node']['bundles'] = [];
      }

      $this->moduleHandler()->alterDeprecated(
        'Deprecated in social:11.4.0 and is removed from social:12.0.0. Use hook_social_core_title_alter instead. See https://www.drupal.org/node/3285045',
        'social_node_title_prefix_articles',
        $titles['node']['bundles'],
      );
    }

    if (($route_name = $this->routeMatch->getRouteName()) === NULL) {
      return '';
    }

    $route_name = explode('.', $route_name);
    $entity_type_id = $route_name[count($route_name) - 2];

    if (isset($titles[$entity_type_id]['callback'])) {
      $entity_type = $titles[$entity_type_id]['callback']();
    }
    else {
      $definition = $this->entityTypeManager()->getDefinition($entity_type_id);

      if (
        $definition !== NULL &&
        ($bundle_entity_type = $definition->getBundleEntityType()) !== NULL
      ) {
        $entity_type = $this->routeMatch->getParameter($bundle_entity_type);
      }
    }

    if (
      isset($entity_type) &&
      $entity_type instanceof EntityInterface &&
      ($label = $entity_type->label()) !== NULL
    ) {
      return $this->t('Create @article @name', [
        '@article' => $titles[$entity_type_id]['bundles'][$entity_type->id()] ?? 'a',
        '@name' => mb_strtolower($label),
      ])->render();
    }

    return '';
  }

}
