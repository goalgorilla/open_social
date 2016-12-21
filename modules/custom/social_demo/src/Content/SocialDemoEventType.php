<?php

namespace Drupal\social_demo\Content;

/*
 * Social Demo Content Topic.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeStorageInterface;
use Drupal\social_demo\Yaml\SocialDemoParser;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorage;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Demo content for Events.
 */
class SocialDemoEventType implements ContainerInjectionInterface {

  protected $eventtypeterms;
  protected $eventtypes;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;
  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorage
   */
  protected $termStorage;

  /**
   * Read file contents on construction.
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\node\NodeStorageInterface $node_storage
   * @param \Drupal\taxonomy\TermStorage $term_storage
   */
  public function __construct(UserStorageInterface $user_storage, NodeStorageInterface $node_storage, TermStorage $term_storage) {
    $this->userStorage = $user_storage;
    $this->nodeStorage = $node_storage;
    $this->termStorage = $term_storage;

    $yml_data = new SocialDemoParser();
    $this->eventtypes = $yml_data->parseFile('entity/eventtype.yml');
    $this->eventtypeterms = $yml_data->parseFile('entity/eventtype-terms.yml');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager')->getStorage('node'),
      $container->get('entity.manager')->getStorage('taxonomy_term')
    );
  }

  /**
   * Function to create content.
   */
  public function createContent() {
    // Check if event types are enabled.
    if (!\Drupal::moduleHandler()->moduleExists('social_event_type')) {
      var_dump('The social event type module is not enabled.');
      return 0;
    }

    $content_counter = 0;

    // First create the taxonomy terms.
    foreach ($this->eventtypeterms as $uuid => $taxonomy) {
      // Check if the term exist.
      $taxterms = $this->termStorage->loadByProperties(array('uuid' => $uuid));
      $taxterm = reset($taxterms);

      // If it already exists, leave it.
      if ($taxterm) {
        var_dump('Term with uuid: ' . $uuid . ' already exists.');
        continue;
      }

      $term = Term::create([
        'vid' => 'event_types',
        'uuid' => $taxonomy['uuid'],
        'name' => $taxonomy['title'],
      ]);
      $term->save();

      $content_counter++;
    }

    // Loop through the content and try to update it them with the new taxonomies.
    foreach ($this->eventtypes as $uuid => $eventtype) {
      // Must have uuid and same key value.
      if ($uuid !== $eventtype['uuid']) {
        var_dump('Node with uuid: ' . $uuid . ' has a different uuid in content.');
        continue;
      }

      // Load the event by uuid.
      $event_id = $this->loadEventByUuid($uuid);
      /** @var Node $event */
      $event = Node::load($event_id);

      // If it doesn't exists, leave it.
      if (!$event) {
        var_dump('Event with uuid: ' . $uuid . ' does not exists.');
        continue;
      }

      $taxterms = $this->termStorage->loadByProperties(array('uuid' => $eventtype['event_type']));
      $taxterm = reset($taxterms);

      // Set the field in the event.
      $event->set('field_event_type', $taxterm->id());
      // Save the event.
      $event->save();
    }

    return $content_counter;
  }

  /**
   * Function to remove content.
   */
  public function removeContent() {
    // Check if event types are enabled.
    if (!\Drupal::moduleHandler()->moduleExists('social_event_type')) {
      var_dump('The social event type module is not enabled.');
      return 0;
    }

    // Loop through the content and try to create new entries.
    foreach ($this->eventtypes as $uuid => $event) {

      // Must have uuid and same key value.
      if ($uuid !== $event['uuid']) {
        continue;
      }

      // Load the nodes from the uuid.
      $nodes = $this->nodeStorage->loadByProperties(array('uuid' => $uuid));

      // Loop through the nodes.
      foreach ($nodes as $key => $node) {
        // And delete them.
        $node->set('field_event_type', 0);
        // Save the event.
        $node->save();
      }
    }

    $content_counter = 0;
    // Load all terms.
    $taxterms = $this->termStorage->loadByProperties();
    // Delete them.
    foreach ($taxterms as $term) {
      $term->delete();
      $content_counter++;
    }

    return $content_counter;
  }

  /**
   * Load a file object by uuid.
   *
   * @param string $uuid
   *   The uuid of the file.
   *
   * @return int $fid
   *   Returns the file id for the given uuid.
   */
  public function loadEventByUuid($uuid) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'event');
    $query->condition('uuid', $uuid);
    $query->addMetaData('account', user_load(1)); // Run the query as user 1.
    $fids = $query->execute();
    // Get a single item.
    $fid = reset($fids);
    // And return it.
    return $fid;
  }

}
