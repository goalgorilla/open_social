<?php

namespace Drupal\social_activity;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\filter\FilterPluginManager;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The stream wrapper manager.
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * The configfactory.
   */
  protected ConfigFactory $config;

  /**
   * The filter plugin manager service.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterPluginManager;

  /**
   * Constructs a EmailTokenServices object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManager object.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   DateFormatter object.
   * @param \Drupal\social_group\GroupStatistics $group_statistics
   *   GroupStatistics object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   * @param \Drupal\filter\FilterPluginManager $filter_plugin_manager
   *   FilterPluginManager object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatter $date_formatter,
    GroupStatistics $group_statistics,
    ModuleHandlerInterface $module_handler,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    ConfigFactory $config,
    FilterPluginManager $filter_plugin_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->groupStatistics = $group_statistics;
    $this->moduleHandler = $module_handler;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->config = $config;
    $this->filterPluginManager = $filter_plugin_manager;
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
    if ($message->get('field_message_related_object')->isEmpty()) {
      return NULL;
    }

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
        $processed_text = $this->processMentionsPreview($summary, $comment->language()->getId());
        // Prepare the preview information.
        $preview_info = [
          '#theme' => 'message_post_comment_preview',
          '#summary' => !empty($processed_text) ? $processed_text : $summary,
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

    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->entityTypeManager
      ->getStorage('node_type')
      ->load($node->bundle());

    // Prepare the preview.
    $preview_info = [
      '#theme' => 'message_content_preview',
      '#author_name' => $node->getOwner()->getDisplayName(),
      '#date' => $date,
      '#title' => $node->getTitle(),
      '#type' => mb_strtoupper((string) $node_type->label()),
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
        $processed_text = $this->processMentionsPreview($summary, $post->language()->getId());
        // Prepare the preview information.
        $preview_info = [
          '#theme' => 'message_post_comment_preview',
          '#summary' => !empty($processed_text) ? $processed_text : $summary,
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

    /** @var \Drupal\file\FileInterface $image */
    $image = !$profile->get('field_profile_image')->isEmpty() ? $profile->get('field_profile_image')->entity : '';

    if (
      $image instanceof FileInterface &&
      $this->streamWrapperManager->getScheme($image->getFileUri()) !== 'private'
    ) {
      $image_url = $image_style->buildUrl($image->getFileUri());
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
      '#profile_class' => $this->moduleHandler->moduleExists('lazy') ? $this->config->get('lazy.settings')->get('skipClass') : '',
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
    $label = $group->getGroupType()->label();
    $group_type_label = $label instanceof TranslatableMarkup ? $label->render() : $label;
    $group_type_label = $group_type_label ?? '';
    return [
      '#theme' => 'message_group_preview',
      '#group_title' => $group->label(),
      '#group_type' => strtoupper($group_type_label),
      '#group_members' => $this->groupStatistics->getGroupMemberCount($group),
    ];
  }

  /**
   * Generates the renderable array for creation of a CTA button.
   *
   * @param \Drupal\Core\Url $url
   *   The href property for the button.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $text
   *   The label of the button.
   *
   * @return array
   *   The renderable array.
   */
  public function getCtaButton(Url $url, TranslatableMarkup $text) {
    return [
      '#theme' => 'message_cta_button',
      '#link' => $url,
      '#text' => $text,
    ];
  }

  /**
   * Process mentions in text, if the Social Mentions module exists.
   *
   * @param string $text
   *   Text we need to process.
   * @param string $language_id
   *   Langcode of entity.
   *
   * @return \Drupal\filter\FilterProcessResult|string
   *   Result with processed text.
   */
  public function processMentionsPreview(string $text, string $language_id) {
    $result = '';
    if ($this->moduleHandler->moduleExists('social_mentions')) {
      /** @var \Drupal\filter\Plugin\FilterInterface $mentions_filter */
      $mentions_filter = $this->filterPluginManager->createInstance(
        'filter_mentions',
         [
           'settings' => [
             'mentions_filter' => [
               'ProfileMention' => 1,
               'UserMention' => 1,
             ],
           ],
         ],
      );
      $result = $mentions_filter->process(
        $text, $language_id
      );
    }
    return $result;
  }

}
