<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

/**
 * Change group membership role.
 *
 * @Action(
 *   id = "social_group_add_members_to_group_action",
 *   label = @Translation("Add members to group"),
 *   type = "user",
 *   confirm = TRUE,
 * )
 */
class AddMembersToGroup extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * The group storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->storage = $entity_type_manager->getStorage('group');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Load the Group.
    $group = Group::load($this->configuration['groups']);

    if (NULL !== $group) {
      // Check if user already is a member.
      $is_member = $group->getMember($entity);

      // If that is not the case we can add it to the group.
      if (!$is_member) {
        $group->addMember($entity);

        return $this->t('Amount of users added to group');
      }

      // Return this when user is already a member.
      return $this->t('Amount of existing members');
    }

    // Fail safe if something went wrong.
    return $this->t('Amount of users added not added');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // There is no permission to check if a user is able to be "added" to a
    // group. Joining doesn't cover it since we also want people to be able
    // to be added to a Secret/Closed group.
    // @todo when Invite people to a group or Add members to a group permission
    // lands add it.
    if ($object instanceof User) {
      $access = AccessResult::allowed();
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
    $form['#title'] = $this->formatPlural($this->context['selected_count'], 'Add selected member to a group', 'Add @count selected members to a group');

    $groups = Group::loadMultiple(social_group_get_all_groups());

    $options = [];
    // Grab all the groups, sorted by group type for the select list.
    foreach ($groups as $group) {
      /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
      $group_type = $group->getGroupType();
      $options[$group_type->label()][$group->id()] = $group->label();
    }

    $markup = $this->formatPlural($this->context['selected_count'],
      'Please select the group you want to add the member you have selected to',
      'Please select the group you want to add the @count members you have selected to'
    );

    $form['description'] = [
      '#markup' => $markup,
    ];

    // Empty the options so we don't have a massive list of users.
    unset($form['list']);

    $form['groups'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#options' => $options,
    ];

    $form['actions']['submit']['#value'] = $this->t('Add to Group');

    $form['actions']['submit']['#attributes']['class'] = ['button button--primary js-form-submit form-submit btn js-form-submit btn-raised btn-primary waves-effect waves-btn waves-light'];
    $form['actions']['cancel']['#attributes']['class'] = ['button button--danger btn btn-flat waves-effect waves-btn'];

    return $form;
  }

}
