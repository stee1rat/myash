import React from 'react';
import ActivityBar from './ActivityBar';

class TopSQLTableRow extends React.Component {
  render() {
    return (
      <tr>
        <td>
          <a href='sql' title={this.props.sqlText} onClick={e => e.preventDefault()} >
            {this.props.sqlId}
          </a>
        </td>
        <td>
          <ActivityBar
            barWidth={this.props.barWidth}
            waits={this.props.waits}
            percent={this.props.percentTotal}
            colors={this.props.colors}
          />
        </td>
        <td>{this.props.sqlType}</td>
        <td>{this.props.executions}</td>
        <td>{this.props.avgTime}</td>
      </tr>
    );
  }
}

export default TopSQLTableRow;
