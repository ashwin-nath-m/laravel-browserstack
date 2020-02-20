# Laravel BrowserStack

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chinleung/laravel-browserstack.svg?style=flat-square)](https://packagist.org/packages/chinleung/laravel-browserstack)
[![Total Downloads](https://img.shields.io/packagist/dt/chinleung/laravel-browserstack.svg?style=flat-square)](https://packagist.org/packages/chinleung/laravel-browserstack)

A package to run [Laravel Dusk](https://github.com/laravel/dusk) tests on [BrowserStack](https://www.browserstack.com).

## Installation

You can install the package via composer:

```bash
composer require --dev chinleung/laravel-browserstack
```

Make sure to add your [credentials](https://www.browserstack.com/accounts/settings) to the `.env`:

```
BROWSERSTACK_USERNAME=<username>
BROWSERSTACK_ACCESS_KEY=<access-key>
```

## Configuration

You can customize the capabilities and other configuration for BrowserStack by publishing the config file:

```bash
php artisan vendor:publish --provider="ChinLeung\BrowserStack\BrowserStackServiceProvider" --tag="config"
```

## Quick Usage

Simply add the `RunsOnBrowserStack` trait to the test class you want to run on BrowserStack.

```php
abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, RunsOnBrowserStack;
}
```

Then you can run your tests:

```bash
BROWSERSTACK_BROWSER=WINDOWS_10_CHROME php artisan dusk
```

The browser slug should match the following pattern:

```
(OS)_(OS_VERSION)_(BROWSER)_(BROWSER_VERSION)
```

If no browser version has been provided, the tests will be run on the latest version of the browser available in BrowserStack.

## Browsers

You can find below the list of available slugs for the browser option.

<details>
  <summary>Windows</summary>
  
- WINDOWS_10_IE
- WINDOWS_10_EDGE
- WINDOWS_10_CHROME
- WINDOWS_10_FIREFOX
- WINDOWS_8.1_IE
- WINDOWS_8.1_EDGE
- WINDOWS_8.1_CHROME
- WINDOWS_8.1_FIREFOX
- WINDOWS_8_IE
- WINDOWS_8_EDGE
- WINDOWS_8_CHROME
- WINDOWS_8_FIREFOX
- WINDOWS_7_IE
- WINDOWS_7_EDGE
- WINDOWS_7_CHROME
- WINDOWS_7_FIREFOX
- WINDOWS_XP_IE
- WINDOWS_XP_CHROME
- WINDOWS_XP_FIREFOX
- WINDOWS_XP_OPERA
</details>

<details>
  <summary>OS X</summary>
  
- MACOS_CATALINA_SAFARI
- MACOS_CATALINA_CHROME
- MACOS_CATALINA_FIREFOX
- MACOS_CATALINA_EDGE
- MACOS_MOJAVE_SAFARI
- MACOS_MOJAVE_CHROME
- MACOS_MOJAVE_FIREFOX
- MACOS_MOJAVE_OPERA
- MACOS_HIGH_SIERRA_SAFARI
- MACOS_HIGH_SIERRA_CHROME
- MACOS_HIGH_SIERRA_FIREFOX
- MACOS_HIGH_SIERRA_OPERA
- MACOS_SIERRA_SAFARI
- MACOS_SIERRA_CHROME
- MACOS_SIERRA_FIREFOX
- MACOS_SIERRA_OPERA
- MACOS_EL_CAPITAN_SAFARI
- MACOS_EL_CAPITAN_CHROME
- MACOS_EL_CAPITAN_FIREFOX
- MACOS_EL_CAPITAN_OPERA
- MACOS_YOSEMITE_SAFARI
- MACOS_YOSEMITE_CHROME
- MACOS_YOSEMITE_FIREFOX
- MACOS_YOSEMITE_OPERA
- MACOS_MOUNTAIN_LION_SAFARI
- MACOS_MOUNTAIN_LION_CHROME
- MACOS_MOUNTAIN_LION_FIREFOX
- MACOS_MOUNTAIN_LION_OPERA
- MACOS_LION_SAFARI
- MACOS_LION_CHROME
- MACOS_LION_FIREFOX
- MACOS_LION_OPERA
- MACOS_SNOW_LEOPARD_SAFARI
- MACOS_SNOW_LEOPARD_CHROME
- MACOS_SNOW_LEOPARD_FIREFOX
- MACOS_SNOW_LEOPARD_OPERA
</details>

<details>
  <summary>ANDROID</summary>
  
- ANDROID_SAMSUNG_GALAXY_S9_PLUS
- ANDROID_SAMSUNG_GALAXY_S8_PLUS
- ANDROID_SAMSUNG_GALAXY_S10E
- ANDROID_SAMSUNG_GALAXY_S10_PLUS
- ANDROID_SAMSUNG_GALAXY_S10
- ANDROID_SAMSUNG_GALAXY_NOTE_10_PLUS
- ANDROID_SAMSUNG_GALAXY_NOTE_10
- ANDROID_SAMSUNG_GALAXY_A10
- ANDROID_SAMSUNG_GALAXY_NOTE_9
- ANDROID_SAMSUNG_GALAXY_S9_PLUS
- ANDROID_SAMSUNG_GALAXY_S9
- ANDROID_SAMSUNG_GALAXY_NOTE_8
- ANDROID_SAMSUNG_GALAXY_A8
- ANDROID_SAMSUNG_GALAXY_S8
- ANDROID_SAMSUNG_GALAXY_S7
- ANDROID_SAMSUNG_GALAXY_NOTE_4
- ANDROID_SAMSUNG_GALAXY_S6
- ANDROID_GOOGLE_PIXEL_4_XL
- ANDROID_GOOGLE_PIXEL_4
- ANDROID_GOOGLE_PIXEL_3
- ANDROID_GOOGLE_PIXEL_3_XL
- ANDROID_GOOGLE_PIXEL_3A
- ANDROID_GOOGLE_PIXEL_3A_XL
- ANDROID_GOOGLE_PIXEL_2
- ANDROID_GOOGLE_PIXEL
- ANDROID_GOOGLE_NEXUS_6
- ANDROID_GOOGLE_NEXUS_5
- ANDROID_MOTOROLA_MOTO_G7_PLAY
- ANDROID_MOTOROLA_MOTO_X_2ND_GEN
- ANDROID_ONEPLUS_7
- ANDROID_ONEPLUS_6T
</details>

The list of possible combinations of OS, Browsers and Browser Versions are available here: https://www.browserstack.com/automate/capabilities

## Security

If you discover any security related issues, please email hello@chinleung.com instead of using the issue tracker.

## Credits

- [Chin Leung](https://github.com/chinleung)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
