<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use OpenSocial\TestBridge\Attributes\Command;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class BookBridge {

  use EntityTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  #[Command(name: "book-id-from-title")]
  public function getBookIdFromTitle(string $title) {
    return ['id' => $this->getEntityIdFromLabel("node", "book", $title)];
  }

  /**
   * Create multiple books.
   *
   * @param array $books
   *   The book information that'll be passed to Node::create().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the books successfully created and an array of
   *   errors for failures.
   */
  #[Command(name: "create-books")]
  public function createBooks(array $books) : array {
    $created = [];
    $errors = [];
    foreach ($books as $inputId => $book) {
      try {
        $book = $this->bookCreate($book);
        $created[$inputId] = $book->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
  }

  /**
   * Enable Drupal core book functionality for a content type.
   */
  #[Command(name: "enable-book-for-content-type")]
  public function enableBookStructureForContentType(string $content_type) : array {
    $config = $this->configFactory->getEditable('book.settings');

    if ($config->isNew()) {
      throw new \Exception("The book.settings configuration did not yet exist, is the 'book' module enabled?");
    }

    $allowed_types = $config->get('allowed_types');
    $allowed_types[] = $content_type;

    $config->set('allowed_types', $allowed_types)->save();

    return ["status" => "ok"];
  }

  /**
   * Create a topic.
   *
   * @return \Drupal\node\Entity\Node
   *   The topic values.
   */
  private function bookCreate($book) : Node {
    if (!isset($book['author'])) {
      throw new \Exception("You must specify an `author` when creating a book.");
    }

    $account = user_load_by_name($book['author']);
    if ($account === FALSE) {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $book['author']));
    }
    $book['uid'] = $account->id();
    unset($book['author']);

    if (isset($book['group'])) {
      $group_id = $this->getEntityIdFromLabel('group', NULL, $book['group']);
      if ($group_id === NULL) {
        throw new \Exception("Group '{$book['group']}' does not exist.");
      }
      unset($book['group']);
    }

    if (!isset($book['book']) || trim($book['book']) === "") {
      $book_id = 'new';
      if (isset($book['parent']) && trim($book['parent']) !== "") {
        throw new \Exception("Can not set property 'parent' without specifying 'book'.");
      }
    }
    else {
      $book_id = $this->getBookIdFromTitle($book['book']);
      if ($book_id === NULL) {
        throw new \Exception("Book '{$book['book']}' does not exist.");
      }
    }
    unset($book['book']);

    $book['type'] = 'book';

    $this->validateEntityFields("node", $book);
    $book_object = Node::create($book);
    $violations = $book_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The book you tried to create is invalid: $violations");
    }


    $book_object->book = \Drupal::service('book.manager')->getLinkDefaults($book_object);
    $book_object->book['bid'] = $book_id;

    if (!isset($book['parent']) && isset($book['book'])) {
      // A book can only have one top level book, so we automatically set the
      // parent.
      $book['parent'] = $book['book'];
    }
    if (isset($book['parent'])) {
      $parent_id = $this->getBookIdFromTitle($book['parent']);
      if ($parent_id === NULL) {
        throw new \Exception("Book '{$book['parent']}' does not exist.");
      }
      $book_object->book['pid'] = $parent_id;
    }

    $book_object->save();

    // Adding to group usually happens in a form handler so for initialization
    // we must do that ourselves.
    if (isset($group_id)) {
      try {
        $this->entityTypeManager->getStorage('group')
          ->load($group_id)
          ?->addContent($book_object, "group_node:book");
      }
      catch (PluginNotFoundException $_) {
        throw new \Exception("Modules that allow adding content to groups should ensure the `gnode` module is enabled.");
      }
    }

    return $book_object;
  }

}
