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
    } else {
       jsonData['data'] = 'awr';
       jsonData['dbid'] = $('#dbid').val();
       jsonData['day']  = $('#day').val();
    }

   $.post('top.php', jsonData, function(data) {
      $('#' + name).html('').append(data);
   });
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

   if (typeof(waitClass)!=='undefined') {
      $('#top-activity').css('visibility','visible');
   } else {
      $('#top-activity').css('visibility','hidden');
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

   $('#top-activity').click(function() {
      plot();
   });

   $('#historical').click(function() {
      if (this.checked) {
         $('#dbid').attr('disabled', false);
         $('#day').attr('disabled', false);
         plot();
      } else {
         $('#dbid').attr('disabled', true);
         $('#day').attr('disabled', true);
         $('#connect').trigger('click');
      }
   });
});