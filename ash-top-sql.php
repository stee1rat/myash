<?php
   require_once('ash-connect.php');
   require_once('sql-types.php');

   $start_date = $_POST['startdate'];
   $end_date   = $_POST['enddate'];
   $query_mod1  = "";
   $query_mod2  = "";

   if (isset($_POST['waitclass'])) {
      $query_mod2 = "event";

      if ($_POST['waitclass'] === 'CPU') {
          $query_mod1 = " and wait_class is null";
      } else {
         $query_mod1 = " and wait_class = '" . $_POST['waitclass'] . "'";
      };
   } else {
      $query_mod2 = "wait_class";
   }

   $query = "select count(*) activity
               from V\$ACTIVE_SESSION_HISTORY
              where sample_time > to_date('" . $start_date ."', 'DD.MM.YYYY HH24:MI:SS')
                and sample_time < to_date('" . $end_date ."', 'DD.MM.YYYY HH24:MI:SS') ".$query_mod1."
                and sql_id is not null";

   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   $nrows = oci_fetch_all($statement, $results);

   $sum_activity = $results['ACTIVITY'][0];

   $query = "select h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text, sum(executions) executions, round(sum(elapsed_time)/decode(sum(executions),0,1,sum(executions))/1e6,5) avg_time from (
            select h1.sql_id, h1.sql_opcode, nvl(h2.".$query_mod2.",'CPU') wait_class, round(count(*)/" . $sum_activity ."*100,2) percent, n from (
             select * from (
                 select sql_id, sql_opcode, count(*) n from v\$active_session_history
                  where sample_time > to_date('" . $start_date ."', 'DD.MM.YYYY HH24:MI:SS')
                    and sample_time < to_date('" . $end_date ."', 'DD.MM.YYYY HH24:MI:SS')
                    and sql_id is not null ".$query_mod1."
                  group by sql_id, sql_opcode
                  order by 3 desc
              )  where rownum <= 10 ) h1, v\$active_session_history h2
              where h1.sql_id = h2.sql_id ".$query_mod1."
                and h2.sample_time > to_date('" . $start_date ."', 'DD.MM.YYYY HH24:MI:SS')
                and h2.sample_time < to_date('" . $end_date ."', 'DD.MM.YYYY HH24:MI:SS')
              group by h1.sql_id, h1.sql_opcode, nvl(h2.".$query_mod2.",'CPU'), n
              ) h, v\$sqlarea s
             where s.sql_id (+) = h.sql_id
           group by h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text
           order by n desc, sql_id desc";

   $start_time = microtime(true);

   $statement = oci_parse($connect, $query);
   oci_execute($statement);

   $nrows = oci_fetch_all($statement, $results);

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
?>
