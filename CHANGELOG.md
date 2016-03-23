# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [1.2.3] - 2016-03-23
- Tested Easy Digital Downloads version 2.5.9.
- Set global WordPress gateway config as default config in gateways.

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
- Changed WordPress pay core library requirment from ~1.0.0 to >=1.0.0.

## 1.0.0 - 2015-01-20
- First release.

[unreleased]: https://github.com/wp-pay-extensions/easy-digital-downloads/compare/1.2.3...HEAD
[1.2.3]: https://github.com/wp-pay-extensions/easy-digital-downloads/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/wp-pay-extensions/easy-digital-downloads/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/wp-pay-extensions/easy-digital-downloads/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/wp-pay-extensions/easy-digital-downloads/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/wp-pay-extensions/easy-digital-downloads/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/wp-pay-extensions/easy-digital-downloads/compare/1.0.0...1.0.1
