# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [4.3.1] - 2023-01-31
### Composer

- Changed `php` from `>=8.0` to `>=7.4`.
Full set of changes: [`4.3.0...4.3.1`][4.3.1]

[4.3.1]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/v4.3.0...v4.3.1

## [4.3.0] - 2022-12-23

### Commits

- Added Riverty gateway. ([9a8fed0](https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/commit/9a8fed0b6c4434f4aaedabf262ec09a69c18a467))
- Added https://github.com/WordPress/wp-plugin-dependencies header. ([72da8c4](https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/commit/72da8c4ea30a4fcbb56a1a86bc1e21375e0852a6))

### Composer

- Changed `php` from `>=5.6.20` to `>=8.0`.
- Changed `wp-pay/core` from `^4.4` to `v4.6.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.2.2
Full set of changes: [`4.2.2...4.3.0`][4.3.0]

[4.3.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/v4.2.2...v4.3.0

## [4.2.2] - 2022-11-29
- Fix required field indicator HTML escaped. [#5](https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/issues/5)

## [4.2.1] - 2022-09-27
- Update to `wp-pay/core` version `^4.4`.

## [4.2.0] - 2022-09-26
- Fixed Easy Digital Downloads 3 compatibility.
- Updated for new payment methods and fields registration.

## [4.1.0] - 2022-04-11
- Add company name controller.

## [4.0.0] - 2022-01-10
### Changed
- Updated to https://github.com/pronamic/wp-pay-core/releases/tag/4.0.0.
- Use new AfterPay.nl payment method.
- Added BLIK and MB WAY payment methods.
- Added support for TWINT payment method.

## [3.0.0] - 2021-08-05
- Updated to `pronamic/wp-pay-core`  version `3.0.0`.
- Updated to `pronamic/wp-money`  version `2.0.0`.
- Switched to `pronamic/wp-coding-standards`.
- Added support for SprayPay payment method.

## [2.2.0] - 2021-06-18
- Added initial support for refunds [#129](https://github.com/pronamic/wp-pronamic-pay/issues/129).

## [2.1.4] - 2021-04-26
- Improved adding payment details to 'Thank you' page.

## [2.1.3] - 2021-01-14
- Small improvements.

## [2.1.2] - 2020-07-08
- Added support for company name and VAT number from the custom Pronamic EDD plugins.
- Fixed registering `cancelled` post status for use in EDD payments table view filters.

## [2.1.1] - 2020-04-03
- Improved tax support for Easy Digital Downloads 3.0.
- Set plugin integration name.

## [2.1.0] - 2020-03-19
- Update integration setup with dependencies support.
- Set Easy Digital Downloads payment status to 'cancelled' in case of a cancelled payment.
- Extend `Extension` class from `AbstractPluginIntegration`.

## [2.0.7] - 2020-02-03
- Improve custom input fields HTML markup and validation.

## [2.0.6] - 2019-12-22
- Added payment line ID support with variable price ID.
- Improved output HTML to match Easy Digital Downloads.
- Improved error handling with exceptions.
- Updated payment status class name.

## [2.0.5] - 2019-08-26
- Updated packages.
- Empty cart for completed payments when handling returns.

## [2.0.4] - 2019-05-15
- Improve emptying cart for completed payments.

## [2.0.3] - 2019-03-29
- Always empty cart for completed payments.
- Simplified adding gateways and payment method icons.
- Fixed "The call to edd_record_gateway_error() has too many arguments" error.

## [2.0.2] - 2018-12-10
- More DRY approach for gateways.
- Added support for payment lines.
- Added Billink and Capayable gateways.

## [2.0.1] - 2018-07-06
- Added fallback to the default Pronamic Pay configuration ID.
- Prefixed the Pronamic gateways with 'Pronamic - '.
- Added new payment URL for Easy Digital Downloads version 3.0+.

## [2.0.0] - 2018-05-14
- Switched to PHP namespaces.

## [1.2.7] - 2017-09-13
- Implemented `get_first_name()` and `get_last_name()`.

## [1.2.6] - 2017-01-25
- Added Bank Transfer gateway.
- Added Bitcoin gateway.
- Added filter for payment source description and URL.
- Changed to class functions.
- Added new icons for Bitcoin and Soft.

## [1.2.5] - 2016-10-20
- Switched to Bancontact label and constant.

## [1.2.4] - 2016-04-12
- No longer use camelCase for payment data.

## [1.2.3] - 2016-03-23
- Tested Easy Digital Downloads version 2.5.9.
- Set global WordPress gateway config as default config in gateways.
- Use new redirect URL filter.
- Return to checkout if there is no gateway found.

## [1.2.2] - 2016-02-04
- Removed discontinued MiniTix gateway.
- Removed status code from redirect in status_update.

## [1.2.1] - 2015-10-19
- Set the payment method to use before getting the gateway inputs. 

## [1.2.0] - 2015-08-28
- Use output of `edd_get_payment_number()` as order ID if available.

## [1.1.0] - 2015-03-25
- Added Credit Card gateway.
- Added Direct Debit gateway.
- Added iDEAL gateway.
- Added MiniTix gateway.
- Added Bancontact/Mister Cash gateway.
- Added SOFORT Banking gateway.
- Added gateway setting for the checkout label.
- Only show transaction ID if set.
- Added pending payment note with link to payment post.
- Tested on Easy Digital Downloads version 2.3.

## [1.0.1] - 2015-03-03
- Changed WordPress pay core library requirement from `~1.0.0` to `>=1.0.0`.

## 1.0.0 - 2015-01-20
- First release.

[unreleased]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/4.2.2...HEAD
[4.2.2]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/4.2.1...4.2.2
[4.2.1]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/4.1.0...4.2.0
[4.1.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.2.0...3.0.0
[2.2.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.1.4...2.2.0
[2.1.4]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.1.3...2.1.4
[2.1.3]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.7...2.1.0
[2.0.7]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.6...2.0.7
[2.0.6]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.7...2.0.0
[1.2.7]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.6...1.2.7
[1.2.6]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.5...1.2.6
[1.2.5]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/compare/1.0.0...1.0.1
