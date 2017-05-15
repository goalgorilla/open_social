<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides a 'PostPhotoBlock' block.
 *
 * @Block(
 *  id = "post_photo_block",
 *  admin_label = @Translation("Post photo block"),
 * )
 */
class PostPhotoBlock extends BlockBase {

  public $entityType;
  public $bundle;
  public $formDisplay;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = 'post';
    $this->bundle = 'photo';
    $this->formDisplay = 'default';
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return \Drupal::entityTypeManager()
      ->getAccessControlHandler($this->entityType)
      ->createAccess($this->bundle, $account, [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = array();
    // Specify selected bundle if the entity has bundles.
    if (\Drupal::entityTypeManager()->getDefinition($this->entityType)->hasKey('bundle')) {
      $bundle_key = \Drupal::entityTypeManager()->getDefinition($this->entityType)->getKey('bundle');
      $values = array($bundle_key => $this->bundle);
    }

    $entity = \Drupal::entityTypeManager()
      ->getStorage($this->entityType)
      ->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId(\Drupal::currentUser()->id());
    }

    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($this->entityType . '.' . $this->bundle . '.' . $this->formDisplay);

    $form_object = \Drupal::entityTypeManager()
      ->getFormObject($entity->getEntityTypeId(), 'default');
    $form_object->setEntity($entity);

    $form_state = (new FormState())->setFormState(array());
    $form_state->set('form_display', $display);
    return \Drupal::formBuilder()->buildForm($form_object, $form_state);
  }

}
