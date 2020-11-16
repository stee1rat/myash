import React from 'react';
import ReactHighcharts from 'react-highcharts';

import highlight from '../utils/highcharts-highlight.js';
highlight(ReactHighcharts.Highcharts);

class TopActivity extends React.Component {
  constructor(props) {
    super(props);
    this.handleSelection = this.handleSelection.bind(this);
    this.handleLegendClick = this.handleLegendClick.bind(this);

    this.chartOptions = require('../config/topactivity-config.js');
    this.chartOptions.chart.events = {};
    this.chartOptions.chart.events.selection = this.handleSelection;
    this.chartOptions.plotOptions.area.events = {};
    this.chartOptions.plotOptions.area.events.legendItemClick = this.handleLegendClick;
  }

  shouldComponentUpdate(nextProps, nextState) {
    this.props.plotChart();
    return false;
  }

  componentDidMount() {
    this.props.plotChart();
  }

  handleSelection(event) {
    this.props.onSelection(event.xAxis[0].min, event.xAxis[0].max);
    return false;
  }

  handleLegendClick(event) {
    this.props.onLegendClick(event.target.name);
    return false;
  }

  render() {
    return (
      <div>
        <ReactHighcharts config={this.chartOptions} ref="chart"/>
      </div>
    )
  }
}

export default TopActivity;
