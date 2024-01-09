<?php

namespace Lmate\LaravelGoogleDrive\Providers;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lmate\LaravelGoogleDrive\Adapters\{GoogleDriveAdapter, GoogleDriveHandler};

class LaravelGoogleDriveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/credentials.php' => config_path(
                    'credentials.php'
                ),
            ],
            'config'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/credentials.php',
            'credentials'
        );
    }

    public function register(): void
    {
        $this->app->bind(Client::class, function () {

            $credentials = $this->getCredentials();

            if (empty($credentials)) {
                throw new \Exception(
                    'Service account file not found'
                );
            }

            $client = new Client();
            $client->addScope(Drive::DRIVE);

            $client->setAuthConfig($credentials);
//0886260049
            return $client;
        });

        $this->app->bind(
            Drive::class,
            function (Application $app) {
                $client = $app->make(Client::class);
                $googleServiceDrive = new Drive($client);
                $googleServiceDrive->servicePath = config(
                    'credentials.folder_id'
                );

                return $googleServiceDrive;
            }
        );

        $this->app->bind(
            GoogleDriveAdapter::class,
            function (Application $application) {
                $service = $application->make(Drive::class);

                return new GoogleDriveAdapter($service);
            }
        );

        $this->app->bind('google', function (Application $application) {
            return $application->make(GoogleDriveHandler::class);
        });
    }

    /**
     * @return array<int,string>
     */
    public function provides(): array
    {
        return [
            Drive::class,
            Client::class,
            GoogleDriveAdapter::class,
            GoogleDriveHandler::class,
        ];
    }

    /**
     * @return array<string,string>
     */
    private function getCredentials(): array
    {
        $credentialsFilePath = config('credentials.credentials.json');

        if (empty($credentialsFilePath)) {
            throw new \Exception(
                'Service_account file not found. Please check the GOOGLE_SERVICE_ACCOUNT_JSON_PATH .env variable.'
            );
        }

        $credentialsFileContent = file_get_contents($credentialsFilePath);

        return json_decode($credentialsFileContent ?: '', true) ?: [];
    }
}