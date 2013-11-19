//require-css <xataface/modules/google_maps/widgets/map.css>

(function(){
    var $ = jQuery;
    var pkg = XataJax.load('xataface.modules.google_maps.widgets');
    pkg.MapWidget = MapWidget;
    
    function MapWidget(o){
        var self = this;
        this.input = o.input;
        this.mapOpts = o.mapOpts || {};
        this.width = o.width || '100%';
        this.height = o.height || '500px';
        this.el = $('<div class="xf-map-wrapper" style="height:'+this.height+'; width:'+this.width+'"></div>').get(0);
                //$(this.el).insertAfter(this.input);
        this.map = new google.maps.Map(this.el, this.mapOpts);
        this.markers = {};
        this.nextMarkerId = 0;
        
        this.features = o.features || {};
        if ( this.features.addMarkers ){
            google.maps.event.addListener(this.map, 'click', function(e){
                self.addMarker({
                    position : e.latLng
                });
                
            });
        }
        
    }
    
    $.extend(MapWidget.prototype, {
        
        addMarker : function(o){
            var self = this;
            var marker = {};
            marker.id = o.id || this.nextMarkerId++;
            if ( this.markers[marker.id] !== undefined ){
                return; // marker is already added
            }
            marker.marker = o.marker || new google.maps.Marker({
                position : (o.position instanceof google.maps.LatLng) ? o.position : new google.maps.LatLng(o.position[0], o.position[1]),
                map : this.map
            });
            
            if ( this.features.removeMarkers ){
                google.maps.event.addListener(marker.marker, 'click', function(e){
                    marker.marker.setMap(null);
                    delete self.markers[marker.id];
                });
            }
            
            this.markers[marker.id] = marker;
            
            
        },
        
        install : function(){
            var self = this;
            $(this.el).insertAfter(this.input);
            $(this.input).hide();
            this.pull();
            $(this.input.form).submit(function(){
                self.push();
                return true;
            });
        },
        pull : function(){
            var self = this;
            var data = $(this.input).val();
            if ( data ){
                data = JSON.parse(data);
            } else {
                return;
            }
            if ( data.center ){
                this.map.setCenter(new google.maps.LatLng(data.center[0],data.center[1]));
            }
            if ( data.zoom ){
                this.map.setZoom(data.zoom);
            }
            
            if ( data.nextMarkerId ){
                this.nextMarkerId = data.nextMarkerId;
            }
            
            if ( data.markers ){
                $.each(data.markers, function(id,marker){
                    self.addMarker(marker);
                });
            }
            
        },
        push : function(){
            var data = {};
            var center = this.map.getCenter();
            data.center = [center.lat(), center.lng()];
            data.zoom = this.map.getZoom();
            data.nextMarkerId = this.nextMarkerId;
            data.markers = {};
            $.each(this.markers, function(id,marker){
                data.markers[id] = {
                    id : id,
                    position : [marker.marker.getPosition().lat(), marker.marker.getPosition().lng()]
                };
            });
            $(this.input).val(JSON.stringify(data));
        }
    });
    
    MapWidget.initialized = false;
    MapWidget.queue = [];
    MapWidget.initialize = function(){
        MapWidget.initialized = true;
        while ( MapWidget.queue.length > 0 ){
            var f = MapWidget.queue.shift();
            f();
        }
    };
    MapWidget.ready = function(f){
        if ( MapWidget.initialized ){
            f();
        } else {
            MapWidget.queue.push(f);
        }
    };
    
    
    MapWidget.load = function(){
        var script = document.createElement('script');
        script.type = 'text/javascript';
        var k = '';
        if ( window.location.hostname !== 'localhost' && typeof(window.XF_GOOGLE_MAPS_API_KEY) !== 'undefined'){
            k = 'key='+encodeURIComponent(window.XF_GOOGLE_MAPS_API_KEY)+'&';
        }
        script.src = 'https://maps.googleapis.com/maps/api/js?'+k+'v=3.exp&sensor=false&' +
            'callback=xataface.modules.google_maps.widgets.MapWidget.initialize';
        document.body.appendChild(script);
    };
    
    MapWidget.load();
    
    registerXatafaceDecorator(function(node){
        MapWidget.ready(function(){
            
           $('input.xf-map', node).each(function(){
                var atts = {
                    input : this,
                    mapOpts : {},
                    features : {}
                };
                
                if ( $(this).attr('data-map-zoom') ){
                    atts.mapOpts['zoom'] = parseInt($(this).attr('data-map-zoom'));
                } else {
                    atts.mapOpts['zoom'] = 8;
                }
                if ( $(this).attr('data-map-center') ){
                    var ctrStr = $(this).attr('data-map-center').split(',');
                    
                    atts.mapOpts['center'] = new google.maps.LatLng(parseFloat(ctrStr[0]), parseFloat(ctrStr[1]));
                } else {
                    atts.mapOpts['center'] = new google.maps.LatLng(49.25,123.1);
                }
                
                if ( $(this).attr('data-map-features-addmarkers')){
                    atts.features['addMarkers'] = parseInt($(this).attr('data-map-features-addmarkers'));
                }
                if ( $(this).attr('data-map-features-removemarkers')){
                    atts.features['removeMarkers'] = parseInt($(this).attr('data-map-features-removemarkers'));
                }
                if ( $(this).attr('data-map-width') ){
                    atts.width = $(this).attr('data-map-width');
                    
                }
                if ( $(this).attr('data-map-height') ){
                    atts.height = $(this).attr('data-map-height');
                }
                
                var widget = new MapWidget(atts);
                widget.install();
           });
        });
    });
    
    
    
    
})();
