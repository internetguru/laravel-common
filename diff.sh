#!/usr/bin/env bash

# check $1 is set
if [ -z "$1" ]; then
  echo "Usage: $0 <rev>"
  exit 1
fi

diff <(git show dev:composer.json | jq '.require' | jq 'to_entries[] | select(.key | startswith("internetguru/")) | .key + ":" + .value') <(git show $1:composer.json | jq '.require' | jq 'to_entries[] | select(.key | startswith("internetguru/")) | .key + ":" + .value')
