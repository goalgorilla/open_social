#!/usr/bin/env bash
###############################################
# Executes all checks that are also run by CI #
###############################################

# Abort on any error
set -e

# Store the script and Open Social directory.
# Script should be in <os_dir>/scripts
SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
OS_DIR="$SCRIPT_DIR/.."

# Execute from the Open Social directory.
cd $OS_DIR

# Find the vendor directory and core directory.
# We need this for some of the tools we use.
while [[ `pwd` != '/' ]]; do
  if [ -d "core" ]; then
    CORE_DIR="`pwd`/core"
  fi

  if [ -d "vendor" ]; then
    VENDOR_DIR="`pwd`/vendor"
  fi

  cd ..
done

if [ -z $CORE_DIR ] || [ -z $VENDOR_DIR ]; then
  echo "Could not find core ('$CORE_DIR') or vendor ('$VENDOR_DIR') directory."
  exit 1
fi

# We run our coding checks from the Open Social directory.
cd $OS_DIR

# Perform coding standards check.
echo "└ Executing '$VENDOR_DIR/bin/phpcs'"
$VENDOR_DIR/bin/phpcs

# Perform PHPStan check.
echo "└ Executing '$VENDOR_DIR/bin/phpstan'"
$VENDOR_DIR/bin/phpstan

# Run PHPUnit tests.
echo "└ Executing '$VENDOR_DIR/bin/phpunit'"
$VENDOR_DIR/bin/phpunit
