var app = require( 'express' )();
var http = require( 'http' ).Server( app );
var io = require( 'socket.io' )( http );
var lastLine = require( 'last-line' );
var fileExtension = require( 'file-extension' );
var Inotify = require( 'inotify' ).Inotify;

app.get( '/', function ( req, res ) {
	res.sendFile( __dirname + '/index.html' );
} );

io.on( 'connection', function ( socket ) {
	console.log( 'a user connected' );
	io.emit( 'chat message', { action: 'start', message: 'Welcome!' } );
	socket.on( 'disconnect', function () {
		console.log( 'user disconnected' );
	} );
} );

var inotify = new Inotify();

var data = { }; //used to correlate two events
var baseDir = '/etc/bluespice';
var lastLineContent = "";

var callback = function ( event ) {
	var mask = event.mask;
	var type = mask & Inotify.IN_ISDIR ? 'directory ' : 'file ';
	if ( event.name ) {
		type += ' ' + event.name + ' ';
	} else {
		type += ' ';
	}
	// the purpose of this hell of 'if' statements is only illustrative.

	if ( mask & Inotify.IN_ACCESS ) {
		console.log( type + 'was accessed ' );
	} else if ( mask & Inotify.IN_MODIFY ) {
		console.log( type + 'was modified ' );
		lastLine( baseDir + "/" + event.name, function ( err, res ) {
			if ( res !== undefined && lastLineContent !== res) {
				io.emit( 'chat message', { action: 'modified', file: event.name, message: event.name + ': ' + res } );
				lastLineContent = res;
			}
		} );
	} else if ( mask & Inotify.IN_OPEN ) {
		console.log( type + 'was opened ' );
	} else if ( mask & Inotify.IN_CLOSE_NOWRITE ) {
		console.log( type + ' opened for reading was closed ' );
	} else if ( mask & Inotify.IN_CLOSE_WRITE ) {
		console.log( type + ' opened for writing was closed ' );
	} else if ( mask & Inotify.IN_ATTRIB ) {
		console.log( type + 'metadata changed ' );
	} else if ( mask & Inotify.IN_CREATE ) {
		console.log( type + 'created' );
		io.emit( 'chat message', { action: 'modified', file: event.name, message: event.name + ' has been created' } );
	} else if ( mask & Inotify.IN_DELETE ) {
		console.log( type + 'deleted' );
		io.emit( 'chat message', { action: 'modified', file: event.name, message: event.name + ' has been deleted' } );
	} else if ( mask & Inotify.IN_DELETE_SELF ) {
		console.log( type + 'watched deleted ' );
	} else if ( mask & Inotify.IN_MOVE_SELF ) {
		console.log( type + 'watched moved' );
	} else if ( mask & Inotify.IN_IGNORED ) {
		console.log( type + 'watch was removed' );
	} else if ( mask & Inotify.IN_MOVED_FROM ) {
		data = event;
		data.type = type;
	} else if ( mask & Inotify.IN_MOVED_TO ) {
		if ( Object.keys( data ).length &&
				data.cookie === event.cookie ) {
			console.log( type + ' moved to ' + data.type );
			data = { };
		}
	}
};

var home2_dir = {
	// Change this for a valid directory in your machine
	path: baseDir,
	watch_for: Inotify.IN_MODIFY,
	callback: callback
};

var home2_wd = inotify.addWatch( home2_dir );

http.listen( 3000, function () {
	console.log( 'listening on *:3000' );
} );