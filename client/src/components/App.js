import React from 'react';
import axios from 'axios';

import SelectedInterval from './SelectedInterval';
import TopSessionTable from './TopSessionTable';
import ConnectStatus from './ConnectStatus';
import ConnectError from './ConnectError';
import TopSQLTable from './TopSQLTable';
import TopActivity from './TopActivity';
import AWRDates from './AWRDates';

import Typography from '@material-ui/core/Typography';

class App extends React.Component {

  constructor(props) {
    super(props);
    this.state = {
      connectionParameters: {
        host: "127.0.0.1",
        port: "1521",
        sid: "orcl",
        user: "",
        pass: ""
      },
      connected: {
        host: "",
        sid: "",
        version: "",
        maximumCPU: "",
      },
      selected: {
        minDate: "",
        maxDate: ""
      },
      updateChart: true,
      connectionOK: false,
      intervalSelected: false,
      byEvent: '',
      historical: false,
      historicalDate: '',
      pageRefresh: 'manual',
    };
  
    this.handleConnectClick2 = this.handleConnectClick2.bind(this);
    this.handleSelection = this.handleSelection.bind(this);
    this.handleLegendClick = this.handleLegendClick.bind(this);
    this.handleDateSelect = this.handleDateSelect.bind(this);
    this.handleRefresh = this.handleRefresh.bind(this);
    this.handleHistoricalChange = this.handleHistoricalChange.bind(this);
    this.plotChart = this.plotChart.bind(this);
  }

  handleConnectClick2(host, port, sid, user, pass) {
    let newState = {
      connectionParameters: {
        host: host,
        port: port,
        sid: sid,
        user: user,
        pass: pass
      },
      intervalSelected: false,
    }
    axios.get('/version', {
      params: {
        user: user,
        password: pass,
        host: host,
        port: port,
        service: sid,
      },
    }).then((response) => {
      document.title = `ASH: ${response.data[0][0]}@${host}`;
      newState.connected = {
        host: response.data[0][1],
        sid: response.data[0][0],
        version: response.data[0][2],
        maximumCPU: response.data[0][3],
      };
      newState.connectionOK = true;
      newState.updateChart = true;
      newState.byEvent = '';
      
      if (host !== this.state.connectionParameters.host ||
          port !== this.state.connectionParameters.port ||
          sid !== this.state.connectionParameters.sid) {
         newState.historical = false;
         newState.pageRefresh = 'manual';
      }
      this.setState(newState);
    }).catch((error) => {
      newState.connected = { message: error.response.data.message };
      newState.connectionOK = false;
      newState.updateChart = false;
 
      this.setState(newState);
      document.title = 'ASH';
      console.log(error)
    });
  }

  handleSelection(minDate, maxDate) {
    if (this.state.connected.message) {
      return false;
    }

    const chart = this.refs.topactivity.refs.chart.getChart();

    this.renderPlotBand(chart, minDate, maxDate);

    this.setState({
      selected: {
        minDate: minDate,
        maxDate: maxDate,
      },
      intervalSelected: true,
    });
  }
  
  renderPlotBand(chart, minDate, maxDate) {
    chart.xAxis[0].removePlotBand('selection');
    chart.xAxis[0].addPlotBand({
      id: 'selection',
      from: minDate,
      to: maxDate,
      color: 'rgba(51,92,173,0.25)',
      zIndex: 5
    });
  }

  plotChart() {
    if (this.state.updateChart) {
      if (this.refs.topactivity) { 
        const chart = this.refs.topactivity.refs.chart.getChart();
        chart.showLoading();
      }
      
      if (typeof this._source !== typeof undefined) {
        this._source.cancel();
      }

      this._source = axios.CancelToken.source();

      axios.get('/topactivity', {
        params: {
          user: this.state.connectionParameters.user,
          password: this.state.connectionParameters.pass,
          host: this.state.connectionParameters.host,
          port: this.state.connectionParameters.port,
          service: this.state.connectionParameters.sid,
          byEvent: this.state.byEvent,
          minDate: this.state.minDate,
          maxDate: this.state.maxDate,
          historical: this.state.historical,
          date: this.state.date,
          dbid: this.state.dbid,
        },
        cancelToken: this._source.token,
      }).then((response) => {
        const chart = this.refs.topactivity.refs.chart.getChart();

        while(chart.series.length) {
          chart.series[0].remove(false);
        }

        for (let series of response.data.series) {
          chart.addSeries(series, false);
        }

        if (response.data.sessionsMax < response.data.maximumCPU) {
          chart.yAxis[0].setExtremes(0, response.data.maximumCPU, false);
        } else {
          chart.yAxis[0].setExtremes(0, null, false);
        }

        chart.yAxis[0].removePlotLine('maximum-cpu-line');
        chart.yAxis[0].addPlotLine({
          value: response.data.maximumCPU,
          color: 'salmon',
          width: 1,
          id: 'maximum-cpu-line',
          zIndex: 3,
          label: {
            useHTML: true,
            text: '<font color="red">Maximum CPU</font>',
          },
        });
       
        chart.redraw();

        let colors = {};
        for (let i in chart.legend.allItems) {
          colors[chart.legend.allItems[i]['name']] = chart.legend.allItems[i]['color'];
        }
         
        let maxDate = chart.xAxis[0].dataMax;
        if (this.state.historical) {
          for (let i = chart.series[0].yData.length; i > 0; i--) {
            maxDate = chart.series[0].xData[i];
            let sum = 0;
            for (let j = 0; j < chart.series.length; j++) {
              sum += chart.series[j].yData[i];
            } 
            if (sum !== 0 && !isNaN(sum)) break;
          }
        }

        let minDate = maxDate - (this.state.historical ? 18e5 : 3e5);
        this.renderPlotBand(chart, minDate, maxDate);

        this.setState({
          updateChart: false, 
          selected: {
            minDate: minDate, 
            maxDate: maxDate,
          }, 
          intervalSelected: true,
          colors: colors 
        });
           
        chart.hideLoading();
      }).catch((error) => {
        console.log(error)
      });
    }
  }
 
  handleLegendClick(item) {
    if (this.state.byEvent === '') {
      this.setState({
        updateChart: true, 
        byEvent: item, 
        intervalSelected: false
      });
    }
  }

  handleRefresh() {
    this.setState({
      updateChart: true, 
      intervalSelected: false
    });
  }
  
  handleHistoricalChange(event) {
    this.setState({
      updateChart: this.state.historical,
      historical: (event === 'historical'),
      pageRefresh: event,
      byEvent: '', 
      intervalSelected: false
    });
  }

  handleDateSelect(date, dbid) {
    this.setState({
      date: date,
      dbid: dbid,
      updateChart: true,
      intervalSelected: false
    });
  }

  handleTopActivityClick = e => {
    e.preventDefault();
    this.setState({
      updateChart: true, 
      intervalSelected: false,
      byEvent: '', 
    });
  }

  render() {
    return (
      <div>
        <div style={{textAlign:"center", paddingTop: "15px", paddingBottom: "5px"}}>
          <Typography variant="title" color="inherit">
            <ConnectStatus 
              host={this.state.connected.host} 
              sid={this.state.connected.sid} 
              version={this.state.connected.version} 
              connectionParameters={this.state.connectionParameters}
              onOk={this.handleConnectClick2} 
              connectionOK={this.state.connectionOK}
            /> 
          </Typography>
        </div>
      {this.state.connected.message &&
        <ConnectError  message={this.state.connected.message} />}
      {this.state.connectionOK && 
        <div style={{padding: "5px"}}>
        <div className="breadcrumb">
          <Typography variant = "subheading">
            {this.state.byEvent === "" ?
               "Top Activity" :
               <div>
                 <a
                   onClick={this.handleTopActivityClick}
                   style={{cursor: "pointer"}}
                 >
                   Top Activity
                 </a>
                 &nbsp;/ {this.state.byEvent}
               </div>}
           </Typography>
        </div>
        <div className="controls">
          <AWRDates 
            connectionParameters={this.state.connectionParameters} 
            onDateSelect={this.handleDateSelect}
            onHistoricalChange={this.handleHistoricalChange}
            handleRefresh={this.handleRefresh}
            disabled={!this.state.historical}
            pageRefresh={this.state.pageRefresh}
          />
        </div>
      <div style={{paddingTop:"45px"}}>
          <TopActivity 
            ref='topactivity' 
            onSelection={this.handleSelection} 
            onLegendClick={this.handleLegendClick}
            plotChart={this.plotChart}
            wait_class={this.state.byEvent} 
          />
          { this.state.intervalSelected &&
            <div>
              <SelectedInterval 
                minDate={this.state.selected.minDate} 
                maxDate={this.state.selected.maxDate} 
              />
              <div style={{marginLeft: '5%'}}>
                <TopSQLTable 
                  connectionParameters={this.state.connectionParameters}
                  byEvent={this.state.byEvent}
                  minDate={this.state.selected.minDate} 
                  maxDate={this.state.selected.maxDate} 
                  colors={this.state.colors}
                  historical={this.state.historical}
                  date={this.state.date}
                  dbid={this.state.dbid}
                />
                <TopSessionTable 
                  connectionParameters={this.state.connectionParameters}
                  byEvent={this.state.byEvent}
                  minDate={this.state.selected.minDate} 
                  maxDate={this.state.selected.maxDate} 
                  colors={this.state.colors}
                  historical={this.state.historical}
                  date={this.state.date}
                  dbid={this.state.dbid}
                />
              </div>
            </div> }
          </div>
        </div>
        }
     </div>
    );
  }
}

export default App;
