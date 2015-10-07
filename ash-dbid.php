<?
   $connect = oci_connect($_POST["username"], $_POST["password"], $_POST["host"].':'. $_POST["port"] . '/' . $_POST["service"]);

   if ($connect) {
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
         $query = "select to_char(trunc(begin_interval_time,'DD'), 'DD.MM.YYYY') day
                     from dba_hist_snapshot
                    where dbid = ". $_POST["dbid"] ."
                    group by trunc(begin_interval_time,'DD')
                    order by trunc(begin_interval_time,'DD') desc";

         $statement = oci_parse($connect, $query);
         oci_execute($statement);

         while ($row = oci_fetch_array($statement)) {
            print "<option value='".$row["DAY"]."'>".$row["DAY"]."</option>";
         }
      }
      oci_close($connect);
   }
?>
