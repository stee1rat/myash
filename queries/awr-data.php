<?php
$query = <<<SQL
SELECT to_char(trunc(sample_time - numtodsinterval(mod(extract(minute FROM Cast(sample_time AS TIMESTAMP)), 5), 'minute'),'MI'), 'DD.MM.YYYY HH24:MI:SS') sample_time,
    nvl(wait_class,'rollup') wait_class,
    round(sum(sessions)) sessions,
    round(avg(sessions)) avg_ses,
    round(count(distinct sample_time)) samples
FROM (SELECT sample_time, nvl({$query_mod2},'CPU') wait_class, count(*) sessions
       FROM dba_hist_active_sess_history
      WHERE snap_id between :min_snap_id and :max_snap_id
      and dbid = :dbid
      and instance_number = 1 {$query_mod1}
      GROUP BY sample_time, nvl({$query_mod2},'CPU'))
GROUP BY ROLLUP(to_char(trunc(sample_time - numtodsinterval(mod(extract(minute FROM Cast(sample_time AS TIMESTAMP)), 5), 'minute'),'MI'), 'DD.MM.YYYY HH24:MI:SS'),
             wait_class)
ORDER BY 1,2
SQL;
?>
