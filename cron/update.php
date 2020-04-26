<?php
include __DIR__ . "./../db_stuff.php";

$date = date("Y-m-d H:i:s");

$p = 1;
for ($x = 0; $x < 500; $x++) {// pretty much just a while loop, just making sure it won't be infinite (current page count with size of 500 is ~110)
    echo "Querying page #$p...";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://api.spiget.org/v2/resources?fields=name,downloads,author&size=500&page=$p");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $res = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($res, true);
    $size = sizeof($json);
    echo " => $size resources\n";
    if ($size <= 0) {
        break;// no more data returned, stop.
    } else {
        foreach ($json as $res) {
            $id = $res["id"];
            $name = $res["name"];
            $author = $res["author"]["id"];
            $downloads = $res["downloads"];

            $stmt = $conn->prepare("INSERT INTO spiget_stats (id,name,author,date,downloads) VALUES(?,?,?,?,?)");
            $stmt->bind_param("isisi", $id, $name, $author, $date, $downloads);
            $stmt->execute();
            $stmt->close();
            unset($stmt);
        }
    }

    $p++; // Next page!
}

$conn->close();
unset($conn);
