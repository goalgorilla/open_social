<?php

namespace Drupal\social_user\Plugin\Block;

use Drupal\user\Plugin\Block\UserLoginBlock;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Provides a 'SocialUserLoginBlock' block.
 *
 * @Block(
 *  id = "social_user_login_block",
 *  admin_label = @Translation("Social user login block"),
 * )
 */
class SocialUserLoginBlock extends UserLoginBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\social_user\Form\SocialUserLoginForm');
    unset($form['name_or_mail']['#attributes']['autofocus']);
    // When unsetting field descriptions, also unset aria-describedby attributes
    // to avoid introducing an accessibility bug.
    // @todo Do this automatically in https://www.drupal.org/node/2547063.
    unset($form['name_or_mail']['#description']);
    unset($form['name_or_mail']['#attributes']['aria-describedby']);
    unset($form['pass']['#description']);
    unset($form['pass']['#attributes']['aria-describedby']);
    $form['name_or_mail']['#size'] = 15;
    $form['pass']['#size'] = 15;

    // See UserLoginBlock::build() for the logic behind this.
    $placeholder = 'form_action_p_4r8ITd22yaUvXM6SzwrSe9rnQWe48hz9k1Sxto3pBvE';
    $form['#attached']['placeholders'][$placeholder] = [
      '#lazy_builder' => ['\Drupal\user\Plugin\Block\UserLoginBlock::renderPlaceholderFormAction', []],
    ];
    $form['#action'] = $placeholder;

    // Build action links.
    $items = [];
    if (\Drupal::config('user.settings')->get('register') !== UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      $items['create_account'] = [
        '#type' => 'link',
        '#title' => $this->t('Create new account'),
        '#url' => Url::fromRoute('user.register', [], [
          'attributes' => [
            'title' => $this->t('Create a new user account.'),
            'class' => ['create-account-link'],
          ],
        ]),
      ];
    }
    $items['request_password'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset your password'),
      '#url' => Url::fromRoute('user.pass', [], [
        'attributes' => [
          'title' => $this->t('Send password reset instructions via email.'),
          'class' => ['request-password-link'],
        ],
      ]),
    ];
    return [
      'social_user_login_form' => $form,
      'user_links' => [
        '#theme' => 'item_list',
        '#items' => $items,
      ],
    ];
  }

}
