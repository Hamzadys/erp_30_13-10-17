<?php
include_once("config_db.php");
echo '
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width = 100%
}
td, th {
    border: 1px solid #FF0000;
    text-align: left;
    padding: 1px;
    white-space:nowrap;
}
tr:nth-child(even) {
    background-color: #dddddd;
}
</style>
';
    global $tb_pref_counter;
    $counter = '';

    $total_companies = $tb_pref_counter-1;
    $table_name = $_GET['table_name'];

    echo "<b>";
    echo "TABLE NAME $table_name";
    echo "</br>";
    echo "TOTAL DB COUNT $total_companies";
    echo "</br>";

    for ($x = 0; $x <= $total_companies; $x++) {

    $dbh["$x"] = mysql_connect(localhost, $db_connections["$x"]["dbuser"], $db_connections["$x"]["dbpassword"]);  

    mysql_select_db($db_connections["$x"]["dbname"], $dbh["$x"]); 

      $db = $db_connections["$x"]["dbname"];

   $result_count["$x"] = mysql_query("SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = '$db'
    AND table_name = '$table_name'
  ", $dbh["$x"]);

    $row_count = mysql_fetch_array($result_count["$x"]);  
    echo "<b>";
    echo "COLUMNS = ";
    echo " <font color='red'> ";
    echo $row_count[0];
    echo "</font>";
    echo "<b>";
    echo " / TB PREF = ";
    echo "$x" ;
    echo " / COMPANY = ";
    echo $db_connections["$x"]["name"];
    echo " / DB NAME = "; 
    echo $db_connections["$x"]["dbname"];
    echo "</b>";
     
   $result["$x"] = mysql_query("SELECT column_name,ordinal_position,data_type,column_type FROM
    (
    SELECT
        column_name,ordinal_position,
        data_type,column_type,COUNT(1) rowcount
    FROM information_schema.columns
    WHERE
    (
        (table_schema='$db' AND table_name='$table_name')         
    )
    GROUP BY
      column_name,ordinal_position,
        data_type,column_type
    HAVING COUNT(1)=1
        ORDER BY ordinal_position

) A", $dbh["$x"]);
    $sr = 0;
 echo '
 <table>';

    while ($row = mysql_fetch_array($result["$x"])) {  
  
    echo'
    <td> <b> '. $row[1] .' </b> - '. $row[0] .' - '. $row[3] .' </td> ';
    } 
    echo  '</table>';

}

?>