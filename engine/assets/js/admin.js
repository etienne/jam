$(document).ready(function(){
	// Allow sort
	//if ($('table.sortable')) {
		// Don't display sortIndex column
//		$('table.sortable th:first-child, table.sortable td:first-child').hide();
		
		// Display link to allow reorder
//	}

	// Make table headers clickable
	$('table.items th:not(.draggable)').hover(
		function() {
			$(this).addClass('hover');
		},
		function() {
			$(this).removeClass('hover');
		}
	).click(
		function() {
			location.href = $(this).children('a').attr('href');
		}
	)
	
	// Change table row behavior
	$('table.items td:not(.delete, .draggable)').hover(
		function() {
			$(this).parents('tr').addClass('hover');
		},
		function() {
			$(this).parents('tr').removeClass('hover');
		}
	).click(
		function() {
			location.href = $(this).parents('tr').children('td:first-child').children('a').attr('href');
		}
	);
	
	// FCKEditor
	if ($('textarea.wysiwyg').length > 0) {
		$('textarea.wysiwyg').attr('id', 'wysiwyg').parents('div').width('560px');
		
		// Find base path for FCKEditor
		$('script').each(function(index) {
			var string = $(this).attr('src');
			var index;
			if (index = string.indexOf('assets/js/')) {
				basePath = string.substring(0, index);
				return false;
			}
		});

		var oFCKeditor = new FCKeditor('wysiwyg');
		oFCKeditor.Height = '480';
		oFCKeditor.BasePath = basePath + "assets/js/fckeditor/";
		oFCKeditor.ReplaceTextarea() ;
	}
	
	/*
	// Delete warning
	$('ul.actions a.delete').click(function() {
		var warning = $('div.warning');
		if (!warning.length) {
			$('ul.actions').after('<div class="warning">Voulez-tu vraiment faire des affaires?</div>');
			warning = $('div.warning');
		}
		warning.hide().slideDown();
		$('ul.actions').slideUp();
		return false;
	});
	*/
	
	// Add new selector for case-insensitive :contains(); code by Erik Beeson
	jQuery.extend(jQuery.expr[':'], {
		containsIgnoreCase: "(a.textContent||a.innerText||jQuery(a).text()||'').toLowerCase().indexOf((m[3]||'').toLowerCase())>=0"
	});
	
	// Add search field
	// $('table.items').before('<input type="search" class="search" />');
	
	function filterList() {
		filterText = $(this).attr('value');
		if (filterText.length > 0) {
			$('table.items tbody tr').css('display', 'none');
			$('table.items tbody tr:containsIgnoreCase('+ filterText +')').css('display', 'table-row');
		} else {
			$('table.items tbody tr').css('display', 'table-row');
		}
	}
	
	$('input.search').keyup(filterList);
	
 	function updateFieldVisibility() {
		$('.hidden').hide();
		$('select, :checked').each(function() {
			targetDiv = $('.' + $(this).attr('name') + $(this).val())
			if (targetDiv.length > 0) {
				targetDiv.show();
			}
		})
	}

	$('select, :checkbox').change(function() {
		updateFieldVisibility();
	})
	
	updateFieldVisibility();
	
});