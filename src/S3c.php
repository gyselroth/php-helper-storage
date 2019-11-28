<?php

/**
 * Copyright (c) 2019 gyselroth™  (http://www.gyselroth.net)
 *
 * @package HelperStorage
 * @author  gyselroth™  (http://www.gyselroth.com)
 * @link    http://www.gyselroth.com
 * @license Apache-2.0
 */

namespace Gyselroth\HelperStorage;

use Aws\ResultPaginator;
use Aws\S3\S3Client;

/**
 * S3(-compatible) cloud storage helper methods
 */
class S3c
{
    /**
     * @param array  $s3Credentials
     * @param string $region
     * @param string $version
     * @param string $signatureVersion
     * @param bool   $usePathStyleEndpoint
     * @return S3Client
     * @uses $s3Credentials['endpoint']
     * @uses $s3redentials['accessKey']
     * @uses $s3Credentials['secretKey']
     */
    public static function connectToS3(
        array $s3Credentials,
        string $version = 'latest',
        string $signatureVersion = 'v4',
        bool $usePathStyleEndpoint = true
    ): S3Client
    {
        return new S3Client([
            'region'                  => $s3Credentials['region'],
            'version'                 => $version,
            'signature_version'       => $signatureVersion,
            'endpoint'                => $s3Credentials['endpoint'],
            'use_path_style_endpoint' => $usePathStyleEndpoint,
            'credentials'             => [
                'key'    => $s3Credentials['accessKey'],
                'secret' => $s3Credentials['secretKey'],
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
     * @param array  $s3Credentials
     * @param string $pathLocalStorage
     * @param bool   $deleteDownloadFilesFromBucket
     * @param string $filenameFilter    Sub-string that must be contained in files to be downloaded
     * @return int
     * @uses $s3Credentials['endpoint']
     * @uses $s3Credentials['accessKey']
     * @uses $s3Credentials['secretKey']
     * @uses $s3Credentials['bucketName']
     */
    public static function downloadFilesFromBucket(
        array $s3Credentials,
        string $pathLocalStorage,
        bool $deleteDownloadFilesFromBucket = false,
        string $filenameFilter = ''
    ): int
    {
        try {
            $s3 = self::connectToS3($s3Credentials);
            self::ensureBucketsExist($s3, [$s3Credentials['bucketName']]);

            $amountObjectsDownloaded = 0;
            $objectsInBucket         = self::getObjectsInS3Bucket($s3, $s3Credentials['bucketName']);
            foreach ($objectsInBucket as $objectInBucket) {
                foreach ($objectInBucket['Contents'] as $object) {
                    // Download object body to local XML file
                    $localFilename = $object['Key'];
                    if (!self::filenameMatchesFilter($filenameFilter, $localFilename)) {
                        // Skip files that do not match given filter
                        continue;
                    }

                    $object = $s3->getObject([
                        'Bucket' => $s3Credentials['bucketName'],
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
                            'Bucket' => $s3Credentials['bucketName'],
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
     * @param array $s3Credentials
     * @param array $filePaths
     * @uses $s3Credentials['endpoint']
     * @uses $s3Credentials['accessKey']
     * @uses $s3Credentials['secretKey']
     * @uses $s3Credentials['bucketName']
     */
    public static function uploadFilesToBucket(
        array $s3Credentials,
        array $filePaths
    ): void {

        $s3 = self::connectToS3($s3Credentials);
        self::ensureBucketsExist($s3, [$s3Credentials['bucketName']]);

        foreach ($filePaths as $filePath) {
            if (!\file_exists($filePath)) {
                die('Upload failed - file not found: ' . $filePath);
            }

            $s3->putObject([
                'Bucket' => $s3Credentials['bucketName'],
                'Key'    => basename($filePath),
                'Body'   => \file_get_contents($filePath),
            ]);
        }
    }

    /**
     * @param array $s3Credentials
     * @param array $objects
     * @uses $s3Credentials['endpoint']
     * @uses $s3Credentials['accessKey']
     * @uses $s3Credentials['secretKey']
     * @uses $s3Credentials['bucketName']
     * @uses $objects[['key']]   Filename to be stored
     * @uses $objects[['body']]  Content to be stored
     */
    public static function uploadObjectsToBucket(
        array $s3Credentials,
        array $objects
    ): void {

        $s3 = self::connectToS3($s3Credentials);
        self::ensureBucketsExist($s3, [$s3Credentials['bucketName']]);

        foreach ($objects as $object) {
            $s3->putObject([
                'Bucket' => $s3Credentials['bucketName'],
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
