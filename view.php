<?php
include 'model.php';
$conn = DBConnect('localhost', 'odds', '1234', 'odds');

$ids = getAvailableMatchIDs($conn);

$times = getRefreshTimes($conn);
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="index.css">
    </head>
    <body>
        <table>
            <tr>
                <th>Matches/Drops</th>
                <th>Opening</th>
                <?php $tmcount = count($times); ?>
                <?php foreach ($times as $time) { ?>
                <th><?php echo $time; ?></th>
                <?php } ?>
            </tr>
            <?php 
                $dates = getMatchDates($conn);
                
                
                
                foreach ($dates as $date){
            ?>
                    <tr class="datesep"><td colspan = "<?php echo $tmcount+2; ?>"><?php echo $date; ?></td></tr>
                    <?php $matches = getMatchesByDate($conn, $date); ?>
                    <?php
                    foreach ($matches as $match) {
                        $mu = getUndersPerMatchID($conn, $match['id']);
                        $mo = getOversPerMatchID($conn, $match['id']);
                    ?>
                    <tr>
                        <td rowspan='2' class = 'matchname'><?php echo $match['name']; ?></td>
                        <?php $openings = getMatchOpenings($conn, $match['id'])?>
                        <td><?php echo "<b>Under:</b> ".$openings['under']; ?></td>
                    <?php
                    // Use dashes for matches that added recently and 
                    // are not present in previous updates
                    if (count($mu) < $tmcount){
                        $counter = $tmcount - count($mu);
                        for ($i=0; $i<$counter; $i++){
                            echo "<td>-</td>";
                        }
                        foreach ($mu as $drop){
                            echo "<td>" . $drop['drops'] . "</td>";
                        }
                    } else {
                        foreach ($mu as $drop){
                            echo "<td>" . $drop['drops'] . "</td>";
                        }
                    }

                    ?>
                    </tr>
                    <!-- OVER-->
                    <tr>
                        <?php $openings = getMatchOpenings($conn, $match['id'])?>
                        <td><?php echo "<b>Over:</b> ".$openings['over']; ?></td>
                    <?php
                    // Use dashes for matches that added recently and 
                    // are not present in previous updates
                    if (count($mo) < $tmcount){
                        $counter = $tmcount - count($mo);
                        for ($i=0; $i<$counter; $i++){
                            echo "<td>-</td>";
                        }
                        foreach ($mo as $drop){
                            echo "<td>" . $drop['drops'] . "</td>";
                        }
                    } else {
                        foreach ($mo as $drop){
                            echo "<td>" . $drop['drops'] . "</td>";
                        }
                    }

                    ?>
                    </tr>
                    <?php } ?>
                    <?php ?>
            <?php } ?>
            

        </table>
            <?php //echo "<br/><h2>Total: $count matches.</h2><br/>" ?>
    </body>
</html>