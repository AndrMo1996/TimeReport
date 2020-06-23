<?php

namespace Jira;

require_once '/home/a.moruzhko/Documents/scripts/Salary/TimeReport/Jira/Connector.php';

use Jira\Connector as Connector;
use Zend\Http\Request;

class Adapter
{
    private $connector;

    const MAX_RESULTS = 100;

    public function __construct()
    {
        $this->setConnector(new Connector());
    }

    public function getTask(string $projectKey): array {

        $response = $this->getConnector()->request(
            'search',
            [
                "jql" => "key = $projectKey",
                "startAt" => 0,
                "maxResults" => static::MAX_RESULTS,
                "fields" => [
                    "id",
                    "assignee",
                    "reporter",
                    "summary",
                    "issuetype",
                    "created"
                ]
            ],
            Request::METHOD_POST
        );
        if(isset($response['issues'][0]['id'])) {
            return
                [
                    'id' => $response['issues'][0]['id'],
                    'created' => $response['issues'][0]['fields']['created']
                ];
        } else{
            new \Exception('Task not found');
        }
    }

    public function getChangelog(array $task): array {
        $startAt = 0;
        $id = $task['id'];
        $created = strtotime($task['created']);
        $changelog = [];
        $total = [];
        do {
            $response = $this->getConnector()->request(
                "issue/$id/changelog?startAt=$startAt",
                [],
                Request::METHOD_GET
            );

            if(isset($response['values'])){
                foreach ($response['values'] as $value){
                    if(isset($value['items'])){
                        foreach ($value['items'] as $item){
                            if(isset($item['fieldId']) && $item['fieldId'] === 'status'){
                                $changelog[] = [
                                    'created'    => $value['created'],
                                    'fromStatus' => $item['fromString'],
                                    'toStatus'   => $item['toString']
                                ];

                                $time = strtotime($value['created']) - $created;
                                $created = strtotime($value['created']);

                                if(isset($total[$item['fromString']])){
                                    $total[$item['fromString']] += $time;
                                } else{
                                    $total[$item['fromString']] = $time;
                                }

                            }
                        }
                    }
                }
            }

            $startAt += static::MAX_RESULTS;
        } while(count($response['values']) === static::MAX_RESULTS);
        return $total;
    }

    /**
     * @return Jira\Connector
     */
    public function getConnector(): Connector
    {
        return $this->connector;
    }

    /**
     * @param Jira\Connector $connector
     */
    public function setConnector(Connector $connector): void
    {
        $this->connector = $connector;
    }
}