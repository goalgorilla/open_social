<?php

namespace Drupal\social_album\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_post\Entity\PostInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;

/**
 * Returns responses for Album routes.
 *
 * @package Drupal\social_album\Controller
 */
class SocialAlbumController extends ControllerBase {

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Group Content Enabler manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * File URL Generator services.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * SocialAlbumController constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $group_relation_type_manager
   *   The group content manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator service.
   */
  public function __construct(
    TranslationInterface $translation,
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFormBuilderInterface $entity_form_builder,
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    GroupRelationTypeManagerInterface $group_relation_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    $this->setStringTranslation($translation);
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->groupRelationTypeManager = $group_relation_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('group_relation_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Provides a generic title callback for the first post of the album.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title to page of the post.
   */
  public function title(NodeInterface $node) {
    return $this->t('Add images to album @name', ['@name' => $node->label()]);
  }

  /**
   * Provides a page with images slider.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity object.
   * @param int $fid
   *   The file entity ID.
   *
   * @return array
   *   The renderable array.
   */
  public function viewImage(NodeInterface $node, PostInterface $post, $fid) {
    $query = $this->database->select('post__field_post_image', 'i')
      ->fields('i', ['field_post_image_target_id']);

    $query->innerJoin('post__field_album', 'a', 'a.entity_id = i.entity_id');
    $query->condition('a.field_album_target_id', $node->id());

    $query->innerJoin('post_field_data', 'p', 'p.id = a.entity_id');
    $query->fields('p', ['id']);
    $query->orderBy('p.created');

    $query->orderBy('i.delta');

    $items = [FALSE => [], TRUE => []];
    $found = FALSE;

    /** @var \Drupal\file\FileStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('file');

    foreach ($query->execute()->fetchAllKeyed() as $file_id => $post_id) {
      if (!$found && $file_id == $fid) {
        $found = TRUE;
      }

      /** @var \Drupal\file\FileInterface $file */
      $file = $storage->load($file_id);

      // When the file isn't have an URi, go to the next.
      if (is_null($file->getFileUri())) {
        continue;
      }

      $items[$found][] = [
        'url' => $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
        'pid' => $post_id,
      ];
    }

    return [
      '#theme' => 'social_album_post',
      '#items' => array_merge($items[TRUE], $items[FALSE]),
      '#album' => $node->label(),
    ];
  }

  /**
   * Provides a page with a form for deleting image from post and post view.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity object.
   * @param int $fid
   *   The file entity ID.
   *
   * @return array
   *   The renderable array.
   */
  public function deleteImage(NodeInterface $node, PostInterface $post, $fid) {
    return [
      'form' => $this->entityFormBuilder()->getForm($post, 'delete_image', ['fid' => $fid]),
      'view' => $this->entityTypeManager()->getViewBuilder('post')->view($post, 'featured'),
    ];
  }

  /**
   * Set the current group as the default value of the group field.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return array
   *   The renderable array.
   */
  public function add(GroupInterface $group) {
    $node = $this->entityTypeManager()->getStorage('node')->create([
      'type' => 'album',
      'groups' => $group,
    ]);

    return $this->entityFormBuilder()->getForm($node);
  }

  /**
   * Checks access to the form of a post which will be linked to the album.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return bool
   *   TRUE if it's an album node.
   */
  protected function checkAlbumAccess(NodeInterface $node) {
    return $node->bundle() === 'album';
  }

  /**
   * Checks access to the page for adding an image to an album.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAddImageAccess(NodeInterface $node) {
    if ($this->checkAlbumAccess($node)) {
      $account = $this->currentUser();

      // Access allowed for the owner or for the user who can edit any posts.
      // The 'edit any post entities' is used because there is no separate
      // permission for managers to add to any albums.
      if ($node->getOwnerId() === $account->id() || $account->hasPermission('edit any post entities')) {
        return AccessResult::allowed();
      }
      elseif (
        !$node->field_album_creators->isEmpty() &&
        $node->field_album_creators->value
      ) {
        /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface $storage */
        $storage = $this->entityTypeManager()->getStorage('group_content');

        if ($group_content = $storage->loadByEntity($node)) {
          /** @var \Drupal\social_group\SocialGroupInterface $group */
          $group = reset($group_content)->getGroup();

          return AccessResult::allowedIf($group->hasMember($account));
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Checks access to the page for viewing the image from the post.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity object.
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity object.
   * @param int $fid
   *   The file entity ID.
   * @param string $operation
   *   (optional) The operation to be performed. Defaults to view.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkViewImageAccess(NodeInterface $node, PostInterface $post, $fid, $operation = 'view') {
    if (
      $this->checkAlbumAccess($node) &&
      $post->access($operation) &&
      $post->bundle() === 'photo' &&
      !$post->field_album->isEmpty() &&
      $post->field_album->target_id === $node->id() &&
      !$post->field_post_image->isEmpty()
    ) {
      foreach ($post->field_post_image->getValue() as $item) {
        if ($item['target_id'] === $fid) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Checks access to the page for deleting the image from the post.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity object.
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity object.
   * @param int $fid
   *   The file entity ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkDeleteImageAccess(NodeInterface $node, PostInterface $post, $fid) {
    $access = $this->checkViewImageAccess($node, $post, $fid, 'delete');

    if ($access->isAllowed()) {
      $access = $access->andIf(AccessResult::allowedIf($post->getOwnerId() === $this->currentUser()->id()));
    }

    return $access;
  }

  /**
   * Checks access to the user albums page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkUserAlbumsAccess() {
    $config = $this->config('social_album.settings');
    // Allow access only if Album feature is enabled.
    if ($config->get('status')) {
      return AccessResult::allowed()->addCacheableDependency($config);
    }
    return AccessResult::forbidden()->addCacheableDependency($config);
  }

  /**
   * Checks access to create an album in a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkGroupAccess(GroupInterface $group) {
    $is_allow = $group->getGroupType()->hasPlugin('group_node:album');
    return AccessResult::allowedIf($is_allow);
  }

  /**
   * Checks access to the group albums page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkGroupAlbumsAccess(GroupInterface $group) {
    $access = $this->checkUserAlbumsAccess();
    return $access->isForbidden() ? $access : $this->checkGroupAccess($group);
  }

  /**
   * Checks access to the group album creation page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkGroupAlbumAccess(GroupInterface $group): AccessResultInterface {
    // Allow  access only when album feature is enabled.
    if (!$this->config('social_album.settings')->get('status')) {
      return AccessResult::forbidden();
    }

    $access = $this->checkGroupAccess($group);

    if ($access->isAllowed()) {
      /** @var \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $handler */
      $handler = $this->groupRelationTypeManager->getAccessControlHandler('group_node:album');
      // Reset the access, we are aware there is a plugin.
      $access = $handler->relationshipCreateAccess($group, $this->currentUser(), TRUE);
      if ($access instanceof AccessResultInterface) {
        return $access;
      }
      return AccessResult::allowedIf($access);
    }

    return $access;
  }

}
