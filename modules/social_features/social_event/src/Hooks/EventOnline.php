<?php

declare(strict_types=1);

namespace Drupal\social_event\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hux\Attribute\Alter;
use Drupal\social_event\Form\EventSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides hooks related to node event online feature.
 */
final class EventOnline implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Construct the EventOnline object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * Adds validation constraints to the Meeting entity type.
   *
   * @param array $entity_types
   *   An array of entity type definitions, keyed by entity type ID.
   *
   * @see hook_entity_type_alter()
   */
  #[Alter('entity_type')]
  public function addViolationsToMeetingEntity(array &$entity_types): void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    if (isset($entity_types['meeting_api_meeting'])) {
      // Add validation constraint to a Meeting entity.
      $entity_types['meeting_api_meeting']->addConstraint('MeetingServerSettings');
    }
  }

  /**
   * Adds validation constraints to specific fields of meeting entities.
   *
   * @param array $fields
   *   An array of base fields for the entity.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @see hook_entity_base_field_info_alter()
   */
  #[Alter('entity_base_field_info')]
  public function addViolationsToMeetingFields(array &$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() !== 'meeting_api_meeting') {
      return;
    }

    if (isset($fields['max_attendees'])) {
      $fields['max_attendees']->addConstraint('MeetingCapacity');
    }
  }

  /**
   * Prevent users from adding a BBB meeting without backend configuration.
   *
   * @param array $form
   *   The form build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_meeting_api_meeting_big_blue_button_add_form')]
  #[Alter('form_meeting_api_meeting_big_blue_button_edit_form')]
  public function alterBigBlueButtonForm(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    assert($form_object instanceof EntityFormInterface);

    $meeting = $form_object->getEntity();
    if ($meeting->bundle() !== 'big_blue_button') {
      return;
    }

    // Change min and max attendees.
    if (isset($form['max_attendees']['widget'][0]['value'])) {
      $event_settings = $this->configFactory->get(EventSettingsForm::SETTINGS);
      $max = $event_settings->get('online_meeting.max_attendees') ?: EventSettingsForm::MAX_CONCURRENT_BBB_ATTENDEES;
      $min = EventSettingsForm::DEFAULT_MEETING_ATTENDEES;

      $form['max_attendees']['widget'][0]['value']['#max'] = $max;
      $form['max_attendees']['widget'][0]['value']['#min'] = $min;
      $form['max_attendees']['widget'][0]['value']['#description'] = $this->t('The maximum allowed number of attendees. Allowed value is from <em>@min</em> to <em>@max</em>.', [
        '@min' => $min,
        '@max' => $max,
      ]);
    }
  }

}
