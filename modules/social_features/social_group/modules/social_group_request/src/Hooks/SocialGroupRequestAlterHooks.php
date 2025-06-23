<?php

namespace Drupal\social_group_request\Hooks;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hux\Attribute\Alter;

/**
 * Social Group Request alter hooks.
 *
 * @internal
 */
class SocialGroupRequestAlterHooks {

  /**
   * Implements hook_entity_type_alter().
   *
   * Set custom form classes for group request membership forms.
   */
  #[
    Alter('entity_type'),
  ]
  public function groupRequestEntityTypeAlter(array &$entity_types): void {
    if (isset($entity_types['group_content'])) {
      $entity_types['group_content']->setFormClass('group-request-membership', 'Drupal\social_group_request\Form\GroupRequestMembershipRequestForm');
      $entity_types['group_content']->setFormClass('group-approve-membership', 'Drupal\social_group_request\Form\GroupRequestMembershipApproveForm');
      $entity_types['group_content']->setFormClass('group-reject-membership', 'Drupal\social_group_request\Form\GroupRequestMembershipRejectForm');
    }
  }

  /**
   * Alter of the form_group_content_form_alter.
   *
   * @param array $form
   *   The form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  #[Alter('form_group_content_confirm_form')]
  public function groupRejectMembershipFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Add a Reason field to the Flexible Group reject confirmation form.
    if ($form_id === 'group_content_group_content_type_7fcb76fdf61a9_group_reject_membership_form') {
      $form['field_grequest_reason'] = [
        '#type' => 'textarea',
        '#title' => t('Reason'),
        '#description' => t('Would you like to add a reason?'),
        '#rows' => 4,
        '#required' => FALSE,
        '#weight' => 5,
        '#attributes' => ['class' => ['form-textarea']],
      ];
      // Fix card wrapper for new field.
      $form['field_grequest_reason']['#prefix'] = '<div class="clearfix field--widget-string-textarea">';
      $form['field_grequest_reason']['#suffix'] = '</div></div></div>';
      unset($form['description']['#prefix']);
      unset($form['description']['#suffix']);

      // Add custom submit handler.
      $form['actions']['submit']['#submit'][] = [$this, 'groupRejectMembershipFormSubmit'];
    }
  }

  /**
   * Save a reason after group_reject_membership_form submit.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function groupRejectMembershipFormSubmit(array $form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
    $group_relationship = $form_object->getEntity();
    $reason_value = $form_state->getValue('field_grequest_reason');
    if (!empty($reason_value)) {
      $group_relationship->set('field_grequest_reason', $reason_value);
      $group_relationship->save();
    }
  }

  /**
   * Implements hook_menu_local_tasks_alter().
   */
  #[Alter('menu_local_tasks')]
  public function groupRejectMenuLocalTasksAlter(array &$data, string $route_name, RefinableCacheableDependencyInterface $cacheability): void {
    if ($route_name === 'view.group_membership_requests.pending') {
      if (!empty($data['tabs'][0])) {
        foreach ($data['tabs'][0] as $task_name => $task) {
          if (!str_starts_with($task_name, 'views_view:view.group_membership_requests')) {
            unset($data['tabs'][0][$task_name]);
          }
        }
      }
    }
  }

}
