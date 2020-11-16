import React from 'react';
import moment from 'moment';

class SelectedInterval extends React.Component {
  formatDate = date => 
    moment(Number(date)).utc().format("DD.MM.YYYY HH:mm:ss");

  render() {
    const minDate = this.formatDate(this.props.minDate);
    const maxDate = this.formatDate(this.props.maxDate);

    return (
      <div className="selection">
        Selected interval: {minDate} to {maxDate}
      </div>
    );
  }
}

export default SelectedInterval;
