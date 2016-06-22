<?php

/**
 * @file
 * Contains \Drupal\gnode\Controller\GroupNodeController.
 */

namespace Drupal\gnode\Controller;

use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides group node route controllers.
 *
 * This only controls the routes that are not supported out of the box by the
 * plugin base \Drupal\group\Plugin\GroupContentEnablerBase.
 */
class GroupNodeController extends ControllerBase {

  /**
   * The private store for temporary group nodes.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new GroupNodeController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user, RequestStack $request_stack) {
    $this->privateTempStore = $temp_store_factory->get('gnode_add_temp');
    $this->currentUser = $current_user;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * Provides the form for creating a node in a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a node in.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type to create.
   *
   * @return array
   *   The form array for either step 1 or 2 of the group node creation wizard.
   */
  public function add(GroupInterface $group, NodeTypeInterface $node_type) {
    $plugin_id = 'group_node:' . $node_type->id();
    $storage_id = $plugin_id . ':' . $group->id();

    // If we are on step one, we need to build a node form.
    if ($this->privateTempStore->get("$storage_id:step") !== 2) {
      $this->privateTempStore->set("$storage_id:step", 1);

      // Only create a new node if we have nothing stored.
      if (!$entity = $this->privateTempStore->get("$storage_id:node")) {
        $entity = Node::create(['type' => $node_type->id()]);
      }
    }
    // If we are on step two, we need to build a group content form.
    else {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
      $entity = GroupContent::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
      ]);
    }

    // Return the form with the group and storage ID added to the form state.
    $extra = ['group' => $group, 'storage_id' => $storage_id];
    return $this->entityFormBuilder()->getForm($entity, 'gnode-form', $extra);
  }

  /**
   * The _title_callback for the add node form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a node in.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type to create.
   *
   * @return string
   *   The page title.
   */
  public function addTitle(GroupInterface $group, NodeTypeInterface $node_type) {
    return $this->t('Create %type in %label', ['%type' => $node_type->label(), '%label' => $group->label()]);
  }

  /**
   * Displays add content links for available group node types.
   *
   * Redirects to group/{group}/node/add/{node_type} if only one group node type
   * is available.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add a node to.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added. However,
   *   if there is only one node type available to the user, the function will
   *   return a RedirectResponse to the node add page for that node type.
   */
  public function addPage(GroupInterface $group) {
    $plugins = $group->getGroupType()->getInstalledContentPlugins();

    $node_type_ids = [];
    foreach ($plugins as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      list($base_plugin_id, $derivative_id) = explode(':', $plugin->getPluginId() . ':');

      // Only show the node types the user has access to.
      if ($base_plugin_id == 'group_node' && $plugin->createAccess($group, $this->currentUser)) {
        $node_type_ids[] = $derivative_id;
      }
    }

    // Bypass the page if only one content type is available.
    if (count($node_type_ids) == 1) {
      $node_type_id = reset($node_type_ids);
      $plugin = $group->getGroupType()->getContentPlugin("group_node:$node_type_id");
      return $this->redirect($plugin->getRouteName('add-form'), ['group' => $group->id()]);
    }

    return [
      '#theme' => 'gnode_add_list',
      '#group' => $group,
      '#node_types' => NodeType::loadMultiple($node_type_ids),
    ];
  }

  /**
   * Displays create content links for available group node types.
   *
   * Redirects to group/{group}/node/create/{node_type} if only one group node
   * type is available.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a node in.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be created. However,
   *   if there is only one node type available to the user, the function will
   *   return a RedirectResponse to the node create page for that node type.
   */
  public function createPage(GroupInterface $group) {
    $plugins = $group->getGroupType()->getInstalledContentPlugins();

    $node_type_ids = [];
    foreach ($plugins as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      list($base_plugin_id, $derivative_id) = explode(':', $plugin->getPluginId() . ':');

      // Only show the node types the user has access to.
      if ($base_plugin_id == 'group_node' && $group->hasPermission("create $derivative_id node", $this->currentUser)) {
        $node_type_ids[] = $derivative_id;
      }
    }

    // Bypass the page if only one content type is available.
    if (count($node_type_ids) == 1) {
      $node_type_id = reset($node_type_ids);
      $plugin = $group->getGroupType()->getContentPlugin("group_node:$node_type_id");
      return $this->redirect($plugin->getRouteName('create-form'), ['group' => $group->id()]);
    }

    return [
      '#theme' => 'gnode_create_list',
      '#group' => $group,
      '#node_types' => NodeType::loadMultiple($node_type_ids),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Overwritten to pass on the URL redirect parameter instead of following it.
   */
  protected function redirect($route_name, array $route_parameters = [], array $options = [], $status = 302) {
    $options['query'] = $this->currentRequest->query->all();
    $this->currentRequest->query->remove('destination');
    return parent::redirect($route_name, $route_parameters, $options, $status);
  }

}
