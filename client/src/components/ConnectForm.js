import React from 'react';
import { Container, Row, Col, Input } from 'muicss/react';
//import Button from '@material-ui/core/Button';

class ConnectForm extends React.Component {
  constructor(props) {
    super(props);
    this.handleConnectChange = this.handleConnectChange.bind(this);
  }

  handleConnectChange(event) {
    this.props.onClick();
  }

  render() {
    return (
       <div>
         <Col md="2">
           <Input label="Host" name="host" id="host" defaultValue={this.props.connectionParameters.host} ref={(input) => this.host = input} />
         </Col>
         <Col md="2">
           <Input label="Port" name="port" id="port" defaultValue={this.props.connectionParameters.port} ref={(input) => this.port = input} />
         </Col>
         <Col md="2">
           <Input label="Service Name" type="text" name="sid" defaultValue={this.props.connectionParameters.sid} ref={(input) => this.sid = input} />
         </Col>
         <Col md="2">
           <Input label="Username" type="text" name="user" defaultValue={this.props.connectionParameters.user} ref={(input) => this.user = input} />
         </Col>
         <Col md="2">
           <Input label="Password" type="password" name="pass" defaultValue={this.props.connectionParameters.pass} ref={(input) => this.pass = input} />
         </Col>
         <Col md="2">
           <Button color='primary' onClick={this.handleConnectChange}>Go</Button>
         </Col>
       </div>
    );
  }
}

export default ConnectForm;
