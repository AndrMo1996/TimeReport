<?php

namespace Jira;

require_once '/home/a.moruzhko/Documents/scripts/Salary/TimeReport/vendor/autoload.php';

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Json\Json;

class Connector
{
    public const URL = 'https://magneticone.atlassian.net/rest/api/3';

    private $client;

    public function __construct()
    {
        $this->setClient(new Client());
    }

    /**
     * @param mixed $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    protected function prepareRequest($path, array $params, $method): void
    {
        $this->getClient()->setMethod($method);
        $this->getClient()->setUri(rtrim(static::URL, '/') . '/' . ltrim($path, '/'));
        $this->getClient()->setHeaders(
            [
                'Authorization' => 'Basic YS5tb3J1emhrb0BtYWduZXRpY29uZS5jb206MVBQbWJzQ0JKOXlpcTZJT0pSUlk4MUND',
                'content-type'  => 'application/json',
            ]
        );
        if(Request::METHOD_POST === $method){
            $this->getClient()->setRawBody(json_encode($params));
        }

    }

    protected function readResponse(Response $response)
    {
        $data = [];
        if ($response->getStatusCode() === 414) {
            new \Exception($response->getBody());
        }

        if (false === empty($response->getBody())) {
            $data = Json::decode($response->getBody(), Json::TYPE_ARRAY);
        }

        if (true === $response->isSuccess()) {
            if (Request::METHOD_DELETE === $this->getClient()->getMethod()) {
                $isDeletedOne = 204 === $response->getStatusCode();
                $isDeletedTwo = true === isset($data['deleted']) && true === $data['deleted'];

                if (true === $isDeletedOne || true === $isDeletedTwo) {
                    return true;
                }
            }

            $emptyResponseMethods = [
                Request::METHOD_POST,
                Request::METHOD_PUT,
            ];

            if (null === $data && true === \in_array($this->getClient()->getMethod(), $emptyResponseMethods, true)) {
                return true;
            }

            return true === \is_array($data) ? $data : [];
        }

        new \Exception($response->getBody());
    }

    public function request($path, array $params, $method, array $headers = [], $rawBody = null)
    {

        $this->getClient()->resetParameters();

        if (false === empty($headers)) {
            $this->getClient()->setHeaders($headers);
        }

        if (false === empty($rawBody)) {
            $this->getClient()->setRawBody($rawBody);
        }

        $this->prepareRequest($path, $params, $method);

        $response = $this->getClient()->send();

        return $this->readResponse($response);
    }
}