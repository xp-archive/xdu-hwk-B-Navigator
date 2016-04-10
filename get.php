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
        add_id_to_array($ret, "{$name}前");
        add_id_to_array($ret, "{$name}后");
    } else {
        add_id_to_array($ret, $name);
    }
    return $ret;
}

$a_ids = name2id($a_name);
$b_ids = name2id($b_name);

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

$start_name = $id2name[$start_id];
$end_name = $id2name[$end_id];

$ret = [];
array_push($ret, [$start_name, $name2sn[$start_name]]);
if ($cost !== PHP_INT_MAX) {
    $o_id = $start_id;
    $c_id = $start_id;
    while ($o_id !== $end_id) {
        $c_id = $path[$o_id][$end_id];

        $c_name = $id2name[$c_id];
        if (strpos($c_name, '岔路') !== 0) {
            array_push($ret, [$c_name, $name2sn[$c_name]]);
        }
        $o_id = $c_id;
    }
}

echo json_encode([
    'cost' => $cost,
    'path' => $ret,
]);