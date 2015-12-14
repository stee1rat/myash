<?php
   include('connect.php');
   include('query-mods.php');

   if ($_POST['type'] === 'top-sql') {
      include('sql-types.php');
   }

   $start_date = $_POST['startDate'];
   $end_date   = $_POST['endDate'];

   if ($_POST['type'] === 'top-sql') {

      $query = <<<SQL
SELECT s.sql_id, sql_opcode, wait_class, n, total, total_by_sql_id, percent, rank, 
       dbms_lob.substr(sql_text,1000,1) sql_text, SUM(executions) executions,
       ROUND(SUM(s.elapsed_time)/DECODE(SUM(s.executions),0,1,SUM(s.executions))/1e6, 2) avg_time
  FROM (SELECT sql_id, sql_opcode, wait_class, n, total, total_by_sql_id,
               round(n/total*100,2) percent,
               dense_rank() over (order by total_by_sql_id desc, sql_id desc) as rank
          FROM (SELECT sql_id, sql_opcode, NVL({$query_mod2},'CPU') as wait_class, count(*) n,
                       sum(count(*)) over () total,
                       sum(count(*)) over (partition by sql_id,sql_opcode) total_by_sql_id
                  FROM v\$active_session_history
                 WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                   AND sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')  
                   {$query_mod1} 
                   {$query_mod3} 
                 GROUP BY sql_id, sql_opcode, NVL({$query_mod2},'CPU')
                 ORDER BY 6 DESC)
        GROUP BY sql_id, sql_opcode,  wait_class, n, total, total_by_sql_id) h,
        v\$sqlstats s
 WHERE rank <= 10
   AND s.sql_id (+) = h.sql_id
 GROUP BY s.sql_id, sql_opcode, wait_class, n, total, total_by_sql_id, percent, rank,  dbms_lob.substr(sql_text,1000,1)
 ORDER BY rank   
SQL;
 
   }

   if ($_POST['type'] === 'top-session') {

      $query = <<<SQL
SELECT h.*, u.username
FROM (SELECT session_id, program, wait_class, user_id, n, total, total_by_sid,
            round(n/total*100,2) percent,
            dense_rank() over (order by total_by_sid desc, session_id desc) as rank
       FROM (SELECT session_id || ',' || session_serial# session_id, 
                    program, nvl({$query_mod2},'CPU') wait_class, user_id, count(*) n,
                    sum(count(*)) over () total,
                    sum(count(*)) over (partition by session_id  || ',' ||  session_serial#) total_by_sid
               FROM v\$active_session_history
              WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS')
                AND sample_time < to_date(:end_date, 'DD.MM.YYYY HH24:MI:SS')
                {$query_mod1}
              GROUP BY session_id || ',' || session_serial#, program, nvl({$query_mod2},'CPU'), user_id
              ORDER BY 7 desc, 1))h,
      dba_users u
WHERE h.user_id = u.user_id and rank <= 10
ORDER BY rank
SQL;

   }   

   $query = removeEmptyLines($query);
   
   $start_time = microtime(true);

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_bind_by_name($statement, ":end_date", $end_date);
   oci_execute($statement);
   oci_fetch_all($statement, $results);

   if (sizeof($results["N"]) <= 0) {
      exit;
   } else {
      $sum_activity = $results["TOTAL"][0];
   }
   
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
