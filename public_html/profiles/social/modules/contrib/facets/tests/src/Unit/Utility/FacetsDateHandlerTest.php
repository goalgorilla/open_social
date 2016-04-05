<?php

namespace Drupal\Tests\facets\Unit\Utility;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\facets\Utility\FacetsDateHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test for Date Handler Service.
 *
 * @group facets
 */
class FacetsDateHandlerTest extends UnitTestCase {

  /**
   * Timestamp used by tests: Thu, 26 Nov 1987 20:43:04 GMT.
   */
  const TIMESTAMP = 564957784;

  /**
   * ISO date used by tests: Thu, 26 Nov 1987 20:43:04 GMT.
   */
  const ISO_DATE = '1987-11-26T20:43:04Z';

  /**
   * The system under test.
   *
   * @var \Drupal\facets\Utility\FacetsDateHandler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');

    $em = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $em->expects($this->any())
      ->method('getStorage')
      ->with('date_format')
      ->willReturn($entity_storage);

    $language = new Language(['id' => 'en']);

    $lm = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $lm->method('getCurrentLanguage')
      ->willReturn($language);
    $st = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $rs = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
    $cf = $this->getConfigFactoryStub();

    $config_factory = $this->getConfigFactoryStub([
      'system.date' => ['country' => ['default' => 'GB']],
    ]);
    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);

    $date_formatter = new DateFormatter($em, $lm, $st, $cf, $rs);

    $this->handler = new FacetsDateHandler($date_formatter);
  }

  /**
   * Tests the isoDate method.
   *
   * @dataProvider provideIsoDates
   */
  public function testIsoDate($iso_date, $gap) {
    $fd = $this->handler;
    $this->assertEquals($iso_date, $fd->isoDate(static::TIMESTAMP, $gap));
  }

  /**
   * Tests for ::getNextDateGap.
   */
  public function testGetNextDateGap() {
    $fd = $this->handler;

    $gap = $fd->getNextDateGap($fd::FACETS_DATE_SECOND);
    $this->assertEquals($fd::FACETS_DATE_SECOND, $gap);

    $gap = $fd->getNextDateGap($fd::FACETS_DATE_MINUTE);
    $this->assertEquals($fd::FACETS_DATE_SECOND, $gap);

    $gap = $fd->getNextDateGap($fd::FACETS_DATE_SECOND, $fd::FACETS_DATE_MINUTE);
    $this->assertEquals($fd::FACETS_DATE_MINUTE, $gap);

    $gap = $fd->getNextDateGap($fd::FACETS_DATE_MINUTE, $fd::FACETS_DATE_MINUTE);
    $this->assertEquals($fd::FACETS_DATE_MINUTE, $gap);

    $gap = $fd->getNextDateGap($fd::FACETS_DATE_SECOND, $fd::FACETS_DATE_HOUR);
    $this->assertEquals($fd::FACETS_DATE_HOUR, $gap);

    $gap = $fd->getNextDateGap($fd::FACETS_DATE_MINUTE, $fd::FACETS_DATE_HOUR);
    $this->assertEquals($fd::FACETS_DATE_HOUR, $gap);

    $gap = $fd->getNextDateGap($fd::FACETS_DATE_HOUR, $fd::FACETS_DATE_HOUR);
    $this->assertEquals($fd::FACETS_DATE_HOUR, $gap);
  }

  /**
   * Tests for ::getTimestampGap.
   */
  public function testGetTimestampGap() {
    $fd = $this->handler;

    // The best search gap between two dates must be a year.
    $date_gap = $this->handler->getTimestampGap(static::TIMESTAMP, static::TIMESTAMP + 31536000);
    $this->assertEquals($fd::FACETS_DATE_YEAR, $date_gap);

    // The best search gap between two dates must be a month.
    $date_gap = $this->handler->getTimestampGap(static::TIMESTAMP, static::TIMESTAMP + 86400 * 60);
    $this->assertEquals($fd::FACETS_DATE_MONTH, $date_gap);

    // The best search gap between two dates must be a day.
    $date_gap = $this->handler->getTimestampGap(static::TIMESTAMP, static::TIMESTAMP + 86400);
    $this->assertEquals($fd::FACETS_DATE_DAY, $date_gap);

    // The best search gap between two dates must be an hour.
    $date_gap = $this->handler->getTimestampGap(static::TIMESTAMP, static::TIMESTAMP + 3600);
    $this->assertEquals($fd::FACETS_DATE_HOUR, $date_gap);

    // The best search gap between two dates must be a minute.
    $date_gap = $this->handler->getTimestampGap(static::TIMESTAMP, static::TIMESTAMP + 60);
    $this->assertEquals($fd::FACETS_DATE_MINUTE, $date_gap);

    // The best search gap between two dates must be a second.
    $date_gap = $this->handler->getTimestampGap(static::TIMESTAMP, static::TIMESTAMP + 59);
    $this->assertEquals($fd::FACETS_DATE_SECOND, $date_gap);
  }

  /**
   * Tests for ::getDateGap method.
   */
  public function testGetDateGap() {
    $fd = $this->handler;

    // Cannot convert to timestamp.
    $this->assertFalse($fd->getDateGap(static::TIMESTAMP, static::TIMESTAMP));

    // The min. gap is MONTH but the result is larger.
    $this->assertEquals($fd::FACETS_DATE_YEAR, $fd->getDateGap('1983-03-03T20:43:04Z', '1987-11-26T20:43:04Z', $fd::FACETS_DATE_MONTH));

    // The gap is YEAR.
    $this->assertEquals($fd::FACETS_DATE_YEAR, $fd->getDateGap('1983-03-03T20:43:04Z', '1987-11-26T20:43:04Z'));

    // The gap is MONTH.
    $this->assertEquals($fd::FACETS_DATE_MONTH, $fd->getDateGap('1983-03-03T20:43:04Z', '1983-11-26T20:43:04Z'));

    // The gap is DAY.
    $this->assertEquals($fd::FACETS_DATE_DAY, $fd->getDateGap('1983-03-03T20:43:04Z', '1983-03-26T20:43:04Z'));

    // The gap is HOUR.
    $this->assertEquals($fd::FACETS_DATE_HOUR, $fd->getDateGap('1983-03-03T20:43:04Z', '1983-03-03T21:44:04Z'));

    // The gap is MINUTE.
    $this->assertEquals($fd::FACETS_DATE_MINUTE, $fd->getDateGap('1983-03-03T20:43:04Z', '1983-03-03T20:44:04Z'));

    // The gap is SECOND.
    $this->assertEquals($fd::FACETS_DATE_SECOND, $fd->getDateGap('1983-03-03T20:43:04Z', '1983-03-03T20:43:55Z'));
  }

  /**
   * Tests for ::nextDateIncrement method.
   *
   * @dataProvider provideNextDateIncrementData
   */
  public function testNextDateIncrement($incremented_iso_date, $gap) {
    $this->assertEquals($incremented_iso_date, $this->handler->getNextDateIncrement(static::ISO_DATE, $gap));
  }

  /**
   * Tests for ::gapCompare method.
   */
  public function testGapCompare() {
    $fd = $this->handler;

    // Timestamps are equals.
    $this->assertEquals(0, $fd->gapCompare(static::TIMESTAMP, static::TIMESTAMP));

    // Timestamps are equals.
    $this->assertEquals(0, $fd->gapCompare($fd::FACETS_DATE_YEAR, $fd::FACETS_DATE_YEAR));

    // gap1 is less than gap2.
    $this->assertEquals(-1, $fd->gapCompare($fd::FACETS_DATE_MONTH, $fd::FACETS_DATE_YEAR));

    // gap1 is less than gap2.
    $this->assertEquals(1, $fd->gapCompare($fd::FACETS_DATE_MONTH, $fd::FACETS_DATE_DAY));
  }

  /**
   * Tests for ::formatTimestamp method.
   */
  public function testFormatTimestamp() {
    $fd = $this->handler;

    $year = $fd->formatTimestamp(static::TIMESTAMP);
    $this->assertEquals(1987, $year);
  }

  /**
   * Returns a data provider for the ::testIsoDate().
   *
   * @return array
   *   Arrays with data for the test data.
   */
  public function provideIsoDates() {
    return [
      ['1987-11-26T20:43:04Z', FacetsDateHandler::FACETS_DATE_SECOND],
      ['1987-11-26T20:43:00Z', FacetsDateHandler::FACETS_DATE_MINUTE],
      ['1987-11-26T20:00:00Z', FacetsDateHandler::FACETS_DATE_HOUR],
      ['1987-11-26T00:00:00Z', FacetsDateHandler::FACETS_DATE_DAY],
      ['1987-11-01T00:00:00Z', FacetsDateHandler::FACETS_DATE_MONTH],
      ['1987-01-01T00:00:00Z', FacetsDateHandler::FACETS_DATE_YEAR],
      ['1987-11-26T20:43:04Z', FacetsDateHandler::FACETS_DATE_ISO8601],
    ];
  }

  /**
   * Returns a data provider for the ::testNextDateIncrement().
   *
   * @return array
   *   Arrays with data for the test data.
   */
  public function provideNextDateIncrementData() {
    return [
      ['1987-11-26T20:43:05Z', FacetsDateHandler::FACETS_DATE_SECOND],
      ['1987-11-26T20:44:04Z', FacetsDateHandler::FACETS_DATE_MINUTE],
      ['1987-11-26T21:43:04Z', FacetsDateHandler::FACETS_DATE_HOUR],
      ['1987-11-27T20:43:04Z', FacetsDateHandler::FACETS_DATE_DAY],
      ['1987-12-26T20:43:04Z', FacetsDateHandler::FACETS_DATE_MONTH],
      ['1988-11-26T20:43:04Z', FacetsDateHandler::FACETS_DATE_YEAR],
    ];
  }

}
