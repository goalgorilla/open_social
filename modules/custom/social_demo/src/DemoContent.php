<?php

namespace Drupal\social_demo;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\PluginBase;
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
      $this->data = $this->parser->parseFile($this->getSource(), $this->getModule(), $this->getProfile());
    }

    return $this->data;
  }

  /**
   * Load entity by uuid.
   *
   * @param string $entity_type_id
   *    Identifier of entity type.
   * @param string|int $id
   *    Identifier or uuid.
   * @param bool $all
   *    If set true, method will return all loaded entity.
   *    If set false, will return only one.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityInterface[]|mixed
   *    Returns the entity.
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
   * @param $content
   *    The content that contains the mention.
   *
   * @return mixed
   *    If nothing needs to be replaced, just return the same content.
   */
  protected function checkMentionByUuid($content) {
    // Check if there's a mention in the given content.
    if (strpos($content, '[~') !== FALSE) {
      $input = $content;
      // Strip the uuid from the content.
      preg_match('/~(.*?)]/', $input, $output);
      $uuid = $output[1];
      // Uuid validation check.
      $isValidUuid = '/^[0-9A-F]{8}-[0-9A-F]{4}-[1-5][0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
      // If the uuid is not according the uuid v1 or v4 format
      // then just return the content.
      if (!preg_match($isValidUuid, $uuid)) {
        return $content;
      }
      // Load the account by uuid.
      $account = $this->loadByUuid('user', $uuid);
      if ($account instanceof User) {
        // Load the profile by account id.
        $profile = $this->loadByUuid('profile', $account->id());
        if ($profile instanceof Profile) {
          $mention = preg_replace('/' . $uuid . '/', $profile->id(), $content);
          return $mention;
        }
      }
    }
    return $content;
  }

  /**
   * Makes an array with data of an entity.
   *
   * @param array $item
   *    Array with items.
   *
   * @return array
   *    Returns an array.
   */
  abstract protected function getEntry(array $item);

}
