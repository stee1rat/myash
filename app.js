var cookieParser = require('cookie-parser');
var createError = require('http-errors');
var express = require('express');
var logger = require('morgan');
var path = require('path');

var topactivityRouter = require('./routes/topactivity');
var topSessionRouter = require('./routes/topsession');
var snapshotsRouter = require('./routes/snapshots');
var versionRouter = require('./routes/version');
var topSQLRouter = require('./routes/topsql');
var indexRouter = require('./routes/index');

var app = express();

// view engine setup
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'jade');

app.use(logger('dev'));
app.use(express.json());
app.use(express.urlencoded({ extended: false }));
app.use(cookieParser());
app.use(express.static(path.join(__dirname, 'public')));

app.use('/', indexRouter);
app.use('/topactivity', topactivityRouter);
app.use('/topsession', topSessionRouter);
app.use('/snapshots', snapshotsRouter);
app.use('/version', versionRouter);
app.use('/topsql', topSQLRouter);

// catch 404 and forward to error handler
app.use(function(req, res, next) {
  next(createError(404));
});

// error handler
app.use(function(err, req, res, next) {
  // set locals, only providing error in development
  res.locals.message = err.message;
  res.locals.error = req.app.get('env') === 'development' ? err : {};

  // render the error page
  res.status(err.status || 500);
  res.render('error');
});

module.exports = app;
