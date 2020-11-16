module.exports = {
   global: {
      useUTC: true
   },
   credits: {
      "enabled": false
   },
   legend: {
      layout: 'vertical',
      align: 'right',
      verticalAlign: 'middle',
      symbolRadius: 0
   },
   tooltip: {
      enabled: false
   },
   chart: {
      type: 'area',
      renderTo: "container",
      zoomType: 'x',
      marginTop: 20,
      height: '350px',
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
   },
   yAxis: {
      gridLineWidth: 1,
      title: {
         text: 'Active Sessions'
      },
      __plotLines: [{color: 'red', width:1, value: 54, zIndex: 20, label: {text: 'CPU Cores', style: { color: 'red'}}}]
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
         animation: false,
         states: {
            hover: {
               enabled: false
            }
         }
      }
   },
  series: []
};
