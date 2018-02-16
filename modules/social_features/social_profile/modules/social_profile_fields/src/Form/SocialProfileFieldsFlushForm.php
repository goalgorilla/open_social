<?php

namespace Drupal\social_profile_fields\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\profile\ProfileStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides confirmation form for resetting a vocabulary to alphabetical order.
 */
class SocialProfileFieldsFlushForm extends ConfirmFormBase {


  /**
   * @var \Drupal\profile\ProfileStorage
   */
  protected $profileStorage;

  /**
   * Constructs a new ExportUserConfirm.
   *
   * @param \Drupal\profile\ProfileStorage $profiel_storage
   */
  public function __construct(ProfileStorage $profiel_storage) {
    $this->profileStorage = $profiel_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_profile_fields_flush_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Flush profile.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('social_profile_fields.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, continue');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    drupal_set_message($this->t('All data from unused fields is permanently flushed.'));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('WARNING: Flushing profile data will permanently remove data from unused fields from the database. This cannot be undone. Are you sure you want to contine?');
  }

}
