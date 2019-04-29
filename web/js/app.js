(function() {
    const socket = io(socketURL);

    socket.on('message', function(msg){
        if (msg.login === streamer) {
            const parent = document.getElementById('events');

            if (parent.childNodes.length === 10) {
                parent.removeChild(parent.childNodes[0]);
            }

            const node = document.createElement('div');
            node.innerText = msg.message;

            parent.appendChild(node);
        }
    });
})();