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
        $filterQuery .= " and id in (" . implode(",", array_fill(0, count($resourceFilterArr), '?')) . ") ";
    }
    if (strlen($authorFilter) > 0) {
        $filterQuery .= " and author in (" . implode(",", array_fill(0, count($authorFilterArr), '?')) . ") ";
    }
    // this query is a bit of a mess but works
    // the inner query gets the unique resource ideas, descending by downloads
    // the outer gets all stats data based on the unique ids
    $query = "select id,name,author,date,downloads from spiget_stats join (select distinct id as did from spiget_stats where date > NOW() - INTERVAL 2 WEEK $filterQuery order by downloads desc limit ?,?) d ON spiget_stats.id IN (d.did) WHERE date > NOW() - INTERVAL 2 WEEK  order by downloads desc, date asc";
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
    $stmt->bind_result($id, $name, $author, $date, $downloadsIncrease);
    while ($row = $stmt->fetch()) {
        if (!isset($series["$id"])) {
            $series["$id"] = array(
                "id" => $id,
                "name" => "[#$id] $name",
                "author" => $author,
                "data" => array()
            );
        } else {
            $series["$id"]["name"] =  "[#$id] $name";
        }
        $series["$id"]["data"][] = array(
            strtotime($date) * 1000, (int)$downloadsIncrease
        );
    }
    $stmt->close();
    unset($stmt);
    $conn->close();

    sortByDate($series);
}

if ($type === "resource_growth") {
    header("X-Type: resource_growth");

    $filterQuery = "";
    if (strlen($resourceFilter) > 0) {
        $filterQuery .= " where d1.id in (" . implode(",", array_fill(0, count($resourceFilterArr), '?')) . ") ";
    }
    if (strlen($authorFilter) > 0) {
        if (strlen($resourceFilter) > 0) {
            $filterQuery.=" and ";
        }else{
            $filterQuery .= " where ";
        }
        $filterQuery .= " d1.author in (" . implode(",", array_fill(0, count($authorFilterArr), '?')) . ") ";
    }
    // this has got to be the most ugly query so far
    $query = "select q1.id rid, q1.name, q1.author, DATE(q1.date) dateA, DATE(q2.date) dateB, (q2.downloads-q1.downloads) as diff from spiget_stats q1
                inner join spiget_stats q2 on q1.id=q2.id AND DATE(q1.date) + INTERVAL 1 DAY = DATE(q2.date) 
                inner join (
                    select distinct d1.id as did from spiget_stats d1 inner join spiget_stats d2 on d1.id=d2.id AND DATE(d1.date) + INTERVAL 1 DAY = DATE(d2.date) $filterQuery order by d1.downloads desc limit ?,? 
                ) d on q1.id in (d.did) WHERE (q2.downloads-q1.downloads)>0 group by q1.id, DATE(q2.date) order by diff desc";
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
    $stmt->bind_result($id, $name, $author, $dateA, $dateB, $diff);
    while ($row = $stmt->fetch()) {
        if (!isset($series["$id"])) {
            $series["$id"] = array(
                "id" => $id,
                "name" => "[#$id] $name",
                "author" => $author,
                "data" => array()
            );
        } else {
            $series["$id"]["name"] =  "[#$id] $name";
        }
        $series["$id"]["data"][] = array(
            strtotime($dateB) * 1000, (int)$diff
        );
    }
    $stmt->close();
    unset($stmt);
    $conn->close();

    sortByDate($series);
}

if ($type === "resource_growth2") {
    header("X-Type: resource_growth");

    $filterQuery = "";
    if (strlen($resourceFilter) > 0) {
        $filterQuery .= " and id in (" . implode(",", array_fill(0, count($resourceFilterArr), '?')) . ") ";
    }
    if (strlen($authorFilter) > 0) {
        $filterQuery .= " and author in (" . implode(",", array_fill(0, count($authorFilterArr), '?')) . ") ";
    }
    // this query is a bit of a mess but works
    // the inner query gets the unique resource ideas, descending by downloads
    // the outer gets all stats data based on the unique ids
    $query = "select id,name,author,date,downloads_incr from spiget_stats join (select distinct id as did from spiget_stats where downloads_incr > 0 AND date > NOW() - INTERVAL 2 WEEK   $filterQuery  order by downloads_incr desc limit ?,?) d ON spiget_stats.id IN (d.did) WHERE date > NOW() - INTERVAL 2 WEEK order by downloads_incr desc, date asc";
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
    $stmt->bind_result($id, $name, $author, $date, $downloadsIncrease);
    while ($row = $stmt->fetch()) {
        if (!isset($series["$id"])) {
            $series["$id"] = array(
                "id" => $id,
                "name" => "[#$id] $name",
                "author" => $author,
                "data" => array()
            );
        } else {
            $series["$id"]["name"] =  "[#$id] $name";
        }
        $series["$id"]["data"][] = array(
            strtotime($date) * 1000, (int)$downloadsIncrease
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
        $filterQuery .= " and author in (" . implode(",", array_fill(0, count($authorFilterArr), '?')) . ") ";
    }

    // not particularly pretty either
    $stmt = $conn->prepare("select author,date,sum(downloads) as totalDownloads from spiget_stats join (select author as aid, sum(downloads) as dd from spiget_stats where  date > NOW() - INTERVAL 2 WEEK  $filterQuery group by aid order by dd desc limit ?,?) d ON spiget_stats.author IN (d.aid) WHERE date > NOW() - INTERVAL 2 WEEK   group by author,date order by totalDownloads desc, date asc");

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
    $stmt->bind_result($author, $date, $downloadsIncrease);
    $authors = array();
    while ($row = $stmt->fetch()) {
        if (!isset($series["$author"])) {
            $series["$author"] = array(
                "name" => "[#$author]",
                "author" => $author,
                "data" => array()
            );
            $authors[]=$author;
        }
        $series["$author"]["data"][] = array(
            strtotime($date) * 1000, (int)$downloadsIncrease
        );
    }
    $stmt->close();
    unset($stmt);


    if ($type === "author_average") {
        header("X-Type: author_average");
        // https://stackoverflow.com/a/19666312/6257838
        $placeholders = array_fill(0, count($authors), '?');
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT id) as dcnt, author as authr from spiget_stats where author in (".implode(",",$placeholders).") AND date > NOW() - INTERVAL 2 WEEK  group by authr order by dcnt desc");

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
    }else{
        header("X-Type: author_total");
    }

    $conn->close();

    sortByDate($series);
}


header("Cache-Control: public, max-age=21600"); // Cache for ~6h
header("Content-Type: application/json");
echo json_encode(array_values($series));

