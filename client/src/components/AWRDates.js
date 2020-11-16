import React from 'react';
import axios from 'axios';

import Button from '@material-ui/core/Button';
import NativeSelect from '@material-ui/core/NativeSelect';

class AWRDates extends React.Component {
  constructor(props) {
    super(props);
    this.state = { externalData: null };
    
    this.handleChange = this.handleChange.bind(this);
    this.handleRefresh = this.handleRefresh.bind(this);
    this.handleDateSelect = this.handleDateSelect.bind(this);
    this.handleHistoricalChange = this.handleHistoricalChange.bind(this);
  }
 
  componentDidMount() {
    this.loadAsyncData();
  }

  componentDidUpdate(prevProps, prevState) {
    if (this.props.disabled !== prevProps.disabled && !this.props.disabled) {
      this.props.onDateSelect(this.state.date, this.state.dbid);
    }
    if (JSON.stringify(prevProps.connectionParameters) !== 
        JSON.stringify(this.props.connectionParameters)) {
      this.loadAsyncData();
    }
  }
 
  handleChange(event) {
    this.setState({
      dbid: event.target.value,
      date: this.state.externalData[event.target.value].dates[0]
    });
    this.props.onDateSelect(
      this.state.externalData[event.target.value].dates[0], 
      event.target.value
    );
  }

  handleDateSelect(event) {
    this.setState({
      date: event.target.value,
    });
    this.props.onDateSelect(event.target.value, this.state.dbid);
  }

  handleHistoricalChange(event) {
    this.props.onHistoricalChange(event.target.value);
  }

  handleRefresh() {
    this.props.handleRefresh();
  }

  loadAsyncData() {
    const c = this.props.connectionParameters;
    axios.get('/snapshots', {
      params: {
        user: c.user,
        password: c.pass,
        host: c.host,
        port: c.port,
        service: c.sid,
        byEvent: this.props.byEvent,
      }
    }).then((response) => {
      let currentDatabase = null;
      Object.keys(response.data).forEach(dbid => {
        if (response.data[dbid].isCurrentDatabase) {
          currentDatabase = dbid;
        };
      });
      this.setState({
        externalData: response.data, 
        dbid: currentDatabase,
        date: response.data[currentDatabase].dates[0]
      });
    }).catch((error) => {
      console.log(error)
    });
    return null;
  }

  render() {
    if (this.state.externalData === null) {
      return null;
    } else {
      let data = this.state.externalData;
      let options = [];
      let dates = [];
      Object.keys(data).forEach(dbid => {
        options.push(<option key={dbid} value={dbid}>{dbid + ' (' + data[dbid].name + ')'}</option>);
        if (dbid === this.state.dbid) {
          for(let i = 0; i < data[dbid].dates.length; i++) {
            dates.push(<option key={i} value={data[dbid].dates[i]}>{data[dbid].dates[i]}</option>);
          }
        }
      });
      return (
        <div>
          <NativeSelect 
            value={this.props.pageRefresh}
            onChange={this.handleHistoricalChange} 
            style={{
              display: "inline-block", 
              marginTop: "0px", 
              marginRight: "5px", 
              marginBottom: "0px", 
              paddingTop: "0px",
            }}
          >
            <option value={'manual'}>Manual Refresh</option>
            <option value={'historical'}>Historical</option>
          </NativeSelect>
          <NativeSelect 
            value={this.state.dbid} 
            onChange={this.handleChange} 
            disabled={this.props.disabled}
            style={{
              display: this.props.disabled ? "none" : "inline-block",
              marginTop: "0px", 
              marginRight: "5px", 
              marginBottom: "0px", 
              paddingTop: "0px"
            }}
          >
            {options}
          </NativeSelect>
          &nbsp;
          <NativeSelect 
            value={this.state.date} 
            onChange={this.handleDateSelect} 
            disabled={this.props.disabled}
            style={{
              display: this.props.disabled ? "none" : "inline-block",
              marginTop: "0px", 
              marginRight: "5px", 
              marginBottom: "0px", 
              paddingTop: "0px"
            }}
          >
            {dates}
          </NativeSelect>
          <Button 
            color="primary" 
            size="small"
            variant="contained"
            onClick={this.handleRefresh}
            style={{ 
              display: !this.props.disabled ? "none" : "inline-block",
              marginLeft: "5px" 
            }}
          >
            Refresh
          </Button>
        </div>
      );
    } 
  }
}

export default AWRDates;
