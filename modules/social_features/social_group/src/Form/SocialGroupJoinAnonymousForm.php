<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a login/sign-up form for anonymous in order to join a group.
 */
class SocialGroupJoinAnonymousForm extends FormBase {

  /**
   * The group entity object.
   */
  protected ?GroupInterface $group;

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * SocialGroupJoinAnonymousForm constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    TranslationInterface $string_translation,
    RequestStack $request_stack,
    ModuleHandlerInterface $module_handler,
  ) {
    $this
      ->setStringTranslation($string_translation)
      ->setRequestStack($request_stack);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('string_translation'),
      $container->get('request_stack'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_group_join_anonymous';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?GroupInterface $group = NULL,
  ) {
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('In order to join a group, please first sign up or log in.'),
    ];

    $form['actions']['#type'] = 'actions';

    if (($this->group = $group) !== NULL && $this->getRequest()->headers->has('referer')) {
      $previous_url = (string) $this->getRequest()->headers->get('referer');
      $request = Request::create($previous_url);
      $referer_path = $request->getRequestUri();

      if ($this->moduleHandler->moduleExists('social_group_quickjoin')) {
        $destination = Url::fromRoute(
          'social_group_quickjoin.quickjoin_group',
          ['group' => $group->id()],
        )->toString();

        $referer_path .= '?' . $destination;
      }
    }

    $form['actions']['sign_up'] = [
      '#type' => 'link',
      '#title' => $this->t('Sign up'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'waves-effect',
          'waves-btn',
        ],
      ],
      '#url' => Url::fromRoute(
        'user.register',
        isset($referer_path) ? ['destination' => $referer_path] : [],
      ),
    ];

    $form['actions']['log_in'] = [
      '#type' => 'link',
      '#title' => $this->t('Log in'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-default',
          'waves-effect',
          'waves-btn',
        ],
      ],
      '#url' => Url::fromRoute(
        'user.login',
        isset($destination) ? ['destination' => $destination] : [],
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {}

}
