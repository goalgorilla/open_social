<?php

namespace Drupal\social_activity;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Drupal\image\Entity\ImageStyle;
use Drupal\message\Entity\Message;
use Drupal\node\Entity\Node;
use Drupal\social_group\GroupStatistics;
use Drupal\social_post\Entity\Post;
use Drupal\user\Entity\User;

/**
 * Helper functions for replacing the tokens in messages.
 *
 * 1. message:cta_button
 * 2. message:preview.
 */
class EmailTokenServices {
  use StringTranslationTrait;

  /**
   * Entity type manager services.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Date Formatter services.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected DateFormatter $dateFormatter;

  /**
   * GroupStatistics services.
   *
   * @var \Drupal\social_group\GroupStatistics
   */
  protected GroupStatistics $groupStatistics;

  /**
   * Constructs a EmailTokenServices object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   EntityTypeManager object.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   DateFormatter object.
   * @param \Drupal\social_group\GroupStatistics $group_statistics
   *   GroupStatistics object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, DateFormatter $date_formatter, GroupStatistics $group_statistics) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->groupStatistics = $group_statistics;
  }

  /**
   * Loads the related object from a given message entity.
   *
   * @param \Drupal\message\Entity\Message $message
   *   The message entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function getRelatedObject(Message $message) {
    $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
    $target_id = $message->getFieldValue('field_message_related_object', 'target_id');

    return $this->entityTypeManager
      ->getStorage($target_type)
      ->load($target_id);
  }

  /**
   * Generates renderable array for creation of preview of comment.
   *
   * @param \Drupal\comment\Entity\Comment $comment
   *   The comment entity.
   *
   * @return array
   *   The renderable array.
   */
  public function getCommentPreview(Comment $comment) {
    $preview_info = [];

    if ($comment->hasField('field_comment_body') && !$comment->get('field_comment_body')->isEmpty()) {
      if ($summary = _social_comment_get_summary($comment->getFieldValue('field_comment_body', 'value'))) {
        // Prepare the preview information.
        $preview_info = [
          '#theme' => 'message_post_comment_preview',
          '#summary' => $summary,
        ];
      }
    }
    return $preview_info;
  }

  /**
   * Generates the renderable array for creation of content preview.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return array
   *   The renderable array.
   */
  public function getContentPreview(Node $node) {
    $preview_info = [];

    // Prepare the link to node.
    $link = Url::fromRoute('entity.node.canonical',
      ['node' => $node->id()],
      ['absolute' => TRUE]
    )->toString();

    $date = $this->dateFormatter->format($node->getCreatedTime(), 'social_long_date');
    if ($node->bundle() === 'event') {
      $date = _social_event_format_date($node, NULL);
    }

    // Prepare the preview.
    $preview_info = [
      '#theme' => 'message_content_preview',
      '#author_name' => $node->getOwner()->getDisplayName(),
      '#date' => $date,
      '#title' => $node->getTitle(),
      '#type' => strtoupper($node->getType()),
      '#link' => $link,
    ];

    return $preview_info;
  }

  /**
   * Generates the renderable array for creation of post preview.
   *
   * @param \Drupal\social_post\Entity\Post $post
   *   The post entity.
   *
   * @return array
   *   The renderable array.
   */
  public function getPostPreview(Post $post) {
    $preview_info = [];

    // Get the summary of the comment.
    if ($post->hasField('field_post') && !$post->get('field_post')->isEmpty()) {
      if ($summary = _social_comment_get_summary($post->getFieldValue('field_post', 'value'))) {
        // Prepare the preview information.
        $preview_info = [
          '#theme' => 'message_post_comment_preview',
          '#summary' => $summary,
        ];
      }
    }

    return $preview_info;
  }

  /**
   * Generates the renderable array for creation of user profile preview.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return array
   *   The renderable array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserPreview(User $user) {
    $preview_info = [];

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    /** @var \Drupal\profile\Entity\Profile $profile */
    $profile = $profile_storage->loadByUser($user, 'profile');
    // Add the profile image.
    /** @var \Drupal\image\Entity\ImageStyle $image_style */
    $image_style = ImageStyle::load('social_medium');
    if (!empty($profile->field_profile_image->entity)) {
      $image_url = $image_style->buildUrl($profile->field_profile_image->entity->getFileUri());
    }
    elseif ($default_image = social_profile_get_default_image()) {
      // Add default image.
      if (!empty($default_image['id'])) {
        $file = File::load($default_image['id']);
        $image_url = $image_style->buildUrl($file->getFileUri());
      }
    }
    // Add the profile image.
    $preview_info = [
      '#theme' => 'message_user_profile_preview',
      '#profile_name' => $user->getDisplayName(),
      '#profile_home' => Url::fromRoute('entity.user.canonical', ['user' => $user->id()]),
      '#profile_image' => $image_url ?? NULL,
      '#profile_function' => $profile->getFieldValue('field_profile_function', 'value'),
      '#profile_organization' => $profile->getFieldValue('field_profile_organization', 'value'),
    ];

    return $preview_info;
  }

  /**
   * Generates the renderable array for creation of user profile preview.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The group entity.
   *
   * @return array
   *   The renderable array.
   */
  public function getGroupPreview(Group $group) {
    // Add the group preview.
    return [
      '#theme' => 'message_group_preview',
      '#group_title' => $group->label(),
      '#group_type' => strtoupper($group->getGroupType()->label()),
      '#group_members' => $this->groupStatistics->getGroupMemberCount($group),
    ];
  }

  /**
   * Generates the renderable array for creation of CTA button.
   *
   * @param string $link
   *   The href property for button.
   * @param string $text
   *   The label of button.
   *
   * @return array
   *   The renderable array.
   */
  public function getCtaButton(string $link, string $text) {
    $cta_button = [];

    if (!empty($text) && !empty($link)) {
      $cta_button = [
        '#theme' => 'message_cta_button',
        '#link' => $link,
        '#text' => $this->t($text),
      ];
    }

    return $cta_button;
  }

}
