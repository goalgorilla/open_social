<?php

namespace Drupal\social_album\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\hux\Attribute\Alter;

/**
 * Replace hook: social_album_form_post_form_alter.
 *
 * @package Drupal\social_album\Hooks
 */
final class SocialAlbumFormHooks {

  /**
   * The current route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The config factory service.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    ConfigFactoryInterface $config_factory
  ) {
    $this->routeMatch = $route_match;
    $this->configFactory = $config_factory;
  }

  /**
   * Form alter hook: replacement of social_album_form_post_form_alter.
   *
   * @param array $form
   *   The drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  #[Alter('form_post_form')]
  public function formPostFormAlter(array &$form, FormStateInterface $form_state): void {
    if ($this->routeMatch->getRouteName() === 'social_album.post') {
      if (isset($form['current_user_image'])) {
        unset($form['current_user_image']);
      }
    }
    elseif (isset($form['field_album'])) {
      // Hide album select field when feature is disabled on image post form.
      $status = $this->configFactory->get('social_album.settings')->get('status');
      if (!$status) {
        unset($form['field_album']);
      }
      else {
        $form['field_album']['#states'] = [
          'visible' => [
            ':input[name="field_post_image[0][fids]"]' => [
              'filled' => TRUE,
            ],
          ],
        ];
      }
    }
  }

}
