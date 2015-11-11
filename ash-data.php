<?php
   // Connect to the database and define $connect variable
   include('ash-connect.php');

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
SELECT to_char(sub1.sample_time - numtodsinterval(mod(extract(second FROM cast(sub1.sample_time AS timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_minute,
       round(avg(sub1.active_sessions),1) act_avg
  FROM (SELECT sample_id, sample_time,
               count(*) active_sessions
         FROM  v\$active_session_history
        WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') {$query_mod1}
        GROUP BY sample_id, sample_time) sub1
 GROUP BY to_char(sub1.sample_time - numtodsinterval(mod(extract( second FROM cast(sub1.sample_time AS timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS')
 ORDER BY 1
SQL;

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);
   oci_fetch_all($statement, $sysmetric_history);

   $query = <<<SQL
SELECT to_char(sample_time - numtodsinterval(mod(extract(second FROM Cast(sample_time AS TIMESTAMP)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_time,
       wait_class,
       round(avg(sessions)) sessions
  FROM (SELECT sample_time, nvl({$query_mod2},'CPU') wait_class, count(*) sessions
          FROM v\$active_session_history
         WHERE sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') {$query_mod1}
         GROUP BY sample_time, nvl({$query_mod2},'CPU'))
 GROUP BY to_char(sample_time - numtodsinterval(mod(extract(second FROM cast(sample_time AS timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS'),
          wait_class
 ORDER BY 1,2
SQL;

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);

   $nrows = oci_fetch_all($statement, $row);

   $options = array();
   $options['series'] = array();
   $waits = array();

   $wait_classes= array_unique($row['WAIT_CLASS']);

   $query = <<<SQL
WITH cte AS (SELECT to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') AS start_date FROM dual)
  SELECT start_date + LEVEL / 24 / 60 / 60 * 15 AS mm FROM cte CONNECT BY LEVEL <= 60 * 4
SQL;

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);
   oci_fetch_all($statement, $dates);

   $sysmetric_bytime = array();
   for ($i=0; $i<sizeof($sysmetric_history["SAMPLE_MINUTE"]); $i++) {
      $sysmetric_bytime[$sysmetric_history["SAMPLE_MINUTE"][$i]] = $sysmetric_history["ACT_AVG"][$i];
   }

   $bytime = array();

   for ($i=0; $i<sizeof($row["SAMPLE_TIME"]); $i++) {
      if (!isset($bytime[$row["SAMPLE_TIME"][$i]]["Overall"])) {
         $bytime[$row["SAMPLE_TIME"][$i]]["Overall"] = $row["SESSIONS"][$i];
      } else {
         $bytime[$row["SAMPLE_TIME"][$i]]["Overall"] = $bytime[$row["SAMPLE_TIME"][$i]]["Overall"] + $row["SESSIONS"][$i];
      }

      if (!isset($bytime[$row["SAMPLE_TIME"][$i]]["Count"])) {
         $bytime[$row["SAMPLE_TIME"][$i]]["Count"] = 1;
      } else {
         $bytime[$row["SAMPLE_TIME"][$i]]["Count"] = $bytime[$row["SAMPLE_TIME"][$i]]["Count"] + 1;
      }

      $bytime[$row["SAMPLE_TIME"][$i]]["AvgSess"] = $sysmetric_bytime[$row["SAMPLE_TIME"][$i]];
      $bytime[$row["SAMPLE_TIME"][$i]][$row["WAIT_CLASS"][$i]] = $row["SESSIONS"][$i];
   }

   foreach ($dates["MM"] as $date) {
      $datetime=DateTime::createFromFormat('d.m.Y H:i:s',$date);
      #$datetime->modify('+1 hour');

      foreach($wait_classes as $wait_class) {
         if (isset($bytime[$date][$wait_class])) {
            $pct = ($bytime[$date][$wait_class]/(int)$bytime[$date]["Overall"])*100;
            $avg_sess = round($sysmetric_bytime[$date]/100*$pct,2);
            $waits[$wait_class][] = array($datetime->getTimestamp()*1000,$avg_sess);
         } else {
            $waits[$wait_class][] = array($datetime->getTimestamp()*1000,0);
         }
      }
   }

   foreach($wait_classes as $wait_class) {
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
