<?php

/**
 * @file
 * Contains \Drupal\node\Form\DeleteMultiple.
 */

namespace Drupal\profile\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a node deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of nodes to delete.
   *
   * @var array
   */
  protected $profiles = [];

  /**
   * The private_tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('profile');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'profile_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \Drupal::translation()->formatPlural(count($this->profiles), 'Are you sure you want to delete this profile?', 'Are you sure you want to delete these profiles?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.profile.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->profiles = $this->privateTempStoreFactory->get('profile_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->profiles)) {
      return new RedirectResponse(\Drupal::url('admin/config/people/profiles', ['absolute' => TRUE]));
    }

    $form['profiles'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function ($profile) {
        return $profile->label();
      }, $this->profiles),
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->profiles)) {
      $this->storage->delete($this->profiles);
      $this->privateTempStoreFactory->get('profile_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
      $count = count($this->profiles);
      $this->logger('content')->notice('Deleted @count profiles.', ['@count' => $count]);
      drupal_set_message(\Drupal::translation()->formatPlural($count, 'Deleted 1 profile.', 'Deleted @count profiles.'));
    }
    $form_state->setRedirect('entity.profile.collection');
  }

}
