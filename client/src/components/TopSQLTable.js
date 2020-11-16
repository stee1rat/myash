import React from 'react';
import axios from 'axios';
import TopSQLTableRow from './TopSQLTableRow';
import sqltypes from '../utils/sql-opcodes.js';

class TopSQLTable extends React.Component {
  state = {
    externalData: null,
  };
 
  componentDidMount() {
    this.loadAsyncData();
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevProps.minDate !== this.props.minDate && 
        prevProps.maxDate !== this.props.maxDate) {
      this.loadAsyncData();
    }
  }

  loadAsyncData() {
    const c = this.props.connectionParameters;

    if (typeof this._source !== typeof undefined) {
      this._source.cancel();
    }

    this._source = axios.CancelToken.source();

    axios.get('/topsql', {
      params: {
        user: c.user,
        password: c.pass,
        host: c.host,
        port: c.port,
        service: c.sid,
        byEvent: this.props.byEvent,
        minDate: this.props.minDate,
        maxDate: this.props.maxDate,
        historical: this.props.historical,
        date: this.props.date,
        dbid: this.props.dbid,
      },
      cancelToken: this._source.token,
    }).then((response) => {
      this.setState({externalData: response.data.top});
    }).catch((error) => {
      console.log(error)
    });
    return null;
  }

  render() {
    if (this.state.externalData === null) {
      return null;
    } else {
      const top = this.state.externalData;
      let rows = [];
      Object.keys(top).forEach(i => {
        rows.push(
          <TopSQLTableRow
            key={i}
            sqlId={top[i].sql_id}
            sqlText={top[i].sql_text}
            executions={top[i].executions}
            percentTotal={top[i].percent_total}
            barWidth={50/top[1].percent_total}
            avgTime={top[i].avg_time === null ? '' : top[i].avg_time.toFixed(2)}
            waits={top[i].waits}
            sqlType={sqltypes(top[i].sql_opcode)}
            colors={this.props.colors}
          />
        );
      });
      return (
        <table className="top-sql">
          <thead>
            <tr>
              <th>SQL_ID</th>
              <th>Activity (%)</th>
              <th>SQL Type</th>
              <th>Executions</th>
              <th>Average Time</th>
            </tr>
          </thead>
          <tbody>
            {rows}
          </tbody>
        </table>
      );
    } 
  }
}

export default TopSQLTable;
