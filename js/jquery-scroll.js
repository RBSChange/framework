(function( $ ){		  
	$.fn.scrollTo = function( target, settings ){
		settings = settings || {}; //no mandatory settings.
		var attr = {},
			pos  = settings.horizontal ? 'left' : 'top',
			key  = 'scroll' + pos.charAt(0).toUpperCase() + pos.substring(1),//scrollLeft || scrollTop
			parts;					
		return this.each(function(){
			switch( typeof target ){
				case 'string':
					if( parts = /^([+-])?(=)?\d+(px)?$/.exec(target) ){
						if( !settings.speed && (parts[1]||parts[2]||parts[3]) )//advanced animation requested
							settings.speed = 1;
						break;//skip this one.
					}
					target = $(target,this);// relative selector, no break!
				case 'object'://a DOM element
					target = $(target).offset()[pos] + this[key];//get the real position of the element
					if( !$(this).is('html,body') )//if not checked, will fail if dimensions is included, grrr!
						 target -= $(this).offset()[pos]
			}
			
			if( settings.speed ){//animation is required
				attr[key] = target;
				$(this).animate( attr, settings.speed, settings.easing, settings.onafter );
			}else{//if no speed was given, just alter the scroll value
				this[key] = target;
				if( settings.onafter )
					settings.onafter.call(this);
			}
		});
	};			
	$.scrollTo = function( target, settings ){
		return $('html,body').scrollTo( target, settings );
	};		
})( jQuery );