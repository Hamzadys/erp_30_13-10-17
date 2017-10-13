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
    $table_name = '0_debtor_trans';

    echo "<b>";
    echo "TOTAL DB COUNT $total_companies";
    echo "</br>";

    for ($x = 0; $x <= $total_companies; $x++) {

    $dbh["$x"] = mysql_connect(localhost, $db_connections["$x"]["dbuser"], $db_connections["$x"]["dbpassword"]);  

    mysql_select_db($db_connections["$x"]["dbname"], $dbh["$x"]); 

      $db = $db_connections["$x"]["dbname"];

    $result_count["$x"] = mysql_query("SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
    ", $dbh["$x"]);

    $table_count = mysql_fetch_array($result_count["$x"]);  

    echo "<b>";
    echo "TABLES = ";
    echo " <font color='red'> ";
    echo $table_count[0];
    echo "</font>";
    echo "<b>";
    echo " / TB PREF = ";
    echo "$x" ;
    echo " / COMPANY = ";
    echo $db_connections["$x"]["name"];
    echo " / DB NAME = "; 
    echo $db_connections["$x"]["dbname"];
    echo "</b>";
    
    
    $result["$x"] = mysql_query("SELECT TABLE_NAME from information_schema.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ", $dbh["$x"]);

    echo '
    <table>';
    while ($row = mysql_fetch_array($result["$x"])) {  
    echo " 
        <td> <a href='http://erp30.com/table_check.php?table_name=$row[0]' target= '_blank'> $row[0] </a> ";


            $result_count["$x"] = mysql_query("SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = '$db'
            AND table_name = '$row[0]' ", $dbh["$x"]);

            $row_count = mysql_fetch_array($result_count["$x"]);  
    echo "<b>";

    echo " = ";
    echo "<font color='red'> ";
    echo $row_count[0];
    echo "</font>";
    
                
    echo "</td>";    
    } 
    echo  '</table>';
}

?>