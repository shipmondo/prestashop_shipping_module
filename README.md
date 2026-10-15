# Shipmondo Delivery Checkout for PrestaShop

## Overview

The **Shipmondo Delivery Checkout** module integrates Shipmondo's delivery options into your PrestaShop store.
This allows you to offer a wide range of carrier to your customers, incl. selecting service points in checkout.

[Click here](https://shipmondo.com/) for more information about Shipmondo.

## Installation

For instruction for how to install and configure Shipmondo Delivery Checkout, please refer to our [guide](https://help.shipmondo.com/articles/10002313).

## Requirements

- PHP >= 7.2
- PrestaShop >= 8.0.0

## Development

### Overriding the Shipmondo API URL

By default the module talks to `https://app.shipmondo.com/api/public/v3/`. Shops never need to change this.

For testing, e.g. against a mock server in end-to-end tests, set the `SHIPMONDO_API_URL` environment variable to another base URL:

```sh
SHIPMONDO_API_URL=http://shipmondo-mock:8080/api/public/v3/
```

The variable must be visible to PHP through `getenv()`. With PHP-FPM, environment variables are cleared by default, so either set `clear_env = no` or add `env[SHIPMONDO_API_URL] = ...` to the pool configuration.

API responses are cached in the PrestaShop configuration table, so clear the `SHIPMONDO_*_CACHE*` and `SHIPMONDO_AVAILABLE_CARRIERS*` values after switching URL.

## License

This module is licensed under the [BSD 3-Clause License](LICENSE).
