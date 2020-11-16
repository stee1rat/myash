import React from 'react';

class ActivityBar extends React.Component {
  render() {
    let bar = [];
    for (let i = 0; i < this.props.waits.length; i++) {
      bar.push(
        <div 
          key={i} 
          style={{
            backgroundColor: this.props.colors[this.props.waits[i].name], 
            width: this.props.waits[i].percent*this.props.barWidth + '%', 
            verticalAlign: 'middle',
            display: 'inline-block', 
            height: '15px', 
          }}
        />
      );
    }

    return (
      <div>
        {bar}
        <span className="activity-bar">
          {this.props.percent.toFixed(2)}
        </span>
      </div>
    );
  }
}

export default ActivityBar;
