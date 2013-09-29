/**
 * JS for centre modal windows
 */

var centre = function(url){
	
	/* **************************** */
	/*         Organisations        */
	/* **************************** */
	$("#add-organisation").on('click', function(){
		$("#organisation-title").html("Nouvelle organisation");
		$("#organisation-form").load(url+'/centre/formorganisation');
	});
	
	$(".mod-organisation").on('click', function(){
		$("#organisation-title").html('Modification de <em>'+$(this).data('name')+'</em>');
		$("#organisation-form").load(url+'/centre/formorganisation?id='+$(this).data('id'));
	});
	
	$("#organisation-container").on('click', 'input[type=submit]', function(event){
		event.preventDefault();
		$.post(url+'/centre/saveorganisation', $("#Organisation").serialize(), function(data){
			location.reload();
		}, 'json');
	});
	
	$(".delete-organisation").on('click', function(event){
		$('a#delete-organisation-href').attr('href', $(this).data('href'));
		$('#organisation-name').html($(this).data('name'));
		$("#delete-organisation-href").data('id', $(this).data('id'));
	});
	
	$("#confirm-delete-organisation").on('click', '#delete-organisation-href', function(event){
		event.preventDefault();
		var me = $(this);
		$("#confirm-delete-organisation").modal('hide');
		$.post(me.attr('href'), function(){
			location.reload();
		});
	});
	
	/* **************************** */
	/*        Zone de qualif        */
	/* **************************** */
	$("#add-qualif").on('click', function(){
		$("#qualif-title").html("Nouvelle zone de qualification");
		$("#qualif-form").load(url+'/centre/formqualif');
	});
	
	$(".mod-qualif").on('click', function(){
		$("#qualif-title").html('Modification de <em>'+$(this).data('name')+'</em>');
		$("#qualif-form").load(url+'/centre/formqualif?id='+$(this).data('id'));
	});
	
	$("#qualif-container").on('click', 'input[type=submit]', function(event){
		event.preventDefault();
		$.post(url+'/centre/savequalif', $("#QualificationZone").serialize(), function(data){
			location.reload();
		}, 'json');
	});
	
	$(".delete-qualif").on('click', function(event){
		$('a#delete-qualif-href').attr('href', $(this).data('href'));
		$('#qualif-name').html($(this).data('name'));
		$("#delete-qualif-href").data('id', $(this).data('id'));
	});
	
	$("#confirm-delete-qualif").on('click', '#delete-qualif-href', function(event){
		event.preventDefault();
		var me = $(this);
		$("#confirm-delete-qualif").modal('hide');
		$.post(me.attr('href'), function(){
			location.reload();
		});
	});
	
	/* **************************** */
	/*      Groupe de secteurs      */
	/* **************************** */
	$("#add-group").on('click', function(){
		$("#group-title").html("Nouveau groupe de secteurs");
		$("#group-form").load(url+'/centre/formgroup');
	});
	
	$(".mod-group").on('click', function(){
		$("#group-title").html('Modification de <em>'+$(this).data('name')+'</em>');
		$("#group-form").load(url+'/centre/formgroup?id='+$(this).data('id'));
	});
	
	$("#group-container").on('change', 'select[name=zone]', function(){
		$.getJSON(url+'/centre/getsectors?zone='+$(this).val(), function(data){
			var select = $("#group-container select[name=sectors\\[\\]]");
			var options = select.prop('options');
			$('option', select).remove();
			$.each(data, function(key, value){
				options[options.length] = new Option(value, key);
			});
		});
	});
	
	$("#group-container").on('click', 'input[type=submit]', function(event){
		event.preventDefault();
		$.post(url+'/centre/savegroup', $("#SectorGroup").serialize(), function(data){
			location.reload();
		}, 'json');
	});
	
	$(".delete-group").on('click', function(event){
		$('a#delete-group-href').attr('href', $(this).data('href'));
		$('#group-name').html($(this).data('name'));
		$("#delete-group-href").data('id', $(this).data('id'));
	});
	
	$("#confirm-delete-group").on('click', '#delete-group-href', function(event){
		event.preventDefault();
		var me = $(this);
		$("#confirm-delete-group").modal('hide');
		$.post(me.attr('href'), function(){
			location.reload();
		});
	});
	
	/* **************************** */
	/*            Secteurs          */
	/* **************************** */
	$("#add-sector").on('click', function(){
		$("#sector-title").html("Nouveau  secteur");
		$("#sector-form").load(url+'/centre/formsector');
	});
	
	$(".mod-sector").on('click', function(){
		$("#sector-title").html('Modification de <em>'+$(this).data('name')+'</em>');
		$("#sector-form").load(url+'/centre/formsector?id='+$(this).data('id'));
	});

	$("#sector-container").on('change', 'select[name=zone]', function(){
		$.getJSON(url+'/centre/getgroups?zone='+$(this).val(), function(data){
			var select = $("#sector-container select[name=sectorsgroups\\[\\]]");
			var options = select.prop('options');
			$('option', select).remove();
			$.each(data, function(key, value){
				options[options.length] = new Option(value, key);
			});
		});
	});
	
	$("#sector-container").on('click', 'input[type=submit]', function(event){
		event.preventDefault();
		$.post(url+'/centre/savesector', $("#Sector").serialize(), function(data){
			location.reload();
		}, 'json');
	});
	
	$(".delete-sector").on('click', function(event){
		$('a#delete-sector-href').attr('href', $(this).data('href'));
		$('#sector-name').html($(this).data('name'));
		$("#delete-sector-href").data('id', $(this).data('id'));
	});
	
	$("#confirm-delete-sector").on('click', '#delete-sector-href', function(event){
		event.preventDefault();
		var me = $(this);
		$("#confirm-delete-sector").modal('hide');
		$.post(me.attr('href'), function(){
			location.reload();
		});
	});
};