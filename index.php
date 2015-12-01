<!DOCTYPE HTML>
<html>
   <head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
      <title>ASH</title>

      <link rel="stylesheet" type="text/css" href="style.css">

      <script type='text/javascript' src='jquery/jquery-2.1.4.min.js'></script>
      <script type='text/javascript' src='highcharts/highcharts.js'></script>
      <script type='text/javascript' src='highcharts/legend-highlight.js'></script>
      <script type='text/javascript' src='highcharts/highcharts-default-settings.js'></script>

      <script type='text/javascript'>
         var xash;
         var chart;

         function clear() {
            $('#top-sql').html('');
            $('#top-session').html('');
            $('#selected-interval').html('');
         }

         function topTable(name, minDate, maxDate, waitClass) {

            eventColors = {};

            for (var i in chart.legend.allItems) {
               eventColors[chart.legend.allItems[i]['name']] = chart.legend.allItems[i]['color'];
            }

            jsonData = { 'type'       : name,
                         'host'       : $('#host').val(),
                         'port'       : $('#port').val(),
                         'service'    : $('#service').val(),
                         'username'   : $('#username').val(),
                         'password'   : $('#password').val(),
                         'startDate'  : minDate,
                         'endDate'    : maxDate,
                         'waitClass'  : waitClass,
                         'eventColors': eventColors };

             if (!$('#historical').prop('checked')) {
                jsonData['data'] = 'ash';

                $.post('ash-top.php', jsonData, function(data) {
                  $('#' + name).html('').append(data);
                });
             } else {
                jsonData['data'] = 'awr';
                jsonData['dbid'] = $('#dbid').val();
                jsonData['day']  = $('#day').val();

                $.post('awr-top.php', jsonData, function(data) {
                  $('#' + name).html('').append(data);
                });
             }
         }

         function availableSnapshots() {
            jsonData = { 'host'     : $('#host').val(),
                         'port'     : $('#port').val(),
                         'service'  : $('#service').val(),
                         'username' : $('#username').val(),
                         'password' : $('#password').val(),
                         'dbid'     : $('#dbid').val() } ;

            $.post('dbid.php', jsonData, function(data) {
               $('#day').html(data);
               if ($('#historical').prop('checked')) {
                  $('#day').trigger('change');
               }
            });
         }

         function plot(waitClass) {
            if (typeof(xash)!=='undefined') {
               xash.abort();
            }

            if (typeof(chart)!=='undefined') {
               chart.showLoading();
            }

            jsonData = { 'host'      : $('#host').val(),
                         'port'      : $('#port').val(),
                         'service'   : $('#service').val(),
                         'username'  : $('#username').val(),
                         'password'  : $('#password').val(),
                         'waitClass' : waitClass } ;

            if (!$('#historical').prop('checked')) {
               jsonData['data'] = 'ash';
            } else {
               jsonData['data'] = 'awr';
               jsonData['dbid'] = $('#dbid').val();
               jsonData['day']  = $('#day').val();
            }

            xash = $.post('data.php', jsonData, function(json) {
               chart = new Highcharts.Chart({
                  chart: {
                     events: {
                        selection: function(event) {
                           minDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', event.xAxis[0].min);
                           maxDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', event.xAxis[0].max);

                           $('#selected-interval').html('Selected interval: ' + minDate + ' to ' + maxDate);

                           topTable('top-sql',minDate,maxDate,waitClass);
                           topTable('top-session',minDate,maxDate,waitClass);

                           return false;
                        }
                     }
                  },
                  plotOptions: {
                     area: {
                        events: {
                           legendItemClick: function(event) {
                              if (waitClass === undefined) {
                                 plot(event.target.name);
                              }
                              return false;
                           }
                        }
                     }
                  },
                  series: json.series
               });

               clear();

               if ($('#historical').prop('checked')) {
                  selectedInterval = 120;
               } else {
                  selectedInterval = 5;
               }

               minDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', json.maxDate - selectedInterval*60*1000);
               maxDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', json.maxDate);

               $('#selected-interval').html('Selected interval: ' + minDate + ' to ' + maxDate);
               $('#instance-name').html(json.instance);

               topTable('top-sql',minDate,maxDate,waitClass);
               topTable('top-session',minDate,maxDate,waitClass);

               $('#awr-dates').css('visibility','visible');
            },
            'json')
            .fail(function(err) {
               $('#container').html(err.responseText);
            });
         }

         $(document).ready(function() {
            $('#connect').click(function() {
               $('#historical').attr('checked', false);
               $('#dbid').attr('disabled', true);
               $('#day').attr('disabled', true);

               jsonData = { 'host'     : $('#host').val(),
                            'port'     : $('#port').val(),
                            'service'  : $('#service').val(),
                            'username' : $('#username').val(),
                            'password' : $('#password').val() };

               $.post('dbid.php', jsonData, function(data) {
                  $('#dbid').html(data);
                  availableSnapshots();
               });

               plot();
            });

            $('#connect').trigger('click');

            $('#dbid').change(function() {
               availableSnapshots();
            });

            $('#day').change(function() {
               plot();
            });

            $('#historical').click(function() {
               if (this.checked) {
                  $('#dbid').attr('disabled', false);
                  $('#day').attr('disabled', false);
                  $('#day').trigger('change');
               } else {
                  $('#dbid').attr('disabled', true);
                  $('#day').attr('disabled', true);
                  $('#connect').trigger('click');
               }
            });
         });

      </script>

   </head>
   <body style='font-size:12px;font-family: Tahoma,Verdana,Helvetica,sans-serif;'>
      <table width='100%' border=0>
         <tr style='vertical-align:top' align='left' >
            <td>
               <table>
                  <tr>
                     <td align='right' nowrap >
                        Host: <input type='text' id='host' value='127.0.0.1' size='10' />
                     </td>
                     <td nowrap>
                        Port: <input type='text' id='port' value='1521' size='10'/>
                     </td>
                     <td nowrap>
                        Service name: <input type='text' id='service' value='orcl' size='10'/>
                     </td>
                     <td align='right' nowrap>
                        Username: <input type='text' id='username' value='system' size='10'/>
                     </td>
                     <td nowrap>
                        Password: <input type='password' id='password' value='123456' size='10'/>
                     </td>
                     <td nowrap>
                        <button type='button' id='connect'>Connect</button>
                     </td>
                  </tr>
               </table>

            </td>
         </tr>
         <tr>
            <td style='vertical-align:middle' align='middle' height='40px'>
               <div id='instance-name' style='font-size:14px;'>&nbsp;</div>
            </td>
         </tr>
         <tr>
            <td style='vertical-align:middle;visibility:hidden' align='right' id='awr-dates'>
               <input type='checkbox' align='right' id='historical' style='vertical-align:middle;bottom:1px;position:relative'/>Historical
               &nbsp;&nbsp;<select id='dbid' disabled></select>
               &nbsp;&nbsp;<select id='day' disabled></select>
            </td>
         </tr>
      </table>

      <div id='container' style='min-width: 310px; height: 300px; margin: 0 auto'></div>

      <table align='center'>
         <tr>
            <tr>
               <td style='text-align:center' colspan=2>
                  <div id='selected-interval'> </div><br/>
               </td>
            </tr>
            <td valign='top'>
               <div id='top-sql'></div>
            </td>
            <td valign='top'>
               <div id='top-session' style='margin-left:20px'></div>
            </td>
         </tr>
      </table>
   </body>
</html>
