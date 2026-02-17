<?php
class CloudWatchLogger
{
    private $logGroupName;
    private $logStreamName;
    private $sequenceToken;

    public function __construct()
    {
        $this->logGroupName = getenv('AWS_CLOUDWATCH_LOG_GROUP') ?: '/aws/alumni-portal';
        $this->logStreamName = getenv('AWS_CLOUDWATCH_LOG_STREAM') ?: 'application-logs';
    }

    /**
     * Log message to CloudWatch (if AWS configured) or local file
     */
    public function log($level, $message, $context = [])
    {
        $timestamp = time() * 1000; // CloudWatch expects milliseconds
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ];

        // Try CloudWatch if AWS is configured
        if (class_exists('Aws\Sdk') && getenv('AWS_ACCESS_KEY_ID')) {
            try {
                $sdk = new \Aws\Sdk([
                    'region' => getenv('AWS_REGION') ?: 'us-east-1',
                    'version' => 'latest'
                ]);

                $cloudWatchLogs = $sdk->createCloudWatchLogs();

                $cloudWatchLogs->putLogEvents([
                    'logGroupName' => $this->logGroupName,
                    'logStreamName' => $this->logStreamName,
                    'logEvents' => [
                        [
                            'message' => json_encode($logEntry),
                            'timestamp' => $timestamp
                        ]
                    ]
                ]);

                return true;
            } catch (Exception $e) {
                // Fallback to local logging
                error_log("CloudWatch Error: " . $e->getMessage());
            }
        }

        // Fallback: Local file logging
        $logFile = dirname(__DIR__) . '/storage/logs/app.log';
        $logDir = dirname($logFile);

        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $formattedLog = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        return file_put_contents($logFile, $formattedLog, FILE_APPEND) !== false;
    }

    public function info($message, $context = [])
    {
        return $this->log('INFO', $message, $context);
    }

    public function error($message, $context = [])
    {
        return $this->log('ERROR', $message, $context);
    }

    public function warning($message, $context = [])
    {
        return $this->log('WARNING', $message, $context);
    }
}
