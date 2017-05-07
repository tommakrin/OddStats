<?php

function DBConnect($server, $user, $pass, $db) {

// Create connection
    $conn = new mysqli($server, $user, $pass);
    $conn->set_charset("utf8");

// Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
        return 0;
    } else {
        mysqli_select_db($conn, $db);
        return $conn;
    }
//echo "Connected successfully";
}

function updateMatches($conn, $matches) {

    $whitelist = array();
    $openings = array();

    // Insert new matches
    foreach ($matches as $key => $match) {

        $query = "INSERT INTO matches (name, startson) VALUES ('" . $key . "','" . $match['time'] . "') "
                . "ON DUPLICATE KEY UPDATE id=id";
        $result = $conn->query($query);

        // Create a list of the IDs of the new matches
        $query = "SELECT id FROM matches WHERE name LIKE '%$key%' ";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $whitelist[] = $row['id'];
                $openings[$row['id']] = array($match['overopen'], $match['underopen']);
            }
        }
    }

    // Delete old matches, those which have already began, etc.
    $str = '';
    foreach ($whitelist as $wid) {
        $str .= $wid . ',';
    }
    $str = rtrim($str, ",");

    $query = "DELETE FROM matches WHERE id NOT IN ($str) ";
    $result = $conn->query($query);

    // Insert openings
    foreach ($openings as $key => $openid) {
        $query = "INSERT INTO openings (match_id, over, under) VALUES ('$key', '$openid[0]', '$openid[1]')"
                . "ON DUPLICATE KEY UPDATE match_id=match_id";
        $result = $conn->query($query);
    }

    return $whitelist;
}

function updateOdds($conn, $matches, $whtlst) {
    foreach ($matches as $key => $match) {

        $query = "SELECT id FROM matches WHERE name LIKE '%$key%' ";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
            }
        }
        // Overdiff
        $query = "INSERT INTO drops (match_id, status, drops) VALUES ('" . $id . "','0', '" . $match['overdiff'] . "') ";
        $result = $conn->query($query);
        // Underdiff
        $query = "INSERT INTO drops (match_id, status, drops) VALUES ('" . $id . "','1', '" . $match['underdiff'] . "') ";
        $result = $conn->query($query);
        //var_dump($key);
    }
    $str = '';
    foreach ($whtlst as $whtid) {
        $str .= $whtid . ',';
    }
    $str = rtrim($str, ",");
    $query = "DELETE FROM drops WHERE match_id NOT IN ($str)";
}

function getAvailableMatchIDs($conn) {
    $query = "SELECT id FROM matches ORDER BY id ASC ";
    $result = $conn->query($query);

    $ids = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
    }
    return $ids;
}

function getUndersPerMatchID($conn, $matchid) {

    $drops = array();

    $query = "SELECT match_id, drops, timestamp FROM drops WHERE match_id = $matchid AND status = 1 ORDER BY DATE_FORMAT(`timestamp`, '%H:%i') ASC LIMIT 20";
    //var_dump($query);
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $drops[] = $row;
        }
    }

    return $drops;
}

function getOversPerMatchID($conn, $matchid) {

    $drops = array();

    $query = "SELECT match_id, drops, timestamp FROM drops WHERE match_id = $matchid AND status = 0 ORDER BY DATE_FORMAT(`timestamp`, '%H:%i') ASC LIMIT 30";
    //var_dump($query);
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $drops[] = $row;
        }
    }

    return $drops;
}

function getRefreshTimes($conn) {
    $query = "SELECT DATE_FORMAT(`timestamp`, '%H:%i') AS time FROM `drops` GROUP BY time ASC LIMIT 30";
    $result = $conn->query($query);

    $times = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $times[] = $row['time'];
        }
    }
    return $times;
}

function getMatchDates($conn) {
    $query = "SELECT DATE(`startson`) AS d FROM `matches` GROUP BY d";
    $result = $conn->query($query);

    $dates = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['d'];
        }
    }
    return $dates;
}

function getMatchesByDate($conn, $date) { 
    $query = "SELECT id, name FROM matches WHERE DATE(`startson`) = '$date' ";
    $result = $conn->query($query);

    $matches = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
    }
    return $matches;
}

function getMatchNameByID($conn, $id) {
    $query = "SELECT name FROM matches WHERE id = $id ";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $name = $row['name'];
        }
    }
    return $name;
}

function getMatchOpenings($conn, $id) {
    $query = "SELECT over, under FROM openings WHERE match_id = $id ";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $opening = $row;
        }
    }
    return $opening;
}
