<?php

namespace Drupal\social_event_managers\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\node\NodeInterface;

/**
 * Defines the Enrollments Views Bulk Operations field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_views_bulk_operations_bulk_form_enrollments")
 */
class SocialEventManagersViewsBulkOperationsBulkForm extends ViewsBulkOperationsBulkForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SocialEventManagersViewsBulkOperationsBulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface $viewData
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ViewsbulkOperationsViewDataInterface $viewData,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    PrivateTempStoreFactory $tempStoreFactory,
    AccountInterface $currentUser,
    RequestStack $requestStack,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $viewData, $actionManager, $actionProcessor, $tempStoreFactory, $currentUser, $requestStack);

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkOptions() {
    $bulk_options = parent::getBulkOptions();

    if ($this->view->id() !== 'event_manage_enrollments') {
      return $bulk_options;
    }

    foreach ($bulk_options as $id => &$label) {
      if (!empty($this->options['preconfiguration'][$id]['label_override'])) {
        $real_label = $this->options['preconfiguration'][$id]['label_override'];
      }
      else {
        $real_label = $this->actions[$id]['label'];
      }

      $label = $this->t('<b>@action</b> selected enrollees', [
        '@action' => $real_label,
      ]);
    }

    return $bulk_options;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    $this->view->setExposedInput(['status' => TRUE]);

    parent::viewsForm($form, $form_state);

    if ($this->view->id() !== 'event_manage_enrollments') {
      return;
    }

    $action_options = $this->getBulkOptions();

    if (!empty($this->view->result) && !empty($action_options)) {
      $list = &$form[$this->options['id']];

      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntity($row);
        $list[$row_index]['#title'] = $this->getEntityLabel($entity);
      }
    }

    // Get pager data if available.
    if (!empty($this->view->pager) && method_exists($this->view->pager, 'hasMoreRecords')) {
      $pagerData = [
        'current' => $this->view->pager->getCurrentPage(),
        'more' => $this->view->pager->hasMoreRecords(),
      ];
    }

    $display_select_all = isset($pagerData) && ($pagerData['more'] || $pagerData['current'] > 0);

    // Select all results checkbox.
    if ($display_select_all) {
      $form['header'][$this->options['id']]['select_all'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Select all @count results in this view', [
          '@count' => $this->tempStoreData['total_results'] ? ' ' . $this->tempStoreData['total_results'] : '',
        ]),
        '#attributes' => [
          'class' => ['vbo-select-all', 'form-no-label', 'checkbox'],
        ],
      ];
    }

    $wrapper = &$form['header'][$this->options['id']];
    $wrapper['#attributes']['class'][] = 'card';
    $wrapper['#attributes']['class'][] = 'card__block';

    $form['#attached']['library'][] = 'social_event_managers/views_bulk_operations.frontUi';

    // Render page title.
    $count = isset($this->tempStoreData['list']) ? count($this->tempStoreData['list']) : 0;
    $title = $this->formatPlural($count, '<b>@count enrollee</b> is selected', '<b>@count enrollees</b> are selected');

    $wrapper['multipage']['#title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['placeholder'],
      ],
      '#value' => $title,
    ];

    $wrapper['multipage']['list']['#title'] = $this->t('See selected enrollees on other pages');

    // We don't show the multipage list if there are no items selected.
    $items = isset($wrapper['multipage']['list']['#items']) ? count($wrapper['multipage']['list']['#items']) : 0;
    if ($items < 1) {
      unset($wrapper['multipage']['list']);
    }

    $actions = &$wrapper['actions'];
    $actions['#theme'] = 'links__dropbutton__operations__actions';
    $actions['#label'] = $this->t('Actions');

    unset($actions['#type']);

    unset($wrapper['multipage']['clear']);

    $labels = [];

    foreach (Element::children($actions) as $action_id) {
      $labels[$action_id] = $actions[$action_id]['#value'];
    }

    asort($labels);

    foreach (array_keys($labels) as $weight => $action_id) {
      $actions[$action_id]['#weight'] = $weight;
    }

    $items = [];

    foreach (Element::children($actions, TRUE) as $key) {
      $items[$key] = $actions[$key];
    }

    $actions['#links'] = $items;

    $form['actions']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($this->view->id() === 'event_manage_enrollments' && $this->options['buttons']) {
      $user_input = $form_state->getUserInput();
      $actions = &$form['header'][$this->options['id']]['actions'];

      foreach (Element::children($actions) as $action_id) {
        $action = &$actions[$action_id];

        if (isset($action['#access']) && !$action['#access']) {
          continue;
        }

        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
        $label = $action['#value'];

        if (strip_tags($label->render()) === $user_input['op']) {
          $form_state->setTriggeringElement($action);
          break;
        }
      }
    }

    parent::viewsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    parent::viewsFormSubmit($form, $form_state);

    if ($form_state->get('step') === 'views_form_views_form' && $this->view->id() === 'event_manage_enrollments') {
      /** @var \Drupal\Core\Url $url */
      $url = $form_state->getRedirect();

      if ($url->getRouteName() === 'views_bulk_operations.execute_configurable') {
        $parameters = $url->getRouteParameters();

        if (empty($parameters['node'])) {
          $node = \Drupal::routeMatch()->getParameter('node');
          if ($node instanceof NodeInterface) {
            // You can get nid and anything else you need from the node object.
            $parameters['node'] = $node->id();
          }
          elseif (!is_object($node)) {
            $parameters['node'] = $node;
          }
        }

        $url = Url::fromRoute('social_event_managers.vbo.execute_configurable', [
          'node' => $parameters['node'],
        ]);

        $form_state->setRedirectUrl($url);
      }
    }
  }

  /**
   * Returns modified entity label.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The label text.
   */
  public function getEntityLabel(EntityInterface $entity) {
    $profiles = $this->entityTypeManager->getStorage('profile')
      ->loadByProperties([
        'uid' => $entity->field_account->target_id,
      ]);

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = reset($profiles);

    // It must be a Guest so we pick the name values we can get.
    if (!$profile) {
      $name = '';
      $first_name = $entity->get('field_first_name')->getValue()[0]['value'];
      $last_name = $entity->get('field_last_name')->getValue()[0]['value'];
      if (!empty($first_name)) {
        $name .= $first_name;
        $name .= ' ';
      }
      if (!empty($last_name)) {
        $name .= $last_name;
      }

      return $name;
    }

    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
    $label = $profile->label();

    return $label->getArguments()['@name'];
  }

}
