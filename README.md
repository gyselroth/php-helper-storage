gyselroth PHP Storage Helper
============================

PHP helper methods for storage services.


Features
--------

* Connect to S3 compatible storage service
* List S3 buckets
* Download files from S3 bucket
* Upload files / objects to S3 bucket

  
#### Useage Example:  
```php
<?php 
 use Gyselroth\HelperStorage\S3c;
 
 $awsCredentials = [
      'endpoint'  => 'http://127.0.0.0:9000',  // using custom/local S3-service, e.g. minio 
      'accessKey' => '<YOUR_ACCESSKEY>',
      'secretKEY' => '<YOUR_SECRETKEY>'
  ];
 
 // Download files from bucket
 S3c::downloadFilesFromBucket(
    array_merge($awsCredentials, ['bucketName' => 'myDownloadsBucket']),
    $pathDownloads,
    $deleteDownloadFilesFromBucket);
 
 // Upload objects to bucket
 S3c::uploadObjectsToBucket(
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
