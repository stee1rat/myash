<?php
   // Connect to the database and define $connect variable
   include('ash-connect.php');

   if (!$connect) {
      $m = oci_error();
      trigger_error(htmlentities($m['message']), E_USER_ERROR);
   }

   // Define $query_mod1 and $query_mod2 variables
   include('ash-query-mods.php');

   $query = "alter session set nls_date_format = 'DD.MM.YYYY HH24:MI:SS'";
   $statement = oci_parse($connect, $query);
   oci_execute($statement);

   $query = <<<SQL
SELECT 'Connected to: ' || instance_name || '@' || host_name || ', Version: ' || version instance,
        trunc(sysdate - 1/24,'MI') start_date,
        nvl(cpu_core_count_current,cpu_count_current) cpu_max
  FROM v\$instance, v\$license
SQL;

   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   oci_fetch_all($statement, $instance);

   $start_date = $instance["START_DATE"][0];

   $query = <<<SQL
SELECT to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') + LEVEL/24/60/60*15 mm FROM dual CONNECT BY LEVEL <= 60*4
SQL;

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);
   oci_fetch_all($statement, $dates);

   $query = <<<SQL
SELECT to_char(sample_time - numtodsinterval(mod(extract(second FROM Cast(sample_time AS TIMESTAMP)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_time,
       nvl(wait_class,'rollup') wait_class,
       round(sum(sessions)) sessions,
       round(avg(sessions)) avg_ses,
       round(count(distinct sample_time)) samples
  FROM (SELECT sample_time, nvl({$query_mod2},'CPU') wait_class, count(*) sessions
          FROM v\$active_session_history
        WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') {$query_mod1}
         GROUP BY sample_time, nvl({$query_mod2},'CPU'))
GROUP BY ROLLUP(to_char(sample_time - numtodsinterval(mod(extract(second FROM cast(sample_time AS timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS'),
          wait_class)
ORDER BY 1,2
SQL;

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);

   $waits = array();
   $avg_sess = array();
   $bytime = array();
   while (($row = oci_fetch_assoc($statement))) {
      if ($row["WAIT_CLASS"] != 'rollup') {
         $bytime[$row["SAMPLE_TIME"]][$row["WAIT_CLASS"]]  = $row["AVG_SES"];
         $bytime[$row["SAMPLE_TIME"]]["Overall"] = $bytime[$row["SAMPLE_TIME"]]["Overall"] + $row["AVG_SES"];
      } else {
         $bytime[$row["SAMPLE_TIME"]]["AvgSess"] = round($row["SESSIONS"]/$row["SAMPLES"],1);
      }
   }

   foreach ($bytime as $date) {
      foreach ($date as $key => $value) {
         if ($key != 'Overall' and $key != 'AvgSess') {
            $wait_classes[$key] = 0;
         }
      }
   }

   foreach ($dates["MM"] as $date) {
      $datetime=DateTime::createFromFormat('d.m.Y H:i:s',$date);
      $datetime->modify('+1 hour');

      foreach($wait_classes as $wait_class => $value) {
          if (isset($bytime[$date][$wait_class])) {
             $pct = ($bytime[$date][$wait_class]/(int)$bytime[$date]["Overall"])*100;
             $avg_sess = round($bytime[$date]["AvgSess"]/100*$pct,2);
             $waits[$wait_class][] = array($datetime->getTimestamp()*1000,$avg_sess);
          } else {
             $waits[$wait_class][] = array($datetime->getTimestamp()*1000,0);
          }
      }
   }

   $options = array();
   $options['series'] = array();
   $series = array();

   foreach($wait_classes as $wait_class => $value) {
      $series = array();
      $series["name"] = $wait_class;
      $series["lineWidth"] = 1;
      $series["animation"] = false;
      $series["data"] = $waits[$wait_class];

      switch ($wait_class) {
         case 'User I/O':
            $series["color"] = "blue";
            $series["index"] = 19;
            break;
         case 'Commit':
            $series["color"] = "Orange";
            $series["index"] = 1;
            break;
         case 'CPU':
            $series["color"] = "#00CC00";
            $series["index"] = 20;
            break;
         case 'System I/O':
            $series["color"] = "Cyan";
            $series["index"] = 18;
            break;
         case 'Concurrency':
            $series["color"] = "#800000";
            $series["index"] = 16;
            break;
         case 'Application':
            $series["color"] = "#FF0000";
            $series["index"] = 16;
            break;
         case 'Other':
            $series["color"] = "Pink";
            break;
         case 'Configuration':
            $series["color"] = "#5c3317";
            $series["index"] = 0;
            break;
      }

      array_push($options['series'], $series);
   }

   $options['instance'] = $instance['INSTANCE'];

   print json_encode($options);
?>