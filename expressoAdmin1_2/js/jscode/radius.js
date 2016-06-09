( function()
{
	var radius_option_unselected;
	var radius_option_selected;

	function selects( )
	{
		var selects = document.getElementById( 'tabcontent8' ).getElementsByTagName( 'select' );

		for ( var i = 0; i < selects.length; i++ )
		{
			switch( selects.item( i ).name )
			{
				case 'radius_option_unselected[]' : radius_option_unselected = selects.item( i ); break;
				case 'radius_option_selected[]' : radius_option_selected = selects.item( i ); break;
			}
		}
	}

	function edit( origin, target )
	{
		if ( origin == undefined || target == undefined ) return false;
		for ( var option = origin.options.length; option > 0 ; option-- )
			if ( origin.options.item( option - 1 ).selected )
			{
				var opt = target.appendChild( origin.options.item( option - 1 ) );
				opt.selected = false;
			}
	}

	function add( )
	{
		edit( radius_option_unselected, radius_option_selected );
	}

	function remove( )
	{
		edit( radius_option_selected, radius_option_unselected );
	}

	function submit( )
	{
		for ( var opt = 0; opt < radius_option_selected.options.length; opt++ )
			radius_option_selected.options.item( opt ).selected = true;
	}

	window.ExpressoLivre = {
		'ExpressoAdmin' : {
			'radius' : {
				'end' : submit,
				'init' : selects,
				'options' : {
					'add' : add,
					'remove' : remove
				}
			}
		}
	}
})( );