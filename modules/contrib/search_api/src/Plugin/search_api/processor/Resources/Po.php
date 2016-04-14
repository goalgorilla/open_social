<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Po" ("Punctuation, Other").
 */
class Po implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0021}\x{0022}\x{0023}\x{0025}\x{0026}\x{0027}\x{002A}' .
      '\x{002C}\x{002E}\x{002F}\x{003A}\x{003B}\x{003F}\x{0040}' .
      '\x{005C}\x{00A1}\x{00A7}\x{00B6}\x{00B7}\x{00BF}\x{037E}' .
      '\x{0387}\x{055A}\x{055B}\x{055C}\x{055D}\x{055E}\x{055F}' .
      '\x{0589}\x{05C0}\x{05C3}\x{05C6}\x{05F3}\x{05F4}\x{0609}' .
      '\x{060A}\x{060C}\x{060D}\x{061B}\x{061E}\x{061F}\x{066A}' .
      '\x{066B}\x{066C}\x{066D}\x{06D4}\x{0700}\x{0701}\x{0702}' .
      '\x{0703}\x{0704}\x{0705}\x{0706}\x{0707}\x{0708}\x{0709}' .
      '\x{070A}\x{070B}\x{070C}\x{070D}\x{07F7}\x{07F8}\x{07F9}' .
      '\x{0830}\x{0831}\x{0832}\x{0833}\x{0834}\x{0835}\x{0836}' .
      '\x{0837}\x{0838}\x{0839}\x{083A}\x{083B}\x{083C}\x{083D}' .
      '\x{083E}\x{085E}\x{0964}\x{0965}\x{0970}\x{0AF0}\x{0DF4}' .
      '\x{0E4F}\x{0E5A}\x{0E5B}\x{0F04}\x{0F05}\x{0F06}\x{0F07}' .
      '\x{0F08}\x{0F09}\x{0F0A}\x{0F0B}\x{0F0C}\x{0F0D}\x{0F0E}' .
      '\x{0F0F}\x{0F10}\x{0F11}\x{0F12}\x{0F14}\x{0F85}\x{0FD0}' .
      '\x{0FD1}\x{0FD2}\x{0FD3}\x{0FD4}\x{0FD9}\x{0FDA}\x{104A}' .
      '\x{104B}\x{104C}\x{104D}\x{104E}\x{104F}\x{10FB}\x{1360}' .
      '\x{1361}\x{1362}\x{1363}\x{1364}\x{1365}\x{1366}\x{1367}' .
      '\x{1368}\x{166D}\x{166E}\x{16EB}\x{16EC}\x{16ED}\x{1735}' .
      '\x{1736}\x{17D4}\x{17D5}\x{17D6}\x{17D8}\x{17D9}\x{17DA}' .
      '\x{1800}\x{1801}\x{1802}\x{1803}\x{1804}\x{1805}\x{1807}' .
      '\x{1808}\x{1809}\x{180A}\x{1944}\x{1945}\x{1A1E}\x{1A1F}' .
      '\x{1AA0}\x{1AA1}\x{1AA2}\x{1AA3}\x{1AA4}\x{1AA5}\x{1AA6}' .
      '\x{1AA8}\x{1AA9}\x{1AAA}\x{1AAB}\x{1AAC}\x{1AAD}\x{1B5A}' .
      '\x{1B5B}\x{1B5C}\x{1B5D}\x{1B5E}\x{1B5F}\x{1B60}\x{1BFC}' .
      '\x{1BFD}\x{1BFE}\x{1BFF}\x{1C3B}\x{1C3C}\x{1C3D}\x{1C3E}' .
      '\x{1C3F}\x{1C7E}\x{1C7F}\x{1CC0}\x{1CC1}\x{1CC2}\x{1CC3}' .
      '\x{1CC4}\x{1CC5}\x{1CC6}\x{1CC7}\x{1CD3}\x{2016}\x{2017}' .
      '\x{2020}\x{2021}\x{2022}\x{2023}\x{2024}\x{2025}\x{2026}' .
      '\x{2027}\x{2030}\x{2031}\x{2032}\x{2033}\x{2034}\x{2035}' .
      '\x{2036}\x{2037}\x{2038}\x{203B}\x{203C}\x{203D}\x{203E}' .
      '\x{2041}\x{2042}\x{2043}\x{2047}\x{2048}\x{2049}\x{204A}' .
      '\x{204B}\x{204C}\x{204D}\x{204E}\x{204F}\x{2050}\x{2051}' .
      '\x{2053}\x{2055}\x{2056}\x{2057}\x{2058}\x{2059}\x{205A}' .
      '\x{205B}\x{205C}\x{205D}\x{205E}\x{2CF9}\x{2CFA}\x{2CFB}' .
      '\x{2CFC}\x{2CFE}\x{2CFF}\x{2D70}\x{2E00}\x{2E01}\x{2E06}' .
      '\x{2E07}\x{2E08}\x{2E0B}\x{2E0E}\x{2E0F}\x{2E10}\x{2E11}' .
      '\x{2E12}\x{2E13}\x{2E14}\x{2E15}\x{2E16}\x{2E18}\x{2E19}' .
      '\x{2E1B}\x{2E1E}\x{2E1F}\x{2E2A}\x{2E2B}\x{2E2C}\x{2E2D}' .
      '\x{2E2E}\x{2E30}\x{2E31}\x{2E32}\x{2E33}\x{2E34}\x{2E35}' .
      '\x{2E36}\x{2E37}\x{2E38}\x{2E39}\x{3001}\x{3002}\x{3003}' .
      '\x{303D}\x{30FB}\x{A4FE}\x{A4FF}\x{A60D}\x{A60E}\x{A60F}' .
      '\x{A673}\x{A67E}\x{A6F2}\x{A6F3}\x{A6F4}\x{A6F5}\x{A6F6}' .
      '\x{A6F7}\x{A874}\x{A875}\x{A876}\x{A877}\x{A8CE}\x{A8CF}' .
      '\x{A8F8}\x{A8F9}\x{A8FA}\x{A92E}\x{A92F}\x{A95F}\x{A9C1}' .
      '\x{A9C2}\x{A9C3}\x{A9C4}\x{A9C5}\x{A9C6}\x{A9C7}\x{A9C8}' .
      '\x{A9C9}\x{A9CA}\x{A9CB}\x{A9CC}\x{A9CD}\x{A9DE}\x{A9DF}' .
      '\x{AA5C}\x{AA5D}\x{AA5E}\x{AA5F}\x{AADE}\x{AADF}\x{AAF0}' .
      '\x{AAF1}\x{ABEB}\x{FE10}\x{FE11}\x{FE12}\x{FE13}\x{FE14}' .
      '\x{FE15}\x{FE16}\x{FE19}\x{FE30}\x{FE45}\x{FE46}\x{FE49}' .
      '\x{FE4A}\x{FE4B}\x{FE4C}\x{FE50}\x{FE51}\x{FE52}\x{FE54}' .
      '\x{FE55}\x{FE56}\x{FE57}\x{FE5F}\x{FE60}\x{FE61}\x{FE68}' .
      '\x{FE6A}\x{FE6B}\x{FF01}\x{FF02}\x{FF03}\x{FF05}\x{FF06}' .
      '\x{FF07}\x{FF0A}\x{FF0C}\x{FF0E}\x{FF0F}\x{FF1A}\x{FF1B}' .
      '\x{FF1F}\x{FF20}\x{FF3C}\x{FF61}\x{FF64}\x{FF65}\x{10100}' .
      '\x{10101}\x{10102}\x{1039F}\x{103D0}\x{10857}\x{1091F}\x{1093F}' .
      '\x{10A50}\x{10A51}\x{10A52}\x{10A53}\x{10A54}\x{10A55}\x{10A56}' .
      '\x{10A57}\x{10A58}\x{10A7F}\x{10B39}\x{10B3A}\x{10B3B}\x{10B3C}' .
      '\x{10B3D}\x{10B3E}\x{10B3F}\x{11047}\x{11048}\x{11049}\x{1104A}' .
      '\x{1104B}\x{1104C}\x{1104D}\x{110BB}\x{110BC}\x{110BE}\x{110BF}' .
      '\x{110C0}\x{110C1}\x{11140}\x{11141}\x{11142}\x{11143}\x{111C5}' .
      '\x{111C6}\x{111C7}\x{111C8}\x{12470}\x{12471}\x{12472}\x{12473}';
  }

}
