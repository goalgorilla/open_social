<?php

namespace Drupal\gvbo\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\gvbo\Access\GroupViewsBulkOperationsAccessTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the Groups Views Bulk Operations field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_views_bulk_operations_bulk_form")
 */
class GroupViewsBulkOperationsBulkForm extends ViewsBulkOperationsBulkForm {

  use GroupViewsBulkOperationsAccessTrait;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new GroupViewsBulkOperationsBulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface $viewData
   *   The VBO View Data provider service.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The currently active route match object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ViewsBulkOperationsViewDataInterface $viewData,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    PrivateTempStoreFactory $tempStoreFactory,
    AccountInterface $currentUser,
    RequestStack $requestStack,
    RouteMatchInterface $routeMatch
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $viewData, $actionManager, $actionProcessor, $tempStoreFactory, $currentUser, $requestStack);

    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_bulk_operations.data'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    parent::viewsForm($form, $form_state);

    $wrapper = &$form['header'][$this->options['id']];

    if (isset($wrapper['multipage'])) {
      $form['#attached']['library'][] = 'gvbo/frontUi';

      $group = $this->routeMatch->getRawParameter('group');

      if ($group) {
        $wrapper['multipage']['#attributes']['data-group-id'] = $group;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    parent::viewsFormSubmit($form, $form_state);

    $redirect = $form_state->getRedirect();

    if (!($redirect instanceof Url)) {
      return;
    }

    $current_parameters = $this->routeMatch->getParameters()->all();
    $redirect_parameters = $redirect->getRouteParameters();

    $required_parameters = [
      'view_id',
      'display_id',
    ];

    $valid = TRUE;

    foreach ($required_parameters as $key) {
      if (!(isset($redirect_parameters[$key]) && isset($current_parameters[$key]) && $redirect_parameters[$key] === $current_parameters[$key])) {
        $valid = FALSE;
        break;
      }
    }

    if ($valid && isset($current_parameters['group']) && $this->useGroupPermission($this->view, $redirect_parameters['display_id'])) {
      /** @var \Drupal\group\Entity\GroupInterface $entity */
      $entity = $current_parameters['group'];

      $redirect->setRouteParameter('group', $entity->id());
      $form_state->setRedirectUrl($redirect);
    }
  }

}
