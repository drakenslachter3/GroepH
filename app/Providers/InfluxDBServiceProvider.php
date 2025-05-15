<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use InfluxDB2\Client as InfluxDBClient;

class InfluxDBServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(InfluxDBClient::class, function ($app) {
            return new InfluxDBClient([
                'url' => config('influxdb.url'),
                'token' => config('influxdb.token'),
                'bucket' => config('influxdb.bucket'),
                'org' => config('influxdb.org'),
                'precision' => \InfluxDB2\Model\WritePrecision::NS,
                'debug' => config('influxdb.debug'),
                'timeout' => config('influxdb.timeout'),
                'verifySSL' => config('influxdb.verify_ssl'),
            ]);
        });

        $this->app->alias(InfluxDBClient::class, 'influxdb');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}