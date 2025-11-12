<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_chat\ChatConversationInterface;
use Drupal\social_chat\ConversationManager;
use OpenSocial\TestBridge\Attributes\Command;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class ChatBridge {

  use EntityTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConversationManager $conversationManager,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('social_chat.conversation_manager'),
    );
  }

  /**
   * Create multiple chat conversations.
   *
   * @param array $conversations
   *   The conversation information that'll be passed to
   *   ConversationManager::createChatGroupConversation() or
   *   ConversationManager::loadOrCreatePrivateConversation().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the conversations successfully created and an array
   *   of errors for failures.
   */
  #[Command(name: "create-chat-conversations")]
  public function createChatConversations(array $conversations) {
    $created = [];
    $errors = [];
    foreach ($conversations as $inputId => $conversation) {
      try {
        $conversation = $this->conversationCreate($conversation);
        $created[$inputId] = $conversation->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
  }

  /**
   * Create a chat conversation.
   *
   * @return \Drupal\social_chat\ChatConversationInterface
   *   The created conversation.
   */
  private function conversationCreate($conversation) : ChatConversationInterface {
    $user_storage = $this->entityTypeManager->getStorage('user');

    switch ($conversation['type']) {
      case "chat_group":
        if (!isset($conversation['label'], $conversation['creator'], $conversation['participants'])) {
          throw new \InvalidArgumentException("Creation array for 'chat_group' conversation should have fields: label (string), creator (uid), and participants (uid[])");
        }
        $creator = $user_storage->load($conversation['creator']) ?? throw new \InvalidArgumentException("Creator user with ID '{$conversation['creator']}' does not exist.");
        $participants = $user_storage->loadMultiple($conversation['participants']);
        if (count($participants) !== count($conversation['participants'])) {
          throw new \InvalidArgumentException("One or more participant user IDs do not exist.");
        }
        return $this->conversationManager->createChatGroupConversation(
          $conversation['label'],
          $creator,
          $participants,
        );

      case "private":
        if (!isset($conversation['creator'], $conversation['participant'])) {
          throw new \InvalidArgumentException("Creation array for 'private' conversation should have fields: creator (uid) and participant (uid)");
        }
        $creator = $user_storage->load($conversation['creator']) ?? throw new \InvalidArgumentException("Creator user with ID '{$conversation['creator']}' does not exist.");
        $participant = $user_storage->load($conversation['participant']) ?? throw new \InvalidArgumentException("Participant user with ID '{$conversation['creator']}' does not exist.");
        return $this->conversationManager->loadOrCreatePrivateConversation(
          $creator,
          $participant,
        );

      default:
        throw new \InvalidArgumentException("Unsupported conversation type '{$conversation['type']}'.");
    }
  }

}
