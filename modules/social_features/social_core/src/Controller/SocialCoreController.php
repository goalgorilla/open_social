<?php

namespace Drupal\social_core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\node\NodeTypeInterface;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for social_core module routes.
 */
class SocialCoreController extends ControllerBase {

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
   * SocialGroupController constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   Private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory, ViewsBulkOperationsActionProcessorInterface $actionProcessor) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->actionProcessor = $actionProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('views_bulk_operations.processor')
    );
  }

  /**
   * Custom function for returning markup on the access denied page.
   */
  public function accessDenied() {
    // Get the front page URL.
    $frontpage = $this->config('system.site')->get('page.front');

    // Determine the message we want to set.
    $text = $this->t("<p>You have insufficient permissions to view the page you're trying to access. There could be several reasons for this:</p><ul><li>You are trying to edit content you're not allowed to edit.</li><li>You are trying to view content (from a group) you don't have access to.</li><li>You are trying to access administration pages.</li></ul><p>Click the back button of your browser to go back where you came from or click <a href=\":url\">here</a> to go to the homepage</p>", [':url' => $frontpage]);

    // Return the message in the render array.
    return ['#markup' => $text];
  }

  /**
   * Empty page for the homepage.
   */
  public function stream() {
    $element = [
      '#markup' => '',
    ];
    return $element;
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

    // If the event id doesn't match.
    // We reset the selection and update the group.
    if ($view_id === 'event_manage_enrollments') {
      // Get's overridden by GVBO in to the group argument.
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

    $count = empty($view_data['exclude_mode']) ? count($view_data['list']) : $view_data['total_results'] - count($view_data['list']);

    $response = new AjaxResponse();
    $response->setData(['count' => $count]);
    return $response;
  }

  /**
   * Redirects a user to the group or events invite page, or home if empty.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the events of the currently logged in user.
   */
  public function myInvitesUserPage() {
    /** @var \Drupal\social_core\InviteService $core_invites */
    $core_invites = \Drupal::service('social_core.invite');
    // Only when there are actual Invite plugins enabled.
    if (!empty($core_invites->getInviteData('name'))) {
      return $this->redirect($core_invites->getInviteData('name'), [
        'user' => $this->currentUser()->id(),
      ]);
    }

    // If there are no invites found or no module enabled
    // lets redirect user to their stream.
    return $this->redirect('social_user.user_home', [
      'user' => $this->currentUser()->id(),
    ]);
  }

  /**
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(NodeTypeInterface $node_type) {
    // The node_types that have a different article than a.
    $node_types = [
      'event' => 'an',
    ];

    // Make sure extensions can change this as well.
    \Drupal::moduleHandler()->alter('social_node_title_prefix_articles', $node_types);

    if ($node_type !== NULL && array_key_exists($node_type->id(), $node_types)) {
      return $this->t('Create @article @name', [
        '@article' => $node_types[$node_type->id()],
        '@name' => $node_type->label(),
      ]);
    }

    return $this->t('Create a @name', ['@name' => $node_type->label()]);
  }

}
