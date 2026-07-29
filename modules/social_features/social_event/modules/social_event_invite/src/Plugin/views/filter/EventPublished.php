<?php

declare(strict_types=1);

namespace Drupal\social_event_invite\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters invites to published events only.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("social_event_invite_event_published")]
final class EventPublished extends FilterPluginBase {

  /**
   * Constructs an EventPublished filter.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, private readonly Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    assert($this->query instanceof Sql);

    $this->ensureMyTable();

    $event_table = $this->query->ensureTable('event_enrollment__field_event');
    $published_events = $this->getPublishedEventsQuery();

    $this->query->addWhere(
      $this->options['group'],
      "$event_table.field_event_target_id",
      $published_events,
      'IN',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return array_merge(parent::getCacheTags(), ['node_list:event']);
  }

  /**
   * Builds the subquery listing published event IDs.
   */
  private function getPublishedEventsQuery(): SelectInterface {
    return $this->database->select('node_field_data', 'nfd')
      ->fields('nfd', ['nid'])
      ->condition('nfd.type', 'event')
      ->condition('nfd.status', 1);
  }

}
