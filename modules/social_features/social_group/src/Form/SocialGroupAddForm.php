<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialGroupAddForm.
 *
 * GroupAddForm.
 *
 * @package Drupal\social_group\Form
 */
class SocialGroupAddForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupContentController.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'social_group_add';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($group_type = $form_state->getValue('group_type')) {
      $url = Url::fromUserInput('/group/add/' . $group_type);

      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Defines the settings form for Post entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'form--default';
    $form['group_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];
    $form['group_settings']['group_type'] = $this->getGroupTypeElement();
    $form['actions']['submit'] = [
      '#prefix' => '<div class="form-actions">',
      '#suffix' => '</div>',
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#button_type' => 'primary',
    ];

    $form['#cache']['contexts'][] = 'user';

    return $form;
  }

  /**
   * Get the group type element.
   *
   * Note this element is also used in the edit group form.
   *
   * @return array
   *   Returns an array containing the group type element and descriptions.
   */
  public function getGroupTypeElement() {

    $group_types_options = [];
    $group_types_descriptions = [];
    $group_types = $this->entityTypeManager->getListBuilder('group_type')->load();
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    foreach ($group_types as $group_type) {
      $group_types_options[$group_type->id()] = $group_type->label();
      $group_types_descriptions[$group_type->id()] = ['#description' => $group_type->getDescription()];
    }
    arsort($group_types_options);

    $element = [
      '#type' => 'radios',
      '#title' => $this->t('Group type'),
      '#description' => $this->t('Can not be changed once a group is created.'),
      '#default_value' => 'open_group',
      '#options' => $group_types_options,
      '#required' => TRUE,
    ];

    return $element + $group_types_descriptions;
  }

}
