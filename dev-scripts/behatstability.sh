#!/usr/bin/env bash

PROJECT_FOLDER=/root/behat

behat --version

echo $PROJECT_FOLDER/config/behat.yml;

sleep 30

behat $PROJECT_FOLDER --config $PROJECT_FOLDER/config/behat.yml --tags "stability"