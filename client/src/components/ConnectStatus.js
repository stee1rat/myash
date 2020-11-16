import React from 'react';

import { 
  Button, 
  TextField, 
  Dialog, 
  DialogActions, 
  DialogContent, 
  DialogTitle 
} from '@material-ui/core';

class ConnectStatus extends React.Component {
  state = {
    open: false,
    host: this.props.connectionParameters.host,
    port: this.props.connectionParameters.port,
    sid: this.props.connectionParameters.sid,
    user: this.props.connectionParameters.user,
    pass: this.props.connectionParameters.pass,
  };

  onOpenModal = () => {
    this.setState({ open: true });
  };

  onCloseModal = () => {
    this.setState({ open: false });
  };

  handleOK = () => {
    this.props.onOk(
      this.state.host,
      this.state.port,
      this.state.sid,
      this.state.user,
      this.state.pass,
    );
    this.onCloseModal();
  }

  handleChange = e => {
    this.setState({[e.target.id]: e.target.value});
  }

  handleKeyPress = e => {
    if (e.key === 'Enter') {
      this.handleOK(); 
    }
  }

  render() {
    const { open } = this.state;

    return (
      <div>
         { 
           this.props.connectionOK ?
             <div>Database: <a onClick={this.onOpenModal} style={{cursor: 'pointer'}}>{this.props.sid}@{this.props.host}</a>, Version: {this.props.version}</div> :
             <a onClick={this.onOpenModal} style={{cursor: 'pointer'}}>Oracle Logon</a> 
         }
         <Dialog 
           open={open} 
           onClose={this.onCloseModal} 
           aria-labelledby="simple-modal-title"
         >
           <DialogTitle id="form-dialog-title">
             Connection Parameters
           </DialogTitle>
           <DialogContent>
             <TextField 
               fullWidth 
               label="Host" 
               id="host" 
               value={this.state.host} 
               onChange={this.handleChange} 
               onKeyPress={this.handleKeyPress} 
               autoFocus={true}
             />
             <TextField 
               fullWidth 
               label="Port" 
               id="port" 
               value={this.state.port} 
               onChange={this.handleChange} 
               onKeyPress={this.handleKeyPress} 
               style={{marginTop:"15px"}}
             />
             <TextField 
               fullWidth 
               label="Service Name" 
               id="sid" 
               value={this.state.sid} 
               onChange={this.handleChange} 
               onKeyPress={this.handleKeyPress} 
               style={{marginTop:"15px"}} 
             />
             <TextField 
               fullWidth 
               label="Username" 
               id="user" 
               value={this.state.user} 
               onChange={this.handleChange} 
               onKeyPress={this.handleKeyPress} 
               style={{marginTop:"15px"}} 
             />
             <TextField 
               fullWidth 
               label="Password" 
               id="pass" 
               type="password" 
               value={this.state.pass} 
               onChange={this.handleChange} 
               onKeyPress={this.handleKeyPress} 
               style={{marginTop:"15px"}} 
             />
           </DialogContent>
           <DialogActions>
             <Button color='primary' onClick={this.handleOK}>OK</Button>
             <Button color='primary' onClick={this.onCloseModal}>Cancel</Button>
           </DialogActions>
         </Dialog>
      </div>
    );
  }
}

export default ConnectStatus;
