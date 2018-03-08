toggler = {};

toggler.registerTrigger = function( el ) {
	$("#" + el.dataset.target).find( ".toggleMe" ).toggle();
	$(el).click( function() {
		$("#"+this.dataset.target).find( ".toggleMe").toggle();
		if ( this.innerHTML == this.dataset.expand ) {
			this.innerHTML = this.dataset.collapse;
		} else {
			this.innerHTML = this.dataset.expand;
		}
	} );
};

$(document).ready( function() {
	Array.prototype.filter.call(
		document.getElementsByClassName( "toggleLink" ),
		function( el ) {
			toggler.registerTrigger( el );
		}
	);
} );
