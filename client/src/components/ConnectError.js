import React from 'react';

class ConnectError extends React.Component {
  render() {
    return (
      <div className="status status-error">
        {this.props.message}
      </div>
    );
  }
}

export default ConnectError;
