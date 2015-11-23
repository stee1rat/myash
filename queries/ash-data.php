<?php
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
?>
