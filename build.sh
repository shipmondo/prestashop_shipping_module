#!/bin/bash

rm shipmondo.zip

cp -rf . shipmondo

cd shipmondo
rm -rf .git
rm -rf .vscode
rm -rf .php-cs-fixer.cache
rm -rf .php-cs-fixer.dist.php
rm -rf build.sh
rm -rf composer.lock

shopt -s extglob
cd vendor
rm -rf !(.htaccess)
cd ..

docker run --rm -v "$(pwd)":/app -w /app composer install --no-dev

cd ..
zip -r shipmondo.zip shipmondo

rm -rf shipmondo