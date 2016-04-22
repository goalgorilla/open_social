<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

use Drupal\Component\Utility\Unicode;
use Drupal\search_api\Plugin\search_api\processor\Tokenizer;
use Drupal\search_api\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Tokenizer" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Tokenizer
 */
class TokenizerTest extends UnitTestCase {

  use ProcessorTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->processor = new Tokenizer(array(), 'tokenizer', array());
  }

  /**
   * Tests the processFieldValue() method.
   *
   * @param string $passed_value
   *   The field value passed to the processor's processFieldValue() method.
   * @param string $expected_value
   *   The expected preprocessed value.
   * @param array $config
   *   (optional) Configuration to override the processor's defaults.
   *
   * @dataProvider textDataProvider
   */
  public function testProcessFieldValue($passed_value, $expected_value, array $config = array()) {
    if ($config) {
      $this->processor->setConfiguration($config);
    }
    $type = 'text';
    $this->invokeMethod('processFieldValue', array(&$passed_value, &$type));
    $this->assertEquals($expected_value, $passed_value);
    $this->assertEquals('tokenized_text', $type);
  }

  /**
   * Provides test data for testValueConfiguration().
   *
   * @return array
   *   Arrays of parameters for testProcessFieldValue(), each containing (in
   *   this order):
   *   - The field value passed to the processor's processFieldValue() method.
   *   - The expected preprocessed value.
   *   - (optional) Configuration to override the processor's defaults.
   */
  public function textDataProvider() {
    $word_token = Utility::createTextToken('word');
    return array(
      // Test some simple cases.
      array('word', array($word_token)),
      array('word word', array($word_token, $word_token)),
      // Test whether the default splits on special characters, too.
      array('words!word', array(Utility::createTextToken('words'), $word_token)),
      array('words$word', array(Utility::createTextToken('words'), $word_token)),
      // Test whether overriding the default works and is case-insensitive.
      array(
        'wordXwordxword',
        array($word_token, Utility::createTextToken('wordxword')),
        array('spaces' => 'X'),
      ),
      array(
        'word3word!word',
        array($word_token, Utility::createTextToken('word!word')),
        array('spaces' => '\d'),
      ),
      array(
        'wordXwordRword',
        array($word_token, $word_token, $word_token),
        array('spaces' => 'R-Z'),
      ),
      array(
        'wordXwordRword',
        array($word_token, $word_token, $word_token),
        array('spaces' => 'R-TW-Z'),
      ),
      array(
        'wordXword word',
        array($word_token, $word_token, $word_token),
        array('spaces' => 'R-Z'),
      ),
      // Test whether minimum word size works.
      array(
        'wordSwo',
        array($word_token),
        array('spaces' => 'R-Z'),
      ),
      array(
        'wordSwo',
        array($word_token, Utility::createTextToken('wo')),
        array('spaces' => 'R-Z', 'minimum_word_size' => 2),
      ),
      array(
        'word w',
        array($word_token),
        array('minimum_word_size' => 2),
      ),
      array(
        'word w',
        array($word_token, Utility::createTextToken('w')),
        array('minimum_word_size' => 1),
      ),
      array(
        'word wordword',
        array(),
        array('minimum_word_size' => 10),
      ),
    );
  }

  /**
   * Tests that the simplifyText() method handles CJK characters properly.
   *
   * The simplifyText() method does special things with numbers, symbols and
   * punctuation. So we only test that CJK characters that are not in these
   * character classes are tokenized properly. See PREG_CLASS_CJK for more
   * information.
   */
  public function testCjkSupport() {
    $this->invokeMethod('prepare');

    // Create a string of CJK characters from various character ranges in
    // the Unicode tables. $starts contains the starts of the character ranges,
    // $ends the ends.
    $starts = array(
      'CJK unified' => 0x4e00,
      'CJK Ext A' => 0x3400,
      'CJK Compat' => 0xf900,
      'Hangul Jamo' => 0x1100,
      'Hangul Ext A' => 0xa960,
      'Hangul Ext B' => 0xd7b0,
      'Hangul Compat' => 0x3131,
      'Half non-punct 1' => 0xff21,
      'Half non-punct 2' => 0xff41,
      'Half non-punct 3' => 0xff66,
      'Hangul Syllables' => 0xac00,
      'Hiragana' => 0x3040,
      'Katakana' => 0x30a1,
      'Katakana Ext' => 0x31f0,
      'CJK Reserve 1' => 0x20000,
      'CJK Reserve 2' => 0x30000,
      'Bomofo' => 0x3100,
      'Bomofo Ext' => 0x31a0,
      'Lisu' => 0xa4d0,
      'Yi' => 0xa000,
    );
    $ends = array(
      'CJK unified' => 0x9fcf,
      'CJK Ext A' => 0x4dbf,
      'CJK Compat' => 0xfaff,
      'Hangul Jamo' => 0x11ff,
      'Hangul Ext A' => 0xa97f,
      'Hangul Ext B' => 0xd7ff,
      'Hangul Compat' => 0x318e,
      'Half non-punct 1' => 0xff3a,
      'Half non-punct 2' => 0xff5a,
      'Half non-punct 3' => 0xffdc,
      'Hangul Syllables' => 0xd7af,
      'Hiragana' => 0x309f,
      'Katakana' => 0x30ff,
      'Katakana Ext' => 0x31ff,
      'CJK Reserve 1' => 0x2fffd,
      'CJK Reserve 2' => 0x3fffd,
      'Bomofo' => 0x312f,
      'Bomofo Ext' => 0x31b7,
      'Lisu' => 0xa4fd,
      'Yi' => 0xa48f,
    );

    // Generate characters consisting of starts, midpoints, and ends.
    $chars = array();
    foreach ($starts as $key => $value) {
      $chars[] = self::codepointToUtf8($starts[$key]);
      $mid = round(0.5 * ($starts[$key] + $ends[$key]));
      $chars[] = self::codepointToUtf8($mid);
      $chars[] = self::codepointToUtf8($ends[$key]);
    }

    // Merge into a single string and tokenize.
    $text = implode('', $chars);
    $simplified_text = $this->invokeMethod('simplifyText', array($text));

    // Prepare the expected return value, which consists of all the 3-grams in
    // the original string, separated by spaces.
    $expected = '';
    for ($i = 2; $i < count($chars); ++$i) {
      $expected .= $chars[$i - 2];
      $expected .= $chars[$i - 1];
      $expected .= $chars[$i];
      $expected .= ' ';
    }
    $expected = trim($expected);

    // Verify that the output matches what we expect.
    $this->assertEquals($expected, $simplified_text, 'CJK tokenizer worked on all supplied CJK characters');

    // Verify that disabling the "overlap_cjk" setting works as expected.
    $this->processor->setConfiguration(array('overlap_cjk' => FALSE));
    $this->invokeMethod('prepare');
    $simplified_text = $this->invokeMethod('simplifyText', array($text));
    $this->assertEquals($text, $simplified_text, 'CJK tokenizing is successfully disabled');
  }

  /**
   * Verifies that strings of non-CJK characters are not tokenized.
   *
   * This is just a sanity check – it verifies that strings of letters are
   * not tokenized.
   */
  public function testNoTokenizer() {
    // Set the minimum word size to 1 (to split all CJK characters).
    $this->processor->setConfiguration(array('minimum_word_size' => 1));
    $this->invokeMethod('prepare');

    $letters = 'abcdefghijklmnopqrstuvwxyz';
    $out = $this->invokeMethod('simplifyText', array($letters));

    $this->assertEquals($letters, $out, 'Latin letters are not CJK tokenized');
  }

  /**
   * Converts a Unicode code point to a UTF-8 string.
   *
   * The PHP function "chr()" only works for ASCII characters up to character
   * 255. This function converts a number to the corresponding unicode
   * character. Adapted from functions supplied in comments on several functions
   * on php.net.
   *
   * @param int $num
   *   A Unicode code point.
   *
   * @return string
   *   A UTF-8 string containing the character corresponding to that code point.
   */
  protected static function codepointToUtf8($num) {
    if ($num < 128) {
      return chr($num);
    }

    if ($num < 2048) {
      return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
    }

    if ($num < 65536) {
      return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
    }

    if ($num < 2097152) {
      return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
    }

    return '';
  }

  /**
   * Tests that all Unicode characters simplify correctly.
   *
   * This test uses a Drupal core search file that was constructed so that the
   * even lines are boundary characters, and the odd lines are valid word
   * characters. (It was generated as a sequence of all the Unicode characters,
   * and then the boundary characters (punctuation, spaces, etc.) were split
   * off into their own lines).  So the even-numbered lines should simplify to
   * nothing, and the odd-numbered lines we need to split into shorter chunks
   * and verify that simplification doesn't lose any characters.
   *
   * @see \Drupal\search\Tests\SearchSimplifyTest::testSearchSimplifyUnicode()
   */
  public function testSearchSimplifyUnicode() {
    // Set the minimum word size to 1 (to split all CJK characters).
    $this->processor->setConfiguration(array('minimum_word_size' => 1));
    $this->invokeMethod('prepare');

    $input = file_get_contents($this->root . '/core/modules/search/tests/UnicodeTest.txt');
    $basestrings = explode(chr(10), $input);
    $strings = array();
    foreach ($basestrings as $key => $string) {
      if ($key % 2) {
        // Even line, should be removed by simplifyText().
        $simplified = $this->invokeMethod('simplifyText', array($string));
        $this->assertEquals('', $simplified, "Line $key is excluded from the index");
      }
      else {
        // Odd line, should be word characters (which might be expanded, but
        // never removed). Split this into 30-character chunks, so we don't run
        // into limits of truncation.
        $start = 0;
        while ($start < Unicode::strlen($string)) {
          $newstr = Unicode::substr($string, $start, 30);
          // Special case: leading zeros are removed from numeric strings,
          // and there's one string in this file that is numbers starting with
          // zero, so prepend a 1 on that string.
          if (preg_match('/^[0-9]+$/', $newstr)) {
            $newstr = '1' . $newstr;
          }
          $strings[] = $newstr;
          $start += 30;
        }
      }
    }
    foreach ($strings as $key => $string) {
      $simplified = $this->invokeMethod('simplifyText', array($string));
      $this->assertGreaterThanOrEqual(Unicode::strlen($string), Unicode::strlen($simplified), "Nothing is removed from string $key.");
    }

    // Test the low-numbered ASCII control characters separately. They are not
    // in the text file because they are problematic for diff, especially \0.
    $string = '';
    for ($i = 0; $i < 32; $i++) {
      $string .= chr($i);
    }
    $this->assertEquals('', $this->invokeMethod('simplifyText', array($string)), 'Text simplification works for ASCII control characters.');
  }

  /**
   * Tests whether punctuation is treated correctly.
   *
   * @param string $passed_value
   *   The string passed to simplifyText().
   * @param string $expected_value
   *   The expected return value.
   * @param string $message
   *   The message to display for the assertion.
   *
   * @dataProvider searchSimplifyPunctuationProvider
   */
  public function testSearchSimplifyPunctuation($passed_value, $expected_value, $message) {
    // Set the minimum word size to 1 (to split all CJK characters).
    $this->processor->setConfiguration(array('minimum_word_size' => 1));
    $this->invokeMethod('prepare');

    $out = $this->invokeMethod('simplifyText', array($passed_value));
    $this->assertEquals($expected_value, $out, $message);
  }

  /**
   * Provides test data for testSearchSimplifyPunctuation().
   *
   * @return array
   *   Arrays of parameters for testSearchSimplifyPunctuation(), each containing
   *   (in this order):
   *   - The string passed to simplifyText().
   *   - The expected return value.
   *   - The message to display for the assertion.
   */
  public function searchSimplifyPunctuationProvider() {
    $cases = array(
      array(
        '20.03/94-28,876',
        '20039428876',
        'Punctuation removed from numbers',
      ),
      array(
        'great...drupal--module',
        'great drupal module',
        'Multiple dot and dashes are word boundaries',
      ),
      array(
        'very_great-drupal.module',
        'verygreatdrupalmodule',
        'Single dot, dash, underscore are removed',
      ),
      array(
        'regular,punctuation;word',
        'regular punctuation word',
        'Punctuation is a word boundary',
      ),
      array(
        'Äußerung français repülőtér',
        'Äußerung français repülőtér',
        'Umlauts and accented characters are not treated as word boundaries',
      ),
    );
    return $cases;
  }

}
