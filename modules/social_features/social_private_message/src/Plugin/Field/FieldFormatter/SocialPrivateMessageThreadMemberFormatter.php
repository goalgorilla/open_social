<?php

namespace Drupal\social_private_message\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\private_message\Plugin\Field\FieldFormatter\PrivateMessageThreadMemberFormatter;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the private message member field formatter.
 *
 * @FieldFormatter(
 *   id = "social_private_message_thread_member_formatter",
 *   label = @Translation("Social Private Message Thread Members"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class SocialPrivateMessageThreadMemberFormatter extends PrivateMessageThreadMemberFormatter {

  /**
   * Renderer services.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected Renderer $renderer;

  /**
   * Construct a PrivateMessageThreadFormatter object.
   *
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The field settings.
   * @param mixed $label
   *   The label of the field.
   * @param string $view_mode
   *   The current view mode.
   * @param array $third_party_settings
   *   The third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    EntityDisplayRepositoryInterface $entity_display_repository,
    Renderer $renderer
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $entityTypeManager, $currentUser, $entity_display_repository);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('entity_display.repository'),
      $container->get('renderer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $access_profiles = $this->currentUser->hasPermission('access user profiles');
    $users = [];

    $view_builder = $this->entityTypeManager->getViewBuilder('user');

    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemList $item */
      $user = $item->entity;

      if ($user instanceof UserInterface) {
        if ($this->getSetting('display_type') == 'label') {
          if ($access_profiles) {
            $url = Url::fromRoute('entity.user.canonical', ['user' => $user->id()]);
            $users[$user->id()] = new FormattableMarkup('<a href=":link">@username</a>', [
              ':link' => $url->toString(),
              '@username' => $user->getDisplayName(),
            ]);
          }
          else {
            $users[$user->id()] = $user->getDisplayName();
          }
        }
        elseif ($this->getSetting('display_type') == 'entity') {
          $renderable = $view_builder->view($user, $this->getSetting('entity_display_mode'));
          $users[$user->id()] = $this->renderer->render($renderable);
        }
      }
      else {
        $users['Missing-' . $delta] = $this->t('Deleted user');
      }
    }

    $separator = $this->getSetting('display_type') == 'label' ? ', ' : '';

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['message__thread-members'],
      ],
      '#value' => implode($separator, $users),
    ];
  }

}
