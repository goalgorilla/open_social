<?php

declare(strict_types=1);

namespace Drupal\social_event_an_enroll\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\Node\EventInterface;

/**
 * Provides hooks related to node event online feature compatibility.
 */
final class EventOnlineCompatibility {

  /**
   * Prevent using the feature with online meetings.
   *
   * @param array $form
   *   The form build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_node_event_edit_form')]
  #[Alter('form_node_event_add_form')]
  public function onlineEventCompatibility(array &$form, FormStateInterface $form_state): void {
    if (!isset($form['field_event_meeting']) || !isset($form['field_event_an_enroll']['widget']['value'])) {
      return;
    }

    // Make sure the field is visible only when the meeting is non-online.
    // The reason is that anonymous users enrollment can't be detected, and
    // we can't build the join meeting link for them.
    $form['field_event_an_enroll']['widget']['value']['#states']['visible'][':input[name="field_event_meeting[meeting_form][is_online]"]'] = ['checked' => FALSE];
  }

  /**
   * Prevent anonymous enrollment in online meetings.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object being saved. This is altered if the node is an event
   *   and is set to be online to ensure anonymous enrollment is disabled.
   *
   * @see hook_ENTITY_TYPE_presave()
   */
  #[Hook('node_presave')]
  public function eventPresave(NodeInterface $node): void {
    if (!$node instanceof EventInterface) {
      return;
    }

    // Make sure the online event doesn't have enabled anonymous enrollment.
    if ($node->isOnline()) {
      $node->set('field_event_an_enroll', FALSE);
    }
  }

}
