<!DOCTYPE HTML>
<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
      <title>ASH</title>

      <script type="text/javascript" src="jquery/jquery-2.1.4.min.js"></script>
      <script type="text/javascript" src="highcharts/highcharts.js"></script>
      <script type="text/javascript" src="highcharts/legend-highlight.js"></script>
      <script type="text/javascript" src="highcharts/highcharts-default-settings.js"></script>

      <script type="text/javascript">
         var xash;
         var chart;

         function clear() {
            $("#top-sql").html("");
            $("#top-session").html("");
            $("#selected-interval").html("");
         }

         function topTable(name, minDate, maxDate, waitclass) {
            eventColors = {};
            for (var i in chart.legend.allItems) {
               eventColors[chart.legend.allItems[i]["name"]] = chart.legend.allItems[i]["color"];
            }

            jsonData = { "type"       : name,
                         "host"       : $("#host").val(),
                         "port"       : $("#port").val(),
                         "service"    : $("#service").val(),
                         "username"   : $("#username").val(),
                         "password"   : $("#password").val(),
                         "startDate"  : minDate,
                         "endDate"    : maxDate,
                         "waitClass"  : waitclass,
                         "eventColors": eventColors };

            $.post('ash-top.php', jsonData, function(data) {
               $("#" + name).html("").append(data);
            });
         }

         function availableSnapshots() {
            $.post('ash-dbid.php', {
               host: $("#host").val(),
               port: $("#port").val(),
               service: $("#service").val(),
               username: $("#username").val(),
               password: $("#password").val(),
               dbid: $("#dbid").val()
            }, function(data) {
               $("#day").html(data);
               if ($("#historical").prop('checked')) {
                  $("#day").trigger('change');
               }
            });
         }

         function plot(waitclass) {
            if (typeof(xash)!=='undefined') {
               xash.abort();
            }

            if (typeof(chart)!=='undefined') {
               chart.showLoading();
            }

            if (!$("#historical").prop('checked')) {
               dataPage = 'ash-data.php';
            } else {
               dataPage = 'awr-data.php';
            }

            xash = $.post(dataPage, {
               host: $("#host").val(),
               port:$ ("#port").val(),
               service: $("#service").val(),
               username: $("#username").val(),
               password: $("#password").val(),
               waitClass: waitclass
            }, function(json) {
               chart = new Highcharts.Chart({
                  chart: {
                     events: {
                        selection: function(event) {
                           minDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', event.xAxis[0].min);
                           maxDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', event.xAxis[0].max);

                           $("#selected-interval").html("Selected interval: " + minDate + " to " + maxDate);

                           topTable('top-sql',minDate,maxDate,waitclass);
                           topTable('top-session',minDate,maxDate,waitclass);

                           return false;
                        }
                     }
                  },
                  plotOptions: {
                     area: {
                        events: {
                           legendItemClick: function(event) {
                              if (waitclass === undefined) {
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

               minDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', chart.xAxis[0].max - 5*60*1000);
               maxDate = Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', chart.xAxis[0].max);

               $("#selected-interval").html("Selected interval: " + minDate + " to " + maxDate);
               $("#awr-dates").css("visibility","visible");
               $("#instance-name").html(json.instance);

               topTable('top-sql',minDate,maxDate,waitclass);
               topTable('top-session',minDate,maxDate,waitclass);
            },
            "json")
            .fail(function(err) {
               $("#container").html(err.responseText);
            });

            if (waitclass === undefined) {
               $("#historical").attr('checked', false);
               $("#dbid").attr('disabled', true);
               $("#day").attr('disabled', true);

               $.post('ash-dbid.php', {
                  host: $("#host").val(),
                  port: $("#port").val(),
                  service: $("#service").val(),
                  username: $("#username").val(),
                  password: $("#password").val()
               }, function(data) {
                  $("#dbid").html(data);
                  availableSnapshots();
               });
            }
         }

         $(document).ready(function() {
            plot();

            $("#connect").click(function() {
               plot();
            });

            $("#dbid").change(function() {
               availableSnapshots();
            });

            $("#day").change(function() {
               console.log('DBID: ' + $("#dbid").val() + ', Date: ' + $("#day").val());
               plot();
            });

            $("#historical").click(function() {
               if (this.checked) {
                  $("#dbid").attr('disabled', false);
                  $("#day").attr('disabled', false)   .trigger('change');
               } else {
                  $("#dbid").attr('disabled', true);
                  $("#day").attr('disabled', true);
               }
            });
         });

      </script>

      <style>
         .output {
           border-collapse: collapse;
           border-spacing: 0;
           empty-cells: show;
           border: 1px solid #f8f8f8;
         }

         .output thead {
           background-color: #f8f8f8;
           color: #000;
           text-align: left;
           vertical-align: bottom;
         }

         input, button {
            vertical-align:middle;
            bottom:1px;
            position:relative;
         }
       </style>
   </head>
   <body style='font-size:12px;font-family: Tahoma,Verdana,Helvetica,sans-serif;'>
      <table width="100%" border=0>
         <tr style="vertical-align:top" align="left" >
            <td>
               <table>
                  <tr>
                     <td align="right" nowrap >
                        Host: <input type="text" id="host" value="127.0.0.1" size="10" />
                     </td>
                     <td nowrap>
                        Port: <input type="text" id="port" value="1521" size="10"/>
                     </td>
                     <td nowrap>
                        Service name: <input type="text" id="service" value="orcl" size="10"/>
                     </td>
                     <td align="right" nowrap>
                        Username: <input type="text" id="username" value="system" size="10"/>
                     </td>
                     <td nowrap>
                        Password: <input type="password" id="password" value="123456" size="10"/>
                     </td>
                     <td nowrap>
                        <button type="button" id="connect">Connect</button>
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
         <tr>
            <td style="vertical-align:middle" align="middle" height="40px">
               <div id="instance-name" style='font-size:14px;'>&nbsp;</div>
            </td>
         </tr>
         <tr>
            <td style="vertical-align:middle;visibility:hidden" align="right" id="awr-dates">
               <input type="checkbox" align="right" id="historical" style="vertical-align:middle;bottom:1px;position:relative"/>Historical
               &nbsp;&nbsp;<select id='dbid' disabled></select>
               &nbsp;&nbsp;<select id='day' disabled></select>
            </td>
         </tr>
      </table>

      <div id="container" style="min-width: 310px; height: 300px; margin: 0 auto"></div>

      <table align="center">
         <tr>
            <tr>
               <td style="text-align:center" colspan=2>
                  <div id="selected-interval"> </div><br/>
               </td>
            </tr>
            <td valign="top">
               <div id="top-sql"></div>
            </td>
            <td valign="top">
               <div id="top-session" style='margin-left:20px'></div>
            </td>
         </tr>
      </table>
   </body>
</html>
