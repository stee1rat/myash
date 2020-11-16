# myash
Simple Oracle ASH viewer

## Installation
* Clone repository
* `npm install && npm --prefix ./client install ./client`
* If Oracle client is not installed: download Instant Client Basic or Basic Light package from http://www.oracle.com/technetwork/topics/linuxx86-64soft-092277.html and unzip it
* Set LD_LIBRARY_PATH environment variable, for example: `export LD_LIBRARY_PATH=/opt/oracle/instantclient_19_3:$LD_LIBRARY_PATH`

## Usage
`npm start`

In case you are not running the app on your localhost, you might want to edit the client/.env.development and client/package.json files, to change 127.0.0.1 to your hostname or IP.

## Screenshots
![screenshot](https://github.com/stee1rat/myash/blob/master/screenshots/image001.png?raw=true)

![screenshot](https://github.com/stee1rat/myash/blob/master/screenshots/image002.png?raw=true)

![screenshot](https://github.com/stee1rat/myash/blob/master/screenshots/image002.png?raw=true)
