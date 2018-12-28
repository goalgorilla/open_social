<?php

namespace Drupal\social_event_managers\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the Views Bulk Operations field plugin.
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
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
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
      $container->get('user.private_tempstore'),
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

      $label = $this->t('<b>@action</b> selected members', [
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

    $form['#attached']['library'][] = 'social_group/views_bulk_operations.frontUi';

    if (isset($this->tempStoreData['list'])) {
      $count = count($this->tempStoreData['list']);
    }
    else {
      $form['output']['#access'] = FALSE;
      $count = 0;
    }

    if ($count) {
      $title = $this->formatPlural($count, '<b>@count Member</b> is selected', '<b>@count Members</b> are selected');
    }
    else {
      $title = $this->t('<b>no members</b> are selected', [
        '@count' => $count,
      ]);
    }

    $wrapper['multipage']['#title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['placeholder'],
      ],
      '#value' => $title,
    ];

    $wrapper['multipage']['list']['#title'] = $this->t('See selected members on other pages');

    $actions = &$wrapper['actions'];
    $actions['#theme'] = 'links__dropbutton__operations__actions';
    $actions['#label'] = $this->t('Actions');

    unset($actions['#type']);

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

    if ($form_state->get('step') === 'views_form_views_form' && $this->view->id() === 'group_manage_members') {
      /** @var \Drupal\Core\Url $url */
      $url = $form_state->getRedirect();

      if ($url->getRouteName() === 'views_bulk_operations.execute_configurable') {
        $parameters = $url->getRouteParameters();

        $url = Url::fromRoute('social_group.views_bulk_operations.execute_configurable', [
          'group' => $parameters['group'],
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

    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
    $label = $profile->label();

    return $label->getArguments()['@name'];
  }

}
