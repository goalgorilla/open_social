<?php

namespace Drupal\social_group_request\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class GroupRequestMembershipRequestAnonymousForm extends FormBase {

  /**
   * Group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * Group membership request.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $groupContent;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * GroupRequestMembershipRejectForm constructor.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(
    RedirectDestinationInterface $redirect_destination,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    AccountInterface $current_user,
    TranslationInterface $string_translation
  ) {
    $this->redirectDestination = $redirect_destination;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->currentUser = $current_user;
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('cache_tags.invalidator'),
      $container->get('current_user'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_group_request_membership_request_anonymous';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupInterface $group = NULL) {
    $this->group = $group;

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('In order to send your request, please first sign up or log in.'),
    ];

    $previous_url = \Drupal::requestStack()->getCurrentRequest()->headers->get('referer');
    $request = Request::create($previous_url);
    $referer_path = $request->getRequestUri();

    $form['actions']['#type'] = 'actions';
    $form['actions']['sign_up'] = [
      '#type' => 'link',
      '#title' => $this->t('Sign up'),
      '#attributes' => [
        'class' => [
          'button',
          'button--flat',
          'btn',
          'btn-flat',
          'waves-effect',
          'waves-btn',
        ],
      ],
      '#url' => Url::fromRoute('user.register', [
        'destination' => $referer_path . '?requested-membership=' . $this->group->id(),
      ]),
    ];

    $form['actions']['log_in'] = [
      '#type' => 'link',
      '#title' => $this->t('Log in'),
      '#attributes' => [
        'class' => [
          'button',
          'button--flat',
          'btn',
          'btn-flat',
          'waves-effect',
          'waves-btn',
        ],
      ],
      '#url' => Url::fromRoute('user.login', [
        'destination' => $referer_path . '?requested-membership=' . $this->group->id(),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
