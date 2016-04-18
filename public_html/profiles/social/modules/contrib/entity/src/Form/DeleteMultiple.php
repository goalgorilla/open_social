<?php

/**
 * @file
 * Contains \Drupal\entity\Form\DeleteMultiple.
 */

namespace Drupal\entity\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an entities deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The selection, in the entity_id => langcodes format.
   *
   * @var array
   */
  protected $selection = [];

  /**
   * Constructs a new DeleteMultiple object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStore = $temp_store_factory->get('entity_delete_multiple_confirm');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_delete_multiple_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->selection), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.' . $this->entityTypeId . '.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $entity_type_id
   *   The entity type id.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->entityTypeId = $entity_type_id;
    $this->selection = $this->tempStore->get($this->currentUser->id());
    if (empty($this->entityTypeId) || empty($this->selection)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $storage->loadMultiple(array_keys($this->selection));
    $items = [];
    foreach ($this->selection as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $entity = $entities[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $entity->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $entity->getTranslationLanguages();
        if (count($languages) > 1 && $entity->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following translations will be deleted:</em>', ['@label' => $entity->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $entity->label();
        }
      }
    }

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $total_count = 0;
    $delete_entities = [];
    $delete_translations = [];
    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $storage->loadMultiple(array_keys($this->selection));

    foreach ($this->selection as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $entity = $entities[$id]->getTranslation($langcode);
        if ($entity->isDefaultTranslation()) {
          $delete_entities[$id] = $entity;
          unset($delete_translations[$id]);
          $total_count += count($entity->getTranslationLanguages());
        }
        elseif (!isset($delete_entities[$id])) {
          $delete_translations[$id][] = $entity;
        }
      }
    }

    if ($delete_entities) {
      $storage->delete($delete_entities);
      $this->logger('content')->notice('Deleted @count @entity_type items.', [
        '@count' => count($delete_entities),
        '@entity_type' => $this->entityTypeId,
      ]);
    }

    if ($delete_translations) {
      $count = 0;
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      foreach ($delete_translations as $id => $translations) {
        $entity = $entities[$id]->getUntranslated();
        foreach ($translations as $translation) {
          $entity->removeTranslation($translation->language()->getId());
        }
        $entity->save();
        $count += count($translations);
      }
      if ($count) {
        $total_count += $count;
        $this->logger('content')->notice('Deleted @count @entity_type translations.', [
          '@count' => $count,
          '@entity_type' => $this->entityTypeId,
        ]);
      }
    }

    if ($total_count) {
      drupal_set_message($this->formatPlural($total_count, 'Deleted 1 item.', 'Deleted @count items.'));
    }
    $this->tempStore->delete($this->currentUser->id());
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
