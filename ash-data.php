<?php
   // Connect to the database and define $connect variable
   include('ash-connect.php');

   // Define $query_mod1 and $query_mod2 variables
   include('ash-query-mods.php');

   $query = "alter session set nls_date_format = 'DD.MM.YYYY HH24:MI:SS'";
   $statement = oci_parse($connect, $query);
   oci_execute($statement);

   $query = "select 'Connected to: ' || instance_name || '@' || host_name
                     || ', Version: ' || version instance,
                    trunc(sysdate - 1/24,'MI') start_date,
                    nvl(cpu_core_count_current,cpu_count_current) cpu_max
               from v\$instance, v\$license";

   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   oci_fetch_all($statement, $instance);

   $start_date = $instance["START_DATE"][0];

   $query = "select to_char(sub1.sample_time - numtodsinterval(mod(EXTRACT(
                               second FROM cast(sub1.sample_time as timestamp)),
                                  15), 'second'),
                                    'DD.MM.YYYY HH24:MI:SS') sample_minute,
                    round(avg(sub1.active_sessions),1) as act_avg
               from (select sample_id, sample_time,
                             sum(decode(session_state, 'ON CPU', 1, 0))  as on_cpu,
                             sum(decode(session_state, 'WAITING', 1, 0)) as waiting,
                             count(*) as active_sessions
                        from v\$active_session_history
                       where sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') "
                             . $query_mod1 . "
                       group by sample_id, sample_time) sub1
             group by to_char(sub1.sample_time - numtodsinterval(mod(EXTRACT(
                           second FROM cast(sub1.sample_time as timestamp)),
                             15), 'second'),
                              'DD.MM.YYYY HH24:MI:SS')
             order by 1";

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);
   oci_fetch_all($statement, $sysmetric_history);

   $query = "select to_char(sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_time,
                    wait_class,
                    round(avg(sessions)) sessions
               from (select sample_time,
                            nvl(" . $query_mod2 . ",'CPU') wait_class,
                            count(*) sessions
                       from V\$ACTIVE_SESSION_HISTORY
                      where sample_time > to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') " . $query_mod1 . "
                      group by SAMPLE_TIME,
                            nvl(" . $query_mod2 . ",'CPU'))
              group by to_char(sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS'),
                    wait_class
              order by 1,2";

   $statement = oci_parse($connect, $query);
   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_execute($statement);

   $nrows = oci_fetch_all($statement, $row);

   $options = array();
   $options['series'] = array();
   $waits = array();

   $wait_classes= array_unique($row["WAIT_CLASS"]);

   $query = "with cte as (select to_date(:start_date, 'DD.MM.YYYY HH24:MI:SS') as start_date from dual)
                  select start_date + level/24/60/60*15 as mm from cte
                  connect by level <= 60*4";

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
      $datetime=new DateTime($date);

      $datetime->modify('+3 hour');

      $xAxis['categories'][] = $datetime->format('U')*1000;

      foreach($wait_classes as $wait_class) {
         if (isset($bytime[$date][$wait_class])) {
            $pct = ($bytime[$date][$wait_class]/(int)$bytime[$date]["Overall"])*100;
            $avg_ses = round($sysmetric_bytime[$date]/100*$pct,2);
            $waits[$wait_class][] = $avg_ses;
         } else {
            $waits[$wait_class][] = 0;
         }
      }
   }
   $options['xAxis'] = $xAxis;

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

   $options['instance'] = $instance["INSTANCE"];

   print json_encode($options);
?>
