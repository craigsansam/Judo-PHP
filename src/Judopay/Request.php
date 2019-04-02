<?php

namespace Judopay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Message\FutureResponse;
use GuzzleHttp\Message\Request as GuzzleRequest;
use GuzzleHttp\Ring\Future\FutureArray;
use Judopay\Exception\ApiException;

class Request
{
    /** @var Configuration */
    protected $configuration;
    /** @var  Client */
    protected $client;


    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Make a GET request to the specified resource path
     * @param string $resourcePath
     * @throws ApiException
     * @return FutureArray|FutureResponse
     */
    public function get($resourcePath)
    {
        $endpointUrl = $this->configuration->get('endpointUrl');

        return $this->send('GET', "{$endpointUrl}/{$resourcePath}");
    }

    /**
     * Make a POST request to the specified resource path
     * @param string $resourcePath
     * @param array  $data
     * @return FutureArray|FutureResponse
     */
    public function post($resourcePath, $data)
    {
        $endpointUrl = $this->configuration->get('endpointUrl');

        return $this->send(
            'POST',
            "{$endpointUrl}/{$resourcePath}",
            $data
        );
    }

    public function getRequestHeaders()
    {
        return [
            'api-version' => $this->configuration->get('apiVersion'),
            'Accept' => 'application/json; charset=utf-8',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->configuration->get('userAgent'),
        ];
    }

    public function getRequestAuthentication()
    {
        $this->configuration->validate();
        $oauthAccessToken = $this->configuration->get('oauthAccessToken');

        // Do we have an oAuth2 access token?
        if (!empty($oauthAccessToken)) {
            return ['Authorization' => 'Bearer ' . $oauthAccessToken];
        } else {
            // Otherwise, use basic authentication
            $basicAuth =  $this->configuration->get('apiToken'). ":" . $this->configuration->get('apiSecret');
            return ['Authorization' => 'Basic ' . base64_encode($basicAuth)];
        }
    }

    /**
     * Configuration getter
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param $method
     * @param $uri
     * @param null $data
     * @return FutureArray|FutureResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function send($method, $uri, $data = null)
    {
        $request = new \GuzzleHttp\Psr7\Request(
            $method,
            $uri,
            array_merge($this->getRequestHeaders(), $this->getRequestAuthentication()),
            json_encode($data)
        );

        try {
            $guzzleResponse = $this->client->send($request);
        } catch (BadResponseException $e) {
            // Guzzle throws an exception when it encounters a 4xx or 5xx error
            // Rethrow the exception so we can raise our custom exception classes
            throw ApiException::factory($e->getResponse());
        }


        return $guzzleResponse;
    }
}
