<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\social_user\Plugin\Action\SocialSendEmail as SocialSendEmailBase;

/**
 * Send email to group members.
 *
 * @Action(
 *   id = "social_group_send_email_action",
 *   label = @Translation("Send email to group members"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
class SocialSendEmail extends SocialSendEmailBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof GroupContentInterface) {
      /** @var \Drupal\group\Entity\GroupContentInterface $object */
      return $object->access('view', $account, $return_as_object);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    return $entity->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Add title to the form as well.
    if ($form['#title'] !== NULL) {
      $selected_count = $this->context['selected_count'];
      $subtitle = $this->formatPlural($selected_count,
        'Configure the email you want to send to the one member you have selected.',
        'Configure the email you want to send to the @count members you have selected.'
      );

      $form['subtitle'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['placeholder'],
        ],
        '#value' => $subtitle,
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

}
