<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    private $testStartTime;
    private $currentTestName;
    private static $logInitialized = false;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Initialize the log file with a fresh start and date header
     */
    private static function initializeLogFile(): void
    {
        if (!static::$logInitialized) {
            $logDir = storage_path('logs');
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logFile = storage_path('logs/dusk_tests.log');
            $dateHeader = "=== Dusk Test Run - " . now()->format('Y-m-d H:i:s') . " ===";

            // Clear the log file and add date header
            file_put_contents($logFile, $dateHeader . PHP_EOL . PHP_EOL, LOCK_EX);

            static::$logInitialized = true;
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--ignore-certificate-errors',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->testStartTime = now();

        // Initialize log file once per test run (when Laravel app is available)
        static::initializeLogFile();
    }

    protected function tearDown(): void
    {
        $this->logTestResult('PASSED');
        parent::tearDown();
    }

    protected function onNotSuccessfulTest(\Throwable $t): never
    {
        $this->logTestResult('FAILED', $t->getMessage());
        parent::onNotSuccessfulTest($t);
    }

    protected function setTestName($testName)
    {
        $this->currentTestName = $testName;
    }

    private function logTestResult($status, $error = null)
    {
        $testName = $this->currentTestName ?? 'unknown_test';
        $className = get_class($this);
        $duration = now()->diffInMilliseconds($this->testStartTime);
        $timestamp = now()->format('H:i:s'); // Only time, since date is in header

        $logMessage = "[{$timestamp}] {$className}::{$testName}: {$status} ({$duration}ms)";

        if ($error) {
            $cleanError = str_replace(["\n", "\r"], ' ', $error);
            $cleanError = preg_replace('/\s+/', ' ', $cleanError);
            $logMessage .= " - Error: " . substr($cleanError, 0, 200) . (strlen($cleanError) > 200 ? '...' : '');
        }

        file_put_contents(
            storage_path('logs/dusk_tests.log'),
            $logMessage . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
