<?php

require_once '/home/a.moruzhko/Documents/scripts/Salary/TimeReport/Jira/Adapter.php';

use Jira\Adapter as JiraAdapter;

$adapter = new JiraAdapter();

$task = $adapter->getTask('TRUJAY-8085');
$changelog = $adapter->getChangelog($task);

foreach ($changelog as $key => $value){
    echo $key . " : " . convert_seconds($value)."\n";
}

function convert_seconds($seconds)
{
    $dt1 = new DateTime("@0");
    $dt2 = new DateTime("@$seconds");
    return $dt1->diff($dt2)->format('%a days, %h hours, %i minutes and %s seconds');
}
