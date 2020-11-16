import React from 'react';
import axios from 'axios';
import TopSessionTableRow from './TopSessionTableRow';

class TopSessionTable extends React.Component {
  state = {
    externalData: null,
  };
 
  static getDerivedStateFromProps(props, state) {
    if (props.minDate !== state.minDate && props.maxDate !== state.maxDate) {
      return props;
    }
    return null;
  }

  componentDidMount() {
    this.loadAsyncData();
  }

  componentDidUpdate(prevProps, prevState) {
    if (prevProps.minDate !== this.state.minDate && 
        prevProps.maxDate !== this.state.maxDate) {
      this.loadAsyncData();
    }
  }

  loadAsyncData() {
    const c = this.props.connectionParameters;

    if (typeof this._source !== typeof undefined) {
      this._source.cancel();
    }

    this._source = axios.CancelToken.source();

    axios.get('/topsession', {
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
          <TopSessionTableRow
            key={i}
            session_id={top[i].session_id}
            program={top[i].program}
            username={top[i].username}
            percent_total={top[i].percent_total}
            barWidth={50/top[Object.keys(top)[0]].percent_total}
            waits={top[i].waits}
            colors={this.props.colors}
          />
        );
      })
      return (
        <table className="top-sql">
          <thead>
            <tr>
             <th>Sid,Serial#</th>
             <th>Activity (%)</th>
             <th>Username</th>
             <th>Program</th>
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

export default TopSessionTable;
