<?php
   // Connect to the database and define $connect variable
   include('ash-connect.php');

   // Define $query_mod1 and $query_mod2 variables
   include ('ash-query-mods.php');

   // Define get_sqltype function for top-sql table
   if ($_POST['type'] === 'top-sql') {
      include('sql-types.php');
   }

   $start_date = $_POST['startdate'];
   $end_date   = $_POST['enddate'];

   $query = "select count(*) activity\n" .
            "  from v\$active_session_history\n" .
            " where sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')\n" .
            "   and sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS') ".$query_mod1;

   if (isset($top_sql)) {
     $predicates[] = "\n   and sql_id is not null";
     $query .= implode ($query, $predicates) ;
   }

   $statement = oci_parse($connect, $query);

   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_bind_by_name($statement, ":end_date", $end_date);

   oci_execute($statement);
   oci_fetch_all($statement, $results);

   $sum_activity = $results['ACTIVITY'][0];

   if ($_POST['type'] === 'top-sql') {
      $query = "select h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text, sum(executions) executions, round(sum(elapsed_time)/decode(sum(executions),0,1,sum(executions))/1e6,5) avg_time from (
               select h1.sql_id, h1.sql_opcode, nvl(h2.".$query_mod2.",'CPU') wait_class, round(count(*)/:sum_activity*100,2) percent, n from (
                select * from (
                    select sql_id, sql_opcode, count(*) n from v\$active_session_history
                     where sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                       and sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')
                       and sql_id is not null ".$query_mod1."
                     group by sql_id, sql_opcode
                     order by 3 desc
                 )  where rownum <= 10 ) h1, v\$active_session_history h2
                 where h1.sql_id = h2.sql_id ".$query_mod1."
                   and h2.sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                   and h2.sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')
                 group by h1.sql_id, h1.sql_opcode, nvl(h2.".$query_mod2.",'CPU'), n
                 ) h, v\$sqlarea s
                where s.sql_id (+) = h.sql_id
              group by h.sql_id, h.sql_opcode, h.n, h.wait_class, h.percent, s.sql_text
              order by n desc, sql_id desc";
   } elseif ($_POST['type'] === 'top-session') {
      $query = "select h.*, u.username from (
                  select h1.session_id || ',' ||  h1.session_serial# session_id, h2.program, nvl(h2.".$query_mod2.",'CPU') wait_class, user_id, round(count(*)/:sum_activity*100,2) percent, n from (
                    select * from (
                        select session_id, session_serial#, count(*) n from v\$active_session_history
                         where sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                           and sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')".$query_mod1."
                         group by session_id, session_serial#
                         order by 3 desc
                     )  where rownum <= 10 ) h1, v\$active_session_history h2
                     where h1.session_id = h2.session_id
                       and h1.session_serial# = h2.session_serial#
                       and sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                       and sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')".$query_mod1."
                     group by h1.session_id, h1.session_serial#, h2.program, nvl(h2.".$query_mod2.",'CPU'), n, user_id) h, dba_users u
                  where u.user_id = h.user_id
                  order by n desc, session_id desc";
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
   print "<thead><tr>";

   // Headers
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
         $bg = $_POST["eventColors"][$key];
         print "<div style='background:$bg;width:$value%;float:left;'>&nbsp;</div>";
      }
      print "</td><td>";
      print "<div style=''>".round($position["PERCENT_TOTAL"],2). "%</div></td>";
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
   $end_time = microtime(true);

   print "</table>";
   print "<div align='right'><font style='font-family: Tahoma,Verdana,Helvetica,sans-serif;font-size:9px' color='gray'>Total Sample Count: $sum_activity, Returned in: ".round($end_time - $start_time,2) . "s</font></div>";
?>
