<?php

namespace Drupal\social_demo;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;

/**
 * Class DemoContent.
 *
 * @package Drupal\social_demo
 */
abstract class DemoContent extends PluginBase implements DemoContentInterface {

  /**
   * Contains the created content.
   *
   * @var array
   */
  protected $content = [];

  /**
   * Contains data from a file.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Parser.
   *
   * @var \Drupal\social_demo\DemoContentParserInterface
   */
  protected $parser;

  /**
   * Profile.
   *
   * @var string
   */
  protected $profile = '';

  /**
   * Contains the entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    $definition = $this->getPluginDefinition();
    return isset($definition['source']) ? $definition['source'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setProfile($profile) {
    $this->profile = $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    $definition = $this->getPluginDefinition();
    return isset($definition['provider']) ? $definition['provider'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    return isset($this->profile) ? $this->profile : '';
  }

  /**
   * {@inheritdoc}
   */
  public function removeContent() {
    $data = $this->fetchData();

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        continue;
      }

      $entities = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      foreach ($entities as $entity) {
        $entity->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->content);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityStorage(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Gets the data from a file.
   */
  protected function fetchData() {
    if (!$this->data) {
      $this->data = $this->parser->parseFileFromModule($this->getSource(), $this->getModule(), $this->getProfile());
    }

    return $this->data;
  }

  /**
   * Load entity by uuid.
   *
   * @param string $entity_type_id
   *   Identifier of entity type.
   * @param string|int $id
   *   Identifier or uuid.
   * @param bool $all
   *   If set true, method will return all loaded entity.
   *   If set false, will return only one.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityInterface[]|mixed
   *   Returns the entity.
   */
  protected function loadByUuid($entity_type_id, $id, $all = FALSE) {
    if (property_exists($this, $entity_type_id . 'Storage')) {
      $storage = $this->{$entity_type_id . 'Storage'};
    }
    else {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    }

    if (is_numeric($id)) {
      $entities = $storage->loadByProperties([
        'uid' => $id,
      ]);
    }
    else {
      $entities = $storage->loadByProperties([
        'uuid' => $id,
      ]);
    }

    if (!$all) {
      return current($entities);
    }

    return $entities;
  }

  /**
   * Extract the mention from the content by [~Uuid].
   *
   * @param string $content
   *   The content that contains the mention.
   *
   * @return mixed
   *   If nothing needs to be replaced, just return the same content.
   */
  protected function checkMentionOrLinkByUuid($content) {
    // Check if there's a mention in the given content.
    if (strpos($content, '[~') !== FALSE || strpos($content, '[link=') !== FALSE) {
      // Put the content in a logical var.
      $input = $content;
      $mention_uuid = '';
      $link_uuid = '';

      // Uuid validation check.
      $isValidUuid = '/^[0-9A-F]{8}-[0-9A-F]{4}-[1-5][0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

      if (strpos($content, '[~') !== FALSE) {
        // Strip the mention uuid from the content.
        preg_match('/~(.*?)]/', $input, $output);
        $mention_uuid = $output[1];
        // If the uuid is not according the uuid v1 or v4 format
        // then just return the content.
        if (!preg_match($isValidUuid, $mention_uuid)) {
          return $content;
        }
      }
      if (strpos($content, '[link=') !== FALSE) {
        // Strip the link uuid from the content.
        preg_match('/=(.*?)]/', $input, $output);
        $link_uuid = $output[1];
        // If the uuid is not according the uuid v1 or v4 format
        // then just return the content.
        if (!preg_match($isValidUuid, $link_uuid)) {
          return $content;
        }
      }

      if (!empty($mention_uuid) || !empty($link_uuid)) {
        // Load the account by uuid.
        $account = $this->loadByUuid('user', $mention_uuid);
        if ($account instanceof User) {
          // Load the profile by account id.
          $profile = $this->loadByUuid('profile', $account->id());
          if ($profile instanceof Profile) {
            $mention = preg_replace('/' . $mention_uuid . '/', $profile->id(), $content);
            $content = $mention;
          }
        }
        // Load the node by uuid.
        $node = $this->loadByUuid('node', $link_uuid);
        if ($node instanceof Node) {
          $options = ['absolute' => TRUE];
          $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], $options)->toString();
          // Prepare the link.
          $link = '<a href="' . $url . '">' . $node->getTitle() . '</a>';
          // Replace the uuid with the link.
          $link_replacement = preg_replace('/\[link=' . $link_uuid . ']/', $link, $content);
          $content = $link_replacement;
        }
      }

      // Return the content with the replaced mention and/or link.
      return $content;
    }

    // Return the content as it was given.
    return $content;
  }

  /**
   * Makes an array with data of an entity.
   *
   * @param array $item
   *   Array with items.
   *
   * @return array
   *   Returns an array.
   */
  abstract protected function getEntry(array $item);

}
