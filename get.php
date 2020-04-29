<?php

include "./db_stuff.php";

$limit = 10;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$offset = (max($page, 1) - 1) * $limit;

$type = isset($_GET["type"]) ? $_GET["type"] : "resource";

$resourceFilter = isset($_GET["resource_filter"]) ? $_GET["resource_filter"] : "";
$resourceFilterArr = array_filter(explode(",",$resourceFilter));
$authorFilter = isset($_GET["author_filter"]) ? $_GET["author_filter"] : "";
$authorFilterArr = array_filter(explode(",", $authorFilter));

header("X-Page: $page");
header("X-Limit: $limit");
header("X-Offset: $offset");

header("X-Resource-Filter: " . json_encode($resourceFilterArr));
header("X-Author-Filter: " . json_encode($authorFilterArr));

$series = [];

function sortByDate(&$series)
{
    foreach ($series as $k => $v) {
        usort($series[$k]["data"], function ($a, $b) {
            if ($a[0] < $b[0]) {
                return -1;
            } else if ($a[0] > $b[0]) {
                return 1;
            }
            return 0;
        });
    }
}

if ($type === "resource") {
    header("X-Type: resource");

    $filterQuery = "";
    if (strlen($resourceFilter) > 0) {
        $filterQuery .= " where id in (" . implode(",", array_fill(0, count($resourceFilterArr), '?')) . ") ";
    }
    if (strlen($authorFilter) > 0) {
        if (strlen($resourceFilter) > 0) {
            $filterQuery.=" and ";
        }else{
            $filterQuery .= " where ";
        }
        $filterQuery .= " author in (" . implode(",", array_fill(0, count($authorFilterArr), '?')) . ") ";
    }
    // this query is a bit of a mess but works
    // the inner query gets the unique resource ideas, descending by downloads
    // the outer gets all stats data based on the unique ids
    $query = "select id,name,author,date,downloads from spiget_stats join (select distinct id as did from spiget_stats $filterQuery order by downloads desc limit ?,?) d ON spiget_stats.id IN (d.did) order by downloads desc, date asc";
    $stmt = $conn->prepare($query);

    $params = array();
    foreach ($resourceFilterArr as $r) {
        $params[]= (int)$r;
    }
    foreach ($authorFilterArr as $a) {
        $params[]= (int)$a;
    }
    $params[]=$offset;
    $params[]=$limit;
    $args = array_merge(array(str_repeat('i', count($params))), $params);
    call_user_func_array(array($stmt, "bind_param"), $args);

//    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $stmt->bind_result($id, $name, $author, $date, $downloads);
    while ($row = $stmt->fetch()) {
        if (!isset($series["$id"])) {
            $series["$id"] = array(
                "id" => $id,
                "name" => $name,
                "author" => $author,
                "data" => array()
            );
        } else {
            $series["$id"]["name"] = $name;
        }
        $series["$id"]["data"][] = array(
            strtotime($date) * 1000, (int)$downloads
        );
    }
    $stmt->close();
    unset($stmt);
    $conn->close();

    sortByDate($series);
}

if ($type === "author_total" || $type === "author_average") {
    $filterQuery = "";
    if (strlen($authorFilter) > 0) {
        $filterQuery .= " where author in (" . implode(",", array_fill(0, count($authorFilterArr), '?')) . ") ";
    }

    // not particularly pretty either
    $stmt = $conn->prepare("select author,date,sum(downloads) as totalDownloads from spiget_stats join (select author as aid, sum(downloads) as dd from spiget_stats $filterQuery group by aid order by dd desc limit ?,?) d ON spiget_stats.author IN (d.aid)  group by author,date order by totalDownloads desc, date asc");

    $params = array();
    foreach ($authorFilterArr as $a) {
        $params[]= (int)$a;
    }
    $params[]=$offset;
    $params[]=$limit;
    $args = array_merge(array(str_repeat('i', count($params))), $params);
    call_user_func_array(array($stmt, "bind_param"), $args);

//    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $stmt->bind_result($author, $date, $downloads);
    $authors = array();
    while ($row = $stmt->fetch()) {
        if (!isset($series["$author"])) {
            $series["$author"] = array(
                "name" => "$author",
                "author" => $author,
                "data" => array()
            );
            $authors[]=$author;
        }
        $series["$author"]["data"][] = array(
            strtotime($date) * 1000, (int)$downloads
        );
    }
    $stmt->close();
    unset($stmt);


    if ($type === "author_average") {
        // https://stackoverflow.com/a/19666312/6257838
        $placeholders = array_fill(0, count($authors), '?');
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT id) as dcnt, author as authr from spiget_stats where author in (".implode(",",$placeholders).") group by authr order by dcnt desc");

        $params = array();
        foreach ($authors as &$a) {
            $params[]=&$a;
        }
        $args = array_merge(array(str_repeat('i', count($params))), $params);
        call_user_func_array(array($stmt, "bind_param"), $args);

        $stmt->execute();
        $stmt->bind_result($count,$author);
        while ($row = $stmt->fetch()) {
            if (isset($series["$author"])) {
                foreach ($series["$author"]["data"] as &$d){
                    $d[1] = round($d[1]/$count);
                }
            }
        }
        $stmt->close();
        unset($stmt);
    }

    $conn->close();

    sortByDate($series);
}



header("Content-Type: application/json");
echo json_encode(array_values($series));

