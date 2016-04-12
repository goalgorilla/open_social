<?php

/**
 * @file
 * Contains \Drupal\social_post\Plugin\Block\PostBlock.
 */

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
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
class PostBlock extends BlockBase {

  public $entity_type;
  public $bundle;
  public $form_display;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity_type = 'post';
    $this->bundle = 'post';
    $this->form_display = 'default';
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return \Drupal::entityTypeManager()
      ->getAccessControlHandler($this->entity_type)
      ->createAccess($this->bundle, $account, [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = array();
    // Specify selected bundle if the entity has bundles.
    if (\Drupal::entityTypeManager()->getDefinition($this->entity_type)->hasKey('bundle')) {
      $bundle_key = \Drupal::entityTypeManager()->getDefinition($this->entity_type)->getKey('bundle');
      $values = array($bundle_key => $this->bundle);
    }

    $entity = \Drupal::entityTypeManager()
      ->getStorage($this->entity_type)
      ->create($values);

    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId(\Drupal::currentUser()->id());
    }

    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($this->entity_type . '.' . $this->bundle . '.' . $this->form_display);

    $form_object = \Drupal::entityTypeManager()
      ->getFormObject($entity->getEntityTypeId(), 'default');
    $form_object->setEntity($entity);

    $form_state = (new FormState())->setFormState(array());
    $form_state->set('form_display', $display);
    return \Drupal::formBuilder()->buildForm($form_object, $form_state);
  }

}
