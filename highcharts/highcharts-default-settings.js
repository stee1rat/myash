Highcharts.setOptions({
   global: {
      useUTC: true
   },
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
      zoomType: 'x'
   },
   exporting: {
      enabled: false
   },
   title: {
      text: '',
      style: {fontSize:"12px"}
   },
   subtitle: {
      text: ''
   },
   xAxis: {
      type: "datetime",
      labels: {
         formatter:function() {
            return Highcharts.dateFormat('%H:%M', this.value);
         }
      }
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
         }
      },
      series: {
         states: {
            hover: {
               enabled: false
            }
         }
      }
   }
});
