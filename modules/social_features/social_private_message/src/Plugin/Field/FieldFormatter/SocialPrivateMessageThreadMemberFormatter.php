<?php

namespace Drupal\social_private_message\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\private_message\Plugin\Field\FieldFormatter\PrivateMessageThreadMemberFormatter;
use Drupal\user\UserInterface;

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
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
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
