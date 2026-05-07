<?php

namespace Drupal\social_course\Form;

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
 * Provides a form to request group membership for anonymous.
 */
class CourseJoinAnonymousForm extends FormBase {

  /**
   * The group entity object.
   */
  protected ?GroupInterface $group;

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * CourseJoinAnonymousForm constructor.
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
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('string_translation'),
      $container->get('request_stack'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_course_join_anonymous';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?GroupInterface $group = NULL,
  ): array {
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('In order to join a course, please first sign up or log in.'),
    ];

    $form['actions']['#type'] = 'actions';

    if (($this->group = $group) !== NULL) {
      $previous_url = $this->getRequest()->headers->get('referer');

      // The HTTP Referer header is optional and may be NULL, empty, or
      // malformed. Only build a Request from it when we have a usable
      // string; downstream link builders already guard with isset() on
      // $referer_path and $destination, so skipping is safe.
      if (is_string($previous_url) && $previous_url !== '') {
        try {
          $request = Request::create($previous_url);
          $referer_path = $request->getRequestUri();
        }
        catch (\Throwable) {
          // Ignore malformed referers; fall through to the no-destination
          // flow.
        }

        if (isset($referer_path) && $this->moduleHandler->moduleExists('social_group_quickjoin')) {
          $destination = Url::fromRoute(
            'social_group_quickjoin.quickjoin_group',
            ['group' => $group->id()],
          )->toString();

          $referer_path .= '?' . $destination;
        }
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
