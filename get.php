<?php

include "./db_stuff.php";

$limit = 10;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$offset = (max($page, 1) - 1) * $limit;

header("X-Page: $page");
header("X-Limit: $limit");
header("X-Offset: $offset");

// this query is a bit of a mess but works
// the inner query gets the unique resource ideas, descending by downloads
// the outer gets all stats data based on the unique ids
$stmt = $conn->prepare("select id,name,author,date,downloads from spiget_stats join (select distinct id as did from spiget_stats order by downloads desc limit ?,?) d ON spiget_stats.id IN (d.did) order by downloads desc, date asc");
$stmt->bind_param("ii", $offset,$limit);
$stmt->execute();
$stmt->bind_result($id, $name, $author, $date, $downloads);
$series = [];
while ($row = $stmt->fetch()) {
    if (!isset($series["$id"])) {
        $series["$id"] = array(
            "id" => $id,
            "name" => $name,
            "author" => $author,
            "data"=>array()
        );
    }
    $series["$id"]["data"][]=array(
        strtotime($date)*1000,$downloads
    );
}
$stmt->close();
unset($stmt);
$conn->close();

header("Content-Type: application/json");
echo json_encode(array_values($series));

