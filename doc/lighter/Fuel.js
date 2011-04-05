/**
 * Script:
 *   Fuel.js - Language definition engine for Lighter.js
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
var Fuel = new Class({
	
	Implements: [Options],
	options: {
		matchType: "standard",
		strict: false
	},
	language: '',
	
	patterns: new Hash(),
	keywords: new Hash(),
	delimiters: new Hash({
		start: null,
		end: null
	}),

	/************************
	 * Common Regex Rules
	 ***********************/
	common: {	
		slashComments: /(?:^|[^\\])\/\/.*$/gm, // Matches a C style single-line comment.
		poundComments: /#.*$/gm,               // Matches a Perl style single-line comment.
		multiComments: /\/\*[\s\S]*?\*\//gm,   // Matches a C style multi-line comment.
		aposStrings:   /'[^'\\]*(?:\\.[^'\\]*)*'/gm, // Matches a string enclosed by single quotes. Legacy.
		quotedStrings: /"[^"\\]*(?:\\.[^"\\]*)*"/gm, // Matches a string enclosed by double quotes. Legacy.
		multiLineSingleQuotedStrings: /'[^'\\]*(?:\\.[^'\\]*)*'/gm, // Matches a string enclosed by single quotes across multiple lines.
		multiLineDoubleQuotedStrings: /"[^"\\]*(?:\\.[^"\\]*)*"/gm, // Matches a string enclosed by double quotes across multiple lines.
		multiLineStrings:   /'[^'\\]*(?:\\.[^'\\]*)*'|"[^"\\]*(?:\\.[^"\\]*)*"/gm, // Matches both.
		singleQuotedString: /'[^'\\\r\n]*(?:\\.[^'\\\r\n]*)*'/gm, // Matches a string enclosed by single quotes.
		doubleQuotedString: /"[^"\\\r\n]*(?:\\.[^"\\\r\n]*)*"/gm, // Matches a string enclosed by double quotes.
		strings: /'[^'\\\r\n]*(?:\\.[^'\\\r\n]*)*'|"[^"\\\r\n]*(?:\\.[^"\\\r\n]*)*"/gm, // Matches both.
		properties:    /\.([\w]+)\s*/gi,     // Matches a property: .property style.
		methodCalls:   /\.([\w]+)\s*\(/gm,   // Matches a method call: .methodName() style.
		functionCalls: /\b([\w]+)\s*\(/gm,   // Matches a function call: functionName() style.
		brackets:      /\{|\}|\(|\)|\[|\]/g, // Matches any of the common brackets.
		numbers:       /\b((?:(\d+)?\.)?[0-9]+|0x[0-9A-F]+)\b/gi // Matches integers, decimals, hexadecimals.
	},
	
	/************************
	 * Fuel Constructor
	 ***********************/
	initialize: function(code, options, wicks) {
		this.setOptions(options);
		this.wicks = wicks || [];
		this.code = code;
		this.aliases = $H();
		this.rules = $H();
		
		// Set builder object for matchType.
		this.builder = new Hash({
			'standard': this.findMatches,
			'lazy':     this.findMatchesLazy
		});
		
		// Add delimiter rules if not in strict mode
		if (!options.strict) {
			if (this.delimiters.start) this.addFuel('delimBeg', this.delimiters.start, 'de1');
			if (this.delimiters.end)   this.addFuel('delimEnd', this.delimiters.end,   'de2');
		}
		
		// Set Keyword Rules from this.keywords object.
		this.keywords.each(function(keywordSet, ruleName) {
			if (keywordSet.csv != '') {
				this.addFuel(ruleName, this.csvToRegExp(keywordSet.csv, keywordSet.mod || "g"), keywordSet.alias);
			}
		}, this);
		
		// Set Rules from this.patterns object.
		this.patterns.each(function(regex, ruleName) {
			this.addFuel(ruleName, regex.pattern, regex.alias);
		}, this);
		
		/** Process source code based on match type. */
		var codeBeg = 0,
		    codeEnd = this.code.length,
		    codeSeg = '',
		    delim   = this.delimiters,
		    matches = [],
		    match   = null,
		    endMatch = null;
		
		if (!options.strict) {
			// Find matches through the complete source code.
			matches.extend(this.builder[options.matchType].pass(this.code, this)());
		} else if (delim.start && delim.end) {
			// Find areas between language delimiters and find matches there.
			while ((match = delim.start.exec(this.code)) != null ) {
				delim.end.lastIndex = delim.start.lastIndex;
				if ((endMatch = delim.end.exec(this.code)) != null ) {
					matches.push(new Wick(match[0], 'de1', match.index));
					codeBeg = delim.start.lastIndex;
					codeEnd = endMatch.index-1;
					codeSeg = this.code.substring(codeBeg, codeEnd);
					matches.extend(this.builder[options.matchType].pass([codeSeg, codeBeg], this)());
					matches.push(new Wick(endMatch[0], 'de2', endMatch.index));
				}
			}
		}
		this.wicks = matches;
	},
	
	/************************
	 * Regex Helper methods.
	 ***********************/
	addFuel: function(fuelName, RegEx, className) {
		this.rules[fuelName] = RegEx;
		this.addAlias(fuelName, className);
	},
	addAlias: function(key, alias) { this.aliases[key] = alias || key; },
	csvToRegExp: function(csv, mod) {return new RegExp('\\b(' + csv.replace(/,\s*/g, '|') + ')\\b', mod);},
	delimToRegExp: function(beg, esc, end, mod, suffix) {
		beg = beg.escapeRegExp();
		if (esc) esc = esc.escapeRegExp();
		end = (end) ? end.escapeRegExp() : beg;
		var pat = (esc) ? beg+"[^"+end+esc+'\\n]*(?:'+esc+'.[^'+end+esc+'\\n]*)*'+end : beg+"[^"+end+'\\n]*'+end;
		return new RegExp(pat+(suffix || ''), mod || '');
	},
	strictRegExp: function() {
		var regex = '(';
		for (var i = 0; i < arguments.length; i++) {
			regex += arguments[i].escapeRegExp();
			regex += (i < arguments.length - 1) ? '|' : '';
		}
		regex += ')';
		return new RegExp(regex, "gim");
	},
	
	/************************
	 * Match finding Methods
	 ***********************/
	findMatches: function(code, offset) {
		var wicks       = [],
		    startIndex  = 0,
		    matchIndex  = code.length
		    insertIndex = 0,
		    match      = null,
		    type       = null,
		    newWick    = null,
		    rule       = null,
		    rules      = {},
		    currentMatch = null,
		    futureMatch  = null;
		
		offset = offset || 0;
		
		// Create assosciative array of rules for faster access via for...in loop instead of .each().
		this.rules.each(function(regex, rule) {
			rules[rule] = {pattern: regex, nextIndex: 0};
		}, this);
			
		/**
		 * Step through the source code sequentially finding the left-most/earliest matches and then
		 * continuing beyond the end of that match to prevent parser from adding inner matches.
		 */
		while(startIndex < code.length) {
			matchIndex = code.length;
			match      = null;
			
			// Apply each rule at the current startIndex.
			for (rule in rules) {
				rules[rule].pattern.lastIndex = startIndex;
				currentMatch = rules[rule].pattern.exec(code);
				if (currentMatch === null) {
					// Delete rule if there's no matches.
					delete rules[rule];
				} else {
					// Find earliest and longest match, then store relevant info.
					if (currentMatch.index < matchIndex || (currentMatch.index == matchIndex && match[0].length < currentMatch[0].length)) {
						match      = currentMatch;
						type       = rule;
						matchIndex = currentMatch.index;
					}
					// Store index of rules' next match in nextIndex property.
					rules[rule].nextIndex = rules[rule].pattern.lastIndex - currentMatch[0].length;
				}
			}
			/* Create a new Wick out of found match. Otherwise break out of loop since no
			   matches are left. */
			if (match != null) {
			
				// If $1 capture group exists, use $1 instead of full match.
				index = (match[1] && match[0].contains(match[1])) ? match.index + match[0].indexOf(match[1]) : match.index;
				newWick = new Wick(match[1] || match[0], type, index+offset);
				wicks.push(newWick);
				
				/* Find the next match of current rule and store its index. If not done, the nextIndex
				   would be at the start of current match, thus creating an infinite loop*/
				futureMatch = rules[type].pattern.exec(code);
				if (!futureMatch) {
					rules[type].nextIndex = code.length;
				} else {
					rules[type].nextIndex = rules[type].pattern.lastIndex - futureMatch[0].length;
				}
				
				// Cycle through "nextIndex" properties and store earliest position in min variable.
				var min = code.length;
				for (rule in rules) {
					if (rules[rule].nextIndex < min) {
						min = rules[rule].nextIndex;
					}
				}
				/* Set startIndex to the end of current match if min is located behind it. Normally this
				   would signal an inner match. Future upgrades should do this test in the min loop
				   in order to find the actual earliest match. */
				startIndex = Math.max(min, newWick.end - offset);
			} else {
				break;
			}
		}
		return wicks;
	},
	/* Brute force the matches by finding all possible matches from all rules. Then we sort them
	   and cycle through the matches finding and eliminating inner matches. Faster than findMatches,
	   but less robust and prone to erroneous matches. */
	findMatchesLazy: function(code, offset) {
		var wicks = this.wicks,
		    match = null
		    index = 0;
		
		offset = offset || 0;
		
		this.rules.each(function(regex, rule) {
			while ((match = regex.exec(code)) != null) {
				index = (match[1] && match[0].contains(match[1])) ? match.index + match[0].indexOf(match[1]) : match.index;
				wicks.push(new Wick(match[1] || match[0], rule, index + offset));
			}
		}, this);
		return this.purgeWicks(wicks);
	},
	purgeWicks: function(wicks) {
		wicks = wicks.sort(this.compareWicks);
		for (var i = 0, j = 0; i < wicks.length; i++) {
			if (wicks[i] == null) continue;
			for (j = i+1; j < wicks.length && wicks[i] != null; j++) {
				if      (wicks[j] == null)            {continue;}
				else if (wicks[j].isBeyond(wicks[i])) {break;}
				else if (wicks[j].overlaps(wicks[i])) {wicks[i] = null;}
				else if (wicks[i].contains(wicks[j])) {wicks[j] = null;}
			}
		}
		return wicks.clean();
	},
	compareWicks: function(wick1, wick2) {return wick1.index - wick2.index;}
});

Fuel.standard = new Class({ Extends: Fuel, initialize: function(code, options, wicks) { this.parent(code, options, wicks); } });

var Wick = new Class({

	initialize: function(match, type, index) {
		this.text   = match;
		this.type   = type;
		this.index  = index;
		this.length = this.text.length;
		this.end    = this.index + this.length;
	},
	contains: function(wick) { return (wick.index >= this.index && wick.index < this.end); },
	isBeyond: function(wick) { return (this.index >= wick.end); },
	overlaps: function(wick) { return (this.index == wick.index && this.length > wick.length); },
	toString: function() { return this.index+' - '+this.text+' - '+this.end; }
});
