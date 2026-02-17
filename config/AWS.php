<?php

/**
 * AWS Configuration and SDK Setup
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\SecretsManager\SecretsManagerClient;
use Aws\CloudWatchLogs\CloudWatchLogsClient;

class AWS
{
    private static $s3Client = null;
    private static $secretsClient = null;
    private static $cloudWatchClient = null;

    private static $config = [
        'region' => 'us-east-1',
        'version' => 'latest'
    ];

    /**
     * Initialize AWS configuration
     */
    public static function init()
    {
        // Load AWS credentials from environment
        self::$config['credentials'] = [
            'key' => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY')
        ];

        $region = getenv('AWS_REGION');
        if ($region) {
            self::$config['region'] = $region;
        }
    }

    /**
     * Get S3 Client
     */
    public static function getS3Client()
    {
        if (self::$s3Client === null) {
            self::init();
            self::$s3Client = new S3Client(self::$config);
        }
        return self::$s3Client;
    }

    /**
     * Get Secrets Manager Client
     */
    public static function getSecretsClient()
    {
        if (self::$secretsClient === null) {
            self::init();
            self::$secretsClient = new SecretsManagerClient(self::$config);
        }
        return self::$secretsClient;
    }

    /**
     * Get CloudWatch Logs Client
     */
    public static function getCloudWatchClient()
    {
        if (self::$cloudWatchClient === null) {
            self::init();
            self::$cloudWatchClient = new CloudWatchLogsClient(self::$config);
        }
        return self::$cloudWatchClient;
    }

    /**
     * Get secret from AWS Secrets Manager
     */
    public static function getSecret($secretName)
    {
        try {
            $client = self::getSecretsClient();
            $result = $client->getSecretValue([
                'SecretId' => $secretName
            ]);

            if (isset($result['SecretString'])) {
                return json_decode($result['SecretString'], true);
            }

            return null;
        } catch (Exception $e) {
            error_log("Error retrieving secret: " . $e->getMessage());
            return null;
        }
    }
}
