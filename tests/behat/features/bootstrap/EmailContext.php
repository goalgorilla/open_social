<?php
// @codingStandardsIgnoreFile

namespace Social\Context;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class EmailContext implements Context
{

  use KernelDictionary;

  /**
   * We need to purge the spool between each scenario
   *
   * @BeforeScenario @clear-emails
   */
  public function purgeSpool()
  {
    $filesystem = new Filesystem();
    $finder = $this->getSpooledEmails();

    /** @var File $file */
    foreach ($finder as $file) {
      $filesystem->remove($file->getRealPath());
    }
  }

  /**
   * @return Finder
   */
  public function getSpooledEmails()
  {
    $finder = new Finder();
    $spoolDir = $this->getSpoolDir();
    $finder->files()->in($spoolDir);

    return $finder;
  }

  /**
   * @param $file
   *
   * @return string
   */
  public function getEmailContent($file)
  {
    return unserialize(file_get_contents($file));
  }

  /**
   * @return string
   */
  protected function getSpoolDir()
  {
    return $this->getContainer()->getParameter('swiftmailer.spool.default.file.path');
  }
}