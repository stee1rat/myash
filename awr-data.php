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
        trunc(sysdate - 1/24,'MI') start_date
  FROM v\$instance, v\$license
SQL;

   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   oci_fetch_all($statement, $instance);

   $start_date = $instance["START_DATE"][0];

   $query = <<<SQL
SELECT to_char(trunc(sample_time - numtodsinterval(mod(extract(minute FROM Cast(sample_time AS TIMESTAMP)), 5), 'minute'),'MI'), 'DD.MM.YYYY HH24:MI:SS') sample_time,
       nvl(wait_class,'rollup') wait_class,
       round(sum(sessions)) sessions,
       round(avg(sessions)) avg_ses,
       round(count(distinct sample_time)) samples
  FROM (SELECT sample_time, nvl(wait_class,'CPU') wait_class, count(*) sessions
          FROM dba_hist_active_sess_history
         WHERE snap_id between 16587 and 16730
         and dbid = 3647405474
         and instance_number = 1
         GROUP BY sample_time, nvl(wait_class,'CPU'))
GROUP BY ROLLUP(to_char(trunc(sample_time - numtodsinterval(mod(extract(minute FROM Cast(sample_time AS TIMESTAMP)), 5), 'minute'),'MI'), 'DD.MM.YYYY HH24:MI:SS'),
                wait_class)
ORDER BY 1,2
SQL;

   $statement = oci_parse($connect, $query);
   //oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);

   //print "<pre>";
   //print $query;
   //print "<pre>";

   $history = array();
   while (($row = oci_fetch_assoc($statement))) {
      if ($row["WAIT_CLASS"] != 'rollup') {
         $history[$row["SAMPLE_TIME"]][$row["WAIT_CLASS"]]  = $row["AVG_SES"];

         if (!isset($history[$row["SAMPLE_TIME"]]["Overall"])) {
            $history[$row["SAMPLE_TIME"]]["Overall"]  = $row["AVG_SES"];
         } else {
            $history[$row["SAMPLE_TIME"]]["Overall"] = $history[$row["SAMPLE_TIME"]]["Overall"] + $row["AVG_SES"];
         }
      } else {
         $history[$row["SAMPLE_TIME"]]["AvgSess"] = round($row["SESSIONS"]/$row["SAMPLES"],1);
      }
   }

   foreach ($history as $date) {
      foreach ($date as $key => $value) {
         if ($key != 'Overall' and $key != 'AvgSess') {
            $wait_classes[$key] = 0;
         }
      }
   }

   $query = <<<SQL
SELECT to_date('13.06.2015','DD.MM.YYYY') + LEVEL/24/60*5 mm FROM dual CONNECT BY LEVEL <= 24*60/5
SQL;

   $statement = oci_parse($connect, $query);
   //oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);
   oci_fetch_all($statement, $dates);

   $waits = array();
   foreach ($dates["MM"] as $date) {
      $datetime=DateTime::createFromFormat('d.m.Y H:i:s',$date);
      //$datetime->modify('+1 hour');

      foreach($wait_classes as $wait_class => $value) {
          if (isset($history[$date][$wait_class])) {
             $pct = ($history[$date][$wait_class]/(int)$history[$date]["Overall"])*100;
             $avg_sess = round($history[$date]["AvgSess"]/100*$pct,2);
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
