<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase, MTNDEP\DEPClient;

/*
 * To run test go to root folder and run: ./vendor/bin/phpunit --bootstrap vendor/autoload.php --testdox tests
 */
final class ClientTest extends TestCase
{
    private $accessKey              = '<SET>'; // Replace with staging credentials
    private $accessSecret           = '<SET>'; // Replace with staging credentials
    private $apiKey                 = '<SET>'; // Replace with staging credentials
    private $baseUrl                = 'https://staging.api.dep.mtn.co.za';
    private $apiVersion             = '1.4';
    private $acceptHeader           = 'application/vnd.sdp+json';
    private $contentTypeHeader      = 'application/json';
    private $msisdn                 = '27833334444';
    private $svc_id                 = 1;
    private $ext_ref                = 'UNIT_TESTING';
    private $channel                = 'WAP';
    private $doi_chanel             = 'WAP_REDIRECT';

    public function testCanCreateDepClientObjectWithBaseValidParameters(): void
    {
        $this->assertInstanceOf(
            DEPClient::class,
            new DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl)
        );
    }

    public function testCanCreateDepClientObjectWithAllValidParameters(): void
    {
        $this->assertInstanceOf(
            DEPClient::class,
            new DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl, $this->apiVersion,
                $this->acceptHeader, $this->contentTypeHeader)
        );
    }

    public function testAreRequestHeadersCorrectWithOptionalRequestParameters(): void
    {
        $path = '/subscription';
        $dt = date('Ymd\THis\Z', time());

        $depClient = new DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl, $this->apiVersion, $this->acceptHeader, $this->contentTypeHeader);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $depClient->createRequest(DEPClient::POST, $path);
        $request = $depClient->getRequest();

        $this->assertEquals(DEPClient::$acceptHeader.';version='.DEPClient::$apiVersion , $request->getHeader('Accept')[0]);
        $this->assertEquals(DEPClient::$contentTypeHeader, $request->getHeader('Content-Type')[0]);
        $this->assertEquals(substr($dt, -3), substr($request->getHeader('X-Amz-Date')[0], -3));
        $this->assertEquals($this->apiKey, $request->getHeader('x-api-key')[0]);
        $this->assertEquals(default_user_agent(), $request->getHeader('User-Agent')[0]);
    }

    public function testAreQueryParametersPathAndBodyAddedToRequest(): void
    {
        $path = '/partner/1';
        $queryParameters = ['test' => 'extra dumpy param', 'expand' => 'subscription(status=Active,page=1)'];
        $body = ['test' => 1];

        $depClient = new DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl, $this->apiVersion, $this->acceptHeader, $this->contentTypeHeader);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $depClient->createRequest(DEPClient::GET, $path, $queryParameters, $body);
        $request = $depClient->getRequest();

        $this->assertEquals($path, $request->getUri()->getPath());
        $this->assertNotEquals(http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986), $request->getUri()->getQuery());
        ksort($queryParameters);
        $this->assertEquals(http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986), $request->getUri()->getQuery());
        $this->assertEquals(json_encode($body), $request->getBody()->read(1024));
    }

    public function testCanGetRequestObject(): void
    {
        $path = '/partner/1';
        $queryParameters = ['expand' => 'subscription(status=Active,page=1)', 'test' => 'extra dumpy param'];
        $body = ['test' => 1];

        $request = (new DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl, $this->apiVersion, $this->acceptHeader, $this->contentTypeHeader))
            ->createRequest(DEPClient::GET, $path, $queryParameters, $body)
            ->getRequest();

        $this->assertInstanceOf(GuzzleHttp\Psr7\Request::class, $request);
    }

    public function testCanGetAwsCredentialsObject(): void
    {
        $path = '/partner/1';
        $queryParameters = ['expand' => 'subscription(status=Active,page=1)', 'test' => 'extra dumpy param'];
        $body = ['test' => 1];

        $awsCredentials = (new DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl, $this->apiVersion, $this->acceptHeader, $this->contentTypeHeader))
            ->createRequest(DEPClient::GET, $path, $queryParameters, $body)
            ->getAwsCredentials();

        $this->assertInstanceOf(Aws\Credentials\Credentials::class, $awsCredentials);
        $this->assertSame($this->accessKey, $awsCredentials->getAccessKeyId());
        $this->assertSame($this->accessSecret, $awsCredentials->getSecretKey());
        $this->assertSame(['key' => $this->accessKey, 'secret' => $this->accessSecret, 'token' => NULL, 'expires' => NULL], $awsCredentials->toArray());
        $awsCredentialsJson = $awsCredentials->serialize();
        $this->assertEquals(json_encode(['key' => $this->accessKey, 'secret' => $this->accessSecret, 'token' => NULL, 'expires' => NULL]), $awsCredentialsJson);

        $json = '{"key":"key","secret":"topsecret","token":null,"expires":null}';
        $this->assertNull($awsCredentials->unserialize($json));
        $this->assertSame('key', $awsCredentials->getAccessKeyId());
        $this->assertSame('topsecret', $awsCredentials->getSecretKey());
        $this->assertEquals($json, $awsCredentialsJson = $awsCredentials->serialize());
    }

    public function testCanGetAwsSignatureObject(): void
    {
        $date           = date('Ymd\THis\Z', strtotime('2018-10-22 12:59:51.428'));
        $path       = '/partner/1';
        $queryParameters= ['expand' => 'subscription(status=Active,page=1)', 'test' => 'extra dumpy param'];
        $body           = ['test' => 1];

        $request = (new DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl, $this->apiVersion, $this->acceptHeader, $this->contentTypeHeader))
            ->createRequest(DEPClient::GET, $path, $queryParameters, $body);
        $awsCredentials = $request->getAwsCredentials();
        $awsSignature = $request->getAwsSignature();

        $this->assertInstanceOf(Aws\Signature\SignatureV4::class, $awsSignature);
        $this->assertInstanceOf(GuzzleHttp\Psr7\Request::class, $awsSignature->signRequest($request->getRequest(), $awsCredentials));
        $this->assertInstanceOf(GuzzleHttp\Psr7\Request::class, $awsSignature->presign($request->getRequest(), $awsCredentials, $date));
    }

    public function testIsAuthorisationStringCorrectForDelete(): void
    {

        $path = '/subscription/1';
        $timestamp = gmdate('Ymd\THis\Z');
        $depClient = new MTNDEP\DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $depClient->createRequest(DEPClient::DELETE, $path, null, null);
        $request = $depClient->getRequest();

        // Recreated auth to check against
        $step1  = date('Ymd', strtotime($request->getHeader('X-Amz-Date')[0])) . '/eu-west-1/execute-api/aws4_request';

        $step21 = hash_hmac('sha256', date('Ymd', strtotime($request->getHeader('X-Amz-Date')[0])), utf8_encode('AWS4' . $this->accessSecret), true);
        $step22 = hash_hmac('sha256', DEPClient::$region, $step21, true);
        $step23 = hash_hmac('sha256', DEPClient::$service, $step22, true);
        $step24 = hash_hmac('sha256', DEPClient::$signatureType, $step23, true);

        $step31 = DEPClient::DELETE;
        $step31 .= "\n$path";
        $step31 .= "\n";
        $step31 .= "\nhost:" . trim($request->getHeader('Host')[0]);
        $step31 .= "\nx-amz-date:" . trim($request->getHeader('X-Amz-Date')[0]);
        $step31 .= "\nx-api-key:" . trim($request->getHeader('x-api-key')[0]);
        $step31 .= "\n";
        $step31 .= "\nhost;x-amz-date;x-api-key";
        $step31 .= "\n" . hash('sha256', '');
        $step32 = "AWS4-HMAC-SHA256\n" . $request->getHeader('X-Amz-Date')[0] . "\n$step1\n".hash('sha256', $step31);

        $step4  = hash_hmac('sha256', $step32, $step24);
        $authorizationHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/$step1, SignedHeaders=host;x-amz-date;x-api-key, Signature={$step4}"; // Step 5

        $this->assertEquals(DEPClient::$acceptHeader.';version='.DEPClient::$apiVersion , $request->getHeader('Accept')[0]);
        $this->assertEquals(DEPClient::$contentTypeHeader, $request->getHeader('Content-Type')[0]);
        $this->assertEquals($timestamp, $request->getHeader('X-Amz-Date')[0]);
        $this->assertEquals($this->apiKey, $request->getHeader('x-api-key')[0]);
        $this->assertEquals($authorizationHeader, $request->getHeader('Authorization')[0]);
    }

    // test get
    public function testCanGetPartnerDetails(): void
    {

        $path = '/partner/1';
        $depClient = new MTNDEP\DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $response = $depClient->createRequest(DEPClient::GET, $path, null, null)->send();
        $responseArray = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $responseArray['partner_id']);
    }
    public function testCanGetPartnerSubscriptions(): void
    {
        $path = '/partner/1';
        $depClient = new MTNDEP\DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $response = $depClient->createRequest(DEPClient::GET, $path, ['expand' => 'subscription(page=1)'], null)->send();
        $responseArray = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($responseArray);
        $this->assertArrayHasKey('subscription', $responseArray);
        $this->assertArrayHasKey('subscription_id', array_pop($responseArray['subscription']));
    }
    public function testCanGetServiceDetails(): void
    {
        $path = '/service/1';
        $depClient = new MTNDEP\DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $response = $depClient->createRequest(DEPClient::GET, $path, null, null)->send();
        $responseArray = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $responseArray['status_id']);
        $this->assertEquals(1, $responseArray['service_id']);
    }
    public function testCanGetServiceSubscriptions(): void
    {
        $path = '/service/1';
        $depClient = new MTNDEP\DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $response = $depClient->createRequest(DEPClient::GET, $path, ['expand' => 'subscription(page=1)'], null)->send();
        $responseArray = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($responseArray);
        $this->assertArrayHasKey('service_id', $responseArray);
        $this->assertArrayHasKey('service_name', $responseArray);
        $this->assertArrayHasKey('billing_cycle', $responseArray);
        $this->assertArrayHasKey('billing_rate', $responseArray);
        $this->assertArrayHasKey('status_id', $responseArray);
        $this->assertArrayHasKey('status_name', $responseArray);
        $this->assertArrayHasKey('subscription', $responseArray);
        $this->assertArrayHasKey('subscription_id', array_pop($responseArray['subscription']));
    }

    // test post
    public function testCanPostNewSubscription(): void
    {
        $path = '/subscription';
        $requestBody = [
            'msisdn' => $this->msisdn,
            'svc_id' => $this->svc_id,
            'ext_ref' => $this->ext_ref . '-' . date('YmdHis') . rand(0,100),
            'channel' => $this->channel,
            'doi_channel' => $this->doi_chanel
        ];

        $depClient = new MTNDEP\DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        $response = $depClient->createRequest(DEPClient::POST, $path, null, $requestBody)->send();
        $responseArray = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('subscription_id', $responseArray);
        $this->assertEquals('PENDING', $responseArray['status_name']);
        $this->assertEquals(1, $responseArray['svc_id']);
    }

    // test delete
    public function testDeleteSubscriptionException(): void
    {
        $path = '/subscription/1';
        $depClient = new MTNDEP\DEPClient($this->accessKey, $this->accessSecret, $this->apiKey, $this->baseUrl);
        $this->assertInstanceOf(DEPClient::class, $depClient);
        try {
            $depClient->createRequest(DEPClient::DELETE, $path, null, null)->send();
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseArray = json_decode((string) $response->getBody(), true);

            $this->assertEquals(464, $response->getStatusCode());
            $this->assertEquals(464, $responseArray['error_code']);
            $this->assertArrayHasKey('error_message', $responseArray);
        }
    }

}