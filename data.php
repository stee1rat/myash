<?php
   // Connect to the database and define $connect variable
   include('ash-connect.php');

   // Define $query_mod1 and $query_mod2 variables
   include('ash-query-mods.php');

   $query = "alter session set nls_date_format = 'DD.MM.YYYY HH24:MI:SS'";
   $statement = oci_parse($connect, $query);
   oci_execute($statement);

   if ($_POST['data'] === 'ash') {
      include('queries/instance-name.php');
      $statement = oci_parse($connect, $query);
      oci_execute($statement);
      oci_fetch_all($statement, $instance);

      $start_date = $instance["START_DATE"][0];

      include('queries/ash-data.php');
      $statement = oci_parse($connect, $query);
      oci_bind_by_name($statement, ":start_date", $start_date);
   } else if ($_POST['data'] === 'awr') {
      include('queries/min-max-snapshots.php');
      $statement = oci_parse($connect, $query);
      oci_bind_by_name($statement, ':dbid', $_POST['dbid']);
      oci_bind_by_name($statement, ':day', $_POST['day']);
      oci_execute($statement);
      oci_fetch_all($statement, $snapshots);

      $min_snap_id = $snapshots["MIN_SNAP_ID"][0];
      $max_snap_id = $snapshots["MAX_SNAP_ID"][0];

      include('queries/awr-data.php');
      $statement = oci_parse($connect, $query);
      oci_bind_by_name($statement, ':dbid', $_POST['dbid']);
      oci_bind_by_name($statement, ':min_snap_id', $min_snap_id);
      oci_bind_by_name($statement, ':max_snap_id', $max_snap_id);
   }

   oci_execute($statement);

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

   if ($_POST['data'] === 'ash') {
      include('queries/ash-timeline.php');
      $statement = oci_parse($connect, $query);
      oci_bind_by_name($statement, ":start_date", $start_date);
   } else if ($_POST['data'] === 'awr') {
      include('queries/awr-timeline.php');
      $statement = oci_parse($connect, $query);
      oci_bind_by_name($statement, ":day",  $_POST['day']);
   }

   oci_execute($statement);
   oci_fetch_all($statement, $dates);

   $waits = array();
   foreach ($dates["MM"] as $date) {
      $datetime=DateTime::createFromFormat('d.m.Y H:i:s',$date);

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

   if ($_POST['data'] === 'ash') {
      $options['instance'] = $instance['INSTANCE'];
   }

   print json_encode($options);
?>
