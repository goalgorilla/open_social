<?php

namespace Drupal\social_user\Plugin\Block;

use Drupal\user\Plugin\Block\UserLoginBlock;
use Drupal\Core\Url;

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
    $form['#action'] = $this->url('<current>', [], ['query' => $this->getDestinationArray(), 'external' => FALSE]);
    // Build action links.
    $items = array();
    if (\Drupal::config('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
      $items['create_account'] = \Drupal::l($this->t('Create new account'), new Url('user.register', array(), array(
        'attributes' => array(
          'title' => $this->t('Create a new user account.'),
          'class' => array('create-account-link'),
        ),
      )));
    }
    $items['request_password'] = \Drupal::l($this->t('Reset your password'), new Url('user.pass', array(), array(
      'attributes' => array(
        'title' => $this->t('Send password reset instructions via email.'),
        'class' => array('request-password-link'),
      ),
    )));
    return array(
      'social_user_login_form' => $form,
      'user_links' => array(
        '#theme' => 'item_list',
        '#items' => $items,
      ),
    );
  }

}
