<?php
$query = <<<SQL
SELECT min(snap_id) min_snap_id, max(snap_id) max_snap_id
  FROM dba_hist_snapshot
WHERE trunc(begin_interval_time) = to_date(:day,'DD.MM.YYYY')
   AND dbid = :dbid
SQL;
?>
