<?php

namespace Drupal\social_event_addtocal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Eluceo\iCal\Domain\Entity\TimeZone;
use Eluceo\iCal\Presentation\Factory\TimeZoneFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AddToCalendarIcsController.
 */
class AddToCalendarIcsController extends ControllerBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * AddToCalendarIcsController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(Request $request, FileSystemInterface $file_system) {
    $this->request = $request;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('file_system')
    );
  }

  /**
   * Download generated ICS file.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Empty array.
   */
  public function downloadIcs() {
    // Event dates.
    $dates = $this->request->get('dates');
    $timezone = $this->request->get('timezone');

    // Generate DTSTAMP, which needs to be the time the ICS object created.
    // https://icalendar.org/iCalendar-RFC-5545/3-6-1-event-component.html
    $now = new \DateTime('now', new \DateTimezone('UTC'));
    $dtstamp = $now->format('Ymd\THis\Z');

    // Create ICS filename.
    $name = md5(serialize($this->request->query->all()));
    $filename = $name . '.ics';

    // ICS file destination.
    $file = 'temporary://' . $filename;

    // Generate data for ICS file if it not exists.
    if (!file_exists($file)) {
      // Set initial data for identifying the event.
      $file_data = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:Event ' . $this->request->get('nid') . " - " . $this->request->get('title'),
        'METHOD:PUBLISH',
      ];

      // Generate the VTIMEZONE.
      $timezone_components = [];
      $timezone_components[$timezone] = $this->generateTimezoneComponent($dates['start'], $timezone);
      $timezone_components[$timezone] = $this->generateTimezoneComponent($dates['end'], $timezone);

      $componentFactory = new TimeZoneFactory();
      $timezone_elements = $componentFactory->createComponents($timezone_components);
      foreach ($timezone_elements as $el) {
        $file_data[] = rtrim($el->__toString());
      }

      // Begin the event data.
      $file_data[] = 'BEGIN:VEVENT';
      $file_data[] = 'SUMMARY:' . $this->request->get('title');
      $file_data[] = 'UID:' . $name;
      $file_data[] = 'TRANSP:OPAQUE';

      // Set start and end datetime for event.
      if ($dates['all_day']) {
        $file_data[] = 'DTSTART:' . $dates['start'];
        $file_data[] = 'DTEND:' . $dates['end'];
      }
      else {
        $file_data[] = 'DTSTART;TZID=' . $dates['start'];
        $file_data[] = 'DTEND;TZID=' . $dates['end'];
      }

      // Add the DTSTAMP.
      $file_data[] = 'DTSTAMP:' . $dtstamp;

      // Set location.
      if ($this->request->get('location')) {
        $file_data[] = 'LOCATION:' . $this->request->get('location');
      }

      // Set description.
      if ($this->request->get('description')) {
        $file_data[] = 'DESCRIPTION:' . $this->request->get('description');
      }

      // Set end of file.
      $file_data[] = 'END:VEVENT';
      $file_data[] = 'END:VCALENDAR';

      // Convert array to correct ICS format.
      $data = implode("\r\n", $file_data);

      // Save datta to file.
      $this->fileSystem->saveData($data, $file, FileSystemInterface::EXISTS_REPLACE);
    }

    // Set response for file download.
    $response = new BinaryFileResponse($file);
    $response->headers->set('Content-Type', 'application/calendar; charset=utf-8');
    $response->setContentDisposition('attachment', $filename);

    return $response;
  }

  /**
   * Helper method to generate the VTIMEZONE component for a date.
   *
   * @param string $date
   *   Date string.
   * @param string $timezone
   *   Timezone string.
   *
   * @return \Eluceo\iCal\Domain\Entity\TimeZone
   *   Returns the TimeZone component.
   */
  protected function generateTimezoneComponent(string $date, string $timezone) {
    $date = str_replace("$timezone:", "", $date);
    $timezone_component = TimeZone::createFromPhpDateTimeZone(
      new \DateTimeZone($timezone),
      new \DateTimeImmutable($date, new \DateTimeZone($timezone)),
      new \DateTimeImmutable($date, new \DateTimeZone($timezone)),
    );

    return $timezone_component;
  }

}
