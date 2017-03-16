var NotifyServiceLocator={
    
    _localNotifyService: new NotifyService(),
            
    locateLocalFacetService : function ()
    {
        return this._localNotifyService;
    },
    locateFacetService : function() {

        var facet_notify_service = new FacetNotifyService("130.239.57.89", "1337");
        
        facet_notify_service.initialize();
        
        facet_notify_service.local_notify_service = this._localNotifyService;
        
        return facet_notify_service;
        
    },
    locateResultService : function ()
    {
        return this._localNotifyService;
       
    },
    locateWebSocketResultService : function ()
    {
        var result_notify_service = new ResultNotifyService("130.239.57.89", "1337");
        result_notify_service.initialize();
        return  result_notify_service;
    } ,
    
    
    
}

function NotifyService() {
    
    this.listeners= {
    };
    
    this.listenTo = function(eventName, listener) {
        if (!this.listeners[eventName])
            this.listeners[eventName] = [];
        this.listeners[eventName].push(listener);
    };
    
    this.fire = function(eventName, data) {
        if (this.listeners[eventName]) {
            for (var i = 0; i < this.listeners[eventName].length; i++) {
                this.listeners[eventName][i](this, data);
            }
        }
    };
         
    this.notify = function(eventName,data)
    {
        this.fire(eventName, data);
    }
    
}


/* Web Sockets */

function FacetNotifyService(ip, port) {

    this.local_notify_service = null;
    
    this.websocket = null;
    this.isopen=false;
    
    this.initialize = function()
    {
        if (this.websocket )
        {
            return;
        }
        this.websocket = new WebSocketClient();
        var self = this;
        this.websocket.onopen = function ()
        {
            self.websocket.register("FACET_PEAR");
            self.isopen=true;
        }
        this.websocket.open(ip, port);
    };
    
    this.listenTo = function(eventName, listener) {
    };
    
    this.fire = function(eventName, data) {
    };    
    
    this.notify = function(eventName,data)
    {
        if (!this.isopen)
            return;
        if (eventName === "facet-change") {
            this.websocket.send_facet_xml(data);
        }
        if (this.local_notify_service) {
            this.local_notify_service.notify(eventName,data);
        }
    };
    
}

function ResultNotifyService(ip, port) {

    this.listeners = {
    };
    
    this.websocket=null;
         
    this.initialize = function()
    {
       if (this.websocket) {
            return;
        }
       this.websocket = new WebSocketClient();
       var self = this;
       this.websocket.onopen = function ()
       {
             self.websocket.register("RESULT_PEAR");
       }
       
       this.websocket.open(ip, port);
       
       this.websocket.on_receive_facet_xml = function(message) {
            self.fire("facet-change", message);
       }
    };
    
    this.listenTo = function(eventName, listener) {
        if (!this.listeners[eventName])
            this.listeners[eventName] = [];
        this.listeners[eventName].push(listener);
    };
    
    this.fire = function(eventName, message) {
        if (this.listeners[eventName]) {
            for (var i = 0; i < this.listeners[eventName].length; i++) {
                this.listeners[eventName][i](this, message);
            }
        }
    };    
    
    this.notify = function(eventName,message)
    {
    }
    
}

