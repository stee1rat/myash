<?php
   require_once('ash-connect.php');

   $query = "alter session set nls_date_format = 'DD.MM.YYYY HH24:MI:SS'";
   $statement = oci_parse($connect, $query);
   oci_execute($statement);

   $query = "select 'Connected to: ' || instance_name || '@' || host_name || ', Version: ' || version instance, trunc(sysdate - 1/24,'MI') start_date, nvl(cpu_core_count_current,cpu_count_current) cpu_max from v\$instance, v\$license";
   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   oci_fetch_all($statement, $instance);

   $start_date = "to_date('" . $instance["START_DATE"][0] . "', 'DD.MM.YYYY HH24:MI:SS')";

   if (isset($_POST["waitclass"])) {
            if ($_POST["waitclass"] == 'CPU') {
               $c = 'is null';
            } else {
               $c = "= '" . $_POST["waitclass"] . "'";
            }
            $query = "select
                         to_char(sub1.sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sub1.sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_minute,
                         round(avg(sub1.active_sessions),1) as act_avg
                      from
                         (
                           select
                              sample_id,
                              sample_time,
                              sum(decode(session_state, 'ON CPU', 1, 0))  as on_cpu,
                              sum(decode(session_state, 'WAITING', 1, 0)) as waiting,
                              count(*) as active_sessions
                           from
                              v\$active_session_history
                           where
                              sample_time > " . $start_date . "
                           and
                              wait_class ". $c ."
                           group by
                              sample_id,
                              sample_time
                         ) sub1
                      group by
                         to_char(sub1.sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sub1.sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS')
                      order by
                         1";
   } else {
          $query = "select
                         to_char(sub1.sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sub1.sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_minute,
                         round(avg(sub1.active_sessions),1) as act_avg
                      from
                         (
                           select
                              sample_id,
                              sample_time,
                              sum(decode(session_state, 'ON CPU', 1, 0))  as on_cpu,
                              sum(decode(session_state, 'WAITING', 1, 0)) as waiting,
                              count(*) as active_sessions
                           from
                              v\$active_session_history
                           where
                              sample_time > " . $start_date . "
                           group by
                              sample_id,
                              sample_time
                         ) sub1
                      group by
                         to_char(sub1.sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sub1.sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS')
                      order by
                         1";
   }

   $statement = oci_parse($connect, $query);
   oci_execute($statement);
   oci_fetch_all($statement, $sysmetric_history);

   if (isset($_POST["waitclass"])) {
      if ($_POST["waitclass"] == 'CPU') {
         $c = 'is null';
      } else {
         $c = "= '" . $_POST["waitclass"] . "'";
      }
      $query = "select to_char(sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_time,
                       nvl(event, 'CPU') wait_class,
                       round(avg(sessions)) sessions
                  from (select sample_time,
                               event,
                               count(*) sessions
                          from V\$ACTIVE_SESSION_HISTORY
                         where sample_time > " . $start_date . "
                           and wait_class ". $c ."
                         group by SAMPLE_TIME,
                               event)
                 group by to_char(sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS'),
                       event
                 order by 1,2";
   } else {
      $query = "select to_char(sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS') sample_time,
                       wait_class,
                       round(avg(sessions)) sessions
                  from (select sample_time,
                               nvl(WAIT_CLASS,'CPU') WAIT_CLASS,
                               count(*) sessions
                          from V\$ACTIVE_SESSION_HISTORY
                         where sample_time > " . $start_date . "
                         group by SAMPLE_TIME,
                               nvl(WAIT_CLASS,'CPU'))
                 group by to_char(sample_time - numtodsinterval(mod(EXTRACT(second FROM cast(sample_time as timestamp)), 15), 'second'), 'DD.MM.YYYY HH24:MI:SS'),
                       wait_class
                 order by 1,2";
   }

   $statement = oci_parse($connect, $query);
   oci_execute($statement);

   $nrows = oci_fetch_all($statement, $row);

   $options = array();
   $options['series'] = array();
   $waits = array();

   $wait_classes= array_unique($row["WAIT_CLASS"]);

   $query = "with cte as (select " . $start_date . " as start_date from dual)
               select start_date + level/24/60/60*15 as mm from cte
               connect by level <= 60*4";

   $statement = oci_parse($connect, $query);
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

      if (!isset($bytime[$row["SAMPLE_TIME"][$i]]["AvgSess"])) {
         $bytime[$row["SAMPLE_TIME"][$i]]["AvgSess"] = $sysmetric_bytime[$row["SAMPLE_TIME"][$i]];
      }

      $bytime[$row["SAMPLE_TIME"][$i]][$row["WAIT_CLASS"][$i]] = $row["SESSIONS"][$i];
   }

   //date_default_timezone_set('Europe/Moscow');
   //date_default_timezone_set('Europe/London');

   foreach ($dates["MM"] as $date) {
      //print $date;
      //$newTZ = new DateTimeZone("Europe/Moscow");

      $datetime=new DateTime($date);
      //echo $datetime->format('Y-m-d H:i:s');

      $datetime->modify('+3 hour');

      $xAxis['categories'][] = $datetime->format('U')*1000;
      //$xAxis['categories'][] = $datetime->getTimestamp()*1000;
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

      if ($wait_class == 'User I/O') {
         $series["color"] = "blue";
         $series["index"] = 19;
      } else if ($wait_class == 'Commit') {
         $series["color"] = "Orange";
         $series["index"] = 1;
      } else if ($wait_class == 'CPU') {
         $series["color"] = "#00CC00";
         $series["index"] = 20;
      } else if ($wait_class == 'System I/O') {
         $series["color"] = "Cyan";
         $series["index"] = 18;
      } else if ($wait_class == 'Concurrency') {
         $series["color"] = "#800000";
         $series["index"] = 16;
      } else if ($wait_class == 'Application') {
         $series["color"] = "#FF0000";
         $series["index"] = 16;
      } else if ($wait_class == 'Other') {
         $series["color"] = "Pink";
      } else if ($wait_class == 'Configuration') {
         $series["color"] = "#5c3317";
         $series["index"] = 0;
      }

      array_push($options['series'], $series);
   }

   /*
   $series = array();
   $series["name"] = "Maximum CPU";
   $series["showInLegend"] = false;
   $series["type"] = "scatter";
   $series["data"] = array((int)$instance["CPU_MAX"][0]);

   $marker = array();
   $marker["enabled"] = false;
   $series["marker"] = $marker;

   array_push($options['series'], $series);
   */


   $options['instance'] = '';
   $options['instance'] = $instance["INSTANCE"];
   #$options['tablespace'] = $_GET["tablespace"];

   print json_encode($options);
?>
