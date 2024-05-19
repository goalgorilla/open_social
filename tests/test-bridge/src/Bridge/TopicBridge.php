<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use OpenSocial\TestBridge\Attributes\Command;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class TopicBridge {

  use EntityTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  #[Command(name: "topic-id-from-title")]
  public function getTopicIdFromTitle(string $title) {
    return ['id' => $this->getEntityIdFromLabel("node", "topic", $title)];
  }

  /**
   * Create multiple topics.
   *
   * @param array $topics
   *   The topic information that'll be passed to Node::create().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the topics successfully created and an array of
   *   errors for failures.
   */
  #[Command(name: "create-topics")]
  public function createTopics(array $topics) {
    $created = [];
    $errors = [];
    foreach ($topics as $inputId => $topic) {
      try {
        $topic = $this->topicCreate($topic);
        $created[$inputId] = $topic->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
  }

  /**
   * Create a topic.
   *
   * @return \Drupal\node\Entity\Node
   *   The topic values.
   */
  private function topicCreate($topic) : Node {
    if (!isset($topic['author'])) {
      throw new \Exception("You must specify an `author` when creating a topic.");
    }

    $account = user_load_by_name($topic['author']);
    if ($account === FALSE) {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $topic['author']));
    }
    $topic['uid'] = $account->id();
    unset($topic['author']);

    if (isset($topic['group'])) {
      $group_id = $this->getEntityIdFromLabel('group', NULL, $topic['group']);
      if ($group_id === NULL) {
        throw new \Exception("Group '{$topic['group']}' does not exist.");
      }
      unset($topic['group']);
    }

    $topic['type'] = 'topic';

    $this->validateEntityFields("node", $topic);
    $topic_object = Node::create($topic);
    $violations = $topic_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The topic you tried to create is invalid: $violations");
    }
    if (!$topic_object->body->format) {
      $topic_object->body->format = 'basic_html';
    }
    $topic_object->save();

    // Adding to group usually happens in a form handler so for initialization
    // we must do that ourselves.
    if (isset($group_id)) {
      try {
        $this->entityTypeManager->getStorage('group')
          ->load($group_id)
          ?->addContent($topic_object, "group_node:topic");
      }
      catch (PluginNotFoundException $_) {
        throw new \Exception("Modules that allow adding content to groups should ensure the `gnode` module is enabled.");
      }
    }

    return $topic_object;
  }

}
