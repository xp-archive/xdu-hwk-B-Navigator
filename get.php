<?php

function get($name, $default = null) {
    if (isset($_GET[$name])) {
        return $_GET[$name];
    } else {
        return $default;
    }
}

$strategy = get('strategy', 'debug');

$name2id = require "{$strategy}/name2id.php";
$id2name = require "{$strategy}/id2name.php";
$matrix = require "{$strategy}/matrix.php";
$distance = require "{$strategy}/distance.php";
$path = require "{$strategy}/path.php";
$name2sn = require "name2sn.php";

$a_name = get('start', '入口1');
$b_name = get('end', '443');

function add_id_to_array(&$arr, $name) {
    global $name2id;
    if (isset($name2id[$name])) {
        array_push($arr, $name2id[$name]);
    }
}

function name2id($name) {
    $ret = [];
    if (is_numeric($name)) {
        add_id_to_array($ret, "{$name}西");
        add_id_to_array($ret, "{$name}东");
    } else {
        add_id_to_array($ret, $name);
    }
    return $ret;
}

$a_ids = name2id($a_name);
$b_ids = name2id($b_name);

//var_export($b_ids); die();

$start_id = -1;
$end_id = -1;
$cost = PHP_INT_MAX;

foreach ($a_ids as $a_id) {
    foreach ($b_ids as $b_id) {
        if ($distance[$a_id][$b_id] < $cost) {
            $start_id = $a_id;
            $end_id = $b_id;
            $cost = $distance[$a_id][$b_id];
        }
    }
}

if ($start_id == -1 || $end_id == -1) {
    die(json_encode(['code' => 1]));
}

$start_name = $id2name[$start_id];
$end_name = $id2name[$end_id];

$ret = [];
array_push($ret, [$start_name, $name2sn[$start_name]]);
if ($cost !== PHP_INT_MAX) {
    $o_id = $start_id;
    $c_id = $start_id;
    while (true) {
        $c_id = $path[$o_id][$end_id];
        if ($o_id === $end_id) break;

        $c_name = $id2name[$c_id];
        if (in_array(mb_substr($c_name, 0, 2), ['入口', '楼梯', '电梯', '南北', '北南'])) {
            array_push($ret, [$c_name, $name2sn[$c_name]]);
        }
        $o_id = $c_id;
    }
}
array_push($ret, [$end_name, $name2sn[$end_name]]);

//var_export($ret); die();

$filtered_ret = [];
array_push($filtered_ret, reset($ret));
for ($i = 1; $i < count($ret) - 1; ++$i) {
    if ($ret[$i-1][1] === $ret[$i][1] && $ret[$i][1] === $ret[$i+1][1]) continue;
    array_push($filtered_ret, $ret[$i]);
}
array_push($filtered_ret, end($ret));

echo json_encode([
    'code' => 0,
    'cost' => $cost,
    'path' => $filtered_ret,
]);