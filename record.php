#!/usr/bin/hhvm
<?php

error_reporting(E_ALL ^ E_NOTICE);

if(!isset($argv['3'])) {
        die('Not enough arguments\n');
}

date_default_timezone_set('America/Denver');

$account=$argv['1'];
$action=$argv['2'];

if($action != 'toll' && $action != 'total' && $action != 'detail') {
        die('Invalid action: '.$action.'\n');
}

if($action == 'toll') {
        $startepoch = $argv['3'];
        $type = $argv['4'];
        $callsource = $argv['5'];
        $calldest = $argv['6'];
        $duration = $argv['7'];
        $trunk = $argv['8'];
        $purpose = $argv['9'];
        file_put_contents('/var/tolls/'.$account.'.csv',$startepoch.','.$type.','.$callsource.','.$calldest.','.$duration.','.$trunk.','.$purpose."\n", FILE_APPEND | LOCK_EX);
}

if($action == 'total' || $action == 'detail') {
        $pertype = $argv['3'];
        if($pertype == 'month') {
                $month = $argv['4'];
                if(isset($argv['5'])) { $year = $argv['5']; }
                else { $year = date('Y'); }
                $startper = strtotime(date('Y-m-d', mktime(0, 0, 0, $month, 1, $year)).' 00:00:00');
                $endper = strtotime(date('Y-m-t', mktime(0, 0, 0, $month, 1, $year)).' 23:59:59');
        } elseif ($pertype == 'period') {
                $startper = strtotime($argv['4'].' 00:00:00');
                $endper = strtotime($argv['5'].' 23:59:59');
        } else {
                die('Unknown period type.\n');
        }
}

if($action == 'total') {
        echo "Starting Period: ".date("j F Y H:i:s",$startper)."\n";
        echo "Ending Period: ".date("j F Y H:i:s",$endper)."\n\n";
        $types = array();
        $source = fopen('/var/tolls/'.$account.'.csv', 'r') or die("Problem opening record file\n");
        while (($data = fgetcsv($source, 1000, ",")) !== FALSE)
        {
                $startepoch = $data[0];
                if ($startepoch >= $startper && $startepoch <= $endper) {
                        $duration = $data[4];
                        $minutes = ceil($duration / 60);
                        $type = $data[1];
                        $types[$type] += $minutes;
                        $typess[$type] += $duration;
                        $typeno[$type]++;
                }

        }
        fclose($source);
        foreach ($types as $type => $minutes) {
                echo $type.': '.$minutes." minutes\n";
        }
        echo "\nPlan Type Minutes: ".($types["inbound"] + $types["us48"])."\n";
        echo "\nNon Ceiling Values:\n";
        foreach ($typess as $type => $seconds) {
                echo $type.': '.$seconds." seconds\n";
        }
        echo "\nTotal number of calls:\n";
        foreach ($typeno as $type => $amount) {
                echo $type.': '.$amount." calls\n";
        }
}
?>
