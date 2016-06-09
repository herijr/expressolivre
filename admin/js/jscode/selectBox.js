var SelectBox = new function() {
	
	this.swap = function( origin, destiny ) {
		$('#'+destiny).append($('#'+origin+' option:selected'));
		$('#'+destiny).val('');
		SelectBox.sort(destiny);
		return true;
	};
	
	this.sort = function( id ) {
		$('#'+id).append($('#'+id+' option').remove().sort(function(a, b) {
			var at = $(a).text(), bt = $(b).text();
			return (at > bt)?1:((at < bt)?-1:0);
		}));
	};
	
	this.update = function( origin, destiny ) {
		$('#'+destiny).html($('#'+origin).html());
		$('#'+destiny+' option').prop('selected', 'selected');
	};
}
