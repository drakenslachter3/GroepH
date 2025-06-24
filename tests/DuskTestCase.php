<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use Illuminate\Support\Facades\Artisan;

abstract class DuskTestCase extends BaseTestCase
{
    private $testStartTime;
    private $currentTestName;
    private static $logInitialized = false;
    private static $databaseInitialized = false;

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
     * Initialize database once per test suite, not per test
     */
    private static function initializeDatabase(): void
    {
        if (!static::$databaseInitialized) {
            // Only migrate:fresh once for the entire test suite
            Artisan::call('migrate:fresh');
            
            // Seed any base data that all tests need
            // Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
            
            static::$databaseInitialized = true;
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
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor',
            // Suppress Chrome logging and debug messages
            '--silent',
            '--log-level=3',
            '--disable-logging',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-features=TranslateUI',
            '--disable-ipc-flooding-protection',
            '--disable-hang-monitor',
            '--disable-client-side-phishing-detection',
            '--disable-component-update',
            '--disable-default-apps',
            '--disable-domain-reliability',
            '--disable-features=AudioServiceOutOfProcess',
            '--disable-features=VoiceTranscription',
            '--disable-speech-api',
            '--use-mock-keychain',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        // Set Chrome log preferences to suppress DevTools messages
        $prefs = [
            'profile.default_content_setting_values.notifications' => 2,
            'profile.default_content_settings.popups' => 0,
            'profile.managed_default_content_settings.images' => 2,
            'profile.content_settings.exceptions.automatic_downloads.*.setting' => 1,
        ];
        $options->setExperimentalOption('prefs', $prefs);

        // Suppress Chrome logs
        $options->setExperimentalOption('excludeSwitches', ['enable-logging']);
        $options->setExperimentalOption('useAutomationExtension', false);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setCapability('goog:loggingPrefs', ['browser' => 'OFF', 'driver' => 'OFF']);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            $capabilities
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->testStartTime = now();
        
        // Initialize database once for all tests
        static::initializeDatabase();
        
        // Clear any cached config (but don't refresh database)
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        
        // Initialize log file once per test run
        static::initializeLogFile();
    }

    protected function tearDown(): void
    {
        $this->logTestResult('PASSED');
        
        // Clean up test data, but don't drop entire database
        $this->cleanupTestData();
        
        parent::tearDown();
    }

    /**
     * Clean up only the data created by this specific test
     */
    protected function cleanupTestData(): void
    {
        try {
            // Clean up test-specific data instead of wiping entire database
            \DB::table('energy_budgets')->where('user_id', '>', 0)->delete();
            \DB::table('smart_meters')->where('meter_id', 'like', 'TEST-%')->delete();
            \DB::table('users')->where('email', 'like', '%@example.com')->delete();
            \DB::table('energy_notifications')->truncate();
            \DB::table('monthly_energy_budgets')->truncate();
            \DB::table('user_grid_layouts')->truncate();
        } catch (\Exception $e) {
            // If cleanup fails, log it but don't fail the test
            error_log("Test cleanup failed: " . $e->getMessage());
        }
    }

    protected function onNotSuccessfulTest(\Throwable $t): never
    {
        $this->logTestResult('FAILED', $t->getMessage());
        
        // Still clean up on failure
        $this->cleanupTestData();
        
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
        $timestamp = now()->format('H:i:s');

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