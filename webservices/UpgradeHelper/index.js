var app = require( 'express' )();
var http = require( 'http' ).Server( app );
var io = require( 'socket.io' )( http );
var lastLine = require( 'last-line' );
var fileExtension = require( 'file-extension' );
var Inotify = require( 'inotify' ).Inotify;

io.on( 'connection', function ( socket ) {
	console.log( 'a user connected' );
	io.emit( 'upgradehelper statusupdate', { action: 'start', message: 'Welcome!' } );
	socket.on( 'disconnect', function () {
		console.log( 'user disconnected' );
	} );
} );

var inotify = new Inotify();

var data = { }; //used to correlate two events
var baseDir = '/etc/bluespice';
var lastLineContent = "";
var statesToServe = {
	upgradeRequest: ["task", "error"],
	downgradeRequest: ["task", "error"],
	tokenAvailable: ["task", "error"],
	tokenCheck: ["task", "error"],
	download: ["task", "error"],
	backup: ["task", "error"],
	do_upgrade: ["task", "error"],
	do_downgrade: ["task", "error"],
	check_install: [],
	finish: []
};

var callback = function ( event ) {
	var mask = event.mask;
	var type = mask & Inotify.IN_ISDIR ? 'directory ' : 'file ';

	if ( event.name ) {
		type += ' ' + event.name + ' ';
	} else {
		type += ' ';
	}

	if ( mask & Inotify.IN_CREATE ) {
		console.log( type + 'created' );
		io.emit( 'upgradehelper statusupdate', { action: 'created', file: event.name, message: event.name + ' has been created' } );
	} else if ( mask & Inotify.IN_DELETE ) {
		console.log( type + 'deleted' );
		io.emit( 'upgradehelper statusupdate', { action: 'deleted', file: event.name, message: event.name + ' has been deleted' } );
	}
};

var home2_dir = {
	// Change this for a valid directory in your machine
	path: baseDir,
	watch_for: Inotify.IN_CREATE | Inotify.IN_DELETE,
	callback: callback
};

var home2_wd = inotify.addWatch( home2_dir );

http.listen( 3000, function () {
	console.log( 'listening on *:3000' );
} );