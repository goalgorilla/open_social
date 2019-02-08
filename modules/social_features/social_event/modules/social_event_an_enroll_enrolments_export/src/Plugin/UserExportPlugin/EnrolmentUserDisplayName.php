<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\UserExportPlugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_user_export\Plugin\UserExportPlugin\UserDisplayName;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'EnrolmentUserDisplayName' user export row.
 *
 * @UserExportPlugin(
 *  id = "enrolment_display_name",
 *  label = @Translation("Display name"),
 *  weight = -450,
 * )
 */
class EnrolmentUserDisplayName extends UserDisplayName {

  /**
   * The event an enroll manager.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollManager
   */
  protected $socialEventAnEnrollManager;

  /**
   * EnrolmentUserDisplayName constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollManager $social_event_an_enroll_manager
   *   The event an enroll manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    Connection $database,
    EventAnEnrollManager $social_event_an_enroll_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $date_formatter, $database);

    $this->socialEventAnEnrollManager = $social_event_an_enroll_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('database'),
      $container->get('social_event_an_enroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(UserInterface $entity) {
    if ($entity->isAnonymous()) {
      $value = $this->socialEventAnEnrollManager->getGuestName($this->configuration['entity'], FALSE);

      return $value ?: $this->t('Guest');
    }

    return parent::getValue($entity);
  }

}
