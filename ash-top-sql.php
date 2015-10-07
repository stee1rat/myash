<?
   function get_sqltype($sqlcode) {
      $sql_type = "UNKNOWN";
      switch ($sqlcode) {
         case 1: $sql_type = "CREATE TABLE"; break;
         case 2: $sql_type = "INSERT"; break;
         case 3: $sql_type = "SELECT"; break;
         case 4: $sql_type = "CREATE CLUSTER"; break;
         case 5: $sql_type = "ALTER CLUSTER"; break;
         case 6: $sql_type = "UPDATE"; break;
         case 7: $sql_type = "DELETE"; break;
         case 8: $sql_type = "DROP CLUSTER"; break;
         case 9: $sql_type = "CREATE INDEX"; break;
         case 10: $sql_type = "DROP INDEX"; break;
         case 11: $sql_type = "ALTER INDEX"; break;
         case 12: $sql_type = "DROP TABLE"; break;
         case 13: $sql_type = "CREATE SEQUENCE"; break;
         case 14: $sql_type = "ALTER SEQUENCE"; break;
         case 15: $sql_type = "ALTER TABLE"; break;
         case 16: $sql_type = "DROP SEQUENCE"; break;
         case 17: $sql_type = "GRANT OBJECT"; break;
         case 18: $sql_type = "REVOKE OBJECT"; break;
         case 19: $sql_type = "CREATE SYNONYM"; break;
         case 20: $sql_type = "DROP SYNONYM"; break;
         case 21: $sql_type = "CREATE VIEW"; break;
         case 22: $sql_type = "DROP VIEW"; break;
         case 23: $sql_type = "VALIDATE INDEX"; break;
         case 24: $sql_type = "CREATE PROCEDURE"; break;
         case 25: $sql_type = "ALTER PROCEDURE"; break;
         case 26: $sql_type = "LOCK TABLE"; break;
         case 27: $sql_type = "NO-OP"; break;
         case 28: $sql_type = "RENAME"; break;
         case 29: $sql_type = "COMMENT"; break;
         case 30: $sql_type = "AUDIT OBJECT"; break;
         case 31: $sql_type = "NOAUDIT OBJECT"; break;
         case 32: $sql_type = "CREATE DATABASE LINK"; break;
         case 33: $sql_type = "DROP DATABASE LINK"; break;
         case 34: $sql_type = "CREATE DATABASE"; break;
         case 35: $sql_type = "ALTER DATABASE"; break;
         case 36: $sql_type = "CREATE ROLLBACK SEG"; break;
         case 37: $sql_type = "ALTER ROLLBACK SEG"; break;
         case 38: $sql_type = "DROP ROLLBACK SEG"; break;
         case 39: $sql_type = "CREATE TABLESPACE"; break;
         case 40: $sql_type = "ALTER TABLESPACE"; break;
         case 41: $sql_type = "DROP TABLESPACE"; break;
         case 42: $sql_type = "ALTER SESSION"; break;
         case 43: $sql_type = "ALTER USER"; break;
         case 44: $sql_type = "COMMIT"; break;
         case 45: $sql_type = "ROLLBACK"; break;
         case 46: $sql_type = "SAVEPOINT"; break;
         case 47: $sql_type = "PL/SQL EXECUTE"; break;
         case 48: $sql_type = "SET TRANSACTION"; break;
         case 49: $sql_type = "ALTER SYSTEM"; break;
         case 50: $sql_type = "EXPLAIN"; break;
         case 51: $sql_type = "CREATE USER"; break;
         case 52: $sql_type = "CREATE ROLE"; break;
         case 53: $sql_type = "DROP USER"; break;
         case 54: $sql_type = "DROP ROLE"; break;
         case 55: $sql_type = "SET ROLE"; break;
         case 56: $sql_type = "CREATE SCHEMA"; break;
         case 57: $sql_type = "CREATE CONTROL FILE"; break;
         case 58: $sql_type = "ALTER TRACING"; break;
         case 59: $sql_type = "CREATE TRIGGER"; break;
         case 60: $sql_type = "ALTER TRIGGER"; break;
         case 61: $sql_type = "DROP TRIGGER"; break;
         case 62: $sql_type = "ANALYZE TABLE"; break;
         case 63: $sql_type = "ANALYZE INDEX"; break;
         case 64: $sql_type = "ANALYZE CLUSTER"; break;
         case 65: $sql_type = "CREATE PROFILE"; break;
         case 66: $sql_type = "DROP PROFILE"; break;
         case 67: $sql_type = "ALTER PROFILE"; break;
         case 68: $sql_type = "DROP PROCEDURE"; break;
         case 70: $sql_type = "ALTER RESOURCE COST"; break;
         case 71: $sql_type = "CREATE MATERIALIZED VIEW LOG"; break;
         case 72: $sql_type = "ALTER MATERIALIZED VIEW LOG"; break;
         case 73: $sql_type = "DROP MATERIALIZED VIEW  LOG"; break;
         case 74: $sql_type = "CREATE MATERIALIZED VIEW "; break;
         case 75: $sql_type = "ALTER MATERIALIZED VIEW "; break;
         case 76: $sql_type = "DROP MATERIALIZED VIEW "; break;
         case 77: $sql_type = "CREATE TYPE"; break;
         case 78: $sql_type = "DROP TYPE"; break;
         case 79: $sql_type = "ALTER ROLE"; break;
         case 80: $sql_type = "ALTER TYPE"; break;
         case 81: $sql_type = "CREATE TYPE BODY"; break;
         case 82: $sql_type = "ALTER TYPE BODY"; break;
         case 83: $sql_type = "DROP TYPE BODY"; break;
         case 84: $sql_type = "DROP LIBRARY"; break;
         case 85: $sql_type = "TRUNCATE TABLE"; break;
         case 86: $sql_type = "TRUNCATE CLUSTER"; break;
         case 87: $sql_type = "CREATE BITMAPFILE"; break;
         case 88: $sql_type = "ALTER VIEW"; break;
         case 89: $sql_type = "DROP BITMAPFILE"; break;
         case 90: $sql_type = "SET CONSTRAINTS"; break;
         case 91: $sql_type = "CREATE FUNCTION"; break;
         case 92: $sql_type = "ALTER FUNCTION"; break;
         case 93: $sql_type = "DROP FUNCTION"; break;
         case 94: $sql_type = "CREATE PACKAGE"; break;
         case 95: $sql_type = "ALTER PACKAGE"; break;
         case 96: $sql_type = "DROP PACKAGE"; break;
         case 97: $sql_type = "CREATE PACKAGE BODY"; break;
         case 98: $sql_type = "ALTER PACKAGE BODY"; break;
         case 99: $sql_type = "DROP PACKAGE BODY"; break;
         case 157: $sql_type = "CREATE DIRECTORY"; break;
         case 158: $sql_type = "DROP DIRECTORY"; break;
         case 159: $sql_type = "CREATE LIBRARY"; break;
         case 160: $sql_type = "CREATE JAVA"; break;
         case 161: $sql_type = "ALTER JAVA"; break;
         case 162: $sql_type = "DROP JAVA"; break;
         case 163: $sql_type = "CREATE OPERATOR"; break;
         case 164: $sql_type = "CREATE INDEXTYPE"; break;
         case 165: $sql_type = "DROP INDEXTYPE"; break;
         case 166: $sql_type = "ALTER INDEXTYPE"; break;
         case 167: $sql_type = "DROP OPERATOR"; break;
         case 168: $sql_type = "ASSOCIATE STATISTICS"; break;
         case 169: $sql_type = "DISASSOCIATE STATISTICS"; break;
         case 170: $sql_type = "CALL METHOD"; break;
         case 171: $sql_type = "CREATE SUMMARY"; break;
         case 172: $sql_type = "ALTER SUMMARY"; break;
         case 173: $sql_type = "DROP SUMMARY"; break;
         case 174: $sql_type = "CREATE DIMENSION"; break;
         case 175: $sql_type = "ALTER DIMENSION"; break;
         case 176: $sql_type = "DROP DIMENSION"; break;
         case 177: $sql_type = "CREATE CONTEXT"; break;
         case 178: $sql_type = "DROP CONTEXT"; break;
         case 179: $sql_type = "ALTER OUTLINE"; break;
         case 180: $sql_type = "CREATE OUTLINE"; break;
         case 181: $sql_type = "DROP OUTLINE"; break;
         case 182: $sql_type = "UPDATE INDEXES"; break;
         case 183: $sql_type = "ALTER OPERATOR"; break;
         case 184: $sql_type = "Do not use 184"; break;
         case 185: $sql_type = "Do not use 185"; break;
         case 186: $sql_type = "Do not use 186"; break;
         case 187: $sql_type = "CREATE SPFILE"; break;
         case 188: $sql_type = "CREATE PFILE"; break;
         case 189: $sql_type = "UPSERT"; break;
         case 190: $sql_type = "CHANGE PASSWORD"; break;
         case 191: $sql_type = "UPDATE JOIN INDEX"; break;
         case 192: $sql_type = "ALTER SYNONYM"; break;
         case 193: $sql_type = "ALTER DISK GROUP"; break;
         case 194: $sql_type = "CREATE DISK GROUP"; break;
         case 195: $sql_type = "DROP DISK GROUP"; break;
         case 196: $sql_type = "ALTER LIBRARY"; break;
         case 197: $sql_type = "PURGE USER RECYCLEBIN"; break;
         case 198: $sql_type = "PURGE DBA RECYCLEBIN"; break;
         case 199: $sql_type = "PURGE TABLESPACE"; break;
         case 200: $sql_type = "PURGE TABLE"; break;
         case 201: $sql_type = "PURGE INDEX"; break;
         case 202: $sql_type = "UNDROP OBJECT"; break;
         case 203: $sql_type = "DROP DATABASE"; break;
         case 204: $sql_type = "FLASHBACK DATABASE"; break;
         case 205: $sql_type = "FLASHBACK TABLE"; break;
         case 206: $sql_type = "CREATE RESTORE POINT"; break;
         case 207: $sql_type = "DROP RESTORE POINT"; break;
         case 209: $sql_type = "DECLARE REWRITE EQUIVALENCE"; break;
         case 210: $sql_type = "ALTER REWRITE EQUIVALENCE"; break;
         case 211: $sql_type = "DROP REWRITE EQUIVALENCE"; break;
         case 212: $sql_type = "CREATE EDITION"; break;
         case 213: $sql_type = "ALTER EDITION"; break;
         case 214: $sql_type = "DROP EDITION"; break;
         case 215: $sql_type = "DROP ASSEMBLY"; break;
         case 216: $sql_type = "CREATE ASSEMBLY"; break;
         case 217: $sql_type = "ALTER ASSEMBLY"; break;
         case 218: $sql_type = "CREATE FLASHBACK ARCHIVE"; break;
         case 219: $sql_type = "ALTER FLASHBACK ARCHIVE"; break;
         case 220: $sql_type = "DROP FLASHBACK ARCHIVE"; break;
         case 222: $sql_type = "CREATE SCHEMA SYNONYM"; break;
         case 224: $sql_type = "DROP SCHEMA SYNONYM"; break;
         case 225: $sql_type = "ALTER DATABASE LINK"; break;
      }
      return $sql_type;
   }

   $connect = oci_connect($_POST["username"], $_POST["password"], $_POST["host"].':'. $_POST["port"] . '/' . $_POST["service"]);

   if ($connect) {
      if (isset($_POST["waitclass"])) {

         if ($_POST["waitclass"] == 'CPU') {
               $c = 'is null';
            } else {
               $c = "= '" . $_POST["waitclass"] . "'";
         }

         $query = "select count(*) activity
                     from V\$ACTIVE_SESSION_HISTORY
                    where sample_time > to_date('" . $_POST["startdate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                      and sample_time < to_date('" . $_POST["enddate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                      and wait_class " . $c . "
                      and sql_id is not null";
      } else {
         $query = "select count(*) activity
                     from V\$ACTIVE_SESSION_HISTORY
                    where sample_time > to_date('" . $_POST["startdate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                      and sample_time < to_date('" . $_POST["enddate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                      and sql_id is not null";
      }

      $statement = oci_parse($connect, $query);
      oci_execute($statement);
      $nrows = oci_fetch_all($statement, $results);

      $sum_activity=$results["ACTIVITY"][0];

      if (isset($_POST["waitclass"])) {

         if ($_POST["waitclass"] == 'CPU') {
            $c = 'is null';
         } else {
            $c = "= '" . $_POST["waitclass"] . "'";
         }

         $query = "select h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text, sum(executions) executions, round(sum(elapsed_time)/decode(sum(executions),0,1,sum(executions))/1e6,5) avg_time from (
                   select h1.sql_id, h1.sql_opcode, nvl(h2.event,'CPU') wait_class, round(count(*)/" . $sum_activity ."*100,2) percent, n from (
                    select * from (
                        select sql_id, sql_opcode, count(*) n from v\$active_session_history
                         where sample_time > to_date('" . $_POST["startdate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                           and sample_time < to_date('" . $_POST["enddate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                           and sql_id is not null
                           and wait_class " . $c . "
                         group by sql_id, sql_opcode
                         order by 3 desc
                     )  where rownum <= 10 ) h1, v\$active_session_history h2
                     where h1.sql_id = h2.sql_id
                       and wait_class " . $c . "
                       and h2.sample_time > to_date('" . $_POST["startdate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                       and h2.sample_time < to_date('" . $_POST["enddate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                     group by h1.sql_id, h1.sql_opcode, nvl(h2.event,'CPU'), n
                     ) h, v\$sqlarea s
                    where s.sql_id (+) = h.sql_id
                  group by h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text
                  order by n desc, sql_id desc";

      } else {
          $query = "select h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text, sum(executions) executions, round(sum(elapsed_time)/decode(sum(executions),0,1,sum(executions))/1e6,5) avg_time from (
                      select h1.sql_id, h1.sql_opcode, nvl(h2.wait_class,'CPU') wait_class, round(count(*)/" . $sum_activity ."*100,2) percent, n from (
                       select * from (
                           select sql_id, sql_opcode, count(*) n from v\$active_session_history
                            where sample_time > to_date('" . $_POST["startdate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                              and sample_time < to_date('" . $_POST["enddate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                              and sql_id is not null
                            group by sql_id, sql_opcode
                            order by 3 desc
                        )  where rownum <= 10 ) h1, v\$active_session_history h2
                        where h1.sql_id = h2.sql_id
                          and h2.sample_time > to_date('" . $_POST["startdate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                          and h2.sample_time < to_date('" . $_POST["enddate"] ."', 'DD.MM.YYYY HH24:MI:SS')
                        group by h1.sql_id, h1.sql_opcode, nvl(h2.wait_class,'CPU'), n
                        ) h, v\$sqlarea s
                       where s.sql_id (+) = h.sql_id
                     group by h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text
                     order by n desc, sql_id desc";
      }

      $start_time = microtime(true);

      $statement = oci_parse($connect, $query);
      oci_execute($statement);

      $nrows = oci_fetch_all($statement, $results);

      /*
      print "<pre>";
      print_r($query);
      print "</pre>";
      */

      $top = array();
      for ($i=0; $i<sizeof($results["N"]); $i++) {
         if (!isset($top[$results["SQL_ID"][$i]]["TEXT"])) {
            $top[$results["SQL_ID"][$i]]["TEXT"] = $results["SQL_TEXT"][$i];
         }
         if (!isset($top[$results["SQL_ID"][$i]]["SQL_ID"])) {
            $top[$results["SQL_ID"][$i]]["SQL_ID"] = $results["SQL_ID"][$i];
         }
         if (!isset($top[$results["SQL_ID"][$i]]["SQL_OPCODE"])) {
            $top[$results["SQL_ID"][$i]]["SQL_OPCODE"] = $results["SQL_OPCODE"][$i];
         }
         if (!isset($top[$results["SQL_ID"][$i]]["AVG_TIME"])) {
            $top[$results["SQL_ID"][$i]]["AVG_TIME"] = $results["AVG_TIME"][$i];
         }
         if (!isset($top[$results["SQL_ID"][$i]]["EXECUTIONS"])) {
            $top[$results["SQL_ID"][$i]]["EXECUTIONS"] = $results["EXECUTIONS"][$i];
         }
         if (!isset($top[$results["SQL_ID"][$i]]["PERCENT_TOTAL"])) {
            $top[$results["SQL_ID"][$i]]["PERCENT_TOTAL"] = $results["N"][$i]/$sum_activity*100;
         }

         $top[$results["SQL_ID"][$i]]["WAIT_CLASS"][$results["WAIT_CLASS"][$i]] = $results["PERCENT"][$i];
      }

      print "<table class='output'>";
      print "<thead>";
      print "<tr><th align='left'>SQL ID</th>";
      print "<th width='150px' align='left'>Activity</th>";
      print "<th align='left' nowrap>SQL Type</th>";
      print "<th align='left' nowrap>&nbsp;&nbsp;Executions</th>";
      print "<th align='left' nowrap>&nbsp;&nbsp;Average Time</th>";
      print "</tr></thead>";

      /*
      print "<pre>";
      print_r($top);
      print "</pre>";
      */
      //print $_POST("t");

      foreach ($top as $sql) {
         print "<tr>";
         print "<td><a href='#' title='".htmlspecialchars($sql["TEXT"]) ."'>".$sql["SQL_ID"] . "</a>&nbsp;</td>";
         print "<td>";

         print "<table width='100%'><tr>";
         print "<td width='100%'>";
         foreach ($sql["WAIT_CLASS"] as $key => $value) {
            $bg = $_POST["eventColors"][$key];
            print "<div style='background:$bg;width:$value%;float:left;'>&nbsp;</div>";
         }
         print "</td><td>";
         print "<div style=''>".round($sql["PERCENT_TOTAL"],2). "%</div></td>";
         print "</td>";
         print "</tr></table>";
         print "<td nowrap align='left'>".get_sqltype($sql["SQL_OPCODE"]) . "</td>";
         print "<td nowrap align='right'>". $sql["EXECUTIONS"] . "</td>";
         print "<td nowrap align='right'>". number_format(round($sql["AVG_TIME"],4),2,'.','') . "s</td>";
         print "</tr>";
      }
      $end_time = microtime(true);

      print "</table>";
      print "<div align='right'><font style='font-family: Tahoma,Verdana,Helvetica,sans-serif;font-size:9px' color='gray'>Total Sample Count: $sum_activity, Returned in: ".round($end_time - $start_time,2) . "s</font></div>";

   }
?>
