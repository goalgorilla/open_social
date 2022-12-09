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

    // Create ICS filename.
    $name = md5(serialize($this->request->query->all()));
    $filename = $name . '.ics';

    // ICS file destination.
    $file = 'temporary://' . $filename;

    // Generate data for ICS file if it not exists.
    if (!file_exists($file)) {
      // Set initial data.
      $file_data = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $name,
        'SUMMARY:' . $this->request->get('title'),
      ];

      // Generate the VTIMEZONE.
      $timezone_components = [];

      $timezone_components[$timezone] = $this->generateTimezoneComponent($dates['start'], $timezone);
      $timezone_components[$timezone] = $this->generateTimezoneComponent($dates['end'], $timezone);

      $componentFactory = new TimeZoneFactory();
      $timezone_elements = $componentFactory->createComponents($timezone_components);
      foreach ($timezone_elements as $el) {
        $file_data[] = $el->__toString();
      }

      // Set start and end datetime for event.
      if ($dates['all_day']) {
        $file_data[] = 'DTSTART:' . $dates['start'];
        $file_data[] = 'DTEND:' . $dates['end'];
      }
      else {
        $file_data[] = 'DTSTART;TZID=' . $dates['start'];
        $file_data[] = 'DTEND;TZID=' . $dates['end'];
      }

      // Set location.
      if ($this->request->get('description')) {
        $file_data[] = 'DESCRIPTION:' . $this->request->get('description');
      }

      // Set description.
      if ($this->request->get('location')) {
        $file_data[] = 'LOCATION:' . $this->request->get('location');
      }

      // Set end of file.
      $file_data[] = 'TRANSP:OPAQUE';
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
