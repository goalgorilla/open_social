<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Change group membership role.
 */
#[Action(
  id: 'social_group_change_member_role_action',
  label: new TranslatableMarkup('Change group membership role'),
  type: 'group_content',
)]
class ChangeGroupMembershipRole extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ViewsBulkOperationSendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $role = $this->configuration['role'];
    $is_member = $this->configuration['is_member'];
    $update = TRUE;
    $value = [];

    /** @var \Drupal\group\Entity\GroupRelationshipInterface $entity */
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $roles */
    $roles = &$entity->get('group_roles');

    $group_manager_role = $entity->getGroup()->getGroupType()->id() . '-group_manager';

    if ($roles->isEmpty() && $is_member) {
      $update = FALSE;
    }
    elseif (!$roles->isEmpty() && !$is_member) {
      $value = $roles->getValue();

      foreach ($value as $item) {
        if ($item['target_id'] === $role) {
          if (array_search($item, $value) == 0) {
            if ($role === $group_manager_role) {
              // Set role 'Group Manager' only if user chosen 'Group Manager'.
              $entity->set('group_roles', [0 => $item])->save();
            }
          }
          else {
            // Set role 'Group Manager' only if user chosen 'Group Manager'.
            if ($role === $group_manager_role) {
              $entity->set('group_roles', [0 => $item])->save();
            }
            // Add role 'Group Admin' if user chosen 'Group Admin'.
            else {
              $entity->set('group_roles', array_reverse($value))->save();
            }
          }

          $update = FALSE;
          break;
        }
      }
    }

    if ($update) {
      if (!$is_member) {
        $new_value = ['target_id' => $role];

        // Set role 'Group Manager' only if user chosen 'Group Manager'.
        if ($role === $group_manager_role) {
          $value = $new_value;
        }
        // Add role 'Group Admin' if user chosen 'Group Admin'.
        else {
          array_unshift($value, $new_value);
        }
      }

      $entity->set('group_roles', $value)->save();
    }

    return $this->t('Change roles');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof GroupRelationshipInterface && $object->getPluginId() === 'group_membership') {
      $access = $object->access('update', $account, TRUE);
    }
    else {
      $access = AccessResult::forbidden();
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->formatPlural($this->context['selected_count'], 'Change the role of selected member', 'Change the role of @count selected members');

    $id = $this->routeMatch->getRawParameter('group');

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->entityTypeManager->getStorage('group')->load($id);

    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $group->getGroupType();

    $roles = $group_type->getRoles(FALSE);

    // In case that getMemberRoleId() and getMemberRole() have been removed
    // try to load directly.
    $id = $group_type->id() . '-member';
    $roles[$id] = $this->entityTypeManager->getStorage('group_role')->load($id);

    $markup = $this->formatPlural($this->context['selected_count'],
      'Choose which roles to assign to the member you selected',
      'Choose which roles to assign to the @count members you selected'
    );

    $form['description'] = [
      '#markup' => '<p>' . $markup . '</p>',
    ];

    $form_state->set('member_role', $id);

    /** @var \Drupal\group\Entity\GroupRoleInterface $role */
    foreach ($roles as &$role) {
      $role = $role->label();
    }

    $form['role'] = [
      '#type' => 'radios',
      '#title' => $this->t('Roles'),
      '#options' => $roles,
      '#default_value' => $id,
    ];

    unset($form['list']);

    $form['actions']['submit']['#value'] = $this->t('Change role');

    $form['actions']['submit']['#attributes']['class'] = ['button button--primary js-form-submit form-submit btn js-form-submit btn-raised btn-primary waves-effect waves-btn waves-light'];
    $form['actions']['cancel']['#attributes']['class'] = ['button button--danger btn btn-flat waves-effect waves-btn'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['is_member'] = $this->configuration['role'] === $form_state->get('member_role');
  }

}
