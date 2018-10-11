<?php

/**
 * @file
 * This file is part of the Open Social Blackfire testing.
 */

// require_once __DIR__.'../../../../../../vendor/autoload.php';.
use Blackfire\Client;
use Blackfire\Profile\Configuration;

// Grab the values from the command line so we can push any data to github.
$commandLine = getopt(NULL, ["commit:uuid"]);
$uuid = $commandLine['uuid'];
$commit = $commandLine['commit'];

if (empty($uuid) || empty($commit)) {
  return 0;
}

$config = new Configuration();
$blackfire = new Client();
$probe = $blackfire->createProbe($config);

// Create a build that will show up on our dashboard in blackfire.
$build = $blackfire->startBuild(
    $uuid, [
      'title' => 'Build from GitHub Commit: ' . $commit,
      'external_id' => $commit,
      'trigger_name' => 'Travis CI - Commit: ' . $commit,
    ]
);

// Create a scenario (if the $build argument is null, a new will be created).
$scenario = $blackfire->startScenario(
    $build, [
      'title' => 'Test to see if it works.',
    ]
);

// Create a configuration.
$config = new Configuration();
$config->setScenario($scenario);

// Create as many profiles as you need.
$probe = $blackfire->createProbe($config);

// TEST STARTS HERE.
$request = $blackfire->createRequest('Homepage');
$header = 'X-Blackfire-Query: ' . $request->getToken();

$blackfire->endProbe($probe);

// End the scenario and fetch the report.
$report = $blackfire->closeScenario($scenario);

// End the build.
$blackfire->closeBuild($build);

// Output the URL if we have any.
$profile = $blackfire->getProfile($uuid);
$url = $profile->getUrl();
sprintf('The URL of your build can be found here: %s', $url);
