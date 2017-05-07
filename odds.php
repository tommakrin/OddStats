<?php

// This script parses an odds website and gets the values for the biggest match changes.
// Its purpose is to be executed regurarly, save the values fetched in a database and
// then extract some statistics, which may help making a safer, more profitable bet.

require 'model.php';

require 'simple_html_dom.php';

// Function to find the Nth position of needle in a haystack.

function Npos($haystack, $needle, $position)
{
    if ($position == '1') {
        return strpos($haystack, $needle);
    }
    elseif ($position > '1') {
        return strpos($haystack, $needle, Npos($haystack, $needle, $position - 1) + strlen($needle));
    }
    else {
        return error_log('Warning: Argument $position out of range (must be positive integer).');
    }
}

// Preparational. Set DB connection to hold the stats, URL to fetch data

$conn = DBConnect('localhost', 'odds', '1234', 'odds');
$url = "http://www.asianodds.com/asian_odds_changes.asp"; // Using data from asianodds.com. Other services are not supported yet, sorry.
$dom = new DOMDocument();

// Fetch website data with CURL

$ch = curl_init(); // Create CURL resource
curl_setopt($ch, CURLOPT_URL, $url); // Set URL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the transfer as a string
$output = curl_exec($ch); // $output contains the output string
curl_close($ch); // Close CURL resource to free up system resources

// Keeping only "Big Totals Odds Changes (Total Lines stable)" table (the one we are interested in)
// Eliminating the sidebar table ("Next 50 matches", social media, etc.) and the first one.

$startPos = Npos($output, "<table", 5);
$endPos = Npos($output, "</table>", 5);

if ($startPos && $endPos) {
    $len = $endPos - $startPos;
    $output = substr_replace($output, '', $startPos, $len);
}

// Parse and load the final HTML

@$dom->loadHTML($output);
$xpath = new DOMXPath($dom);

// Get the values of over/under for which the match opened, and the current ones

$open = $xpath->query("//table//tr[contains(concat(' ',@class,' '),' main ')]//td[last()]");
$current = $xpath->query("//table//tr[contains(concat(' ',@class,' '),' main ')]//td[last()-1]");

// Get team names

$home_teams = $xpath->query("//table//tr[contains(concat(' ',@class,' '),' main ') and contains(@bgcolor, \"#990033\")]//td[2]");
$away_teams = $xpath->query("//table//tr[contains(concat(' ',@class,' '),' main ') and not(@bgcolor)]//td[contains(@bgcolor, \"#FFFFFF\")][1]");

// Get date and time for the matches

$dates = $xpath->query("//table//tr[contains(concat(' ',@class,' '),' down ')]//td[1]");
$times = $xpath->query("//table//tr[contains(concat(' ',@class,' '),' main ')]//td[contains(@bgcolor,\"#D2D597\") and contains(@rowspan,\"2\")][1]");

// Initializing drops array

$drops = array();
$totalmatches = $times->length; // Get the total number of matches, based on the number of "start times" we counted
$i = 0; // Overall counter
$j = 0; // Counter for over/under pairs

// Begin building matches dataset. We should be able to fetch up to 50 matches.

while ($i < $totalmatches) {

    // Check if there are current over/under values for this iteration. If it is NULL, no further matches to process.

    if (isset($current->item($i)->nodeValue)) {

        // Form time/date for the current match

        $time = $times->item($i)->nodeValue;
        if ($dates->item($i+1)->nodeValue != '') {
            $date = $dates->item($i+1)->nodeValue;
            $date = explode(" - ", $date);
        }

        // Make necessary string handling

        if (isset($date[1])) {

            if (strpos($date[1], "-") == FALSE) {
                $olddate = explode("/", $date[1]);
                $date[1] = $olddate[2] . "-" . $olddate[1] . "-" . $olddate[0];
                $date[1] = ltrim($date[1], "-");
            }

            $time = $date[1] . " " . $time;

        } else {

            if (strpos($date[0], "-") == FALSE) {
                $olddate = explode("/", $date[0]);
                $date[0] = $olddate[2] . "-" . $olddate[1] . "-" . $olddate[0];
                $date[0] = ltrim($date[0], "-");
            }

            $time = $date[0] . " " . $time;

        }

        // Form team pair, with time and date of the match.

        $team_a = $home_teams->item($i)->nodeValue;
        $team_b = $away_teams->item($i)->nodeValue;
        $teams = $team_a . " - " . $team_b;
        echo $time . " - " . $teams . "<br/>";

        // Over values

        $r1 = $current->item($j)->nodeValue;
        $r2 = $open->item($j)->nodeValue;

        echo "<b>Over:</b> ";
        echo $r1 . " - " . $r2; // Over
        echo "<br/>";

        $drops[$teams]['overdiff'] = $r2 - $r1;
        $drops[$teams]['overopen'] = $r2;

        // Under

        $r3 = $current->item($j + 1)->nodeValue;
        $r4 = $open->item($j + 1)->nodeValue;

        echo "<b>Under:</b> ";
        echo $r3 . " - " . $r4; // Under

        $drops[$teams]['underdiff'] = $r4 - $r3;
        $drops[$teams]['underopen'] = $r4;

        echo "<br/><br/>";

        $drops[$teams]['time'] = $time;

        // Update counters

        $i++;
        $j = $j + 2;
    }
    else {
        break;
    }
}

// ================================================

$unders = array();
$overs = array();

foreach($drops as $key => $match) {
    $unders[$key] = $match['overdiff'];
    $overs[$key] = $match['underdiff'];
}

// Get max under

$maxunder = max($unders);
$keyunder = array_keys($unders, $maxunder);

// Get max over

$maxover = max($overs);
$keyover = array_keys($overs, $maxover);

echo "<h2>Biggest Drops</h2>";
echo "Largest <b>under</b> drop: $keyunder[0] -> $maxunder";
echo "<br/>";
echo "Largest <b>over</b> drop: $keyover[0] -> $maxover";
echo "<br/><br/>";

// =================================================

$updm = updateMatches($conn, $drops);
$updo = updateOdds($conn, $drops, $updm);