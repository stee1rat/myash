<?php
  // Connect to the database and define $connect variable
   include('ash-connect.php');

   // Define $sum_activity, $start_date, $end_date, $query_mod1 and $query_mod2 variables
   include('ash-top-activity.php');

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

   $start_time = microtime(true);

   print "<pre>";
   print_r($query);
   print "</pre>";

   $statement = oci_parse($connect, $query);

   oci_bind_by_name($statement, ":start_date", $start_date);
   oci_bind_by_name($statement, ":end_date", $end_date);
   oci_bind_by_name($statement, ":sum_activity", $sum_activity);

   oci_execute($statement);
   $nrows = oci_fetch_all($statement, $results);

   $top = array();
   for ($i=0; $i<sizeof($results["N"]); $i++) {
      $top[$results["SESSION_ID"][$i]]["PROGRAM"] = $results["PROGRAM"][$i];
      $top[$results["SESSION_ID"][$i]]["SESSION_ID"] = $results["SESSION_ID"][$i];
      $top[$results["SESSION_ID"][$i]]["USERNAME"] = $results["USERNAME"][$i];
      $top[$results["SESSION_ID"][$i]]["PERCENT_TOTAL"] = $results["N"][$i]/$sum_activity*100;
      $top[$results["SESSION_ID"][$i]]["WAIT_CLASS"][$results["WAIT_CLASS"][$i]] = $results["PERCENT"][$i];
   }

   print "<table  class='output'>";
   print "<thead>";
   print "<tr><th align='left' nowrap>SID,Serial#&nbsp;&nbsp;</th>";
   print "<th width='150px' align='left'>Activity</th>";
   print "<th align='left'>Username&nbsp;&nbsp;</th>";
   print "<th align='left'>Program</th></tr>";
   print "</thead>";

   foreach ($top as $session) {
      print "<tr>";
      print "<td>".$session["SESSION_ID"] . "</td>";
      print "<td>";

      print "<table width=100%><tr>";
      print "<td width=100%'>";
      foreach ($session["WAIT_CLASS"] as $key => $value) {
         $bg = $_POST["eventColors"][$key];
         print "<div style='background:$bg;width:$value%;float:left;'>&nbsp;</div>";
      }
      print "</td><td>";
      print "<div style=''>".number_format(round($session["PERCENT_TOTAL"],2),2,'.',''). "%</div></td>";
      print "</td>";
      print "</tr></table>";

      print "<td nowrap>". $session["USERNAME"] . "</td>";
      print "<td nowrap>". $session["PROGRAM"] . "</td>";
      print "</tr>";
   }

   $end_time = microtime(true);

   print "</table>";
   print "<div align='right'><font style='font-family: Tahoma,Verdana,Helvetica,sans-serif;font-size:9px' color='gray'>Total Sample Count: $sum_activity, Returned in: ".round($end_time - $start_time,2) . "s</font></div>";
?>
