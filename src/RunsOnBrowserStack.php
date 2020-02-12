<?php

namespace ChinLeung\BrowserStack;

use BrowserStack\Local;
use Facebook\WebDriver\Chrome\ChromeOptions;
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
     * Update the BrowserStack status if the test has run on BrowserStack
     * and close the current session if the user wants one test per session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->hasActiveBrowserStackConnection()) {
            $this->updateBrowserStackSessionStatus();

            if (config('browserstack.separate_sessions')) {
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
            $this->capabilitiesForBrowserStack(),
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
        preg_match('/(IE|EDGE|CHROME|FIREFOX|SAFARI|OPERA)(\d*)/', $slug, $browser);

        return array_filter([
            'browser' => $browser[1],
            'browser_version' => $browser[2],
        ]);
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
        preg_match(
            '/(MACOS_(CATALINA|MOJAVE|HIGH_SIERRA|SIERRA|EL_CAPITAN|YOSEMITE|MAVERICKS|MOUNTAIN_LION|LION|SNOW_LEOPARD)|WINDOWS_(10|8(\.1)?|XP))/',
            $slug,
            $os
        );

        return [
            'os' => strpos($slug, 'WINDOWS') !== false ? 'Windows' : 'OS X',
            'os_version' => isset($os[3]) ? $os[3] : $os[2],
        ];
    }

    /**
     * Retrieve the capabilities for BrowserStack.
     *
     * @link https://www.browserstack.com/automate/capabilities
     * @return array
    */
    protected function capabilitiesForBrowserStack(): array
    {
        return array_merge(
            [
                'project' => $this->getProjectName(),
                'build' => $this->getBuildName(),
                'name' => $this->getSessionName(),
            ],
            config('browserstack.capabilities'),
            $this->browserCapabilities()
        );
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
        if (is_null(static::$connection)) {
            static::$connection = new Local;

            static::$connection->start(array_filter([
                'key' => config('browserstack.key'),
                'forcelocal' => config('browserstack.capabilities.browserstack.local') ? true : null,
            ]));
        }

        static::afterClass(function () {
            optional(static::$connection)->stop();
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
}
