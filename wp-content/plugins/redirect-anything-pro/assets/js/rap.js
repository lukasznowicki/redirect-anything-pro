jQuery( document ).ready( function( $ ) {

	$('#phylax_create_redirect').accordion( {
		collapsible: true,
		active: phylax_rap_active,
		icons: {
			header: 'phylax_disabled',
			activeHeader: 'phylax_enabled'
		}
	} );

} );