<?php

$raw = require 'data.php';

$name2id = [];
$id2name = [];

$matrix = [];
$matrix_count = 0;

$stairs = [];

$distance = [];
$path = [];

function expand_matrix() {
    global $matrix, $matrix_count;

    ++$matrix_count;

    foreach ($matrix as &$row) {
        array_push($row, PHP_INT_MAX);
    }
    unset($row);

    $row = array_pad([], $matrix_count, PHP_INT_MAX);
    $row[$matrix_count - 1] = 0;
    array_push($matrix, $row);

    return $matrix_count - 1;
}

function push_name2id_atomic(array $names, $level) {
    if (count($names) === 0) throw new Exception("未输入有效的name");

    global $name2id, $id2name;
    global $stairs;

    $ids = [];
    foreach ($names as $name) {
        if (strpos($name, '楼梯') === 0 || strpos($name, '电梯') === 0) {
            if (isset($stairs[$name])) {
                if (!in_array($level, $stairs[$name])) {
                    array_push($stairs[$name], $level);
                }
            } else {
                $stairs[$name] = [$level];
            }

            $name .= "-{$level}";
        }

        if (!array_key_exists($name, $name2id)) {
            $id = expand_matrix();
            $name2id[$name] = $id;
            $id2name[$id] = $name;
        } else {
            $id = $name2id[$name];
        }
        array_push($ids, $id);
    }

    global $matrix;
    foreach ($ids as $a) {
        foreach ($ids as $b) {
            if ($a == $b) continue;
            $matrix[$a][$b] = 0;
            $matrix[$b][$a] = 0;
        }
    }

    return $ids[0];
}

function array_user_merge(array $a, array $b) {
    foreach ($b as $b_item) {
        array_push($a, $b_item);
    }
    return $a;
}

function push_name2id($level, $name) {
    global $name2id;
    if (is_array($name)) {
        return push_name2id_atomic(array_user_merge([$name[0]], $name[1]), $level);
    } else if (array_key_exists($name, $name2id)) {
        return $name2id[$name];
    } else {
        return push_name2id_atomic([$name], $level);
    }
}

function set_cost($a_id, $b_id, $cost) {
    global $matrix;
    $matrix[$a_id][$b_id] = $cost;
    $matrix[$b_id][$a_id] = $cost;
}

function add_record($level, array $record) {
    $a_id = push_name2id($level, $record[0]);
    $b_id = push_name2id($level, $record[1]);
    set_cost($a_id, $b_id, $record[2]);
}

function connect_stairs() {
    global $stairs, $name2id;

    foreach ($stairs as $stair_name => $stair_levels) {
        if (strpos($stair_name, '电梯') === 0) {
            $cost = 0;
        } else {
            $cost = ctype_alpha(mb_substr($stair_name, 3, 1)) ? 70 : 35;
        }

        reset($stair_levels);
        list(, $a_level) = each($stair_levels);
        while ((list(, $b_level) = each($stair_levels)) !== false) {
            $a_name = "{$stair_name}-{$a_level}";
            $b_name = "{$stair_name}-{$b_level}";
            $a_id = $name2id[$a_name];
            $b_id = $name2id[$b_name];
            set_cost($a_id, $b_id, $cost);

            $a_level = $b_level;
        }
    }
}

function make_matrix() {
    global $raw;

    foreach ($raw as $level => $records) {
        foreach ($records as $record) {
            add_record($level, $record);
        }
    }

    connect_stairs();
}

function floyd() {
    global $matrix, $distance, $path;
    $distance = $matrix;

    foreach ($distance as $a => $row) {
        foreach ($row as $b => $cost) {
            if ($cost == PHP_INT_MAX) continue;

            $path[$a][$b] = $b;
        }
    }

    $n = count($distance);
    for ($k = 0; $k < $n; ++$k) {
        for ($i = 0; $i < $n; ++$i) {
            for ($j = 0; $j < $n; ++$j) {
                $distance_ikj = $distance[$i][$k] + $distance[$k][$j];
                if ($distance_ikj < $distance[$i][$j]) {
                    $path[$i][$j] = $path[$i][$k];
                    $distance[$i][$j] = $distance_ikj;
                }
            }
        }
    }
}

function save_array($filename, array &$array) {
    file_put_contents($filename, "<?php\nreturn " . var_export($array, true) . "\n?>");
}

function generate($strategy) {
    global $matrix, $distance, $path, $name2id, $id2name;
    if (!is_dir($strategy)) mkdir($strategy);
    save_array("{$strategy}/matrix.php", $matrix);
    save_array("{$strategy}/distance.php", $distance);
    save_array("{$strategy}/path.php", $path);
    save_array("{$strategy}/name2id.php", $name2id);
    save_array("{$strategy}/id2name.php", $id2name);
}

make_matrix();
floyd();
generate($argc > 1 ? $argv[1] : 'debug');