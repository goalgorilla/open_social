<?php

namespace Drupal\social_queue_storage\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Queue storage entity edit forms.
 *
 * @ingroup social_queue_storage
 */
class QueueStorageEntityForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntity $entity */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Possibility to add additional data to the entity upon saving.
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    // Add status message depending on the created state of the entity.
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Queue storage entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Queue storage entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.queue_storage_entity.canonical', ['queue_storage_entity' => $entity->id()]);
  }

}
