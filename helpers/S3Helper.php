<?php

/**
 * S3 Helper for file uploads and management
 */

require_once __DIR__ . '/../config/AWS.php';

class S3Helper
{
    private $s3Client;
    private $bucket;

    public function __construct()
    {
        $this->s3Client = AWS::getS3Client();
        $this->bucket = getenv('AWS_BUCKET') ?: 'alumni-portal-uploads';
    }

    /**
     * Upload file to S3
     * 
     * @param string $localPath Local file path
     * @param string $s3Key S3 object key (path in bucket)
     * @param array $metadata Optional metadata
     * @return array Result with success status and URL
     */
    public function uploadFile($localPath, $s3Key, $metadata = [])
    {
        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key,
                'SourceFile' => $localPath,
                'ACL' => 'public-read',
                'Metadata' => $metadata,
                'ContentType' => mime_content_type($localPath)
            ]);

            return [
                'success' => true,
                'url' => $result['ObjectURL'],
                'key' => $s3Key
            ];
        } catch (Exception $e) {
            error_log("S3 Upload Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload file from uploaded file array
     * 
     * @param array $file $_FILES array element
     * @param string $directory Directory in S3 bucket
     * @return array Result with success status and URL
     */
    public function uploadFromUpload($file, $directory = 'uploads')
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'Upload error: ' . $file['error']
            ];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $s3Key = $directory . '/' . $filename;

        return $this->uploadFile($file['tmp_name'], $s3Key, [
            'original_name' => $file['name']
        ]);
    }

    /**
     * Delete file from S3
     * 
     * @param string $s3Key S3 object key
     * @return bool Success status
     */
    public function deleteFile($s3Key)
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key
            ]);
            return true;
        } catch (Exception $e) {
            error_log("S3 Delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate presigned URL for temporary access
     * 
     * @param string $s3Key S3 object key
     * @param int $expiresIn Expiration time in seconds (default 1 hour)
     * @return string Presigned URL
     */
    public function getPresignedUrl($s3Key, $expiresIn = 3600)
    {
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $s3Key
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, "+{$expiresIn} seconds");
            return (string) $request->getUri();
        } catch (Exception $e) {
            error_log("S3 Presigned URL Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if file exists in S3
     * 
     * @param string $s3Key S3 object key
     * @return bool
     */
    public function fileExists($s3Key)
    {
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get file URL (public)
     * 
     * @param string $s3Key S3 object key
     * @return string Public URL
     */
    public function getFileUrl($s3Key)
    {
        $cloudFrontUrl = getenv('AWS_CLOUDFRONT_URL');

        if ($cloudFrontUrl) {
            return rtrim($cloudFrontUrl, '/') . '/' . ltrim($s3Key, '/');
        }

        return "https://{$this->bucket}.s3.amazonaws.com/{$s3Key}";
    }

    /**
     * Copy file within S3
     * 
     * @param string $sourceKey Source S3 key
     * @param string $destKey Destination S3 key
     * @return bool Success status
     */
    public function copyFile($sourceKey, $destKey)
    {
        try {
            $this->s3Client->copyObject([
                'Bucket' => $this->bucket,
                'CopySource' => "{$this->bucket}/{$sourceKey}",
                'Key' => $destKey
            ]);
            return true;
        } catch (Exception $e) {
            error_log("S3 Copy Error: " . $e->getMessage());
            return false;
        }
    }
}
