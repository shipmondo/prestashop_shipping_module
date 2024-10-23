#!/bin/bash

docker build -t shipmondo-prestashop-builder .
docker run --name build-shipmondo-prestashop shipmondo-prestashop-builder
docker cp build-shipmondo-prestashop:/app/shipmondo.zip ./shipmondo.zip
docker rm build-shipmondo-prestashop