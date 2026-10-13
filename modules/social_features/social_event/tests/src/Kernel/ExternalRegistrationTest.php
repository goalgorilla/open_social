<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageTemplate;
use Drupal\message\MessageInterface;
use Drupal\node\Entity\Node;
use Drupal\social_event\Entity\Node\Event;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Form\EnrollActionForm;
use Drupal\social_event\Plugin\Validation\Constraint\ExternalEventRegistrationConstraint;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;

/**
 * Tests per-event external registration.
 *
 * @group social_event
 */
class ExternalRegistrationTest extends SocialEventGraphQLKernelTestBase {

  use NodeCreationTrait;

  private const REGISTRATION_URL = 'https://lu.ma/govtech-event';

  /**
   * {@inheritdoc}
   */
  protected function setUpCommonSchemaConfig(): void {
    $this->installEntitySchema('event_enrollment');
    parent::setUpCommonSchemaConfig();
  }

  /**
   * A missing or invalid registration URL blocks saving an external event.
   */
  public function testRegistrationUrlValidation(): void {
    $constraint = new ExternalEventRegistrationConstraint();
    $this->assertSame([$constraint->missingUrlMessage], $this->registrationUrlViolations(TRUE, NULL));
    $this->assertContains($constraint->missingUrlMessage, $this->registrationUrlViolations(TRUE, '   '));
    $this->assertContains($constraint->invalidUrlMessage, $this->registrationUrlViolations(TRUE, 'ftp://lu.ma/event'));
    $this->assertContains($constraint->invalidUrlMessage, $this->registrationUrlViolations(TRUE, 'https://lu.ma/govtech event'));
    $this->assertSame([], $this->registrationUrlViolations(TRUE, self::REGISTRATION_URL));
    $this->assertSame([], $this->registrationUrlViolations(TRUE, 'http://lu.ma/event'));

    // Native events do not need a registration URL.
    $this->assertSame([], $this->registrationUrlViolations(FALSE, NULL));
  }

  /**
   * External registration turns native enrolment off; native events keep it.
   */
  public function testExternalRegistrationDisablesNativeEnrollment(): void {
    $external = $this->createRegistrationEvent(TRUE, ['field_event_enroll' => 1]);
    $this->assertTrue($external->isExternalRegistration());
    $this->assertSame(self::REGISTRATION_URL, $external->getExternalRegistrationUrl());
    $this->assertFalse($external->isEnrollmentEnabled());
    $this->assertFalse($external->showEnrollments());

    $external->set('field_external_registration', FALSE)->save();
    $this->assertFalse($external->isExternalRegistration());

    $native_enabled = $this->createRegistrationEvent(FALSE, ['field_event_enroll' => 1]);
    $this->assertFalse($native_enabled->isExternalRegistration());
    $this->assertNull($native_enabled->getExternalRegistrationUrl());
    $this->assertSame('1', $native_enabled->get('field_event_enroll')->getString());
    $this->assertTrue($native_enabled->isEnrollmentEnabled());

    $native_disabled = $this->createRegistrationEvent(FALSE, ['field_event_enroll' => 0]);
    $this->assertFalse($native_disabled->isEnrollmentEnabled());
  }

  /**
   * Existing enrolments do not count as participation after the switch.
   */
  public function testParticipationIsIgnoredForExternalRegistration(): void {
    $account = $this->createAccount();
    $event = $this->createRegistrationEvent(FALSE, ['field_event_enroll' => 1]);
    $this->createEnrollment($event, (int) $account->id());

    $this->assertTrue($event->getParticipation($account));

    $event->set('field_external_registration', TRUE);
    $event->set('field_event_registration_url', self::REGISTRATION_URL);
    $event->save();

    $this->assertFalse($event->getParticipation($account));
  }

  /**
   * Enrolments on external events are saved but logged.
   */
  public function testEnrollmentOnExternalEventIsLogged(): void {
    $logger = new class() implements LoggerInterface {
      use RfcLoggerTrait;

      /**
       * The logged warnings for the social_event channel.
       *
       * @var string[]
       */
      public array $warnings = [];

      /**
       * {@inheritdoc}
       */
      public function log($level, string|\Stringable $message, array $context = []): void {
        if ($level === RfcLogLevel::WARNING && ($context['channel'] ?? '') === 'social_event') {
          $this->warnings[] = strtr((string) $message, $context);
        }
      }

    };
    $this->container->get('logger.factory')->addLogger($logger);

    $account = $this->createAccount();
    $native = $this->createRegistrationEvent(FALSE, ['field_event_enroll' => 1]);
    $this->createEnrollment($native, (int) $account->id());
    $this->assertSame([], $logger->warnings);

    $external = $this->createRegistrationEvent(TRUE);
    $enrollment = $this->createEnrollment($external, (int) $account->id());

    $this->assertNotNull($enrollment->id());
    $this->assertSame([
      "Enrollment {$enrollment->id()} was created for event {$external->id()}, which uses external registration.",
    ], $logger->warnings);
  }

  /**
   * Enrolment messages on external events get no destination.
   */
  public function testEnrollmentMessagesOnExternalEventHaveNoDestination(): void {
    MessageTemplate::create([
      'template' => 'external_registration_test',
      'label' => 'External registration test',
    ])->save();
    foreach (['field_message_destination', 'field_message_related_object'] as $field_name) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'message',
        'bundle' => 'external_registration_test',
        'settings' => $field_name === 'field_message_related_object' ? [
          'event_enrollment' => [
            'handler' => 'default:event_enrollment',
            'handler_settings' => [],
          ],
        ] : [],
      ])->save();
    }

    $account = $this->createAccount();
    $native = $this->createRegistrationEvent(FALSE, ['field_event_enroll' => 1]);
    $external = $this->createRegistrationEvent(TRUE);

    $native_message = $this->createEnrollmentMessage($this->createEnrollment($native, (int) $account->id()));
    $this->assertSame([['value' => 'email']], $native_message->get('field_message_destination')->getValue());

    $external_message = $this->createEnrollmentMessage($this->createEnrollment($external, (int) $account->id()));
    $this->assertTrue($external_message->get('field_message_destination')->isEmpty());
  }

  /**
   * Native enrolment routes are denied for external events.
   */
  public function testEnrollmentRouteAccess(): void {
    $access = $this->container->get('social_event.external_registration_enrollment_access');
    $account = $this->createAccount();
    $external = $this->createRegistrationEvent(TRUE);
    $native = $this->createRegistrationEvent(FALSE, ['field_event_enroll' => 1]);
    $external_enrollment = $this->createEnrollment($external, (int) $account->id());
    $native_enrollment = $this->createEnrollment($native, (int) $account->id());

    $result = $access->access($this->routeMatch(['node' => $external]));
    $this->assertInstanceOf(AccessResultForbidden::class, $result);
    $this->assertSame('The event uses external registration.', $result->getReason());
    $this->assertContains('node:' . $external->id(), $result->getCacheTags());

    // Routes may only provide the raw node ID.
    $raw_match = new RouteMatch('test', new Route('/node/{node}'), [], ['node' => $external->id()]);
    $this->assertTrue($access->access($raw_match)->isForbidden());

    $this->assertTrue($access->access($this->routeMatch(['node' => $native]))->isAllowed());
    $this->assertTrue($access->access($this->routeMatch([]))->isAllowed());

    $result = $access->access($this->routeMatch(['node' => $native, 'event_enrollment' => $external_enrollment]));
    $this->assertInstanceOf(AccessResultForbidden::class, $result);
    $this->assertSame('The enrolment does not belong to this event.', $result->getReason());

    $this->assertTrue($access->access($this->routeMatch(['node' => $external, 'event_enrollment' => $external_enrollment]))->isForbidden());
    $this->assertTrue($access->access($this->routeMatch(['event_enrollment' => $external_enrollment]))->isForbidden());
    $this->assertTrue($access->access($this->routeMatch(['node' => $native, 'event_enrollment' => $native_enrollment]))->isAllowed());

    $route_provider = $this->container->get('router.route_provider');
    foreach ([
      'social_event.update_enrollment_request',
      'social_event.enrollment_request_decline_form',
      'social_event.request_enroll_dialog',
    ] as $route_name) {
      $this->assertSame('TRUE', $route_provider->getRouteByName($route_name)->getRequirement('_social_event_external_registration_access'), $route_name);
    }

    // The requirement denies even the administrator.
    $admin = $this->container->get('entity_type.manager')->getStorage('user')->load(1);
    $this->assertFalse(Url::fromRoute('social_event.request_enroll_dialog', ['node' => $external->id()])->access($admin));
  }

  /**
   * External events show a link to the registration URL instead of enrolment.
   */
  public function testRegistrationButton(): void {
    $builder = $this->container->get('social_event.external_registration_link_builder');

    $event = $this->createRegistrationEvent(TRUE);
    $build = $builder->build($event);
    $this->assertSame('link', $build['register']['#type']);
    $this->assertSame('Register for event', (string) $build['register']['#title']);
    $this->assertSame(self::REGISTRATION_URL, $build['register']['#url']->toString());
    $this->assertSame('_blank', $build['register']['#attributes']['target']);
    $this->assertSame('noopener noreferrer', $build['register']['#attributes']['rel']);
    $this->assertContains('node:' . $event->id(), $build['#cache']['tags']);

    $passed = $this->createRegistrationEvent(TRUE, [
      'field_event_date' => gmdate('Y-m-d\TH:i:s', strtotime('-2 days')),
      'field_event_date_end' => gmdate('Y-m-d\TH:i:s', strtotime('-1 day')),
    ]);
    $build = $builder->build($passed);
    $this->assertSame('button', $build['register']['#tag']);
    $this->assertSame('Event has passed', (string) $build['register']['#value']);
    $this->assertSame('disabled', $build['register']['#attributes']['disabled']);

    // Stored values that bypassed validation do not render a link.
    $invalid = $this->createRegistrationEvent(TRUE, ['field_event_registration_url' => 'ftp://lu.ma/event']);
    $build = $builder->build($invalid);
    $this->assertArrayNotHasKey('register', $build);
    $this->assertContains('node:' . $invalid->id(), $build['#cache']['tags']);

    $this->assertArrayNotHasKey('register', $builder->build($this->createRegistrationEvent(FALSE, ['field_event_enroll' => 1])));

    // The native enrol form is not built for external events.
    foreach ([$this->container->get('current_user')->getAccount(), new AnonymousUserSession()] as $account) {
      $this->setCurrentUser($account);
      $this->setRouteNode($event);
      $form = $this->container->get('form_builder')->getForm(EnrollActionForm::class);
      $this->assertArrayNotHasKey('enroll_for_this_event', $form);
    }
  }

  /**
   * The enrolment field group follows the external registration checkbox.
   */
  public function testEnrollmentFieldGroup(): void {
    $element = $this->processEnrollmentFieldGroup();
    $this->assertSame([
      ':input[name="field_event_enroll[value]"]' => ['checked' => TRUE],
      ':input[name="field_external_registration[value]"]' => ['checked' => FALSE],
    ], $element['group_enrollment_methods']['#states']['visible']);
    $this->assertArrayNotHasKey('#access', $element['group_enrollment']);
    $this->assertArrayNotHasKey('#access', $element['field_event_enroll']);

    // External registration stays available when native enrolment is off.
    $this->config('social_event.settings')->set('disable_event_enroll', TRUE)->save();
    $element = $this->processEnrollmentFieldGroup();
    $this->assertArrayNotHasKey('#access', $element['group_enrollment']);
    $this->assertArrayNotHasKey('#access', $element['field_external_registration']);
    $this->assertArrayNotHasKey('#access', $element['field_event_registration_url']);
    $this->assertFalse($element['field_event_enroll']['#access']);
    $this->assertFalse($element['group_enrollment_methods']['#access']);

    // Without the external registration field the whole group is hidden.
    $element = $this->processEnrollmentFieldGroup(FALSE);
    $this->assertFalse($element['group_enrollment']['#access']);
  }

  /**
   * Validates an event and returns the registration URL violation messages.
   *
   * @return string[]
   *   The violation messages.
   */
  protected function registrationUrlViolations(bool $external, ?string $url): array {
    $event = Node::create([
      'type' => 'event',
      'title' => $this->randomMachineName(),
      'field_external_registration' => $external,
      'field_event_registration_url' => $url === NULL ? [] : ['uri' => $url],
    ]);

    $messages = [];
    foreach ($event->validate()->getByField('field_event_registration_url') as $violation) {
      $messages[] = (string) $violation->getMessage();
    }
    return $messages;
  }

  /**
   * Creates an upcoming public event.
   */
  protected function createRegistrationEvent(bool $external, array $values = []): Event {
    $event = $this->createNode($values + [
      'type' => 'event',
      'title' => $this->randomMachineName(),
      'field_content_visibility' => 'public',
      'field_event_date' => gmdate('Y-m-d\TH:i:s', strtotime('+1 day')),
      'field_event_date_end' => gmdate('Y-m-d\TH:i:s', strtotime('+2 days')),
      'field_external_registration' => $external,
      'field_event_registration_url' => $external ? self::REGISTRATION_URL : [],
      'status' => 1,
    ]);
    assert($event instanceof Event);
    return $event;
  }

  /**
   * Creates an enrolled enrolment for an account.
   */
  protected function createEnrollment(Event $event, int $uid): EventEnrollmentInterface {
    $enrollment = $this->container->get('entity_type.manager')->getStorage('event_enrollment')->create([
      'field_event' => $event->id(),
      'field_account' => $uid,
      'field_enrollment_status' => '1',
      'user_id' => $uid,
    ]);
    $enrollment->save();
    return $enrollment;
  }

  /**
   * Saves an email message about an enrolment.
   */
  protected function createEnrollmentMessage(EventEnrollmentInterface $enrollment): MessageInterface {
    $message = Message::create([
      'template' => 'external_registration_test',
      'uid' => $enrollment->getAccount(),
      'field_message_destination' => [['value' => 'email']],
      'field_message_related_object' => [
        'target_type' => 'event_enrollment',
        'target_id' => $enrollment->id(),
      ],
    ]);
    $message->save();
    return $message;
  }

  /**
   * Creates a user account.
   */
  protected function createAccount(): UserInterface {
    $account = $this->createUser();
    assert($account instanceof UserInterface);
    return $account;
  }

  /**
   * Builds a route match with the given upcasted parameters.
   */
  protected function routeMatch(array $parameters): RouteMatch {
    $raw = array_map(static fn ($entity) => $entity->id(), $parameters);
    $path = '/test' . implode('', array_map(static fn ($name) => '/{' . $name . '}', array_keys($parameters)));
    return new RouteMatch('test', new Route($path), $parameters, $raw);
  }

  /**
   * Puts an event on the current route, the way the enrol form reads it.
   */
  protected function setRouteNode(Event $event): void {
    $this->container->set('current_route_match', new RouteMatch(
      'entity.node.canonical',
      new Route('/node/{node}'),
      ['node' => $event],
      ['node' => $event->id()],
    ));
  }

  /**
   * Runs the field group process alter on an event form enrolment section.
   */
  protected function processEnrollmentFieldGroup(bool $with_external_registration = TRUE): array {
    $element = [
      'group_enrollment' => ['#type' => 'details'],
      'group_enrollment_methods' => ['#type' => 'fieldset', '#title' => 'Enrollment methods'],
      'field_event_enroll' => ['#type' => 'container'],
    ];
    if ($with_external_registration) {
      $element['field_external_registration'] = ['#type' => 'container'];
      $element['field_event_registration_url'] = ['#type' => 'container'];
    }

    $form_state = new FormState();
    $complete_form = [];
    social_event_field_group_form_process_build_alter($element, $form_state, $complete_form);
    return $element;
  }

}
