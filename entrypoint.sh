#!/bin/bash

cd shipmondo
rm -rf .git
rm -rf .vscode
rm -rf .php-cs-fixer.cache
rm -rf .php-cs-fixer.dist.php
rm -rf Dockerfile
rm -rf entrypoint.sh
rm -rf build.sh

shopt -s extglob
cd vendor
rm -rf !(.htaccess)
cd ..

composer install --no-dev
cd ..
zip -r shipmondo.zip shipmondo