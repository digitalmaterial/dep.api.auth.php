# DEP API Auth

An MTN DEP API Client implementation supporting the Amazon AWS authentication mechanism, used to make REST calls to the DEP platform.

## Installation
Edit your projects composer.json and add
```composer
"repositories": [
    ...
    {
      "type": "vcs",
      "url": "https://github.com/digitalmaterial/dep.api.auth.php"
    }
    ...
]
```
Add the new package to your require section
```bash
project/root/folder# composer require digitalmaterial/dep.api.auth.php
```

## Usage
**Basic multi step process:**
```php
$depClient      = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za');
$depClient->createRequest('POST' '/subscription'); // Returns DEPClient object for chaining, see below
$depClient->getRequest(); // Will return the GuzzleHttp\Psr7\Request object with signed auth details for DEP API requests 
$response       = $depClient->send();
$statusCode     = $response->getStatusCode(); // returns the http status code
$rawResponse    = (string) $response->getBody(); // body, you will need to cast to string or echo to get the body data.
$responseArray  = json_decode($rawResponse, true); // return json decode array.
```
**Note:** the `->send()` response is a Guzzle response object.
  

**Chained request:**
```php
$depClient       = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za');
$responseObject  = json_decode((string) $depClient->createRequest('POST' '/subscription')->send()->getBody());
```

The result will be a Guzzle response object
```php
$response = $depClient->createRequest('POST' '/subscription')->send();
echo $response->getBody(); // echo converts object to string(will be json data).
$responseArray = json_decode((string) $response->getBody(), true); // return json decode array.
```

See the guzzle response object documentation for more details on whats available:
https://guzzle3.readthedocs.io/http-client/response.html 

## Tests
In order to run the unit tests you will need to install PHPUnit and replace the `<SET>` strings in the `tests/ClientTest.php` file with your DEP credentials.
```bash
/app/dep.api.auth.php# composer install
/app/dep.api.auth.php# ./vendor/bin/phpunit --version
                        PHPUnit 8.0.4 by Sebastian Bergmann and contributors.

/app/dep.api.auth.php# ./vendor/bin/phpunit --bootstrap vendor/autoload.php --testdox tests
```
_The example shown above assumes that composer is on your `$PATH.`
