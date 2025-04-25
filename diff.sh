#!/usr/bin/env bash

# check $1 is set
if [ -z "$1" ]; then
  echo "Usage: $0 <rev>"
  exit 1
fi

diff <(git show dev:composer.lock | jq '.packages[] | select(.name | startswith("internetguru/")) | .name + ":" + .version') <(git show $1:composer.lock | jq '.packages[] | select(.name | startswith("internetguru/")) | .name + ":" + .version')
