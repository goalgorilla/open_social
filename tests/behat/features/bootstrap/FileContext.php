<?php

namespace Drupal\social\Behat;

use Behat\MinkExtension\Context\RawMinkContext;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;

/**
 * Steps related to handling files such as the user export.
 */
class FileContext extends RawMinkContext {

  /**
   * @param string $link_text
   *   The link url.
   * @param string $contents
   *   The expected text.
   *
   * @Then the file downloaded from :link_text should have contents:
   */
  public function fileDownloadedMatches(string $link_text, string $contents) : void {
    $actual = trim($this->getDownloadedFileContent($link_text));
    $expected = trim($contents);
    if ($actual !== $expected) {
      throw new \RuntimeException("File does not match the expected contents. Received:\n$actual\n\nExpected:\n{$contents}");
    }
  }

  /**
   * @param string $link_text
   *   The link url.
   * @param string $contents
   *   The expected text.
   *
   * @Then the file downloaded from :link_text should contain individual lines:
   */
  public function fileDownloadedContains(string $link_text, string $contents) : void {
    $actual = trim($this->getDownloadedFileContent($link_text));
    $expected = explode(PHP_EOL, trim($contents));
    foreach ($expected as $word) {
      if (strpos($actual, $word) === FALSE) {
        throw new \RuntimeException("File does not contain the expected contents: {$word}");
      }
    }
  }

  /**
   * Fetches a URL with Guzzle.
   *
   * Can be used to downlaod files in case the browser would move those to the
   * downloads folder.
   *
   * @param array $cookies
   *   The array of cookies (from Session::getDriver()->getCookies()) to use.
   * @param string $url
   *   The URL to fetch.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The Guzzle response.
   */
  private function getUrlWithGuzzle(array $cookies, string $url) : ResponseInterface {
    $jar = new CookieJar();
    foreach ($cookies as $cookieValue) {
      $cookie = new SetCookie();
      $cookie->setName($cookieValue['name']);
      $cookie->setValue($cookieValue['value']);
      $cookie->setDomain($cookieValue['domain']);
      $jar->setCookie($cookie);
    }

    return (new Client(['cookies' => $jar]))->get($url);
  }

  /**
   * Allows getting content from downloaded file.
   *
   * @param string $link_text
   *   The url of file.
   *
   * @return string
   *   The content of the downloaded file.
   */
  protected function getDownloadedFileContent(string $link_text): string {
    $session = $this->getSession();
    $link = $session->getPage()->find('named', ['link', $link_text]);
    $url = $link->getAttribute('href');

    $hostname = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    if (strpos($url, $hostname) === FALSE) {
      $url = $hostname . $url;
    }

    $cookies = $this->getSession()->getDriver()->getCookies();
    $response = $this->getUrlWithGuzzle($cookies, $url);

    return $response->getBody()->getContents();
  }

}
