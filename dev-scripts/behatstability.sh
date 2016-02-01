#!/usr/bin/env bash

PROJECT_FOLDER=/root/behat

behat --version

echo $PROJECT_FOLDER/config/behat.yml;

behat $PROJECT_FOLDER --config $PROJECT_FOLDER/config/behat.yml --tags "stability"