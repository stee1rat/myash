<?php
   $start_date = $_POST['startdate'];
   $end_date   = $_POST['enddate'];

   $query_mod1  = "";
   $query_mod2  = "";

   if (isset($_POST['waitclass'])) {
      $query_mod2 = "event";

      if ($_POST['waitclass'] === 'CPU') {
         $query_mod1 = "\n   and wait_class is null";
      } else {
         $query_mod1 = "\n   and wait_class = '" . $_POST['waitclass'] . "'";
      };
   } else {
      $query_mod2 = "wait_class";
   }

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
?>
