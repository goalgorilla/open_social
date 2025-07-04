<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;

/**
 * Steps related to handling files such as the user export.
 */
class FileContext extends RawMinkContext {

  /**
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * I try to download file status code.
   *
   * @var int|mixed|null
   */
  private mixed $iTryToDownloadFileStatusCode;

  /**
   * I try to download file response.
   *
   * @var \Psr\Http\Message\ResponseInterface|null
   */
  private ?ResponseInterface $iTryToDownloadFileResponse;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * Try to download file from url.
   *
   * @param string $url
   *   The link url.
   *
   * @When I try to download :url
   */
  public function iTryToDownloadFile(string $url): void {
    $this->iTryToDownloadFileStatusCode = NULL;
    $this->iTryToDownloadFileResponse = NULL;

    $cookies = $this->getSession()->getDriver()->getCookies();

    //$hostname = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $hostname = $this->getMinkParameter('base_url');
    if (strpos($url, $hostname) === FALSE) {
      $url = $hostname . $url;
    }

    try {
      $response = $this->getUrlWithGuzzle($cookies, $url);
      $this->iTryToDownloadFileResponse = $response;
      $this->iTryToDownloadFileStatusCode = $response->getStatusCode();
    } catch (\Exception $e) {
      $this->iTryToDownloadFileStatusCode = $e->getCode();
    }
  }

  /**
   * Validate the status code after you try to download file from url
   * with "iTryToDownloadFile" method.
   *
   * Note: this step must be always after step "@When I try to download :url".
   *
   * @param $status_code
   *   Status code
   *
   * @Then I should see response status code :statusCode
   */
  public function iShouldSeeResponseStatusCode($status_code) {
    $responseStatusCode = $this->iTryToDownloadFileStatusCode;

    if (!$responseStatusCode == intval($status_code)) {
      throw new \RuntimeException('Did not see response status code "' . $status_code . '", but "' . $responseStatusCode . '"%s.');
    }
  }

  /**
   * Validate the response header value after you try to download file from url
   * with "iTryToDownloadFile" method.
   *
   * Note: this step must be always after step "@When I try to download :url".
   *
   * @param $header
   *   Response header key
   * @param $value
   *   Response header value
   *
   * @Then I should see in the response header :header with :value
   */
  public function iShouldSeeInTheHeader($header, $value) {
    if ($this->iTryToDownloadFileResponse) {
      if (!empty($this->iTryToDownloadFileResponse->getHeader($header)) && $this->iTryToDownloadFileResponse->getHeader($header)[0] !== $value) {
        throw new \RuntimeException('There is no response header ' . $header . ' with value "' . $value . '"');
      }
    }
    else {
      throw new \RuntimeException('Response is missing or is not valid. Response code: "' . $this->iTryToDownloadFileStatusCode . '". Also check if "I try to download :url" behat step was correctly triggered in previous step.');
    }
  }

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
    
    // Fix malformed URLs with empty query parameters.
    // Issue introduced with views_data_export 1.6.0.
    $url = $this->ensureWellFormedUrl($url);

    $hostname = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    if (strpos($url, $hostname) === FALSE) {
      $url = $hostname . $url;
    }

    $cookies = $this->getSession()->getDriver()->getCookies();
    $response = $this->getUrlWithGuzzle($cookies, $url);

    return $response->getBody()->getContents();
  }

  /**
   * Clean malformed URLs by removing empty query parameters.
   *
   * @param string $url
   *   The URL to clean.
   *
   * @return string
   *   The cleaned URL.
   */
  protected function ensureWellFormedUrl(string $url): string {
    $parsed = parse_url($url);
    
    if (!isset($parsed['query'])) {
      return $url;
    }
    
    // Parse query string.
    parse_str($parsed['query'], $query_params);
    
    // Remove empty parameters.
    $query_params = array_filter($query_params, function($value) {
      return $value !== '' && $value !== null;
    });
    
    // Rebuild query string.
    if (!empty($query_params)) {
      $parsed['query'] = http_build_query($query_params);
    } else {
      unset($parsed['query']);
    }
    
    // Rebuild complete URL.
    $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
    $host = isset($parsed['host']) ? $parsed['host'] : '';
    $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    $user = isset($parsed['user']) ? $parsed['user'] : '';
    $pass = isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
    $pass = ($user || $pass) ? "$pass@" : '';
    $path = isset($parsed['path']) ? $parsed['path'] : '';
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
    
    return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
  }

  /**
   * Check if the correct amount of files were uploaded.
   *
   * @Then I should have uploaded :count :scheme files
   */
  public function assertUploadedCount(int $count, string $scheme) : void {
    $uid = $this->drupalContext->getUserManager()->getCurrentUser()?->uid ?? 0;

    $query = \Drupal::database()->select('file_managed', 'fm');
    $query->addField('fm', 'fid');
    $query->condition('fm.uid', $uid, '=');
    $query->condition('fm.uri', "$scheme://%", 'LIKE');
    $actual = count($query->execute()->fetchAllAssoc('fid'));

    if ($actual !== $count) {
      throw new \RuntimeException("Expected $count uploaded $scheme files but found $actual.");
    }
  }

}
