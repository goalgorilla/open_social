#!/bin/bash
#
# File        : behat.sh
#
# Description : Run a certain behat test with selenium
# Author      : Jochem
# Date        : 19-09-2013
#

# First some vars. Change these if necessary.
PROJECT_FOLDER=/root/behat

#
# Must have at least 1 argument.
#
if [ "$#" -eq "0" ]
then
  echo "Use: 'behat.sh -h' for command line parameters"
  exit
fi

function fn_arguments {
  while getopts "p:ht:" opts; do
     case ${opts} in
      h)
        GIVEHELP=1
        ;;
      t)
        TAG=${OPTARG}
        ;;
      \?)
        echo "Invalid option: -$OPTARG" >&2
        ;;

     esac
  done
}

function fn_message {
  if [[ ! -z $1 ]]
  then
    echo "---"
    echo "--- " $1
    echo "---"
  fi
}

# clear the screen
clear

# grab command line arguments
fn_arguments $*

if [[ $GIVEHELP == "1" ]]
then
  echo "Use: 'behat.sh -t <tagname> to execute"
  exit
fi

# run the darn thing
if [ "$TAG" != "" ]
then
  fn_message "RUNNING TESTS WITH TAG: $TAG"
  behat $PROJECT_FOLDER --config $PROJECT_FOLDER/config/behat.yml --tags "$TAG"
fi

