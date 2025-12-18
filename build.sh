#!/bin/bash

rm shipmondo.zip
rm -rf shipmondo/

cp -rf . shipmondo

cd shipmondo
rm -rf .git
rm -rf .vscode
rm -rf .php-cs-fixer.cache
rm -rf .php-cs-fixer.dist.php
rm -rf .DS_Store
rm -rf build.sh

shopt -s extglob
cd vendor
rm -rf !(.htaccess)
cd ..

docker run --rm -v "$(pwd)":/app -w /app composer:latest composer install --no-dev

cd ..
zip -r shipmondo.zip shipmondo

rm -rf shipmondo
