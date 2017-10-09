<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PostBlock' block.
 *
 * @Block(
 *  id = "post_block",
 *  admin_label = @Translation("Post block"),
 * )
 */
class PostBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public $entityType;
  public $bundle;
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
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountProxy $currentUser, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'default';
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->formBuilder = $formBuilder;
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
      $container->get('form_builder')
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
