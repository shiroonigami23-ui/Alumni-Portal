<?php

// Ideally we'd use 'use Aws\S3\S3Client;' but autoloader might not be ready
// So we wrap this in a class that checks for SDK presence

class AWSHelper
{
    private $s3;
    private $ses;
    private $cloudWatch;
    private $bucket;
    private $region;

    public function __construct()
    {
        // Only initialize if SDK is loaded
        if (class_exists('Aws\Sdk')) {
            $this->region = getenv('AWS_REGION') ?: 'us-east-1';
            $this->bucket = getenv('AWS_BUCKET');

            $sdk = new \Aws\Sdk([
                'region'   => $this->region,
                'version'  => 'latest'
            ]);

            $this->s3 = $sdk->createS3();
            $this->ses = $sdk->createSes();
            $this->cloudWatch = $sdk->createCloudWatchLogs();
        }
    }

    public function uploadFile($key, $sourcePath)
    {
        if (!$this->s3) return false;

        try {
            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SourceFile' => $sourcePath,
                'ACL'    => 'public-read' // Adjust based on privacy needs
            ]);
            return $result['ObjectURL'];
        } catch (Exception $e) {
            error_log("AWS Upload Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendEmail($to, $subject, $body)
    {
        if (!$this->ses) return false;

        try {
            $result = $this->ses->sendEmail([
                'Destination' => [
                    'ToAddresses' => [$to],
                ],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'UTF-8',
                            'Data' => $body,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => $subject,
                    ],
                ],
                'Source' => getenv('AWS_SES_FROM_EMAIL'),
            ]);
            return $result['MessageId'];
        } catch (Exception $e) {
            error_log("AWS SES Error: " . $e->getMessage());
            return false;
        }
    }

    public static function isConfigured()
    {
        return getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY');
    }
}
