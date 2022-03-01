<?php

namespace Drupal\social_user\Form;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialUserSettingsForm.
 *
 * @package Drupal\social_user\Form
 */
class SocialUserSettingsForm extends ConfigFormBase {

  /**
   * Provides a proxy class for \Drupal\Core\Routing\RouteBuilder.
   *
   * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected RouteBuilder $routeBuilder;

  /**
   * Passes cache tag events to classes that wish to respond to them.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected CacheTagsInvalidator $cacheTagsInvalidator;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\ProxyClass\Routing\RouteBuilder $route_builder
   *   Provides a proxy class for \Drupal\Core\Routing\RouteBuilder.
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $cache_tags_invalidator
   *   Passes cache tag events to classes that wish to respond to them.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RouteBuilder $route_builder,
    CacheTagsInvalidator $cache_tags_invalidator
  ) {
    parent::__construct($config_factory);
    $this->routeBuilder = $route_builder;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_user.settings');

    $form['social_user_profile_landingpage'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a default landing page'),
      '#description' => $this->t('When visiting a profile the user will end up at this page first'),
      '#options' => [
        'social_user.stream' => $this->t('Stream'),
        'view.user_information.user_information' => $this->t('Information'),
      ],
      '#default_value' => $config->get('social_user_profile_landingpage'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('social_user.settings')
      ->set('social_user_profile_landingpage', $form_state->getValue('social_user_profile_landingpage'))
      ->save();

    // Rebuild the router cache.
    $this->routeBuilder->rebuild();

    // Invalidate cache tags.
    $this->cacheTagsInvalidator->invalidateTags(['rendered']);
  }

}
