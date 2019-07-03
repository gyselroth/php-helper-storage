gyselroth PHP S3-Helper Library
===============================

PHP helper methods for S3-compatible storage services.


Features
--------

* Connect to S3 compatible storage service
* List buckets
* Download files from bucket
* Upload files / objects to bucket

  
#### Useage Example:  
```php
<?php 
 use Gyselroth\HelperS3;
 
 $awsCredentials = [
      'endpoint'  => 'http://127.0.0.0:9000',  // using custom/local S3-service, e.g. minio 
      'accessKey' => '<YOUR_ACCESSKEY>',
      'secretKEY' => '<YOUR_SECRETKEY>'
  ];
 
 // Download files from bucket
 HelperS3::downloadFilesFromBucket(
    array_merge($awsCredentials, ['bucketName' => 'myDownloadsBucket']),
    $pathDownloads,
    $deleteDownloadFilesFromBucket);
 
 // Upload objects to bucket
 HelperS3::uploadObjectsToBucket(
    array_merge($awsCredentials, ['bucketName' => 'myUploadsBucket']),
    [
        [
            'key'   => 'README.md',
            'body'  => 'hello world!'
        ]
    ]);
 
```


History
-------

See `CHANGELOG.md`


Author and License
------------------

Copyright 2019 gyselroth™ (http://www.gyselroth.com)

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0":http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License. 
