#!/bin/env bash
docker build -t laravel-common-test . \
  && docker run --rm laravel-common-test
