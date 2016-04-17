<?php

/**
 * @file
 * Contains \Drupal\field_group\Form\FieldGroupDeleteForm.
 */

namespace Drupal\field_group\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for removing a fieldgroup from a bundle.
 */
class FieldGroupDeleteForm extends ConfirmFormBase {

  /**
   * The fieldgroup to delete.
   *
   * @var stdClass
   */
  protected $fieldGroup;

  /**
   * Construct the delete form: get the group config out of the request.
   */
  public function __construct() {
    $this->fieldGroup = (object)$this->getRequest()->attributes->get('field_group');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_group_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = entity_get_bundles();
    $bundle_label = $bundles[$this->fieldGroup->entity_type][$this->fieldGroup->bundle]['label'];

    field_group_group_delete($this->fieldGroup);

    drupal_set_message(t('The group %group has been deleted from the %type content type.', array('%group' => t($this->fieldGroup->label), '%type' => $bundle_label)));

    // Redirect.
    $form_state->setRedirectUrl($this->getCancelUrl());

  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the group %group?', array('%group' => t($this->fieldGroup->label)));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

    $entity_type_id = $this->fieldGroup->entity_type;
    $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
    if (!$entity_type->get('field_ui_base_route')) {
      return;
    }

    $options = array(
      $entity_type->getBundleEntityType() => $this->fieldGroup->bundle,
    );

    // Redirect to correct route.
    if ($this->fieldGroup->context == 'form') {
      if ($this->fieldGroup->mode == 'default') {
        $route_name = "entity.entity_form_display.{$entity_type_id}.default";
      }
      else {
        $route_name = "entity.entity_form_display.{$entity_type_id}.form_mode";
        $options['form_mode_name'] = $this->fieldGroup->mode;
      }
    }
    else {
      if ($this->fieldGroup->mode == 'default') {
        $route_name = "entity.entity_view_display.{$entity_type_id}.default";
      }
      else {
        $route_name = "entity.entity_view_display.{$entity_type_id}.view_mode";
        $options['view_mode_name'] = $this->fieldGroup->mode;
      }
    }

    return new Url($route_name, $options);

  }

}
