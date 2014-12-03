/* 
 * Timeline - JQuery UI Widget
 * 
 * Usage :
 * $('element').timeline({
 *      eventUrl: "url to get events",
 *      categoriesUrl: "url to get categories"
 * });
 * 
 * @author Jonathan Colson
 */

$.widget("epeires.timeline", {
    version: "0.0.1",
    /**
     * List of events
     */
    events: [],
    
    /**
     * List of categories
     */
    categories: [],
    
    /**
     * Current view
     */
    dayview: true,
    
    /**
     * Beginning of the timeline
     */
    timelineBegin: new Date(),
    /**
     * End of the timeline
     */
    timelineEnd: new Date(),
    /**
     * Duration of the timeline in hours
     */
    timelineDuration: 6,
    /**
     * Intervalle entre deux barres "heure"
     */
    intervalle: 0,
        
    //default options
    options: {
        eventUrl: "",
        categoriesUrl: "",
        topOffset: 0,
        leftOffset: 80,
        rightOffset: 40,
        eventHeight:30
    },
    
    //Main function
    //Initialize the timeline
    _create: function () {
        var self = this;
        $.when(
                $.getJSON(self.options.categoriesUrl, function (data) {
                    $.each(data, function (key, value) {
                        self.categories.push(value);
                    });
                }),
                $.getJSON(self.options.eventUrl, function (data) {
                    $.each(data, function (key, value) {
                        self.events.push(value);
                    });
                })
                ).then(function () {
            //unable user to select objects
            self._setUnselectable();
            //sort categories by name
            self.sortCategories(function (a,b) { return a.place - b.place; });
            //sort events by categories
            self.sortEvents();
            // ini params
            self.view("sixhours");
            // draw base
            self._drawBase();
            // draw timeBar
            self._drawTimeBar();
            
            for (i=0;i<self.events.length;i++) {
            	self.element.append(self._getSkeleton(self.events[i]));
            	self._drawEvent(self.events[i]);
            }
            //trigger event when init is finished
        //     self._trigger("initComplete");
        });
    },
    /* ********************** */
    /* *** Public methods *** */
    /* ********************** */

    /*
     * 
     * @param {type} event Object
     */
    addEvent: function (event) {

    },
    modifyEvent: function (event) {

    },
    removeEvent: function (event) {
    	
    },
    view: function (viewName) {
        if (viewName == "day" && !this.dayview) {
            this.dayview = true;
            this._changeView();
        } else if (viewName == "sixhours" && this.dayview) {
            this.dayview = false;
            this._changeView();
        }
    },
    day: function (date) {
    	
    },
    /*
     * Sort events
     * @param {type} comparator callback 
     * @returns {undefined}
     */
    sortEvents: function (comparator) {
        if (typeof comparator === "undefined") {
        	this.events.sort(function (a, b) {
        		if (a.category_place == b.category_place) {
        			return a.start_date > b.start_date;
        		} else {
        			return a.category_place > b.category_place;   			
        		}
        	}); 
        } else {
            this.events.sort(comparator);
        }
    },
    /**
     * Sort categories according to comparator
     * If comparator is undefined, sort alphabetically
     * @param {type} comparator
     * @returns {undefined}
     */
    sortCategories: function (comparator) {
        if (typeof comparator === "undefined") {
            this.categories.sort(function (a, b) {
                return a.name > b.name;
            });
        } else {
            this.categories.sort(comparator);
        }
        
    },
    filter: function () {

    },

    /* ********************** */
    /* *** Private methods ** */
    /* ********************** */

    _setUnselectable: function () {
        this.element.attr('unselectable', 'on')
                .css({'-moz-user-select': '-moz-none',
                    '-moz-user-select':'none',
                            '-o-user-select': 'none',
                    '-khtml-user-select': 'none',
                    '-webkit-user-select': 'none',
                    '-ms-user-select': 'none',
                    'user-select': 'none'
                }).bind('selectstart', function () {
            return false;
        });
    },
    _createTimeline: function () {
        this.element.css('height', $(window).height() - this.options.topOffset + 'px');

    },
    /**
     * Draw new events and update positions
     * @returns {undefined}
     */
    _applyChanges: function () {

    },
    /**
     * Switch between dayview and 6-hours view
     * @returns {undefined}
     */
    _changeView: function () {
        //update local var
        if(this.dayview) {
            this.timelineDuration = 24;
            var now = new Date();
            //diff between utc and local time
            //TODO
            var diff = 2;
            this.timelineBegin = new Date(now.getFullYear(), now.getMonth(), now.getDate(), diff, 0, 0);
            this.timelineEnd = new Date(this.timelineBegin.getFullYear(), this.timelineBegin.getMonth(), this.timelineBegin.getDate(),
                                    this.timelineBegin.getHours()+this.timelineDuration, 0, 0);
        } else {
            this.timelineDuration = 6;
            var now = new Date();
            this.timelineBegin = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours()-1, 0, 0);
            this.timelineEnd = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours()-1+this.timelineDuration, 0, 0);
        }
    },
    /**
     * Calcule l'abscisse correspondant à une date
     * Retourne -1 si en dehors de la timeline
     * @param {type} date
     * @returns {undefined}
     */
    _computeX: function (date) {
    	if(date < this.timelineBegin || date > this.timelineEnd){    
    		return -1;
    	} else {
    		var delta;
    		if (date.getUTCHours() >= this.timelineBegin.getUTCHours()) { 
    			delta = date.getUTCHours() - this.timelineBegin.getUTCHours();
    			console.log(delta);
    		} else { 
    			delta = 24 + date.getUTCHours() - this.timelineBegin.getUTCHours();
    		}
    		return this.options.leftOffset + delta*this.intervalle*2 + date.getMinutes()*this.intervalle*2/60;
    	}
    },
    
    /**
     * Calcule l'ordonnée correspondant à un évènement
     * @param {type} event
     * @returns {undefined}
     */
    _computeY: function (event) {
    		return 150;
    },
    
    /**
     * Calcule le nouvel intervalle lorsque timelineDuration a été modifié
     */
    _computeIntervalle: function () {
		var nbIntervalles = this.timelineDuration * 2;
		var largeurDisponible = this.element.width()-this.options.leftOffset-this.options.rightOffset;
		//if scrollbar visible, width is different
		//TODO : do it better
		if ($(document).height() > $(window).height()) {
			largeurDisponible += this._getScrollbarWidth();
		}
		this.intervalle = largeurDisponible / nbIntervalles;
    },
       
    
    _drawBase: function () {
    	
    	this._computeIntervalle();
    	//si la timeline est décalée, ajouter le décalage
    	var left = parseInt(this.element.css('left'));
    	if(isNaN(left)) {
    		left = 0;
    	}
    	var h_temp = this.timelineBegin.getUTCHours();
    	var time_obj = $('<div id="Time_obj"></div>');
    	var base_elmt = $('<div id="timeline-base" class="Base"></div>');
    	this.element.append(base_elmt);
    	base_elmt.append(time_obj);
    	time_obj.css({'position':'fixed', 
    		'top': this.options.topOffset+50+'px', 
    		'left': this.options.leftOffset+left+'px', 
    		'width': this.intervalle*this.timelineDuration*2+'px', 
    		'height':1 ,
    		'background-color':'#C0C0C0'});
    	for (var i=0;i<this.timelineDuration*2+1;i++) { 
    		var vert_bar = $('<div class="Time_obj vert_bar"></div>');
    		base_elmt.append(vert_bar); 
    		vert_bar.css({'position':'fixed',
    			'top': this.options.topOffset+45+'px', 
    			'left': this.intervalle*i+this.options.leftOffset+left+'px', 
    			'width': 1, 
    			'height':this.element.height()-50, 
    			'background-color':'#C0C0C0'});
    		if (i%2 == 1) {
    			var halfHour = $('<div>30</div>');
    			base_elmt.append(halfHour);
    			halfHour.css({'position':'fixed', 
    				'top': this.options.topOffset+30+'px', 
    				'left': this.intervalle*i+this.options.leftOffset-10+left+'px',
    				'width':'20px', 'text-align':'center', 'color':'#0000FF', 'font-family':'Calibri','font-size':'12px'});
    		} else {
    			var Roundhour = $('<div class="Time_obj">'+h_temp+':00</div>');
    			base_elmt.append(Roundhour);
    			Roundhour.css({'position':'fixed', 
    				'top': this.options.topOffset+20+'px', 
    				'left': this.intervalle*i+this.options.leftOffset-20+left+'px', 
    				'text-align':'center', 'color':'#0000FF', 'font-family':'Calibri','font-size':'16px'});
    			if (h_temp == 23) {
    				h_temp = 0;
    			} else {
    				h_temp ++;
    			}
    		}
    	}
    },
    
    _drawTimeBar: function () {
		var TimeBar = $('<div class="TimeBar"></div>');
		this.element.append(TimeBar);
		TimeBar.css({'position':'fixed', 'top': this.options.topOffset+50+'px', 
			'left': this._computeX(new Date())+'px', 'width': 3, 'height':this.element.height()-50 ,'z-index' : 10, 
			'background-color':'red'});
    },
    
    _getCategory: function (id) {
    	
    	for (i=0;i<this.categories.length;i++) {
    		console.log(this.categories[i].id);
    		console.log(id);
    		if (this.categories[i].id == id) { return this.categories[i]; } 
    	}
    },
    
    _drawEvent: function (event) {
		var elmt = this.element.find('#event'+event.id);
		var elmt_rect = elmt.find('.rect_elmt');
		var elmt_compl = elmt.find('.complement');
		var elmt_flecheG = elmt.find('.elmt_flecheG');
		var elmt_flecheD = elmt.find('.elmt_flecheD');
		var elmt_mod = elmt.find('.modify-evt');
		var elmt_check = elmt.find('.checklist-evt');
		var elmt_txt = elmt.find('.label_elmt');
		var elmt_deb = elmt.find('.elmt_deb');
		var elmt_fin = elmt.find('.elmt_fin');
		var move_deb = elmt.find('.move_deb');
		var move_fin = elmt.find('.move_fin');
		var lien = elmt.find('.no_lien');
		var categ = this._getCategory(event.id);
		var couleur = categ.color;
		if (event.punctual) {
			var x = this._computeX(event.startDate);
			var haut = this.options.eventHeight*2/3;
			var larg = haut*5/8;
			elmt_rect.css({'position':'absolute', 'left': -larg+'px', 'width':0, 'height':0, 'border-left':larg+'px solid transparent',
				'border-right':larg+'px solid transparent', 'border-bottom':haut+'px solid '+couleur,'z-index' : 1});
			elmt_compl.css({'position':'absolute', 'left': '0px','width':0, 'height':0, 'border-left':larg+'px solid transparent',
				'border-right':larg+'px solid transparent', 'border-top':haut+'px solid '+couleur, 'margin':haut*3/8+'px 0 0 -'+larg+'px','z-index' : 2});
		} else if (event.startDate < this.timelineBegin) {
			
		}
		
		
    },
    /**
     * Compute width of the scrollbar
     * @returns int Width of the scrollbar in pixels
     */
    _getScrollbarWidth: function () {
        var outer = document.createElement("div");
        outer.style.visibility = "hidden";
        outer.style.width = "100px";
        outer.style.msOverflowStyle = "scrollbar"; // needed for WinJS apps

        document.body.appendChild(outer);

        var widthNoScroll = outer.offsetWidth;
        // force scrollbars
        outer.style.overflow = "scroll";

        // add innerdiv
        var inner = document.createElement("div");
        inner.style.width = "100%";
        outer.appendChild(inner);

        var widthWithScroll = inner.offsetWidth;

        // remove divs
        outer.parentNode.removeChild(outer);

        return widthNoScroll - widthWithScroll;
    },
    
    _getSkeleton: function(event) {
		// création d'un élément
		var elmt = $('<div class="elmt" id="event'+event.id+'"></div>');
		elmt.data("ident", event.id);
		// ajout d'un rectangle
		var elmt_rect = $('<div class="rect_elmt"></div>');
		elmt.append(elmt_rect);
		var elmt_compl = $('<div class="complement"></div>');
		elmt_rect.after(elmt_compl);
		// si l'événement a commencé avant la timeline, ajout d'une flèche gauche
		var elmt_flecheG = $('<div class="elmt_flecheG"></div>');
		elmt.append(elmt_flecheG);
		$(elmt_flecheG).append('<i class="icon-arrow-left"></i>');
		// si l'événement se poursuit au-delà de la timeline, ajout d'une flèche droite
		var elmt_flecheD = $('<div class="elmt_flecheD"></div>');
		elmt.append(elmt_flecheD);
		$(elmt_flecheD).append('<i class="icon-arrow-right"></i>');
		// ajout du nom de l'événement
		elmt_txt = $('<p class="label_elmt">'+event.name+'</p>');
		$(elmt).append(elmt_txt);
		// ajout du bouton "ouverture fiche"
		elmt_b1 = $('<a href="#" class="modify-evt data-id="'+event.id+'"data-name="'+event.name+'"></a>');
		$(elmt_txt).append(elmt_b1);
		$(elmt_b1).append('    <i class="icon-pencil"></i>');
		// ajout du bouton "ouverture fiche réflexe"
		elmt_b2 = $('<a href="#" class="checklist-evt" data-id="'+event.id+'"data-name="'+event.name+'"></a>');
		$(elmt_txt).append(elmt_b2);
		$(elmt_b2).append('    <i class="icon-tasks"></i>');
		// lien entre le texte et l'événement (si texte écrit en dehors)
		var lien = $('<div class="no_lien"></div>');
		$(elmt).append(lien);
		elmt_deb = $('<a href="#" class="elmt_deb"></a>');
		$(elmt).append(elmt_deb);
		elmt_fin = $('<a href="#" class="elmt_fin"></a>');
		$(elmt).append(elmt_fin);
		move_deb = $('<p class="move_deb"></p>');
		$(elmt_rect).append(move_deb);
		move_fin = $('<p class="move_fin"></p>');
		$(elmt_rect).append(move_fin);
		var dy = this.options.eventHeight;
		var largeur = 300;
		elmt.css({'position':'absolute', 'top': 150+'px', 'left':'0px', 'width': largeur, 'height':dy});
		elmt_flecheG.css({'position':'absolute', 'top': dy-22+'px', 'left': '0px'});
		elmt_flecheD.css({'position':'absolute', 'top': dy-22+'px', 'left': '0px'});
		elmt_b1.css({'z-index' : 1});
		elmt_b2.css({'z-index' : 1});
		elmt_txt.css({'position':'absolute', 'top': dy/2-11+'px', 'left': '0px', 'font-weight':'normal', 'z-index' : 2});
		lien.css({'position':'absolute', 'top': dy/2+'px', 'left': '0px','width':'10px','height':'1px','background-color':'gray', 'z-index' : 1});
		elmt_deb.css({'position':'absolute', 'top': '6px','left': '0px', 'width': '54px', 'text-align' : 'right', 
			'font-style':'italic', 'background-color':'Transparant', 'z-index':3});
		elmt_fin.css({'position':'absolute', 'top': '6px','left': '0px', 'width': '54px', 'text-align' : 'left', 
			'font-style':'italic', 'background-color':'Transparant', 'z-index':3});
		move_deb.css({'position':'absolute', 'top': 4+'px','height':dy-8, 'z-index':2, 'background-color':'Transparant', 
			'border-right-style':'solid', 'border-left-style':'solid', 'border-width':'2px', 'display':'none'});
		move_fin.css({'position':'absolute', 'top': 4+'px','height':dy-8, 'z-index':2, 'background-color':'Transparant', 
			'border-right-style':'solid', 'border-left-style':'solid', 'border-width':'2px', 'display':'none'});
		move_deb.hover(function(){$(this).css({'cursor':'e-resize'});});
		move_fin.hover(function(){$(this).css({'cursor':'e-resize'});});
    	return elmt;
    }
    
    
    
});
