<?php

namespace Drupal\social_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The SocialUserSettingsForm class.
 *
 * @package Drupal\social_user\Form
 */
class SocialUserSettingsForm extends ConfigFormBase {

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->routerBuilder = $container->get('router.builder');
    $instance->cacheTagInvalidator = $container->get('cache_tags.invalidator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'social_user.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('social_user.settings');

    $form['social_user_profile_landingpage'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a default landing page'),
      '#description' => $this->t('When visiting a profile the user will end up at this page first'),
      '#options' => [
        'social_user.stream' => $this->t('Stream'),
        'view.user_information.user_information' => $this->t('Information'),
        '<front>' => $this->t('Home'),
      ],
      '#default_value' => $config->get('social_user_profile_landingpage'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('social_user.settings')
      ->set('social_user_profile_landingpage', $form_state->getValue('social_user_profile_landingpage'))
      ->save();

    $this->routerBuilder->rebuild();
    $this->cacheTagInvalidator->invalidateTags(['rendered']);
  }

}
