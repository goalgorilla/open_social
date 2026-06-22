<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\meeting_api\MeetingEntityInterface;
use Drupal\social_event\Plugin\Field\FieldWidget\EventMeetingWidget;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the date resolution of the Event Meeting widget.
 *
 * @coversDefaultClass \Drupal\social_event\Plugin\Field\FieldWidget\EventMeetingWidget
 * @group social_event
 */
class EventMeetingWidgetTest extends UnitTestCase {

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
   * Invokes the protected ::getMeetingDateTime() with a controlled context.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The stored event start date (as it comes from the form/entity).
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The stored event end date.
   * @param string $user_timezone
   *   The timezone of the current user.
   *
   * @return array
   *   The result of ::getMeetingDateTime().
   */
  protected function resolveDates(DrupalDateTime $start, DrupalDateTime $end, string $user_timezone): array {
    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->method('getTimeZone')->willReturn($user_timezone);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(
      static fn (array $key) => match ($key) {
        ['field_event_date', 0, 'value'] => $start,
        ['field_event_date_end', 0, 'value'] => $end,
        default => NULL,
      }
    );

    $reflection = new \ReflectionClass(EventMeetingWidget::class);
    $widget = $reflection->newInstanceWithoutConstructor();
    $current_user_property = $reflection->getProperty('currentUser');
    $current_user_property->setAccessible(TRUE);
    $current_user_property->setValue($widget, $current_user);

    $method = $reflection->getMethod('getMeetingDateTime');
    $method->setAccessible(TRUE);

    return $method->invoke($widget, $form_state);
  }

  /**
   * An all-day event must be anchored to the user's local day.
   *
   * All-day events are stored as midnight UTC for both the start and end
   * dates. They represent a floating calendar day, so the scheduler must show
   * them starting at 00:00 in the user's timezone. Previously the stored UTC
   * midnight was converted to the user timezone, which shifted the start by
   * the timezone offset (e.g. 02:00 for Europe/Amsterdam in summer) and made
   * the calendar appear "cut at 2AM" while the busy slot bled into the next
   * day.
   *
   * @covers ::getMeetingDateTime
   */
  public function testAllDayEventIsAnchoredToLocalDay(): void {
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    // All-day event on 2026-06-18: both dates stored at midnight UTC.
    $start = new DrupalDateTime('2026-06-18 00:00:00', $storage_timezone);
    $end = new DrupalDateTime('2026-06-18 00:00:00', $storage_timezone);

    $dates = $this->resolveDates($start, $end, 'Europe/Amsterdam');

    $this->assertInstanceOf(\DateTimeImmutable::class, $dates['start_date']);
    $this->assertInstanceOf(\DateTimeImmutable::class, $dates['end_date']);

    // The calendar must start at local midnight, not 02:00.
    $this->assertSame('2026-06-18 00:00:00', $dates['start_date']->format('Y-m-d H:i:s'));
    $this->assertSame('+02:00', $dates['start_date']->format('P'));

    // The end must stay within the same local day, not bleed into the 19th.
    $this->assertSame('2026-06-18 23:59:59', $dates['end_date']->format('Y-m-d H:i:s'));
    $this->assertSame('+02:00', $dates['end_date']->format('P'));
  }

  /**
   * A timed (non all-day) event keeps being converted to the user timezone.
   *
   * @covers ::getMeetingDateTime
   */
  public function testTimedEventIsConvertedToUserTimezone(): void {
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    // A meeting from 10:00 to 12:00 UTC.
    $start = new DrupalDateTime('2026-06-18 10:00:00', $storage_timezone);
    $end = new DrupalDateTime('2026-06-18 12:00:00', $storage_timezone);

    $dates = $this->resolveDates($start, $end, 'Europe/Amsterdam');

    // 10:00/12:00 UTC become 12:00/14:00 in Europe/Amsterdam (UTC+2 summer).
    $this->assertSame('2026-06-18 12:00:00', $dates['start_date']->format('Y-m-d H:i:s'));
    $this->assertSame('2026-06-18 14:00:00', $dates['end_date']->format('Y-m-d H:i:s'));
    $this->assertSame('+02:00', $dates['start_date']->format('P'));
  }

  /**
   * Invokes the protected ::putFormValuesToMeeting() with a controlled context.
   *
   * Returns the 'datetime' value array that the widget would set on the
   * meeting entity.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The event start date as it arrives from the form state.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The event end date as it arrives from the form state.
   * @param string $user_timezone
   *   The timezone of the current user.
   *
   * @return array
   *   The 'datetime' value set on the meeting (value, end_value, timezone).
   */
  protected function putDates(DrupalDateTime $start, DrupalDateTime $end, string $user_timezone): array {
    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->method('getTimeZone')->willReturn($user_timezone);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(
      static fn (array $key) => match ($key) {
        ['field_event_date', 0, 'value'] => $start,
        ['field_event_date_end', 0, 'value'] => $end,
        ['title', 0, 'value'] => 'Test',
        default => NULL,
      }
    );

    $captured = [];
    $meeting = $this->createMock(MeetingEntityInterface::class);
    $meeting->method('hasField')->willReturn(TRUE);
    $meeting->method('set')->willReturnCallback(
      function (string $field, $value) use (&$captured) {
        $captured[$field] = $value;
      }
    );

    $reflection = new \ReflectionClass(EventMeetingWidget::class);
    $widget = $reflection->newInstanceWithoutConstructor();

    $current_user_property = $reflection->getProperty('currentUser');
    $current_user_property->setAccessible(TRUE);
    $current_user_property->setValue($widget, $current_user);

    $widget->setStringTranslation($this->getStringTranslationStub());

    $method = $reflection->getMethod('putFormValuesToMeeting');
    $method->setAccessible(TRUE);
    $method->invoke($widget, $meeting, [], $form_state);

    return $captured['datetime'];
  }

  /**
   * An all-day meeting must store the user's local day as a UTC instant.
   *
   * All-day events arrive from the form as midnight UTC for both dates (a
   * floating calendar day). The meeting datetime is consumed as a real UTC
   * instant, so storing the literal midnight UTC would shift the meeting by
   * the user's timezone offset. The widget must re-anchor the span to the
   * user's local day and convert it to UTC for storage.
   *
   * @covers ::putFormValuesToMeeting
   */
  public function testAllDayMeetingStoresUserLocalDayInUtc(): void {
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    // All-day event on 2026-08-20: both dates arrive as midnight UTC.
    $start = new DrupalDateTime('2026-08-20 00:00:00', $storage_timezone);
    $end = new DrupalDateTime('2026-08-20 00:00:00', $storage_timezone);

    $datetime = $this->putDates($start, $end, 'Europe/Kyiv');

    // Europe/Kyiv is UTC+3 in August, so local midnight is 21:00 UTC the day
    // before, and local 23:59:59 is 20:59:59 UTC the same day.
    $this->assertSame('2026-08-19T21:00:00', $datetime['value']);
    $this->assertSame('2026-08-20T20:59:59', $datetime['end_value']);
  }

  /**
   * A timed meeting keeps the already converted UTC instant unchanged.
   *
   * @covers ::putFormValuesToMeeting
   */
  public function testTimedMeetingKeepsUtcInstant(): void {
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    // A timed meeting already arrives converted to UTC (14:00/15:00 Kyiv).
    $start = new DrupalDateTime('2026-08-21 11:00:00', $storage_timezone);
    $end = new DrupalDateTime('2026-08-21 12:00:00', $storage_timezone);

    $datetime = $this->putDates($start, $end, 'Europe/Kyiv');

    $this->assertSame('2026-08-21T11:00:00', $datetime['value']);
    $this->assertSame('2026-08-21T12:00:00', $datetime['end_value']);
  }

}
