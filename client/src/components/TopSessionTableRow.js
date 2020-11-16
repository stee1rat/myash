import React from 'react';
import ActivityBar from './ActivityBar';

class TopSessionTableRow extends React.Component {
  render() {
    return (
      <tr>
        <td>{this.props.session_id}</td>
        <td>
          <ActivityBar
            barWidth={this.props.barWidth}
            waits={this.props.waits}
            percent={this.props.percent_total}
            colors={this.props.colors}
          />
        </td>
        <td>{this.props.username}</td>
        <td>{this.props.program}</td>
      </tr>
    );
  }
}

export default TopSessionTableRow;
