<?php

namespace Drupal\social_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\social_core\InviteService;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Traits\ViewsBulkOperationsFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for social_core module routes.
 */
class SocialCoreController extends ControllerBase {

  use ViewsBulkOperationsFormTrait;

  /**
   * The private temporary storage factory.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The Views Bulk Operations action processor.
   */
  protected ViewsBulkOperationsActionProcessorInterface $actionProcessor;

  /**
   * The currently active route match object.
   */
  private RouteMatchInterface $routeMatch;

  /**
   * The invite service.
   */
  private InviteService $invite;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SocialGroupController constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $action_processor
   *   The Views Bulk Operations action processor.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\social_core\InviteService $invite
   *   The invite service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    ViewsBulkOperationsActionProcessorInterface $action_processor,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    InviteService $invite,
    LanguageManagerInterface $language_manager,
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->actionProcessor = $action_processor;
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->invite = $invite;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('tempstore.private'),
      $container->get('views_bulk_operations.processor'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('social_core.invite'),
      $container->get('language_manager'),
    );
  }

  /**
   * Custom function for returning markup on the access denied page.
   */
  public function accessDenied(): array {
    // Get the front page URL.
    $frontpage = $this->config('system.site')->get('page.front');

    // Determine the message we want to set.
    $text = $this->t(
      "<p>You have insufficient permissions to view the page you're trying to access. There could be several reasons for this:</p><ul><li>You are trying to edit content you're not allowed to edit.</li><li>You are trying to view content (from a group) you don't have access to.</li><li>You are trying to access administration pages.</li></ul><p>Click the back button of your browser to go back where you came from or click <a href=\":url\">here</a> to go to the homepage</p>",
      [':url' => $frontpage],
    );

    // Return the message in the render array.
    return ['#markup' => $text];
  }

  /**
   * Empty page for the homepage.
   */
  public function stream(): array {
    return ['#markup' => ''];
  }

  /**
   * Redirects a user to the group or events invite page, or home if empty.
   */
  public function myInvitesUserPage(): RedirectResponse {
    /** @var string $invite */
    $invite = $this->invite->getInviteData('name');

    $route_name = !empty($invite)
      // Only when there are actual Invite plugins enabled.
      ? $invite
      // If there are no invites found or no module enabled lets redirect user
      // to their stream.
      : 'social_user.user_home';

    return $this->redirect($route_name, ['user' => $this->currentUser()->id()]);
  }

  /**
   * Generate entity add form titles.
   *
   * @see \Drupal\social_core\Routing\RouteSubscriber::alterRoutes()
   */
  public function generateAddFormTitle(): string {
    $route_name = $this->routeMatch->getRouteName();
    $titles = $this->moduleHandler->invokeAll('social_core_add_form_title_override');

    // Set language to English.
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $language = $this->languageManager->getLanguage('en');
    $this->languageManager->setConfigOverrideLanguage($language);

    // Get label.
    $label = is_callable($titles[$route_name]['label']) ? $titles[$route_name]['label']() : $titles[$route_name]['label'];
    $label = mb_strtolower($label);

    // Transliterate the label.
    $transliterated_label = transliterator_transliterate(
      'Any-Latin; Latin-ASCII',
      $label
    );

    // Determine the article to be used according to the bundle label.
    $article = preg_match('/^[aeiou]/i', (string) $transliterated_label) ? 'an' : 'a';

    // Revert back to the original default language.
    $this->languageManager->setConfigOverrideLanguage($original_language);

    // phpcs:disable
    return $this->t("Create $article $label")->render();
    // phpcs:enable
  }

}
