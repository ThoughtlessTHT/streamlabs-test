const app = require('express')();
const http = require('http').Server(app);
const io = require('socket.io')(http);

let port = process.env.PORT;

io.on('connection', function(socket){
    socket.on('message', function(data) {
        console.log(data);
        io.emit('message', data);
    })
});

http.listen(port, function(){
    console.log('listening on *:' + port);
});