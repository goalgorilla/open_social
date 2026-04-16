<?php

namespace Drupal\social_user\Hooks;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\hux\Attribute\Alter;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Alters the user registration form with Open Social customizations.
 *
 * @internal
 */
final class UserRegisterFormHooks {

  /**
   * Social user configuration object name.
   */
  private const SOCIAL_USER_SETTINGS = 'social_user.settings';

  /**
   * Weights applied to the core account fields so other modules can slot in.
   */
  private const ACCOUNT_FIELD_WEIGHTS = [
    'mail' => -150,
    'name' => -100,
    'pass' => -50,
  ];

  /**
   * Weight of the "Have an account already?" login link.
   */
  private const LOGIN_LINK_WEIGHT = 1000;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AccountInterface $currentUser,
    private readonly RequestStack $requestStack,
    private readonly UrlGeneratorInterface $urlGenerator,
  ) {
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the user register form.
   *
   * @see \Drupal\user\RegisterForm
   */
  #[Alter('form_user_register_form')]
  public function alter(array &$form, FormStateInterface $form_state): void {
    $this->setDefaultNotifyValue($form);

    if (isset($form['account'])) {
      $this->configureAccountFieldset($form);
      $this->attachSignupHelp($form);
      $this->applyFieldWeights($form['account']);
      $this->maybeAddLoginLink($form);
    }

    $form['#validate'][] = 'social_user_register_validate';
  }

  /**
   * Opts users in to the "notify" checkbox by default.
   */
  private function setDefaultNotifyValue(array &$form): void {
    if (isset($form['account']['notify']) && $form['account']['notify']['#access'] === TRUE) {
      $form['account']['notify']['#default_value'] = 1;
    }
  }

  /**
   * Themes the account container as a socialbase fieldset.
   */
  private function configureAccountFieldset(array &$form): void {
    $form['account']['#type'] = 'fieldset';
    $form['account']['#title'] = new TranslatableMarkup('Sign up with <b>email</b>');
  }

  /**
   * Adds the configured signup help text and its cache tag to the form.
   */
  private function attachSignupHelp(array &$form): void {
    $config = $this->configFactory->get(self::SOCIAL_USER_SETTINGS);
    $signup_help = $config->get('signup_help');

    if (!empty($signup_help)) {
      $form['account']['#description'] = $signup_help;
    }

    // Ensure the rendered form is invalidated when the help text changes.
    $form['#cache']['tags'] = array_merge(
      $form['#cache']['tags'] ?? [],
      $config->getCacheTags(),
    );
  }

  /**
   * Assigns weights to the core account fields.
   */
  private function applyFieldWeights(array &$account): void {
    foreach (self::ACCOUNT_FIELD_WEIGHTS as $field => $weight) {
      if (isset($account[$field])) {
        $account[$field]['#weight'] = $weight;
      }
    }
  }

  /**
   * Adds a login link for anonymous users only.
   *
   * Site managers creating accounts through the admin UI should not see it.
   */
  private function maybeAddLoginLink(array &$form): void {
    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    $form['account']['login-link'] = [
      '#markup' => new TranslatableMarkup('Have an account already? @link', [
        '@link' => $this->buildLoginLink(),
      ]),
      '#weight' => self::LOGIN_LINK_WEIGHT,
      '#cache' => [
        'contexts' => ['url.query_args'],
      ],
    ];
  }

  /**
   * Builds the login link, preserving any destination query arg.
   */
  private function buildLoginLink(): MarkupInterface {
    $options = [];
    $request = $this->requestStack->getCurrentRequest();

    if ($request !== NULL && $request->query->has('destination')) {
      $options['query'] = [
        'destination' => $request->query->get('destination'),
      ];
    }

    $url = $this->urlGenerator->generateFromRoute('user.login', [], $options);

    return new FormattableMarkup('<a href=":url">@text</a>', [
      ':url' => $url,
      '@text' => new TranslatableMarkup('Log in'),
    ]);
  }

}
