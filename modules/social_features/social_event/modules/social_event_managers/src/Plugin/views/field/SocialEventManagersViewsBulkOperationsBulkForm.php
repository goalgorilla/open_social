<?php

namespace Drupal\social_event_managers\Plugin\views\field;

use Drupal\Core\Action\ActionManager;
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
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface;
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
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * Constructs a new SocialEventManagersViewsBulkOperationsBulkForm object.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Action\ActionManager $pluginActionManager
   *   The action manager.
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
    EntityTypeManagerInterface $entity_type_manager,
    ActionManager $pluginActionManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $viewData, $actionManager, $actionProcessor, $tempStoreFactory, $currentUser, $requestStack);

    $this->entityTypeManager = $entity_type_manager;
    $this->actionManager = $pluginActionManager;
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
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.action')
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

    foreach ($this->options['selected_actions'] as $key => $selected_action_data) {
      $definition = $this->actions[$selected_action_data['action_id']];
      if (!empty($selected_action_data['preconfiguration']['label_override'])) {
        $real_label = $selected_action_data['preconfiguration']['label_override'];
      }
      else {
        $real_label = $definition['label'];
      }

      $bulk_options[$key] = $this->t('<b>@action</b> selected enrollees', [
        '@action' => $real_label,
      ]);
    }

    // Check access and return.
    return $this->bulkOptionAccess($bulk_options);
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

    $event = social_event_get_current_event();
    if (!$event) {
      return;
    }
    $tempstoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);

    // Make sure the selection is saved for the current event.
    if (!empty($tempstoreData['event_id']) && $tempstoreData['event_id'] !== $event->id()) {
      // If not we clear it right away.
      // Since we don't want to mess with cached date.
      $this->deleteTempstoreData($this->view->id(), $this->view->current_display);

      // Calculate bulk form keys.
      $bulk_form_keys = [];
      if (!empty($this->view->result)) {
        $base_field = $this->view->storage->get('base_field');
        foreach ($this->view->result as $row_index => $row) {
          if ($entity = $this->getEntity($row)) {
            $bulk_form_keys[$row_index] = self::calculateEntityBulkFormKey(
              $entity,
              $row->{$base_field}
            );
          }
        }
      }
      // Reset initial values.
      if (
        empty($form_state->getUserInput()['op']) &&
        !empty($bulk_form_keys)
      ) {
        $this->updateTempstoreData($bulk_form_keys);
      }
      else {
        $this->updateTempstoreData();
      }

      // Initialize it again.
      $tempstoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);
    }
    // Add the Event ID to the data.
    $tempstoreData['event_id'] = $event->id();
    $this->setTempstoreData($tempstoreData, $this->view->id(), $this->view->current_display);

    // Reorder the form array.
    if (!empty($form['header'])) {
      $multipage = $form['header'][$this->options['id']]['multipage'];
      unset($form['header'][$this->options['id']]['multipage']);
      $form['header'][$this->options['id']]['multipage'] = $multipage;
    }

    // Render proper classes for the header in VBO form.
    $wrapper = &$form['header'][$this->options['id']];

    if (!empty($event->id())) {
      $wrapper['multipage']['#attributes']['event-id'] = $event->id();
      if (!empty($wrapper['multipage']['#attributes']['data-display-id'])) {
        $current_display = $wrapper['multipage']['#attributes']['data-display-id'];
        $wrapper['multipage']['#attributes']['data-display-id'] = $current_display . '/' . $event->id();
      }
    }

    // Styling related for the wrapper div.
    $wrapper['#attributes']['class'][] = 'card';
    $wrapper['#attributes']['class'][] = 'card__block';
    $form['#attached']['library'][] = 'social_event_managers/views_bulk_operations.frontUi';

    // Render select all results checkbox.
    if (!empty($wrapper['select_all'])) {
      $wrapper['select_all']['#title'] = $this->t('Select / unselect all @count members across all the pages', [
        '@count' => $this->tempStoreData['total_results'] ? ' ' . $this->tempStoreData['total_results'] : '',
      ]);
      // Styling attributes for the select box.
      $form['header'][$this->options['id']]['select_all']['#attributes']['class'][] = 'form-no-label';
      $form['header'][$this->options['id']]['select_all']['#attributes']['class'][] = 'checkbox';
    }

    $count = 0;
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $title */
    if (!empty($wrapper['multipage']) && !empty($wrapper['multipage']['#title'])) {
      $title = $wrapper['multipage']['#title'];
      $arguments = $title->getArguments();
      $count = empty($arguments['%count']) ? 0 : $arguments['%count'];
    }
    $title = $this->formatPlural($count, '<b><em class="placeholder">@count</em> enrollee</b> is selected', '<b><em class="placeholder">@count</em> enrollees</b> are selected');
    $wrapper['multipage']['#title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $title,
    ];

    // Add selector so the JS of VBO applies correctly.
    $wrapper['multipage']['#attributes']['class'][] = 'vbo-multipage-selector';

    // Get tempstore data so we know what messages to show based on the data.
    $tempstoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);
    if (!empty($wrapper['multipage']['list']['#items']) && count($wrapper['multipage']['list']['#items']) > 0) {
      $excluded = FALSE;
      if (isset($tempstoreData['exclude_mode']) && $tempstoreData['exclude_mode']) {
        $excluded = TRUE;
      }
      $wrapper['multipage']['list']['#title'] = !$excluded ? $this->t('See selected enrollees on other pages') : $this->t('See excluded enrollees on other pages');
    }

    // Update the clear submit button.
    if (!empty($wrapper['multipage']['clear'])) {
      $wrapper['multipage']['clear']['#value'] = $this->t('Clear all selected enrollees');
      $wrapper['multipage']['clear']['#attributes']['class'][] = 'btn-default dropdown-toggle waves-effect waves-btn margin-top-l margin-left-m';
    }

    $actions = &$wrapper['actions'];
    if (!empty($actions) && !empty($wrapper['action'])) {
      $actions['#theme'] = 'links__dropbutton__operations__actions';
      $actions['#label'] = $this->t('Actions');
      $actions['#type'] = 'dropbutton';

      $items = [];
      foreach ($wrapper['action']['#options'] as $key => $value) {
        if ($key !== '' && array_key_exists($key, $this->bulkOptions)) {
          $items[] = [
            '#type' => 'submit',
            '#value' => $value,
          ];
        }
      }

      // Add our links to the dropdown buttondrop type.
      $actions['#links'] = $items;
    }

    // Remove the Views select list and submit button.
    $form['actions']['#type'] = 'hidden';
    $form['header']['social_views_bulk_operations_bulk_form_enrollments_1']['action']['#access'] = FALSE;
    // Hide multipage list.
    $form['header']['social_views_bulk_operations_bulk_form_enrollments_1']['multipage']['list']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($this->view->id() === 'event_manage_enrollments') {
      $user_input = $form_state->getUserInput();
      $available_options = $this->getBulkOptions();
      $selected_actions = $this->options['selected_actions'];
      // Grab all the actions that are available.
      foreach (Element::children($this->actions) as $action) {

        // Combine both arrays elements.
        $array_combine = (array) array_combine(
          array_keys($selected_actions),
          array_column($selected_actions, 'action_id')
        );

        // Get the action key.
        $action_key = array_search($action, array_filter($array_combine));

        // If the option is not in our selected options, next.
        if ($action_key === FALSE) {
          continue;
        }

        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
        $label = $available_options[$action_key];

        // Match the Users action from our custom dropdown.
        // Find the action from the VBO selection.
        // And set that as the chosen action in the form_state.
        if (strip_tags($label->render()) === $user_input['op']) {
          $user_input['action'] = $action_key;
          $form_state->setUserInput($user_input);
          $form_state->setValue('action', $action_key);
          $form_state->setTriggeringElement($this->actions[$action]);
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

    $profile = reset($profiles);

    // It must be a Guest, so we pick the name values we can get.
    if (!$profile) {
      $name = '';
      if (!empty($first_name = $entity->get('field_first_name')->getValue())
        && !empty($first_name[0]['value'])
      ) {
        $name .= $first_name[0]['value'];
        $name .= ' ';
      }
      if (!empty($last_name = $entity->get('field_last_name')->getValue())
        && !empty($last_name[0]['value'])
      ) {
        $name .= $last_name[0]['value'];
      }

      return trim($name);
    }

    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
    $label = $profile->label();

    return $label->getArguments()['@name'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTempstoreData($view_id = NULL, $display_id = NULL) {
    $data = parent::getTempstoreData($view_id, $display_id);

    if (is_array($data) && $data) {
      if ($view_id && !isset($data['view_id'])) {
        $data['view_id'] = $view_id;
      }

      if ($display_id && !isset($data['display_id'])) {
        $data['display_id'] = $display_id;
      }
    }

    return $data;
  }

  /**
   * Removes all bulk options that user don't have access to it.
   *
   * @param array $bulkOptions
   *   Array of bulk options.
   *
   * @return array
   *   Returns array of bulk options.
   */
  protected function bulkOptionAccess(array $bulkOptions) {
    /** @var \Drupal\node\NodeInterface $event */
    $event = social_event_get_current_event();
    $isEventOrganizer = social_event_manager_or_organizer($event);

    // Event organizers have all permissions.
    if ($isEventOrganizer) {
      return $bulkOptions;
    }

    // Get the user enrollment.
    $eventEnrollment = $this->entityTypeManager->getStorage('event_enrollment')->loadByProperties([
      'user_id' => $this->currentUser()->id(),
      'field_event' => $event->id(),
    ]);
    $eventEnrollment = end($eventEnrollment);

    // If the user is not enrolled, then they should not see
    // any operations at all.
    if (!$eventEnrollment) {
      return [];
    }

    // Load each action and check the access.
    foreach ($bulkOptions as $id => $name) {
      $action_id = $this->options['selected_actions'][$id]['action_id'];
      if ($this->actionManager->hasDefinition($action_id)) {
        /** @var \Drupal\Core\Action\ActionInterface $action */
        $action = $this->actionManager->createInstance($action_id);
        // Check the access.
        /** @var bool $access */
        $access = $action->access($eventEnrollment, $this->currentUser);
        if (!$access) {
          unset($bulkOptions[$id]);
        }
      }
    }

    return $bulkOptions;
  }

}
