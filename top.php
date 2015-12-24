<?php

include('connect.php');
include('query-mods.php');

if ($_POST['data'] === 'awr') {

   $query = <<<SQL
SELECT min(snap_id) min_snap_id, max(snap_id) max_snap_id
  FROM dba_hist_snapshot
 WHERE begin_interval_time > to_date('{$_POST['startDate']}', 'DD.MM.YYYY HH24:MI:SS')
   AND begin_interval_time < to_date('{$_POST['endDate']}', 'DD.MM.YYYY HH24:MI:SS')
   AND dbid = {$_POST['dbid']}
SQL;

   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   oci_fetch_all($statement, $snapshots);

   if ($snapshots["MIN_SNAP_ID"][0] === null) {
      exit;
   }

   $min_snap_id = $snapshots["MIN_SNAP_ID"][0];
   $max_snap_id = $snapshots["MAX_SNAP_ID"][0];

}

if ($_POST['type'] === 'top-sql' && $_POST['data'] === 'awr') {

   include('sql-types.php');

   $query = <<<SQL
SELECT *
  FROM (SELECT sql_id, sql_opcode, wait_class, n, total,
               round(n/total*100,2) percent,
               total_by_sql_id/total*100 percent_total,
               dense_rank() over (order by total_by_sql_id desc, sql_id desc) as rank
          FROM (SELECT sql_id, sql_opcode, NVL({$query_mod2},'CPU') as wait_class, count(*) n,
                       sum(count(*)) over () total,
                       sum(count(*)) over (partition by sql_id,sql_opcode) total_by_sql_id
                  FROM dba_hist_active_sess_history
                 WHERE sample_time > to_date('{$_POST['startDate']}', 'DD.MM.YYYY HH24:MI:SS')
                   AND sample_time < to_date('{$_POST['endDate']}', 'DD.MM.YYYY HH24:MI:SS')
                   AND snap_id >= {$min_snap_id}
                   AND snap_id <= {$max_snap_id}
                   AND dbid = {$_POST['dbid']}
                   AND instance_number = 1
                  {$query_mod1}
                  {$query_mod3}
                 GROUP BY sql_id, sql_opcode, NVL({$query_mod2},'CPU')
                 ORDER BY 6 DESC)
        GROUP BY sql_id, sql_opcode,  wait_class, n, total, total_by_sql_id)
 WHERE rank <= 10
 ORDER BY rank
SQL;

}

if ($_POST['type'] === 'top-sql' && $_POST['data'] === 'ash') {

   include('sql-types.php');

   $query = <<<SQL
SELECT s.sql_id, sql_opcode, wait_class, n, total,
       total_by_sql_id/total*100 percent_total,
       percent, rank,
       dbms_lob.substr(sql_text,1000,1) sql_text, SUM(executions) executions,
       ROUND(SUM(s.elapsed_time)/DECODE(SUM(s.executions),0,1,SUM(s.executions))/1e6, 2) avg_time
  FROM (SELECT sql_id, sql_opcode, wait_class, n, total, total_by_sql_id,
               round(n/total*100,2) percent,
               dense_rank() over (order by total_by_sql_id desc, sql_id desc) as rank
          FROM (SELECT sql_id, sql_opcode, NVL({$query_mod2},'CPU') as wait_class, count(*) n,
                       sum(count(*)) over () total,
                       sum(count(*)) over (partition by sql_id,sql_opcode) total_by_sql_id
                  FROM v\$active_session_history
                 WHERE sample_time > to_date('{$_POST['startDate']}', 'DD.MM.YYYY HH24:MI:SS')
                   AND sample_time < to_date('{$_POST['endDate']}', 'DD.MM.YYYY HH24:MI:SS')
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

if ($_POST['type'] === 'top-session' && $_POST['data'] === 'awr') {

   $query = <<<SQL
SELECT *
FROM (SELECT session_id, program, wait_class, user_id username, n, total,
            total_by_sid/total*100 percent_total,
            round(n/total*100,2) percent,
            dense_rank() over (order by total_by_sid desc, session_id desc) as rank
       FROM (SELECT session_id || ',' || session_serial# session_id,
                    program, nvl({$query_mod2},'CPU') wait_class, user_id, count(*) n,
                    sum(count(*)) over () total,
                    sum(count(*)) over (partition by session_id  || ',' ||  session_serial#) total_by_sid
               FROM dba_hist_active_sess_history
              WHERE sample_time > to_date('{$_POST['startDate']}', 'DD.MM.YYYY HH24:MI:SS')
                AND sample_time < to_date('{$_POST['endDate']}', 'DD.MM.YYYY HH24:MI:SS')
                AND snap_id >= {$min_snap_id}
                AND snap_id <= {$max_snap_id}
                AND dbid = {$_POST['dbid']}
                AND instance_number = 1 {$query_mod1}
              GROUP BY session_id || ',' || session_serial#, program, nvl({$query_mod2},'CPU'), user_id
              ORDER BY 7 desc, 1))
WHERE rank <= 10
ORDER BY rank
SQL;

}

if ($_POST['type'] === 'top-session' && $_POST['data'] === 'ash') {

   $query = <<<SQL
SELECT h.*, u.username
FROM (SELECT session_id, program, wait_class, user_id, n, total,
             total_by_sid/total*100 percent_total,
             round(n/total*100,2) percent,
             dense_rank() over (order by total_by_sid desc, session_id desc) as rank
       FROM (SELECT session_id || ',' || session_serial# session_id,
                    program, nvl({$query_mod2},'CPU') wait_class, user_id, count(*) n,
                    sum(count(*)) over () total,
                    sum(count(*)) over (partition by session_id  || ',' ||  session_serial#) total_by_sid
               FROM v\$active_session_history
              WHERE sample_time > to_date('{$_POST['startDate']}', 'DD.MM.YYYY HH24:MI:SS')
                AND sample_time < to_date('{$_POST['endDate']}', 'DD.MM.YYYY HH24:MI:SS')
                {$query_mod1}
              GROUP BY session_id || ',' || session_serial#, program, nvl({$query_mod2},'CPU'), user_id
              ORDER BY 7 desc, 1)) h,
      dba_users u
WHERE h.user_id = u.user_id and rank <= 10
ORDER BY rank
SQL;

}

$start_time = microtime(true);

$query = removeEmptyLines($query);

$statement = oci_parse($connect, $query);
oci_execute($statement);
oci_fetch_all($statement, $results);

if (sizeof($results["N"]) <= 0) {
   exit;
}

$sum_activity = $results["TOTAL"][0];

if ($_POST['type'] === 'top-sql' && $_POST['data'] === 'awr') {

   $sqlid_list = '';

   foreach($results['SQL_ID'] as $key=>$val) {
      if (!strpos($sqlid_list, $val)) {
         $sqlid_list .= "'" . $val ."'," ;
      }
   }

   if ($sqlid_list === '') {
      exit;
   }

   $sqlid_list = rtrim($sqlid_list, ",");

   $query = <<<SQL
SELECT s.sql_id,
       SUM(executions_delta) executions,
       ROUND(SUM(s.elapsed_time_delta)/DECODE(SUM(s.executions_delta),0,1,SUM(s.executions_delta))/1e6, 2) avg_time,
       dbms_lob.substr(sql_text,1000,1) sql_text
  FROM dba_hist_sqlstat s,
       dba_hist_sqltext t
 WHERE s.sql_id IN ({$sqlid_list})
   AND snap_id >= {$min_snap_id}
   AND snap_id <= {$max_snap_id}
   AND s.dbid = {$_POST['dbid']}
   AND s.instance_number = 1
   AND s.sql_id = t.sql_id
 GROUP   BY s.sql_id, dbms_lob.substr(sql_text,1000,1)
SQL;

   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   oci_fetch_all($statement, $sqlstats_results);

   $sqlstats = array();
   foreach($sqlstats_results['SQL_ID'] as $row_number => $sqlid) {
      $sqlstats[$sqlid]['EXECUTIONS'] = $sqlstats_results['EXECUTIONS'][$row_number];
      $sqlstats[$sqlid]['AVG_TIME'] = $sqlstats_results['AVG_TIME'][$row_number];
      $sqlstats[$sqlid]['SQL_TEXT'] = $sqlstats_results['SQL_TEXT'][$row_number];
   }
}

$top = array();

if ($_POST['type'] === 'top-sql') {
   $id = 'SQL_ID';
} else {
   $id = 'SESSION_ID';
}

foreach ($results[$id] as $i => $val1) {
   foreach ($results as $col => $val2) {
      if ($col !== "WAIT_CLASS") {
         $top[$val1][$col] = $results[$col][$i];
      } else {
         $top[$val1][$col][$results['WAIT_CLASS'][$i]] = $results['PERCENT'][$i];
      }
   }
   if (isset($sqlstats)) {
      if (isset($sqlstats[$val1]["EXECUTIONS"])) $top[$val1]["EXECUTIONS"] = $sqlstats[$val1]["EXECUTIONS"];
        else $top[$val1]["EXECUTIONS"] = '';
      if (isset($sqlstats[$val1]["AVG_TIME"])) $top[$val1]["AVG_TIME"] = $sqlstats[$val1]["AVG_TIME"];
        else $top[$val1]["AVG_TIME"] = '';
      if (isset($sqlstats[$val1]["SQL_TEXT"])) $top[$val1]["SQL_TEXT"] = $sqlstats[$val1]['SQL_TEXT']; 
        else $top[$val1]["SQL_TEXT"] = '';
   }
}

print "<table class='output'>";
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

   if ($_POST['data'] === 'awr') {
      print "<th align='right'>User ID&nbsp;&nbsp;</th>";
   } else {
      print "<th align='right'>Username&nbsp;&nbsp;</th>";
   }
   print "<th align='left'>Program</th>";
}
print "</tr></thead>";

foreach ($top as $position) {
   print "<tr>";

   if ($_POST['type'] === 'top-sql') {
      print "<td><a href='#' title='".htmlspecialchars($position["SQL_TEXT"]) ."'>".$position["SQL_ID"] . "</a>&nbsp;</td>";
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
      if (isset($position["AVG_TIME"])) {
         print "<td nowrap align='right'>". number_format(round($position["AVG_TIME"],4),2,'.','') . "s</td>";
      }
   } elseif ($_POST['type'] === 'top-session') {
      print "<td nowrap align='right'>". $position["USERNAME"] . "</td>";
      print "<td nowrap>". $position["PROGRAM"] . "</td>";
   }
   print "</tr>";
}
print "</table>";

$end_time = microtime(true);

print "<div align='right'><font style='font-family: Tahoma,Verdana,Helvetica,sans-serif;font-size:9px' color='gray'>Total Sample Count: $sum_activity, Returned in: ".round($end_time - $start_time,2) . "s</font></div>";

?>