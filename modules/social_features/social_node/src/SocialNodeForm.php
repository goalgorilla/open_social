<?php

namespace Drupal\social_node;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\node\NodeForm;
use Drupal\social_node\Service\SocialNodeMessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the node edit forms.
 */
class SocialNodeForm extends NodeForm {

  /**
   * Constructs a NodeForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\social_node\Service\SocialNodeMessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    PrivateTempStoreFactory $temp_store_factory,
    EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    TimeInterface $time = NULL,
    AccountInterface $current_user,
    DateFormatterInterface $date_formatter,
    SocialNodeMessengerInterface $messenger
  ) {
    parent::__construct(
      $entity_repository,
      $temp_store_factory,
      $entity_type_bundle_info,
      $time,
      $current_user,
      $date_formatter
    );

    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('tempstore.private'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('social_node.messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = clone $this->entity;

    $this->messenger()->setNode($node);

    parent::save($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ContentEntityInterface {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->buildEntity($form, $form_state);

    // Get visibility options.
    $visibilities = $form['field_content_visibility']['widget']['#options'];

    // Check if the values are being altered while it's disabled.
    foreach ($visibilities as $visibility => $value) {
      if (isset($form['field_content_visibility']['widget'][$visibility]['#disabled'])
        && $form['field_content_visibility']['widget'][$visibility]['#disabled'] === TRUE
        && $form_state->getValue('field_content_visibility')[0]['value'] === $visibility) {
        $form_state->setErrorByName('field_content_visibility', t('@visibility visibility is not allowed', ['@visibility' => $visibility]));
      }
    }

    return $entity;
  }

  /**
   * Gets the messenger.
   *
   * @return \Drupal\social_node\Service\SocialNodeMessengerInterface
   *   The messenger.
   */
  public function messenger() {
    if (!isset($this->messenger)) {
      $this->messenger = \Drupal::service('social_node.messenger');
    }

    return $this->messenger;
  }

}
