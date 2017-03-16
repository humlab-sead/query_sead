function WebSocketClient() {
    
    this.WebSocket = window.WebSocket || window.MozWebSocket;

    this.connection = null;
    
    this.open = function(ip, port) {

        if (!this.WebSocket) {
            throw "Browser does not support WebSockets";
        }
        
        this.connection = new this.WebSocket("ws://" + ip + ":" + port.toString());
        
        var self = this;
        
        this.connection.onopen =
            function() {
                if (self.onopen){ self.onopen();
                  //  console.log("port is opened");
                }
            };
        
        this.connection.onerror =
            function(error) {
                if (self.onopen)
                    self.onopen(error);
                // TODO Add default error handling
            };
            
        this.connection.onmessage =
            function(message) {
                if (self.onmessage) 
                {
                    self.onmessage(message.data);
                }
            };
    };
    
    this.close = function()
    {
        if (this.connection)
            this.connection.close();
    };

    this.send = function(message)
    {
        if (this.connection)
            this.connection.send(message);
    };

    this.register = function(pear_type)
    {
        if ($.inArray(pear_type, ["FACET_PEAR","RESULT_PEAR","FACET+RESULT_PEAR"]) == -1)
            throw "register: pear type unexpected"
        this.send("REGISTER " + pear_type);
    };
    
    this.send_facet_xml = function(xml)
    {
        this.send("FACET_XML " + xml);
    };

    this.onopen = function () {
    };

    this.onerror = function (error) {
        throw error;
    };

    this.onmessage = function (message) {
       // console.log(message.data);
        if (this.starts_with(message, "FACET_XML")) {
            var xml = message.substring("FACET_XML".length+1);
            if (this.on_receive_facet_xml)
                this.on_receive_facet_xml(xml);
        }
    };

    this.on_receive_facet_xml = null;
   
    this.in_array = function (array, id) {
        for (var i = 0;i < array.length; i++) {
            return (array[i] === id)
        }
        return false;
    };
            
    this.starts_with = function(data, key)
    {
        return data.substring(0, key.length) === key;
    };

}
