<?php

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates backend settings based on meeting type configuration.
 *
 * For BigBlueButton servers, this constraint ensures that the server
 * has both URL and key configured properly.
 */
#[Constraint(
  id: 'MeetingServerSettings',
  label:  new TranslatableMarkup('Validate Big Blue Button configuration', [], ['context' => 'Validation']),
  type: 'entity:meeting_api_meeting'
)]
class MeetingServerSettingsConstraint extends SymfonyConstraint {

  /**
   * Error message for BigBlueButton server missing configuration.
   *
   * @var string
   */
  public string $bigBlueButtonServerNotConfigured = 'BigBlueButton server is not properly configured. Please ensure the URL and Key are set for the selected server.';

}
