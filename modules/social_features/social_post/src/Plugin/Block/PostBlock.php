<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PostBlock' block.
 *
 * @Block(
 *   id = "post_block",
 *   admin_label = @Translation("Post block"),
 * )
 */
class PostBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type ID.
   *
   * @var string
   */
  public $entityType;

  /**
   * The bundle.
   *
   * @var string
   */
  public $bundle;

  /**
   * The form display.
   *
   * @var string
   */
  public $formDisplay;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * PostBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    FormBuilderInterface $form_builder,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'default';
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;

    if ($module_handler->moduleExists('social_post_photo')) {
      $this->bundle = 'photo';
    }
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
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return $this->entityTypeManager
      ->getAccessControlHandler($this->entityType)
      ->createAccess($this->bundle, $account, [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = [];
    // Specify selected bundle if the entity has bundles.
    if ($this->entityTypeManager->getDefinition($this->entityType)->hasKey('bundle')) {
      $bundle_key = $this->entityTypeManager->getDefinition($this->entityType)->getKey('bundle');
      $values = [$bundle_key => $this->bundle];
    }

    $entity = $this->entityTypeManager
      ->getStorage($this->entityType)
      ->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($this->currentUser->id());
    }

    $display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($this->entityType . '.' . $this->bundle . '.' . $this->formDisplay);

    $form_object = $this->entityTypeManager
      ->getFormObject($entity->getEntityTypeId(), 'default');
    $form_object->setEntity($entity);

    $form_state = (new FormState())->setFormState([]);
    $form_state->set('form_display', $display);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
