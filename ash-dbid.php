<?php
   include('ash-connect.php');

   if (!isset($_POST["dbid"])) {
      $query = "select dbid from v\$database";

      $statement = oci_parse($connect, $query);
      oci_execute($statement);
      oci_fetch_all($statement,$result);

      $dbid = $result["DBID"][0];

      $query = "select distinct dbid, dbid || ' (' || db_name || ')' option_name from dba_hist_database_instance";

      $statement = oci_parse($connect, $query);
      oci_execute($statement);

      while ($row = oci_fetch_array($statement)) {
         print "<option value='".$row["DBID"]."' ";
         if ($row["DBID"]==$dbid) {
            print "selected";
         }
         print ">".$row["OPTION_NAME"]."</option>";
      }
   } else {
      $query = <<<SQL
SELECT to_char(trunc(begin_interval_time,'DD'), 'DD.MM.YYYY') day
  FROM dba_hist_snapshot
 WHERE dbid = {$_POST["dbid"]}
 GROUP BY trunc(begin_interval_time,'DD')
 ORDER BY trunc(begin_interval_time,'DD') DESC
SQL;

      $statement = oci_parse($connect, $query);
      oci_execute($statement);

      while ($row = oci_fetch_array($statement)) {
         print "<option value='".$row["DAY"]."'>".$row["DAY"]."</option>";
      }
   }
   oci_close($connect);
?>
