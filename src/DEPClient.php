<?php

namespace MTNDEP;

use Aws\Credentials\Credentials;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Psr7\Request;

/**
 * Class Client
 *
 * An MTN DEP API Client implementation supporting the Amazon AWS authentication mechanism, used to make REST calls
 * to the DEP platform endpoint(s).
 *
 * @package MTNDEP
 * @author  Digital Material Dev
 */
class DEPClient
{
    const VERSION   = '1.0.0';
    const GET       = 'GET';
    const POST      = 'POST';
    const PATCH     = 'PATCH';
    const DELETE    = 'DELETE';

    public static $timestampFormat      = 'Ymd\THis\Z';
    public static $apiVersion           = '1.4';
    public static $acceptHeader         = 'application/vnd.sdp+json';
    public static $contentTypeHeader    = 'application/json';
    public static $region               = 'eu-west-1';
    public static $service              = 'execute-api';
    public static $signatureType        = 'aws4_request';

    private $accessKey;
    private $accessSecret;
    private $apiKey;
    private $httpMethod;
    private $baseUrl;
    private $path;
    private $requestUrl;
    private $requestHeaders;
    private $queryParameters;
    private $requestBody;

    private $request;
    private $awsCredentials;
    private $awsSignature;

    /**
     * DEPClient constructor.
     * Example: $depClient = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za')
     *
     * @param string $accessKey
     * @param string $accessSecret
     * @param string $apiKey
     * @param string $baseUrl
     * @param string|null $apiVersion
     * @param string|null $acceptHeader
     * @param string|null $contentTypeHeader
     *
     * @returns DEPClient
     */
    public function __construct(string $accessKey, string $accessSecret, string $apiKey, string $baseUrl, string $apiVersion = null,
        string $acceptHeader = null, string $contentTypeHeader = null)
    {
        $this->accessKey    = $accessKey;
        $this->accessSecret = $accessSecret;
        $this->baseUrl      = $this->requestUrl = $baseUrl;
        $this->apiKey       = $apiKey;

        if (!is_null($apiVersion))          self::$apiVersion           = $apiVersion;
        if (!is_null($acceptHeader))        self::$acceptHeader         = $acceptHeader;
        if (!is_null($contentTypeHeader))   self::$contentTypeHeader    = $contentTypeHeader;
    }

    /**
     * This method creates the GuzzleHttp\Psr7\Request object and signs it for the DEP API.
     *
     * # Usage
     * Can be used independently to only get the authorized request:
     * ```php
     *  $depClient = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za');
     *  $depClient->createRequest('POST' '/subscription');
     *  $authorised = $depAuth->getRequest();
     * ```
     *
     * Can be used in a two step process to authorize request before sending it
     * ```php
     *  $depClient = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za');
     *  $depClient->createRequest('POST' '/subscription');
     *  $response = $depClient->send();
     * ```
     *
     * Or can be used on a chained call to authorize and send request
     * ```php
     *  $depClient = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za');
     *  $response = $depClient->createRequest('POST' '/subscription')->send();
     * ```
     *
     * @param string $httpMethod
     * @param string|null $path
     * @param array|null $requestParameters
     * @param array|null $requestBody
     * @return DEPClient
     * @throws DEPClientException
     */
    public function createRequest(string $httpMethod, string $path = null, array $requestParameters = null, array $requestBody = null)
    {
        $this->addHttpMethod($httpMethod);
        $this->addEndpointToBaseUrl($path);
        $this->addRequestParametersToUrl($requestParameters);
        $this->addRequestBody($requestBody);

        $this->requestHeaders = [
            'Accept'        => self::$acceptHeader . ';version=' . self::$apiVersion,
            'Content-Type'  => self::$contentTypeHeader,
            'User-Agent'    => default_user_agent(),
            'x-api-key'     => $this->apiKey
        ];

        $this->request          = new Request($this->httpMethod, $this->requestUrl, $this->requestHeaders, $this->requestBody);
        $this->awsCredentials   = new Credentials($this->accessKey, $this->accessSecret);
        $this->awsSignature     = new SignatureV4(self::$service, self::$region);
        $this->request = $this->awsSignature->signRequest($this->request, $this->awsCredentials);
        return $this;
    }

    /**
     * Send a request to the MTN DEP API.
     * Returns the Guzzle response object: https://guzzle3.readthedocs.io/http-client/response.html
     *
     * # Usage
     * Can be used in multi step process
     * ```php
     * $depClient = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za');
     * $depClient->createRequest('POST' '/subscription');
     * $response = $depClient->send();
     * ```
     *
     * OR you can chain as follows:
     * ```php
     * $depClient = new MTNDEP\DEPClient($accessKey, $accessSecret, $apiKey, 'https://staging.api.dep.mtn.co.za');
     * $response = $depClient->createRequest('POST' '/subscription')->$depClient->send();
     * ```
     *
     * The result will be a Guzzle response object
     * ```php
     * $response = $depClient->createRequest('POST' '/subscription')->$depClient->send();
     * echo $response->getBody(); // raw body (will be json data)
     * $responseArray = $response->json(); // return json decode array.
     * ```
     *
     * @return Guzzle\Http\Message\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws DEPClientException
     */
    public function send()
    {
        if (empty($this->request)) {
            throw new DEPClientException('No authorized request found to send. You must authorize your request first.');
        }

        $httpClient = new \GuzzleHttp\Client();
        return $httpClient->send($this->request);
    }

    /**
     * Returns the request object.
     * It will include the required authorization details if the createRequest method has been called.
     *
     * Note: this is a GuzzleHttp\Psr7\Request object:
     * http://docs.guzzlephp.org/en/stable/psr7.html#requests
     *
     * @return GuzzleHttp\Psr7\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the Aws\Credentials\Credentials object
     * @return Aws\Credentials\Credentials
     */
    public function getAwsCredentials()
    {
        return $this->awsCredentials;
    }

    /**
     * Returns the Aws\Signature\SignatureV4 object
     * @return Aws\Signature\SignatureV4
     */
    public function getAwsSignature()
    {
        return $this->awsSignature;
    }

    /*
     * PRIVATE
     */
    /**
     * Checks and sets the http method for the request
     * @param string $httpMethod
     * @throws DEPClientException
     */
    private function addHttpMethod(string $httpMethod)
    {
        if (!in_array(strtoupper($httpMethod), [self::GET, self::POST, self::PATCH, self::DELETE])) {
            throw new DEPClientException('Invalid http method');
        }
        $this->httpMethod = strtoupper($httpMethod);
    }

    /**
     * Sets the endpoint for the api request.
     * Will add to existing path if the url already has path elements.
     * Will set the endpoint to root "/" if non is given.
     *
     * @param string|null $path
     * @return string
     */
    private function addEndpointToBaseUrl(string $path = null){
        $this->endpoint = (!empty($path)) ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : DIRECTORY_SEPARATOR;

        $urlParts = parse_url($this->requestUrl);
        if (empty($urlParts['path'])) {
            $urlParts['path'] = rtrim($this->endpoint, '?');
        } else {
            $urlParts['path'] = rtrim($urlParts['path'], '/') . rtrim($this->endpoint, '?');
        }
        $this->requestUrl = http_build_url($urlParts);
        return $this->requestUrl;
    }

    /**
     * Merges existing query parameters on url with supplied array of query parameters.
     * Sorts the complete list and converts from array to query string.
     *
     * @param array|null $queryParameters
     * @return string
     */
    private function addRequestParametersToUrl(array $queryParameters = null)
    {
        if (is_null($queryParameters)) $queryParameters = [];

        $urlParts = parse_url($this->requestUrl);
        $queryParameters = (empty($urlParts['query'])) ? $queryParameters : array_merge(parse_str($urlParts['query']), $queryParameters);
        if (!empty($queryParameters)) {
            ksort($queryParameters);
            $this->queryParameters = $queryParameters;
            $urlParts['query'] = http_build_query($this->queryParameters, null, '&', PHP_QUERY_RFC3986);
            $this->requestUrl = http_build_url($urlParts);
        }
        return $this->requestUrl;
    }

    /**
     * Sets the request body
     *
     * @param array|null $requestBody
     * @return string
     */
    private function addRequestBody(array $requestBody = null)
    {
        if (!empty($requestBody)) {
            $this->requestBody = json_encode($requestBody);
        }
        return $this->requestBody;
    }
}