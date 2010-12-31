/**
 * Script:
 *   Lighter.js - Syntax Highlighter written in MooTools.
 *
 * License:
 *   MIT-style license.
 * 
 * Author:
 *   Jose Prado
 *
 * Copyright:
 *   Copyright (©) 2009 [Jose Prado](http://pradador.com/).
 *
 */
(function () {
	Lighter = new Class({	
		Implements: [Options],
		name: 'Lighter',
		options: {
			altLines: '', // Pseudo-selector enabled.
			clipboard: null,
			container: null,
			editable: false,
			flame: 'standard',
			fuel:  'standard',
			id: null,
			indent: -1,
			matchType: "standard",
			mode: "pre",
			path: null,
			strict: false
		},
	
		/***************************
		 * Lighter Initialization
		 **************************/
		initialize: function (codeblock, options) {
			this.setOptions(options);
			this.id = this.options.id || this.name + '_' + $time();
			this.codeblock = $(codeblock);
			this.container = $(this.options.container);
			this.code = chop(this.codeblock.get('html')).replace(/&lt;/gim, '<').replace(/&gt;/gim, '>').replace(/&amp;/gim, '&');
		
			// Indent code if user option is set.
			if (this.options.indent > -1) { this.code = tabToSpaces(this.code, this.options.indent); }
		
			// Figure out path based on script location of Lighter.js or option passed in.
			this.getPath();
			
			// Extract fuel/flame names. Precedence: className > options > 'standard'.
			this.getClass();
			
			// Set builder options.
			this.builder = new Hash({
				'inline': this.createLighter.pass('code', this),
				'pre':    this.createLighter.pass('pre', this),
				'ol':     this.createLighterWithLines.pass([['ol'], ['li']], this),
				'div':    this.createLighterWithLines.pass([['div'], ['div', 'span'], true, 'span'], this),
				'table':  this.createLighterWithLines.pass([['table', 'tbody'], ['tr', 'td'], true, 'td'], this)
			});
		
			// Initialize caches
			Lighter.scripts     = Lighter.scripts || {};
			Lighter.stylesheets = Lighter.stylesheets || {};
		
			// Load fuel/flame to start chain of loads.
			this.loadStylesheet(this.options.flame, 'Flame.'+this.options.flame+'.css');
			this.loadFuel();
		},
		loadFuel: function () {
			try {
				this.fuel = new Fuel[this.options.fuel](this.code, {
					matchType: this.options.matchType,
					strict: this.options.strict
				});
				this.light();
			} catch (e) {
				this.loadScript(this.options.fuel, 'Fuel.'+this.options.fuel+'.js', {
					'load': this.loadFuel.bind(this),
					'error': function () { this.options.fuel = 'standard'; this.loadFuel(); }.bind(this)
				});
			}
		},
		light: function () {
			// Build highlighted code object.
			this.element = this.toElement();
		
			// Insert lighter in the right spot.
			if (this.container) {
				this.container.empty();
				this.element.inject(this.container);
			} else {
				this.codeblock.setStyle('display', 'none');
				this.element.inject(this.codeblock, 'after');
				if (this.options.clipboard) { this.loadClipboard(); }
			}
		},
		unlight: function() {
			$(this).setStyle('display', 'none');
			this.codeblock.setStyle('display', 'inherit');
		},
		loadClipboard: function () {
			try {
				var clip = new ZeroClipboard.Client();
				clip.setPath(this.options.path);
				clip.glue($(this.options.clipboard));
				clip.setText(this.code);
				clip.addEventListener('complete', function (client, text) {
		        alert("Copied text to clipboard:\n" + text);
		    });
			} catch (e) {
				this.loadScript('clipboard', 'ZeroClipboard.js', {
					'load': this.loadClipboard.bind(this),
					'error': $empty
				});
				return false;
			}
		},
		/***************************
		 * Initialize helper methods
		 **************************/
		getPath: function() {
		  if (!$chk(Lighter.path)) {
		      $$('head script').each(function(el) {
		          var script  = el.src.split('?', 1),
		              pattern = /Lighter(\.full|\.lite)?\.js$/gi;
		          if (script[0].match(pattern)) {
		              Lighter.path = script[0].replace(pattern, '');
		          }
		      })
		  }
		  if (!this.options.path) { this.options.path = Lighter.path; }
		},
		getClass: function() {
            var classNames = this.codeblock.get('class').split(' '),
                ff = [null, null];
            switch (classNames.length) {
                case 0: // No language! Simply wrap in Lighter.js standard Fuel/Flame.
					break;
				case 1: // Single class, assume this is the fuel/flame
					ff = classNames[0].split(':');
					break;
				default: // More than one class, let's give the first one priority for now.
					ff = classNames[0].split(':');
            }
            
            if (ff[0]) { this.options.fuel  = ff[0]; }
            if (ff[1]) { this.options.flame = ff[1]; }
		},
		loadScript: function (holder, fileName, events) {
			if ($chk(Lighter.scripts[holder])) {
				Lighter.scripts[holder].addEvents({
					load: events.load,
					error: events.error,
					readystatechange: function() {
						if (['loaded', 'complete'].contains(this.readyState)) events.load();
					}
				});
			} else {
				Lighter.scripts[holder] = new Element('script', {
					'src': this.options.path+fileName+'?'+$time(),
					'type': 'text/javascript',
					'events': {
						load: events.load,
						error: events.error,
						readystatechange: function() {
							if (['loaded', 'complete'].contains(this.readyState)) events.load();
						}
					}
				}).inject(document.head);
			}
		},
		loadStylesheet: function (holder, fileName) {
			if (!$chk(Lighter.stylesheets[holder])) {
				Lighter.stylesheets[holder] = new Element('link', {
					rel: "stylesheet",
					type: "text/css",
					media: "screen",
					href: this.options.path+fileName+'?'+$time()
				}).inject(document.head);
			}
		},
		/***************************
		 * Lighter creation methods
		 **************************/
		createLighter: function (parent) {
			var lighter = new Element(parent, { 'class': this.options.flame + this.name }),
			    pointer = 0;
		    
			// If no matches were found, insert code plain text.
			if (!$defined(this.fuel.wicks[0])) {
				lighter.appendText(this.code);
			} else {
		
				// Step through each match and add unmatched + matched bits to lighter.
				this.fuel.wicks.each(function (match) {
					lighter.appendText(this.code.substring(pointer, match.index));
				
					this.insertAndKeepEl(lighter, match.text, match.type);
					pointer = match.index + match.text.length;
				}, this);
			
				// Add last unmatched code segment if it exists.
				if (pointer < this.code.length) {
					lighter.appendText(this.code.substring(pointer, this.code.length));
				}
			}
		
			//lighter.set('text', lighter.get('html'));
			return lighter;
		},
		createLighterWithLines: function (parent, child, addLines, numType) {
			var lighter = new Element(parent[0], { 'class': this.options.flame + this.name, 'id': this.id }),
			    newLine = new Element(child[0]),
			    lineNum = 1,
			    pointer = 0,
			    text = null;
		
			// Small hack to ensure tables have no ugly styles.
			if (parent[0] == "table") { lighter.set("cellpadding", 0).set("cellspacing", 0).set("border", 0); }
		
			/* If lines need to be wrapped in an inner parent, create that element
			   with this test. (E.g, tbody in a table) */
			if (parent[1]) { lighter = new Element(parent[1]).inject(lighter); }
		
			/* If code needs to be wrapped in an inner child, create that element
			   with this test. (E.g, tr to contain td) */
			if (child[1]) { newLine = new Element(child[1]).inject(newLine); }
			newLine.addClass(this.options.flame + 'line');
			if (addLines) { lineNum = this.insertLineNum(newLine, lineNum, numType); }

			// Step through each match and add matched/unmatched bits to lighter.
			this.fuel.wicks.each(function (match) {
		
				// Create and insert un-matched source code bits.
				if (pointer != match.index) {
					text = this.code.substring(pointer, match.index).split("\n");
					for (var i = 0; i < text.length; i++) {
						if (i < text.length - 1) {
							if (text[i] === '') { text[i] = ' '; }
							newLine = this.insertAndMakeEl(newLine, lighter, text[i], child);
							if (addLines) { lineNum = this.insertLineNum(newLine, lineNum, numType); }
						} else {
							this.insertAndKeepEl(newLine, text[i]);
						}
					}
				}
			
				// Create and insert matched symbol.
				text = match.text.split('\n');
				for (var i = 0; i < text.length; i++) {
					if (i < text.length - 1) {
						newLine = this.insertAndMakeEl(newLine, lighter, text[i], child, match.type);
						if (addLines) { lineNum = this.insertLineNum(newLine, lineNum, numType); }
					} else {
						this.insertAndKeepEl(newLine, text[i], match.type);
					}
				}
			
				pointer = match.end;
			}, this);
		
			// Add last unmatched code segment if it exists.
			if (pointer <= this.code.length) {
				text = this.code.substring(pointer, this.code.length).split('\n');
				for (var i = 0; i < text.length; i++) {
					newLine = this.insertAndMakeEl(newLine, lighter, text[i], child);
					if (addLines) { lineNum = this.insertLineNum(newLine, lineNum, numType); }
				}
			}
		
			// Add alternate line styles based on pseudo-selector.
			if (this.options.altLines !== '') {
				if (this.options.altLines == 'hover') {
					lighter.getElements('.'+this.options.flame+'line').addEvents({
							'mouseover': function () {this.toggleClass('alt');},
							'mouseout':  function () {this.toggleClass('alt');}
					});
				} else {
					if (child[1]) {
						lighter.getChildren(':'+this.options.altLines).getElement('.'+this.options.flame+'line').addClass('alt');
					} else {
						lighter.getChildren(':'+this.options.altLines).addClass('alt');
					}
				}
			}
		
			// Add first/last line classes to correct element based on mode.
			if (child[1]) {
				lighter.getFirst().getChildren().addClass(this.options.flame+'first');
				lighter.getLast().getChildren().addClass(this.options.flame+'last');
			} else {
				lighter.getFirst().addClass(this.options.flame+'first');
				lighter.getLast().addClass(this.options.flame+'last');
			}
		
			// Ensure we return the real parent, not just an inner element like a tbody.
			if (parent[1]) { lighter = lighter.getParent(); }
			return lighter;
		},
		/** Helper function to insert new code segment into existing line. */
		insertAndKeepEl: function (el, text, alias) {
			if (text.length > 0) {
				var span = new Element('span', { 'text': text });
				if (alias) {
					span.addClass(this.fuel.aliases[alias] || alias);
				}
				span.inject(el);
			}
		},
		/** Helper function to insert new code segment into existing line and create new line. */
		insertAndMakeEl: function (el, group, text, child, alias) {
			this.insertAndKeepEl(el, text, alias);
			if (child[1]) { el = el.getParent(); }
			el.inject(group);
		
			var newLine = new Element(child[0]);
			if (child[1]) { newLine = new Element(child[1]).inject(newLine); }
			newLine.addClass(this.options.flame+'line');
			return newLine;
		},
		/** Helper funciton to insert line number into line. */
		insertLineNum: function (el, lineNum, elType) {
			var newNum = new Element(elType, {
				'text':  lineNum++,
				'class': this.options.flame+ 'num'
			});
			newNum.inject(el.getParent(), 'top');
		
			return lineNum;
		},
	
		/******************
		 * Element Methods
		 ******************/
		toElement: function () {
			if (!this.element) {
				this.element = this.builder[this.options.mode]();
				if (this.options.editable) { this.element.set('contenteditable', 'true'); }
			}
		
			return this.element;
		}
	});

	/** Element Native extensions */
	Element.implement({ light: function (options){ return new Lighter(this, options); } });

	/** String functions */
	function chop(str) { return str.replace(/(^\s*\n|\n\s*$)/gi, ''); }	
	function tabToSpaces(str, spaces) {
		for (var i = 0, indent = ''; i < spaces; i++) { indent += ' '; }
		return str.replace(/\t/g, indent);
	}
	
})();