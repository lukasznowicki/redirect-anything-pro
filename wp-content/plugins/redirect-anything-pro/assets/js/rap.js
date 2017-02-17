jQuery( document ).ready( function( $ ) {

	$('#phylax_create_redirect').show();
	$('#phylax_create_redirect').accordion( {
		collapsible: true,
		active: phylax_rap_active,
		heightStyle: 'content',
		icons: {
			header: 'phylax_disabled',
			activeHeader: 'phylax_enabled'
		},
		activate: function( event, ui ) {
			inuse = ui.newHeader.data('redirect_use');
			if ( typeof inuse == 'undefined' ) {
				inuse = 0;
			}
			$('#phylax_redirect_use').val( inuse );
		}
	} );

	$('.pbrapsel').on( 'click', function() {
		$('.pbrapsel').removeClass('pbrapselact');
		$(this).addClass('pbrapselact');
		$('#pbrapsel_select').val( $(this).attr('id') );
	} );
	$('.parapsel').on( 'click', function() {
		$('.parapsel').removeClass('parapselact');
		$(this).addClass('parapselact');
		$('#parapsel_select').val( $(this).attr('id') );
	} );

	$('#rap_list_select select').on( 'change', function() {
		$('.pbrapsel').removeClass('pbrapselact');
		$(this).prev().addClass('pbrapselact');
		$('#pbrapsel_select').val( $(this).prev().attr('id') );
	} );

	$('#rap_list_select input[type="text"]').on( 'input', function() {
		$('.pbrapsel').removeClass('pbrapselact');
		$(this).prev().addClass('pbrapselact');
		$('#pbrapsel_select').val( $(this).prev().attr('id') );
	} );

} );