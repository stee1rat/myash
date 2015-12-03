 <?php

   // Connect to the database and define $connect variable
   include('connect.php');

   // Define $query_mod1, $query_mod2 and $query_mod3 variables
   include('query-mods.php');

   // Define get_sqltype function for top-sql table
   if ($_POST['type'] === 'top-sql') {
      include('sql-types.php');
   }

   $start_date = $_POST['startDate'];
   $end_date   = $_POST['endDate'];

   $query = <<<SQL
SELECT count(*) activity
  FROM v\$active_session_history
 WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
   AND sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS') {$query_mod3} {$query_mod1}
SQL;

   $statement = oci_parse($connect, $query);

   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_bind_by_name($statement, ":end_date", $end_date);

   oci_execute($statement);
   oci_fetch_all($statement, $results);

   $sum_activity = $results['ACTIVITY'][0];

   if ($_POST['type'] === 'top-sql') {
   $query = <<<SQL
SELECT h.sql_id,h.sql_opcode,h.n,h.wait_class,h.percent,s.sql_text,sum(executions) executions,
    round(sum(elapsed_time)/decode(sum(executions),0,1,sum(executions))/1e6,5) avg_time
  FROM (SELECT h1.sql_id, h1.sql_opcode, NVL(h2.{$query_mod2},'CPU') wait_class, round(Count(*)/:sum_activity*100,2) PERCENT, n
       FROM (SELECT *
               FROM (SELECT sql_id, sql_opcode, count(*) n
                       FROM v\$active_session_history
                      WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                        AND sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')
                        AND sql_id IS NOT NULL {$query_mod1}
                      GROUP BY sql_id, sql_opcode
                      ORDER BY 3 DESC)
              WHERE rownum <= 10 ) h1,
            v\$active_session_history h2
       WHERE h1.sql_id = h2.sql_id {$query_mod1}
         AND h2.sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
         AND h2.sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')
       GROUP BY h1.sql_id, h1.sql_opcode, nvl(h2.{$query_mod2},'CPU'), n) h,
    v\$sqlarea s
 WHERE s.sql_id (+) = h.sql_id
 GROUP BY h.sql_id, h.sql_opcode, h.n, h.wait_class, h.PERCENT, s.sql_text
 ORDER BY n DESC, sql_id DESC
SQL;
   }

   if ($_POST['type'] === 'top-session') {
   $query = <<<SQL
SELECT h.*, u.username
  FROM (SELECT h1.session_id || ',' ||  h1.session_serial# session_id, h2.program, nvl(h2.{$query_mod2},'CPU') wait_class,
            user_id, round(count(*)/:sum_activity*100,2) PERCENT, n
       FROM (SELECT *
               FROM (SELECT session_id, session_serial#, Count(*) n
                       FROM v\$active_session_history
                      WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                        AND sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS'){$query_mod1}
                      GROUP BY session_id, session_serial#
                      ORDER BY 3 DESC)
              WHERE rownum <= 10 ) h1,
            v\$active_session_history h2
      WHERE h1.session_id = h2.session_id
        AND h1.session_serial# = h2.session_serial#
        AND sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
        AND sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS'){$query_mod1}
      GROUP BY h1.session_id, h1.session_serial#, h2.program, nvl(h2.{$query_mod2},'CPU'), n, user_id) h,
   dba_users u
 WHERE u.user_id = h.user_id
 ORDER BY n DESC, session_id DESC, wait_class DESC
SQL;
   }

   $start_time = microtime(true);

   $statement = oci_parse($connect, $query);

   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_bind_by_name($statement, ":end_date", $end_date);
   oci_bind_by_name($statement, ":sum_activity", $sum_activity);

   oci_execute($statement);

   $nrows = oci_fetch_all($statement, $results);

   $top = array();
   for ($i=0; $i<sizeof($results["N"]); $i++) {
      if ($_POST['type'] === 'top-sql') {
         $top[$results["SQL_ID"][$i]]["TEXT"] = $results["SQL_TEXT"][$i];
         $top[$results["SQL_ID"][$i]]["SQL_ID"] = $results["SQL_ID"][$i];
         $top[$results["SQL_ID"][$i]]["SQL_OPCODE"] = $results["SQL_OPCODE"][$i];
         $top[$results["SQL_ID"][$i]]["AVG_TIME"] = $results["AVG_TIME"][$i];
         $top[$results["SQL_ID"][$i]]["EXECUTIONS"] = $results["EXECUTIONS"][$i];
         $top[$results["SQL_ID"][$i]]["PERCENT_TOTAL"] = $results["N"][$i]/$sum_activity*100;
         $top[$results["SQL_ID"][$i]]["WAIT_CLASS"][$results["WAIT_CLASS"][$i]] = $results["PERCENT"][$i];
      } elseif ($_POST['type'] === 'top-session') {
         $top[$results["SESSION_ID"][$i]]["PROGRAM"] = $results["PROGRAM"][$i];
         $top[$results["SESSION_ID"][$i]]["SESSION_ID"] = $results["SESSION_ID"][$i];
         $top[$results["SESSION_ID"][$i]]["USERNAME"] = $results["USERNAME"][$i];
         $top[$results["SESSION_ID"][$i]]["PERCENT_TOTAL"] = $results["N"][$i]/$sum_activity*100;
         $top[$results["SESSION_ID"][$i]]["WAIT_CLASS"][$results["WAIT_CLASS"][$i]] = $results["PERCENT"][$i];
      }
   }

   print "<table class='output'>";

   // Table headers
   print "<thead><tr>";
   if ($_POST['type'] === 'top-sql') {
      print "<th align='left'>SQL ID</th>";
      print "<th width='150px' align='left'>Activity</th>";
      print "<th align='left' nowrap>SQL Type</th>";
      print "<th align='left' nowrap>&nbsp;&nbsp;Executions</th>";
      print "<th align='left' nowrap>&nbsp;&nbsp;Average Time</th>";
   } elseif ($_POST['type'] === 'top-session') {
      print "<th align='left' nowrap>SID,Serial#&nbsp;&nbsp;</th>";
      print "<th width='150px' align='left'>Activity</th>";
      print "<th align='left'>Username&nbsp;&nbsp;</th>";
      print "<th align='left'>Program</th>";
   }
   print "</tr></thead>";

   // Rest of the table
   foreach ($top as $position) {
      print "<tr>";

      if ($_POST['type'] === 'top-sql') {
         print "<td><a href='#' title='".htmlspecialchars($position["TEXT"]) ."'>".$position["SQL_ID"] . "</a>&nbsp;</td>";
      } elseif ($_POST['type'] === 'top-session') {
         print "<td>".$position["SESSION_ID"] . "</td>";
      }

      print "<td>";
      print "<table width='100%'><tr>";
      print "<td width='100%'>";
      foreach ($position["WAIT_CLASS"] as $key => $value) {
         print "<div style='background:".$_POST["eventColors"][$key].";width:$value%;float:left;'>&nbsp;</div>";
      }
      print "</td>";
      print "<td><div style=''>".round($position["PERCENT_TOTAL"],2). "%</div></td>";
      print "</td>";
      print "</tr></table>";

      if ($_POST['type'] === 'top-sql') {
         print "<td nowrap align='left'>".get_sqltype($position["SQL_OPCODE"]) . "</td>";
         print "<td nowrap align='right'>". $position["EXECUTIONS"] . "</td>";
         print "<td nowrap align='right'>". number_format(round($position["AVG_TIME"],4),2,'.','') . "s</td>";
      } elseif ($_POST['type'] === 'top-session') {
         print "<td nowrap>". $position["USERNAME"] . "</td>";
         print "<td nowrap>". $position["PROGRAM"] . "</td>";
      }
      print "</tr>";
   }
   print "</table>";

   $end_time = microtime(true);

   print "<div align='right'><font style='font-family: Tahoma,Verdana,Helvetica,sans-serif;font-size:9px' color='gray'>Total Sample Count: $sum_activity, Returned in: ".round($end_time - $start_time,2) . "s</font></div>";

?>
