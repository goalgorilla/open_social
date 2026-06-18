<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\social_event\Plugin\Field\FieldWidget\EventMeetingWidget;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the scheduler render array built by the Event Meeting widget.
 *
 * @coversDefaultClass \Drupal\social_event\Plugin\Field\FieldWidget\EventMeetingWidget
 * @group social_event
 */
class EventMeetingWidgetSchedulerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // DrupalDateTime resolves a default langcode via the language manager.
    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');
    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->method('getCurrentLanguage')->willReturn($language);

    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Invokes the protected ::buildScheduler() with a controlled context.
   *
   * @return array
   *   The scheduler render array.
   */
  protected function buildScheduler(): array {
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $start = new DrupalDateTime('2026-06-18 10:00:00', $storage_timezone);
    $end = new DrupalDateTime('2026-06-18 12:00:00', $storage_timezone);

    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->method('getTimeZone')->willReturn('Europe/Amsterdam');

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(
      static fn (array $key) => match ($key) {
        ['field_event_date', 0, 'value'] => $start,
        ['field_event_date_end', 0, 'value'] => $end,
        default => NULL,
      }
    );

    $attendees_field = $this->createMock(FieldItemListInterface::class);
    $attendees_field->method('getString')->willReturn('50');

    $meeting_entity = $this->createMock(MeetingEntityInterface::class);
    $meeting_entity->method('getServerId')->willReturn('server_1');
    $meeting_entity->method('isNew')->willReturn(TRUE);
    $meeting_entity->method('get')->with('max_attendees')->willReturn($attendees_field);

    $reflection = new \ReflectionClass(EventMeetingWidget::class);
    $widget = $reflection->newInstanceWithoutConstructor();

    $current_user_property = $reflection->getProperty('currentUser');
    $current_user_property->setAccessible(TRUE);
    $current_user_property->setValue($widget, $current_user);

    $widget->setStringTranslation($this->getStringTranslationStub());

    $method = $reflection->getMethod('buildScheduler');
    $method->setAccessible(TRUE);

    return $method->invoke($widget, $form_state, $meeting_entity);
  }

  /**
   * The scheduler is wrapped so the AJAX ReplaceCommand keeps working.
   *
   * @covers ::buildScheduler
   */
  public function testSchedulerKeepsTheReplaceableWrapper(): void {
    $element = $this->buildScheduler();

    $this->assertSame('container', $element['#type']);
    $this->assertSame('meeting-scheduler-wrapper', $element['#attributes']['id']);
  }

  /**
   * An explanatory warning message is shown together with the calendar.
   *
   * Normal users do not understand why a calendar appears when their chosen
   * timeslot is full, so a warning message must accompany it.
   *
   * @covers ::buildScheduler
   */
  public function testSchedulerShowsExplanatoryWarning(): void {
    $element = $this->buildScheduler();

    $this->assertArrayHasKey('message', $element);
    $this->assertSame('status_messages', $element['message']['#theme']);
    $this->assertArrayHasKey('warning', $element['message']['#message_list']);

    $warning = (string) $element['message']['#message_list']['warning'][0];
    $this->assertSame(
      'This time slot is already full, reduce the number of attendees or find a free slot in the BigBlueButton agenda below.',
      $warning
    );
  }

  /**
   * The calendar element is still present alongside the warning.
   *
   * @covers ::buildScheduler
   */
  public function testSchedulerStillRendersTheCalendar(): void {
    $element = $this->buildScheduler();

    $this->assertArrayHasKey('calendar', $element);
    $this->assertSame('meeting_api_period_schedule', $element['calendar']['#type']);
  }

}
