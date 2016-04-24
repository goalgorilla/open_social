<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the category "Mc" ("Mark, Spacing Combining").
 */
class Mc implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0903}\x{093B}\x{093E}\x{093F}\x{0940}\x{0949}\x{094A}' .
      '\x{094B}\x{094C}\x{094E}\x{094F}\x{0982}\x{0983}\x{09BE}' .
      '\x{09BF}\x{09C0}\x{09C7}\x{09C8}\x{09CB}\x{09CC}\x{09D7}' .
      '\x{0A03}\x{0A3E}\x{0A3F}\x{0A40}\x{0A83}\x{0ABE}\x{0ABF}' .
      '\x{0AC0}\x{0AC9}\x{0ACB}\x{0ACC}\x{0B02}\x{0B03}\x{0B3E}' .
      '\x{0B40}\x{0B47}\x{0B48}\x{0B4B}\x{0B4C}\x{0B57}\x{0BBE}' .
      '\x{0BBF}\x{0BC1}\x{0BC2}\x{0BC6}\x{0BC7}\x{0BC8}\x{0BCA}' .
      '\x{0BCB}\x{0BCC}\x{0BD7}\x{0C01}\x{0C02}\x{0C03}\x{0C41}' .
      '\x{0C42}\x{0C43}\x{0C44}\x{0C82}\x{0C83}\x{0CBE}\x{0CC0}' .
      '\x{0CC1}\x{0CC2}\x{0CC3}\x{0CC4}\x{0CC7}\x{0CC8}\x{0CCA}' .
      '\x{0CCB}\x{0CD5}\x{0CD6}\x{0D02}\x{0D03}\x{0D3E}\x{0D3F}' .
      '\x{0D40}\x{0D46}\x{0D47}\x{0D48}\x{0D4A}\x{0D4B}\x{0D4C}' .
      '\x{0D57}\x{0D82}\x{0D83}\x{0DCF}\x{0DD0}\x{0DD1}\x{0DD8}' .
      '\x{0DD9}\x{0DDA}\x{0DDB}\x{0DDC}\x{0DDD}\x{0DDE}\x{0DDF}' .
      '\x{0DF2}\x{0DF3}\x{0F3E}\x{0F3F}\x{0F7F}\x{102B}\x{102C}' .
      '\x{1031}\x{1038}\x{103B}\x{103C}\x{1056}\x{1057}\x{1062}' .
      '\x{1063}\x{1064}\x{1067}\x{1068}\x{1069}\x{106A}\x{106B}' .
      '\x{106C}\x{106D}\x{1083}\x{1084}\x{1087}\x{1088}\x{1089}' .
      '\x{108A}\x{108B}\x{108C}\x{108F}\x{109A}\x{109B}\x{109C}' .
      '\x{17B6}\x{17BE}\x{17BF}\x{17C0}\x{17C1}\x{17C2}\x{17C3}' .
      '\x{17C4}\x{17C5}\x{17C7}\x{17C8}\x{1923}\x{1924}\x{1925}' .
      '\x{1926}\x{1929}\x{192A}\x{192B}\x{1930}\x{1931}\x{1933}' .
      '\x{1934}\x{1935}\x{1936}\x{1937}\x{1938}\x{19B0}\x{19B1}' .
      '\x{19B2}\x{19B3}\x{19B4}\x{19B5}\x{19B6}\x{19B7}\x{19B8}' .
      '\x{19B9}\x{19BA}\x{19BB}\x{19BC}\x{19BD}\x{19BE}\x{19BF}' .
      '\x{19C0}\x{19C8}\x{19C9}\x{1A19}\x{1A1A}\x{1A55}\x{1A57}' .
      '\x{1A61}\x{1A63}\x{1A64}\x{1A6D}\x{1A6E}\x{1A6F}\x{1A70}' .
      '\x{1A71}\x{1A72}\x{1B04}\x{1B35}\x{1B3B}\x{1B3D}\x{1B3E}' .
      '\x{1B3F}\x{1B40}\x{1B41}\x{1B43}\x{1B44}\x{1B82}\x{1BA1}' .
      '\x{1BA6}\x{1BA7}\x{1BAA}\x{1BAC}\x{1BAD}\x{1BE7}\x{1BEA}' .
      '\x{1BEB}\x{1BEC}\x{1BEE}\x{1BF2}\x{1BF3}\x{1C24}\x{1C25}' .
      '\x{1C26}\x{1C27}\x{1C28}\x{1C29}\x{1C2A}\x{1C2B}\x{1C34}' .
      '\x{1C35}\x{1CE1}\x{1CF2}\x{1CF3}\x{302E}\x{302F}\x{A823}' .
      '\x{A824}\x{A827}\x{A880}\x{A881}\x{A8B4}\x{A8B5}\x{A8B6}' .
      '\x{A8B7}\x{A8B8}\x{A8B9}\x{A8BA}\x{A8BB}\x{A8BC}\x{A8BD}' .
      '\x{A8BE}\x{A8BF}\x{A8C0}\x{A8C1}\x{A8C2}\x{A8C3}\x{A952}' .
      '\x{A953}\x{A983}\x{A9B4}\x{A9B5}\x{A9BA}\x{A9BB}\x{A9BD}' .
      '\x{A9BE}\x{A9BF}\x{A9C0}\x{AA2F}\x{AA30}\x{AA33}\x{AA34}' .
      '\x{AA4D}\x{AA7B}\x{AAEB}\x{AAEE}\x{AAEF}\x{AAF5}\x{ABE3}' .
      '\x{ABE4}\x{ABE6}\x{ABE7}\x{ABE9}\x{ABEA}\x{ABEC}\x{11000}' .
      '\x{11002}\x{11082}\x{110B0}\x{110B1}\x{110B2}\x{110B7}\x{110B8}' .
      '\x{1112C}\x{11182}\x{111B3}\x{111B4}\x{111B5}\x{111BF}\x{111C0}' .
      '\x{116AC}\x{116AE}\x{116AF}\x{116B6}\x{16F51}\x{16F52}\x{16F53}' .
      '\x{16F54}\x{16F55}\x{16F56}\x{16F57}\x{16F58}\x{16F59}\x{16F5A}' .
      '\x{16F5B}\x{16F5C}\x{16F5D}\x{16F5E}\x{16F5F}\x{16F60}\x{16F61}' .
      '\x{16F62}\x{16F63}\x{16F64}\x{16F65}\x{16F66}\x{16F67}\x{16F68}' .
      '\x{16F69}\x{16F6A}\x{16F6B}\x{16F6C}\x{16F6D}\x{16F6E}\x{16F6F}' .
      '\x{16F70}\x{16F71}\x{16F72}\x{16F73}\x{16F74}\x{16F75}\x{16F76}' .
      '\x{16F77}\x{16F78}\x{16F79}\x{16F7A}\x{16F7B}\x{16F7C}\x{16F7D}' .
      '\x{16F7E}\x{1D165}\x{1D166}\x{1D16D}\x{1D16E}\x{1D16F}\x{1D170}' .
      '\x{1D171}\x{1D172}';
  }

}
