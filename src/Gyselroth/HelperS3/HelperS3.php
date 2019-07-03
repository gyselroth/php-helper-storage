<?php

/**
 * Copyright (c) 2019 gyselroth™  (http://www.gyselroth.net)
 *
 * @package \gyselroth\HelperS3
 * @author  gyselroth™  (http://www.gyselroth.com)
 * @link    http://www.gyselroth.com
 * @license Apache-2.0
 */

//require_once __DIR__ . '/../../vendor/autoload.php';

use Aws\ResultPaginator;
use Aws\S3\S3Client;

/**
 * S3 cloud storage helper methods
 */
class HelperS3
{
    /**
     * @param array $awsCredentials
     * @return S3Client
     * @uses $awsCredentials['endpoint']
     * @uses $awsCredentials['accessKey']
     * @uses $awsCredentials['secretKey']
     */
    public static function connectToS3(array $awsCredentials): S3Client
    {
        return new Aws\S3\S3Client([
            'region'                  => 'us-west-2',
            'version'                 => 'latest',
            'signature_version'       => 'v4',
            'endpoint'                => $awsCredentials['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => $awsCredentials['accessKey'],
                'secret' => $awsCredentials['secretKey'],
            ]
        ]);
    }

    public static function ensureBucketsExist(S3Client $s3, array $bucketNames = []): void
    {
        if ([] === $bucketNames) {
            return;
        }

        $buckets          = $s3->listBuckets();
        $foundBucketNames = \array_column($buckets['Buckets'], 'Name');

        foreach ($bucketNames as $bucketName) {
            if (!\in_array($bucketName, $foundBucketNames, true)) {
                die("Bucket \"$bucketName\" NOT found on S3 volume.");
            }
        }
    }

    public static function getObjectsInS3Bucket(S3Client $s3, string $bucketName): ResultPaginator
    {
        return $s3->getPaginator('ListObjects', ['Bucket' => $bucketName]);
    }

    /**
     * @param array  $awsCredentials
     * @param string $pathLocalStorage
     * @param bool   $deleteDownloadFilesFromBucket
     * @param string $filenameFilter    Sub-string that must be contained in files to be downloaded
     * @return int
     * @uses $awsCredentials['endpoint']
     * @uses $awsCredentials['accessKey']
     * @uses $awsCredentials['secretKey']
     * @uses $awsCredentials['bucketName']
     */
    public static function downloadFilesFromBucket(
        array $awsCredentials,
        string $pathLocalStorage,
        bool $deleteDownloadFilesFromBucket = false,
        string $filenameFilter = ''
    ): int
    {
        try {
            $s3 = self::connectToS3($awsCredentials);
            self::ensureBucketsExist($s3, [$awsCredentials['bucketName']]);

            $amountObjectsDownloaded = 0;
            $objectsInBucket         = self::getObjectsInS3Bucket($s3, $awsCredentials['bucketName']);
            foreach ($objectsInBucket as $objectInBucket) {
                foreach ($objectInBucket['Contents'] as $object) {
                    // Download object body to local XML file
                    $localFilename = $object['Key'];
                    if (!self::filenameMatchesFilter($filenameFilter, $localFilename)) {
                        // Skip files that do not match given filter
                        continue;
                    }

                    $object = $s3->getObject([
                        'Bucket' => $awsCredentials['bucketName'],
                        'Key'    => $localFilename
                    ]);

                    \ob_start();
                    echo $object['Body'];
                    $fileContent = \ob_get_clean();

                    $pathDownloadedFile = $pathLocalStorage . '/' . $localFilename;
                    $fileHandle         = \fopen($pathDownloadedFile, 'wb+');
                    if (false === \fwrite($fileHandle, $fileContent)) {
                        die('Failed writing: ' . $pathDownloadedFile);
                    }
                    \fclose($fileHandle);
                    $amountObjectsDownloaded++;
                }
            }

            if ($deleteDownloadFilesFromBucket) {
                foreach ($objectsInBucket as $objectInBucket) {
                    foreach ($objectInBucket['Contents'] as $object) {
                        $s3->deleteObject([
                            'Bucket' => $awsCredentials['bucketName'],
                            'Key'    => $object['Key']]);
                    }
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage() . PHP_EOL);
        }

        return $amountObjectsDownloaded > 0;
    }

    /**
     * @param array $awsCredentials
     * @param array $filePaths
     * @uses $awsCredentials['endpoint']
     * @uses $awsCredentials['accessKey']
     * @uses $awsCredentials['secretKey']
     * @uses $awsCredentials['bucketName']
     */
    public static function uploadFilesToBucket(
        array $awsCredentials,
        array $filePaths
    ): void {

        $s3 = self::connectToS3($awsCredentials);
        self::ensureBucketsExist($s3, [$awsCredentials['bucketName']]);

        foreach ($filePaths as $filePath) {
            if (!\file_exists($filePath)) {
                die('Upload failed - file not found: ' . $filePath);
            }

            $s3->putObject([
                'Bucket' => $awsCredentials['bucketName'],
                'Key'    => basename($filePath),
                'Body'   => \file_get_contents($filePath),
            ]);
        }
    }

    /**
     * @param array $awsCredentials
     * @param array $objects
     * @uses $awsCredentials['endpoint']
     * @uses $awsCredentials['accessKey']
     * @uses $awsCredentials['secretKey']
     * @uses $awsCredentials['bucketName']
     * @uses $objects[['key']]   Filename to be stored
     * @uses $objects[['body']]  Content to be stored
     */
    public static function uploadObjectsToBucket(
        array $awsCredentials,
        array $objects
    ): void {

        $s3 = self::connectToS3($awsCredentials);
        self::ensureBucketsExist($s3, [$awsCredentials['bucketName']]);

        foreach ($objects as $object) {
            $s3->putObject([
                'Bucket' => $awsCredentials['bucketName'],
                'Key'    => $object['key'],
                'Body'   => $object['body'],
            ]);
        }
    }

    private static function filenameMatchesFilter(string $filesFilter, string $filename): bool
    {
        return
            '' === $filesFilter ||
            false !== \strpos($filename, $filesFilter);
    }
}
