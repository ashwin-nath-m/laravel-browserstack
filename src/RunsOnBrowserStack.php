<?php

namespace ChinLeung\BrowserStack;

use BrowserStack\Local;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxPreferences;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use PHPUnit\Runner\BaseTestRunner;

trait RunsOnBrowserStack
{
    /**
     * The BrowserStack connection instance.
     *
     * @var \BrowserStack\Local
     */
    protected static $connection;

    /**
     * The BrowserStack capabitities.
     *
     * @var array
     */
    protected static $capabilities = [];

    /**
     * Flag to know if the after class callback has been registered.
     *
     * @var bool
     */
    protected static $registeredAfterClassCallback = false;

    /**
     * Update the BrowserStack status if the test has run on BrowserStack
     * and close the current session if the user wants one test per session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->hasActiveBrowserStackConnection()) {
            if (config('browserstack.separate_sessions')) {
                $this->updateBrowserStackSessionStatus();
                static::closeAll();
            }
        }

        parent::tearDown();
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver(): RemoteWebDriver
    {
        if ($this->shouldRunOnBrowserStack()) {
            return $this->createBrowserStackDriver();
        }

        return $this->createLocalDriver();
    }

    /**
     * Create the driver for local tests.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function createBrowserStackDriver(): RemoteWebDriver
    {
        $this->connectToBrowserStack();

        return RemoteWebDriver::create(
            $this->endpointForBrowserStack(),
            $this->capabilitiesForBrowserStack()
        );
    }

    /**
     * Create the driver for local tests.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function createLocalDriver(): RemoteWebDriver
    {
        static::startChromeDriver();

        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    /**
     * Retrieve the capabilities of the browser.
     *
     * @return array
     */
    protected function browserCapabilities(): array
    {
        $slug = $this->getBrowserSlug();

        return array_merge($this->detectOs($slug), $this->detectBrowser($slug));
    }

    /**
     * Retrieve the capabilities for the correct browser based on the slug.
     *
     * @param  string  $slug
     * @return array
     */
    protected function detectBrowser(string $slug): array
    {
        preg_match('/_(IE|EDGE|CHROME|FIREFOX|SAFARI|OPERA)(\d*)/', $slug, $browser);

        $capabilities = array_filter([
            'browser' => $browser[1],
            'browser_version' => $browser[2],
        ]);

        // Disable the Devtools JSONView of Firefox which causes error when
        // parsing a JSON response.
        if ($browser[1] == 'FIREFOX') {
            $caps = DesiredCapabilities::firefox();

            $profile = new FirefoxProfile;
            $profile->setPreference('devtools.jsonview.enabled', false);
            $profile->setPreference(
                FirefoxPreferences::READER_PARSE_ON_LOAD_ENABLED,
                false
            );

            $caps->setCapability(FirefoxDriver::PROFILE, $profile);
        } else {
            $method = str_replace(
                ['ie', 'edge'],
                ['internetExplorer', 'microsoftEdge'],
                strtolower($browser[1])
            );

            $caps = DesiredCapabilities::$method();
        }

        return array_merge($capabilities, $caps->toArray());
    }

    /**
     * Retrieve the capabilities for the correct operating system based on the
     * slug.
     *
     * @param  string  $slug
     * @return array
     */
    protected function detectOs(string $slug): array
    {
        if (strpos($slug, 'IOS_') === 0 || strpos($slug, 'ANDROID_') === 0) {
            return $this->detectMobileOs($slug);
        }

        preg_match(
            '/(MACOS_(CATALINA|MOJAVE|HIGH_SIERRA|SIERRA|EL_CAPITAN|YOSEMITE|MAVERICKS|MOUNTAIN_LION|LION|SNOW_LEOPARD)|WINDOWS_(10|8(\.1)?|XP))/',
            $slug,
            $os
        );

        return [
            'os' => strpos($slug, 'WINDOWS') !== false ? 'Windows' : 'OS X',
            'os_version' => str_replace(
                '_',
                ' ',
                isset($os[3]) ? $os[3] : $os[2]
            ),
        ];
    }

    /**
     * Detect the correct operating system for a mobile device.
     *
     * @param  string  $array
     * @return array
     */
    protected function detectMobileOs(string $slug): array
    {
        preg_match('/(ANDROID|IOS)_(.*)/', $slug, $os);

        $method = $os[1] == 'ANDROID' ? 'android' : (
            strpos($os[2], 'IPHONE') !== false ? 'iphone' : 'ipad'
        );

        return array_merge([
            'device' => str_replace('_', ' ', $os[2]),
            'real_mobile' => true,
        ], DesiredCapabilities::$method()->toArray());
    }

    /**
     * Retrieve the capabilities for BrowserStack.
     *
     * @link https://www.browserstack.com/automate/capabilities
     * @return array
     */
    protected function capabilitiesForBrowserStack(): array
    {
        $slug = $this->getBrowserSlug();

        if (! isset(static::$capabilities[$slug])) {
            static::$capabilities[$slug] = array_merge(
                Arr::dot(config('browserstack.capabilities')),
                $this->browserCapabilities()
            );
        }

        return array_merge(static::$capabilities[$slug], [
            'project' => $this->getProjectName(),
            'build' => $this->getBuildName(),
            'name' => $this->getSessionName(),
        ]);
    }

    /**
     * Retrieve the endpoint for BrowserStack.
     *
     * @return string
     */
    protected function endpointForBrowserStack(): string
    {
        return sprintf(
            'https://%s:%s@hub-cloud.browserstack.com/wd/hub',
            config('browserstack.username'),
            config('browserstack.key')
        );
    }

    /**
     * Connect to BrowserStack.
     *
     * @return void
     */
    protected function connectToBrowserStack(): void
    {
        if ($this->connectedToBrowserStack()) {
            return;
        }

        rescue(function () {
            static::$connection = tap(new Local)->start(
                $this->argumentsForBrowserStack()
            );
            $this->connectedToBrowserStack();

            if (! static::$registeredAfterClassCallback) {
                static::$registeredAfterClassCallback = true;

                static::afterClass(function () {
                    optional(static::$connection)->stop();
                    $this->connectedToBrowserStack();

                    static::$connection = null;
                });
            }
        });
    }

    /**
     * Retrieve the slug of the browser.
     *
     * @return string|null
     */
    protected function getBrowserSlug(): ?string
    {
        return config('browserstack.browser');
    }

    /**
     * Retrieve the name of the build to display in the BrowserStack
     * dashboard.
     *
     * @return string
     */
    protected function getBuildName(): string
    {
        $sha = env('GITHUB_SHA');

        if (is_null($sha)) {
            return config('app.env');
        }

        return sprintf(
            '%s @ %s',
            str_replace('refs', '', $sha),
            env('GITHUB_REF', config('app.env'))
        );
    }

    /**
     * Retrieve the name of the project to display in the BrowserStack
     * dashboard.
     *
     * @return string
     */
    protected function getProjectName(): string
    {
        return config('app.name');
    }

    /**
     * Retrieve the name of the session to display in the BrowserStack
     * dashboard.
     *
     * @return string
     */
    protected function getSessionName(): string
    {
        $class = get_called_class();

        return sprintf('%s @ %s', class_basename($class), Arr::get(
            collect(debug_backtrace())->firstWhere('class', $class),
            'function',
            'Unknown function'
        ));
    }

    /**
     * Check if we have an active connection with BrowserStack.
     *
     * @return bool
     */
    protected function hasActiveBrowserStackConnection(): bool
    {
        return ! is_null(static::$connection);
    }

    /**
     * Check if the test should run on BrowserStack.
     *
     * @return bool
     */
    protected function shouldRunOnBrowserStack(): bool
    {
        return ! is_null($this->getBrowserSlug());
    }

    /**
     * Update the status of the session in BrowserStack.
     *
     * @link  https://www.browserstack.com/automate/rest-api
     * @return void
     */
    protected function updateBrowserStackSessionStatus(): void
    {
        $browser = collect(static::$browsers)->first();

        (new Client)->put(
            "https://api.browserstack.com/automate/sessions/{$browser->driver->getSessionID()}.json",
            [
                'json' => array_filter([
                    'status' => $this->getStatus() == BaseTestRunner::STATUS_PASSED
                        ? 'passed' : 'failed',
                    'reason' => $this->getStatusMessage(),
                ]),
                'auth' => [
                    config('browserstack.username'),
                    config('browserstack.key'),
                ],
            ]
        );
    }

    /**
     * Verify if the connection to BrowserStack has been made.
     *
     * @return bool
     */
    protected function connectedToBrowserStack(): bool
    {
        return ! is_null(static::$connection) && static::$connection->isRunning();
    }

    /**
     * Retrieve the arguments for the BrowserStack connection.
     *
     * @return array
     */
    protected function argumentsForBrowserStack(): array
    {
        return array_filter(array_merge(
            [
                'key' => config('browserstack.key'),
            ],
            config('browserstack.arguments')
        ));
    }
}
