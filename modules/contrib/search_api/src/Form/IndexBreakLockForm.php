<?php

namespace Drupal\search_api\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to break the lock of an edited search index.
 */
class IndexBreakLockForm extends EntityConfirmFormBase {

  /**
   * The shared temporary storage for unsaved search indexes.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an IndexBreakLockForm object.
   *
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The factory for shared temporary storages.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer to use.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->tempStore = $temp_store_factory->get('search_api_index');
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $temp_store_factory = $container->get('user.shared_tempstore');
    $entity_type_manager = $container->get('entity_type.manager');
    $renderer = $container->get('renderer');

    return new static($temp_store_factory, $entity_type_manager, $renderer);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_index_break_lock_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to break the lock on search index %name?', array('%name' => $this->entity->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $locked = $this->tempStore->getMetadata($this->entity->id());
    $account = $this->entityTypeManager->getStorage('user')->load($locked->owner);
    $username = array(
      '#theme' => 'username',
      '#account' => $account,
    );
    return $this->t('By breaking this lock, any unsaved changes made by @user will be lost.', array('@user' => $this->renderer->render($username)));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('fields');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Break lock');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->tempStore->getMetadata($this->entity->id())) {
      $form['message']['#markup'] = $this->t('There is no lock on search index %name to break.', array('%name' => $this->entity->id()));
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tempStore->delete($this->entity->id());
    $form_state->setRedirectUrl($this->entity->toUrl('fields'));
    drupal_set_message($this->t('The lock has been broken and you may now edit this search index.'));
  }

}
