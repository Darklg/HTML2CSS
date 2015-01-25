#!/bin/bash

# Tests
phpunit --colors tests/app.php;

# Code coverage
phpunit --colors --coverage-html tests/coverage tests/app.php