<!DOCTYPE HTML>
<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>ASH</title>

		<script type="text/javascript" src="jquery/jquery-2.1.4.min.js"></script>

      <script type="text/javascript">
         var xash;
         var chart;

         function request_table(name, minDate, maxDate, waitclass) {
            eventColors = {};

            for (var i in chart.legend.allItems) {
               eventColors[chart.legend.allItems[i]["name"]] = chart.legend.allItems[i]["color"];
            }

            $.post('ash-' + name + '.php',
               {
                  host:$("#host").val(),
                  port:$("#port").val(),
                  service:$("#service").val(),
                  username:$("#username").val(),
                  password:$("#password").val(),
                  startdate:minDate,
                  enddate:maxDate,
                  waitclass:waitclass,
                  eventColors:eventColors
               },
              function(data) {
                 $("#" + name).html("").append(data);
              });
         }

         function clear_outputs() {
            $("#selected_interval").html("");
            $("#top-sql").html("");
            $("#report").html("")
            $("#top-session").html("");
         }

         function formatDate(d) {
            return ('0' + d.getUTCDate()).slice(-2) + '.' +
                   ('0' + (d.getUTCMonth() + 1)).slice(-2) + '.' +
                   d.getUTCFullYear() + ' ' +
                   ('0' + d.getUTCHours()).slice(-2) + ':' +
                   ('0' + d.getUTCMinutes()).slice(-2) + ':' +
                   ('0' + d.getUTCSeconds()).slice(-2);
         }

         function historicalDays() {
            $.post('ash-dbid.php',
                  {host:$("#host").val(),
                   port:$("#port").val(),
                   service:$("#service").val(),
                   username:$("#username").val(),
                   password:$("#password").val(),
                   dbid:$("#dbid").val()},
                   function(data) {
                     $("#day").html(data);
                   });
         }

         function plot(waitclass) {
            if (typeof(xash)!=='undefined') {
               xash.abort();
            }

            xash = $.post('ash-data.php',
               {
                  host:$("#host").val(),
                  port:$("#port").val(),
                  service:$("#service").val(),
                  username:$("#username").val(),
                  password:$("#password").val(),
                  waitclass:waitclass
               },
               function(json) {
                   (function (Highcharts) {
                       var each = Highcharts.each;

                       Highcharts.wrap(Highcharts.Legend.prototype, 'renderItem', function (proceed, item) {

                           proceed.call(this, item);

                           var isPoint = !!item.series,
                               collection = isPoint ? item.series.points : this.chart.series,
                               groups = isPoint ? ['graphic'] : ['group', 'markerGroup'],
                               element = item.legendGroup.element;

                           element.onmouseover = function () {
                              each(collection, function (seriesItem) {
                                   if (seriesItem !== item) {
                                       each(groups, function (group) {
                                           seriesItem[group].animate({
                                               opacity: 0.25
                                           }, {
                                               duration: 150
                                           });
                                       });
                                   }
                               });
                           }
                           element.onmouseout = function () {
                              each(collection, function (seriesItem) {
                                   if (seriesItem !== item) {
                                       each(groups, function (group) {
                                           seriesItem[group].animate({
                                               opacity: 1
                                           }, {
                                               duration: 50
                                           });
                                       });
                                   }
                               });
                           }

                       });
                   }(Highcharts));

                  chart = new Highcharts.Chart({
                             credits: {
                                 "enabled": false
                             },
                             legend: {
                                layout: 'vertical',
                                align: 'right',
                                verticalAlign: 'middle'
                             },
                             tooltip: {
                                enabled: false
                             },
                             chart: {
                                type: 'area',
                                renderTo: "container",
                                zoomType: 'x',
                                events: {
                                   selection: function(event) {
                                      var d = new Date(0);
                                      d.setUTCMilliseconds(json.xAxis.categories[0] + Math.floor(event.xAxis[0].min)*15*1000);
                                      minDate = formatDate(d);
                                      //$("#report").append("min: " + minDate);

                                      var d = new Date(0);
                                      d.setUTCMilliseconds(json.xAxis.categories[0] + Math.floor(event.xAxis[0].max)*15*1000);

                                      maxDate = formatDate(d);

                                      $("#selected_interval").html("Selected interval: " + minDate + " to " + maxDate);

                                      request_table('top-sql',minDate,maxDate,waitclass);
                                      request_table('top-session',minDate,maxDate,waitclass);

                                      return false;
                                   }
                                }
                             },
                             exporting: {
                                 enabled: false
                             },
                             title: {
                                 //text: json.instance,
                                 text: '',
                                 style: {fontSize:"12px"}
                             },
                             subtitle: {
                                 text: ''
                             },
                             xAxis:{
                                 type:"datetime",
                                 labels:{
                                    formatter:function() {
                                       return Highcharts.dateFormat('%H:%M', this.value);
                                    }
                                 },
                                 categories: json.xAxis.categories
                             },
                             yAxis: {
                                 title: {
                                     text: 'Active Sessions'
                                 }
                             },
                             plotOptions: {
                                 area: {
                                     stacking: 'normal',
                                     lineColor: '#666666',
                                     lineWidth: 0,
                                     marker: {
                                         enabled: false,
                                         lineWidth: 1,
                                         lineColor: '#666666'
                                     },
                                     events: {
                                       legendItemClick: function(event) {
                                         if (waitclass === undefined) {
                                            plot(event.target.name);
                                         }

                                         return false;
                                      }
                                    }
                                 },
                                 series: {
                                    states: {
                                       hover: {
                                          enabled: false
                                       }
                                    }
                                 }
                             },
                     series: json.series
                  });

                  clear_outputs();

                  var d = new Date(0);
                  d.setUTCMilliseconds(json.xAxis.categories[0] + chart.xAxis[0].max*15*1000);
                  maxDate = formatDate(d);

                  var d = new Date(d - 600000);
                  minDate = formatDate(d);

                  $("#selected_interval").html("Selected interval: " + minDate + " to " + maxDate);

                  $("#instance_name").html(json.instance + ' ');
                  $("#TD_AWR").css("visibility","visible");
                  request_table('top-sql',minDate,maxDate,waitclass);
                  request_table('top-session',minDate,maxDate,waitclass);
               },
               "json")
               .fail(function(err) {
                 $("#container").html(err.responseText);
               });

               $("#AWR").click(function() {
               if ($("#AWR").is(':checked')) {
                  $("#dbid").attr('disabled', false);
                  $("#day").attr('disabled', false);
               } else {
                  $("#dbid").attr('disabled', true);
                  $("#day").attr('disabled', true);
               }
            });

            if (waitclass === undefined) {
               $("#dbid").attr('disabled', true);
               $("#day").attr('disabled', true);
               $("#AWR").attr('checked', false);

               $.post('ash-dbid.php',{
                      host:$("#host").val(),
                      port:$("#port").val(),
                      service:$("#service").val(),
                      username:$("#username").val(),
                      password:$("#password").val()
                     },
                     function(data) {
                        $("#dbid").html(data);
                        historicalDays();
                     });
            }
         }

         $(document).ready(function() {
            plot();

            $("#connect").click(function() {
               plot();
            });

            $("#dbid").change(function() {
               historicalDays();
            });

            $("#day").change(function() {
               console.log($("#day").val());
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

<script src="highcharts/highcharts.js"></script>

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
         <div id="instance_name" style='font-size:14px;'>&nbsp;</div>
      </td>
   </tr>
   <tr>
      <td style="vertical-align:middle;visibility:hidden" align="right" id="TD_AWR">
         <input type="checkbox" align="right" id="AWR" style="vertical-align:middle;bottom:1px;position:relative"/>Historical
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
            <div id="selected_interval"> </div><br/>
         </td>
      </tr>
      <td valign="top">
         <div id="top-sql"></div>
      </td>
      <td valign="top">
         <div id="top-session"  style='margin-left:20px'></div>
      </td>
   </tr>
</table>
<br>
<div id="report" style="min-width: 310px; margin: 0 auto; padding-left:70px"></div>
</body>
</html>
