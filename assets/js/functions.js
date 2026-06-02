jQuery.noConflict();

function cfdevEscHtml(str) {
	return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function cfdevFileSvg(mime) {
	const map = [
		[/^application\/pdf$/,                             'PDF', '#e74c3c'],
		[/word/,                                           'DOC', '#2980b9'],
		[/sheet|excel/,                                    'XLS', '#27ae60'],
		[/presentation|powerpoint/,                        'PPT', '#e67e22'],
		[/^image\//,                                       'IMG', '#8e44ad'],
		[/zip|rar|tar/,                                    'ZIP', '#7f8c8d'],
		[/^video\//,                                       'VID', '#c0392b'],
		[/^audio\//,                                       'AUD', '#16a085'],
	];
	let label = 'FILE', color = '#95a5a6';
	for (let i = 0; i < map.length; i++) {
		if (map[i][0].test(mime || '')) { label = map[i][1]; color = map[i][2]; break; }
	}
	return '<svg class="cfdev-file-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="29" viewBox="0 0 36 44" aria-hidden="true">'
		+ '<path d="M0 4C0 1.8 1.8 0 4 0h18l14 14v26c0 2.2-1.8 4-4 4H4C1.8 44 0 42.2 0 40V4z" fill="' + color + '"/>'
		+ '<path d="M22 0l14 14H26c-2.2 0-4-1.8-4-4V0z" fill="rgba(0,0,0,.2)"/>'
		+ '<text x="18" y="32" font-family="Arial,sans-serif" font-size="11" font-weight="bold" fill="#fff" text-anchor="middle">' + label + '</text>'
		+ '</svg>';
}

jQuery( function( $ ) {

	let add_events;

	(add_events = function( object ) 
	{
		// Datepicker
		$('.js-cfdev-datepicker', object).map(function(){
			return $(this).datepicker({ dateFormat: $(this).data('date-format') });
		});

		// Timepicker
		$('.js-cfdev-timepicker', object).map(function(){
			return $(this).timepicker({ timeFormat: $(this).data('time-format') });
		});

		// Datetime
		$('.js-cfdev-datetimepicker', object).map(function(){
			return $(this).datetimepicker({ 
				timeFormat: $(this).data('time-format'),
				dateFormat: $(this).data('date-format')
			});
		});
		
		// Colorpicker
		$('.js-cfdev-colorpicker', object).wpColorPicker();

		// Tabs
		$('.js-cfdev-tabs', object).tabs();

		// Auto-activate the first tab panel that contains a validation error
		$('.js-cfdev-tabs', object).each(function () {
			const $tabsWidget = $(this);
			$tabsWidget.children('div[id]').each(function (i) {
				if ($(this).find('.cfdev-has-error').length) {
					$tabsWidget.tabs('option', 'active', i);
					return false;
				}
			});
		});

		// Slider (jQuery UI legacy)
		$( '.js-slider', object ).slider();

		// Range
		$('.js-cfdev-range', object).each(function() {
			$(this).siblings('.js-cfdev-range-output').text($(this).val());
		}).on('input', function() {
			$(this).siblings('.js-cfdev-range-output').text($(this).val());
		});

		// Accordion
		$('.js-cfdev-accordion', object).accordion({
			heightStyle: "content"
		});

		// Auto-activate the first accordion section that contains a validation error
		$('.js-cfdev-accordion', object).each(function () {
			const $acc = $(this);
			$acc.children('div').each(function (i) {
				if ($(this).find('.cfdev-has-error').length) {
					$acc.accordion('option', 'active', i);
					return false;
				}
			});
		});

		// Sortable
		$('.js-cfdev-sortable', object).each(function() {
			if ( ! $(this).data('ui-sortable') ) {
				$(this).sortable({
					items:  '> li',
					handle: '> .cfdev-handle-sortable'
				});
			}
		});

		// Gallery — sortable items
		$('.js-cfdev-gallery-items', object).each(function() {
			if (!$(this).data('ui-sortable')) {
				$(this).sortable({ items: '> .js-cfdev-gallery-item' });
			}
		});

		// Gallery — add images
		$('.js-cfdev-gallery', object).on('click', '.js-cfdev-gallery-add', function(e) {
			e.preventDefault();
			const wrap  = $(this).closest('.js-cfdev-gallery');
			const items = wrap.find('.js-cfdev-gallery-items');
			const name  = wrap.data('field-name');

			const uploader = wp.media({ title: 'Select Images', multiple: true, library: { type: 'image' } });

			uploader.on('select', function() {
				uploader.state().get('selection').each(function(attachment) {
					const a   = attachment.toJSON();
					const url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
					items.append(
						'<div class="cfdev-gallery-item js-cfdev-gallery-item">' +
						'<input type="hidden" name="' + name + '" value="' + a.id + '" />' +
						'<img src="' + url + '" />' +
						'<button type="button" class="cfdev-gallery-remove js-cfdev-gallery-remove" aria-label="' + Cfdev.remove_image + '">&times;</button>' +
						'</div>'
					);
				});
			});

			uploader.open();
		});

		// Gallery — remove one image
		$('.js-cfdev-gallery', object).on('click', '.js-cfdev-gallery-remove', function(e) {
			e.preventDefault();
			$(this).closest('.js-cfdev-gallery-item').remove();
		});

		// Remove current attached image
		$('.cfdev-td, .form-field', object).on( 'click', '.js-cfdev-remove-media', function()
		{
			const that 	= $( this ),
				td 		= that.closest('.cfdev-field, .cfdev-td, .form-field');

			$( '.cfdev-preview', td ).html('');
			$( '.cfdev-hidden', td ).val('');
			
			that.hide();
			
			return false;
		});

		// Upload image
		$('.cfdev-td, .form-field', object).on( 'click', '.js-cfdev-upload', function()
		{
			const that	= $(this),
				type 	= that.data('cfdev-media-type'),
				parent	= that.closest('.cfdev-field, .cfdev-td, .form-field'),
				hidden 	= $( '.cfdev-hidden', parent ),
				preview = $( '.cfdev-preview', parent );
			let preview_size,
				attachment,
				_cfdev_uploader; // eslint-disable-line prefer-const -- réassigné plus bas (faux positif : déclaration sans valeur initiale)

			try {
				preview_size  = $.parseJSON( that.data('cfdev-media-preview-size') );
			} catch(_e) { // eslint-disable-line no-unused-vars -- ecmaVersion 2017 requiert un paramètre catch
				preview_size  = that.data('cfdev-media-preview-size');
			}

			// if( Cfdev.wp_version >= '3.5' )
			// {
				if( _cfdev_uploader ) 
				{
					_cfdev_uploader.open();
	            	return;
	        	}

	        	//Extend the wp.media object
		        _cfdev_uploader = wp.media.frames.file_frame = wp.media({
		            multiple: false,
		            library: type === 'image' ? { type: 'image' } : {},
		        });

		        // Send the data to the fields
		        _cfdev_uploader.on('select', function() {
	            	attachment = _cfdev_uploader.state().get('selection').first().toJSON();

	            	// (Re)set the remove button
	            	$('.js-cfdev-remove-media', parent).remove();
	            	that.after('<button type="button" class="js-cfdev-remove-media cfdev-remove-media">' + ( type === 'image' ? Cfdev.remove_image : Cfdev.remove_file ) + '</button> ');

	            	// Send an id or url to the field and set the preview
	            	if( type === 'image' )
					{
						const thumbnail = preview_size && !$.isArray(preview_size) && attachment.sizes[preview_size] ? attachment.sizes[preview_size] : ( attachment.sizes.medium ? attachment.sizes.medium : attachment.sizes.full );
						if( $.isArray( preview_size ) ) {
							if( parseInt( preview_size[0] ) > 0 )
								thumbnail.width = parseInt( preview_size[0] );
							if( parseInt( preview_size[1] ) > 0 )
								thumbnail.height = parseInt( preview_size[1] );
						}

						preview.html('<img src="' + thumbnail.url + '" height="' + thumbnail.height + '" width="' + thumbnail.width + '" alt="" />')
						hidden.val( attachment.id );
					}
					else
					{
						preview.html('<span class="cfdev-mime"><a href="' + encodeURI(attachment.url) + '" target="_blank">' + cfdevFileSvg(attachment.mime) + '<span class="cfdev-file-name">' + cfdevEscHtml(attachment.title) + '</span></a></span>' );
						hidden.val( attachment.id );
					}
	        	});

	        	_cfdev_uploader.open();
			// }
			// else
			// {
			// 	var uploadID 	= hidden,
			//     	spanID 		= preview;

			//     var	_original_send_to_editor = window.send_to_editor;
			    
			// 	tb_show( '', 'media-upload.php?post_id=0&type=image&TB_iframe=true' );
				
			// 	window.send_to_editor = function( html ) 
			// 	{
			// 		if( type == 'image' )
			// 		{
			// 			// Add image source to the hidden field
			// 		    img 	= $(html).find('img');
			// 		    imgID 	= html.match(/wp-image-(\d+)/g)[0].split('-')[2];

			// 		    uploadID.val( imgID );

			// 			// Add the image to the preview
			// 			html 	= $(html).find('img');
			// 		    spanID.html( html );
			// 		}
			// 		else
			// 		{
			// 			url		= $(html).attr('href');
			// 			uploadID.val( url );

			// 			anchor	= $(html).find('img').attr('title');
			// 			html	= $('<span class="cfdev-mime"><a href="' + url + '" target="_blank">' + anchor + '</a></span>' );
			// 			spanID.html( html );
			// 		}
					
			// 		// Close Wordpress media popup
			// 		tb_remove();

			// 		$('.js-cfdev-remove-media', parent).remove();
			// 		that.after('<a href="#" class="js-cfdev-remove-media cfdev-remove-media">' + ( type == 'image' ? Cfdev.remove_image : Cfdev.remove_file ) + '</a> ');

			// 		// Reset default function
			// 		window.send_to_editor = _original_send_to_editor;
			// 	}
			// }

			return false;
		});
	})( $('body') );

	// When a postbox is opened, activate any tab/accordion panel that contains a validation error.
	// Complements the add_events page-load activation which only covers postboxes already open.
	$(document).on('postbox-toggled', function (e, box) {
		const $box = $(box);
		if (!$box.length || $box.hasClass('closed')) { return; }
		$box.find('.js-cfdev-tabs').each(function () {
			const $w = $(this);
			$w.children('div[id]').each(function (i) {
				if ($(this).find('.cfdev-has-error').length) {
					$w.tabs('option', 'active', i);
					return false;
				}
			});
		});
		$box.find('.js-cfdev-accordion').each(function () {
			const $w = $(this);
			$w.children('div').each(function (i) {
				if ($(this).find('.cfdev-has-error').length) {
					$w.accordion('option', 'active', i);
					return false;
				}
			});
		});
	});

	function init_editors( object, settings ) {
		const editors = $('.wp-editor-wrap', object);
		
		if( Cfdev.wp_version >= '3.9' && editors.length )
		{
			editors.each(function() {
				$('.mce-tinymce, .quicktags-toolbar', this).remove();
				const new_id = $('.cfdev-input', this).attr('id');

				const editor_settings = $.extend( true, { tmce: {}, quicktags: { id: new_id, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close' } }, settings );

				// Clone tinyMCEPreInit.mceInit object
				tinyMCEPreInit.mceInit[new_id] = editor_settings.tmce;

				// Clone QTags instance
				new QTags(editor_settings.quicktags);
				QTags._buttonsInit();

				// Switch to Visual/Text mode
				let mode = 'html';
				if( $(this).hasClass('tmce-active') )
					mode = 'tmce';
				switchEditors.go(new_id, mode);

				$(this).on( 'click', '.insert-media', function( event ) {
					const elem = $( event.currentTarget ),
						editor = elem.data('editor'),
						options = {
							frame:    'post',
							state:    'insert',
							title:    wp.media.view.l10n.addMedia,
							multiple: true
						};

					wpActiveEditor = editor;
					
					event.preventDefault();

					elem.blur();

					if ( elem.hasClass( 'gallery' ) ) {
						options.state = 'gallery';
						options.title = wp.media.view.l10n.createGalleryTitle;
					}

					wp.media.editor.open( editor, options );
				});
			});
		}
	}

	// Remove sortable
	$('.cfdev').on( 'click', '.js-cfdev-remove-sortable', function() 
	{
		const that 		= $( this ),
			field 		= that.closest('.js-cfdev-sortable-item'),
			wrap 		= that.closest('.js-cfdev-sortable'),
			fields 		= $( '.js-cfdev-sortable-item', wrap ).length;
		
		if( fields > 1 ) { field.remove(); }
		if( fields === 2 ){ $( '.js-cfdev-sortable-item', wrap ).find('.js-cfdev-remove-sortable').remove(); }
	});		
	
	// Add sortable
	$('.cfdev').on( 'click', '.js-cfdev-add-sortable', function() 
	{
		const that			= $( this );
		const	parent 			= that.closest( '.cfdev-td, .cfdev' ),
			wrap 			= that.hasClass( 'js-cfdev-add-bundle' )
								? that.closest( '.padding-wrap' ).children( '.js-cfdev-sortable' )
								: $( '.js-cfdev-sortable', parent ),
			is_bundle		= wrap.data( 'cfdev-sortable-type') === 'bundle',
			last 			= $( '> .js-cfdev-sortable-item:last', wrap ),
			handle 			= '<button type="button" class="cfdev-handle-sortable js-cfdev-handle-sortable" aria-label="' + Cfdev.drag_to_reorder + '"></button>',
			remover 		= '<button type="button" class="cfdev-remove-sortable js-cfdev-remove-sortable" aria-label="' + Cfdev.remove + '"></button>',
			new_item 		= last.clone( false, false );

		let is_editor = false;
		let tinyMCE_options = {};
		let QTags_options = {};
		
		// Set new bundle array key
		if( is_bundle )
		{
			new_item.find('tr').each(function() {

				const cfdev_input = $(this).find('.cfdev-input');
				// Declared here so both Wysiwyg blocks share the same scope.
				let last_id, last_name;

				if(cfdev_input.length > 1 && cfdev_input.first().parent().hasClass('cfdev-sortable-item'))
					$(this).find('.cfdev-input:not(:first)').parent().remove();

				// Checkboxes and radios to default value
				if( cfdev_input.attr('type') === 'checkbox' || cfdev_input.attr('type') === 'radio' ) {
					cfdev_input.each(function() {
						$(this).removeAttr('checked').prop('checked', false);

						const default_value = $(this).closest('.cfdev-checkboxes-wrap').data('default-value');
						if( default_value !== undefined && (default_value + '').length && default_value === $(this).val() )
							$(this).attr('checked', 'checked').prop('checked', true);
					});
				}

				// Wysiwyg
				if( cfdev_input.hasClass('wp-editor-area') ) {
					last_id = cfdev_input.attr('id');
					last_name = cfdev_input.attr('name');
					$(this).find('span.mceEditor').remove();
					cfdev_input.show();
				}

				// New name and id attributes
				cfdev_input.attr('name', function( i, val ) { return val ? val.replace( /\[(\d+)\]/, function( match, n ) { return '[' + ( Number(n) + 1 ) + ']'; }) : val; }).attr('id', function( i, val ) { return val ? val.replace( /_(\d+)/, function( match, n ) { return '_' + ( Number(n) + 1 ); }) : val; }).removeClass('hasDatepicker');

				// Set label for new id
				$(this).find('label').attr('for', cfdev_input.attr('id'));

				// Color
				if( cfdev_input.hasClass('cfdev-colorpicker') ) {
					cfdev_input.attr('value', '');
					$(this).find('.cfdev-td').html(cfdev_input.clone( false ));
				}

				// Select
				if( cfdev_input.hasClass('cfdev-select') ) {
					cfdev_input.each(function() {
						const default_value = $(this).data('default-value');
						$(this).find('option').removeAttr('selected').prop('selected', false);
						if( default_value !== undefined && (default_value + '').length ) {
							$(this).find('option').each(function() {
								if( $(this).val() === default_value )
									$(this).attr('selected', 'selected').prop('selected', true);
							});
						}
					});
				}

				// Add new wysiwyg
				if( cfdev_input.hasClass('wp-editor-area' )) {
					is_editor = true;
					const new_id = cfdev_input.attr('id'), new_name = cfdev_input.attr('name'), last_id_regexp = new RegExp(last_id, 'g'), last_name_regexp = new RegExp(last_name, 'g');
					$(this).html( $(this).html().replace( last_name_regexp, new_name ).replace( last_id_regexp, new_id ) );

					if( Cfdev.wp_version >= '3.9' )
					{
						tinyMCE_options = $.extend(true, {}, tinyMCEPreInit.mceInit[last_id]);
						const QTags_last = QTags.getInstance(last_id);
						$.each( tinyMCE_options, function( key, value ) {
							if( $.type( value ) === 'string' )
							tinyMCE_options[key] = value.replace(last_id_regexp, new_id);
						} );

						QTags_options = { id: new_id, buttons: QTags_last.settings.buttons };
					}
					// else
					// {
					// 	// Clone tinyMCEPreInit.mceInit object
					// 	tinyMCEPreInit.mceInit[new_id] = tinyMCEPreInit.mceInit[last_id];
					// 	tinyMCEPreInit.mceInit[new_id].body_class = tinyMCEPreInit.mceInit[new_id].body_class.replace( last_id_regexp, new_id );
					// 	tinyMCEPreInit.mceInit[new_id].elements = tinyMCEPreInit.mceInit[new_id].elements.replace( last_id_regexp, new_id );

					// 	// Clone QTags instance
					// 	QTags.instances[new_id] = QTags.instances[last_id];
					// 	QTags.instances[new_id].canvas = cfdev_input[0];
					// 	QTags.instances[new_id].id = new_id;
					// 	QTags.instances[new_id].settings.id = new_id;
					// 	QTags.instances[new_id].name = 'qt_' + new_id;
					// 	QTags.instances[new_id].toolbar = $(this).find('.quicktags-toolbar')[0];

					// 	var mode = 'html';
					// 	if( $(this).find('.wp-editor-wrap').hasClass('tmce-active') )
					// 		mode = 'tmce';
					// 	switch_editors.push({'id': new_id, 'mode': mode});
					// }
				}
			});
		}
		
		// Reset data
		new_item.find('input:not([type="radio"],[type="checkbox"],[type="button"],[type="submit"])').val('').attr('value', '');
		new_item.find('textarea').val('');
		new_item.find('select').prop('selectedIndex', 0).find('option').removeAttr('selected').prop('selected', false);
		new_item.find('.js-cfdev-remove-media').remove();
		new_item.find('.cfdev-preview').html('');

		// Add the new item
		new_item.appendTo( wrap );

		// Refresh sortable so the new item is draggable immediately
		if( wrap.data('ui-sortable') ) {
			wrap.sortable('refresh');
		}

		// Add events to the new item
		add_events(new_item);

		// Init Editors
		if( Cfdev.wp_version >= '3.9' && is_editor )
			init_editors( new_item, {tmce: tinyMCE_options, quicktags: QTags_options} );

		// Add new handler and remover if necessary
		$('> .js-cfdev-sortable-item', wrap).each(function( index, item ) {
			if( $('> .js-cfdev-handle-sortable', item ).length === 0 ) { $(item).prepend( handle ); }
			if( $('> .js-cfdev-remove-sortable', item ).length === 0 ) { $(item).append( remover ); }
		});

		// Scroll to the new item
		$('html, body').animate({ scrollTop: new_item.offset().top - 80 }, 300);

		return false;
	});

	// Ajax save
	// $('.cfdev-td').on( 'click', '.js-cfdev-ajax-save', function()
	// {
	// 	var that		= $(this),
	// 		parent		= that.closest('.cfdev-td'),
	// 		cfdev 		= parent.closest('.cfdev'),
	// 		input 		= $('.cfdev-input', parent),
	// 		field_id 	= input.attr('id'),
	// 		value		= input.val(),
	//
	// 		// Needs better handling
	// 		meta_type 	= cfdev.data('meta-type'),
	// 		object_id	= cfdev.data('object-id');
	//
	// 	var data = {
	// 		action: 	'cfdev_field_ajax_save',
	// 		cfdev: 	{
	// 			value: 		value,
	// 			field_id: 	field_id,
	// 			// Needs better handling
	// 			meta_type:  meta_type,
	// 			object_id: 	object_id,
	// 			// nonce
	// 			nonce: Cfdev.nonce,
	// 		}
	// 	};
	//
	// 	$.post( Cfdev.ajax_url, data, function(r) {
	// 		console.log(data)
	// 		var border_color = input.css('border-color');
	// 		input.animate({ borderColor: '#60b334' }, 200, function(){ input.animate({ borderColor: border_color }); });
	// 	});
	//
	// 	return false;
	// });

	$('.cfdev-td').on('click', '.js-cfdev-ajax-save', function() {
		const that       = $(this),
			parent     = that.closest('.cfdev-td'),
			cfdev       = parent.closest('.cfdev'),
			input       = $('.cfdev-input', parent),
			field_id    = input.attr('id'),
			//value       = input.val(),
			//value       = input.is(':checked') ? input.val() : '',
			meta_type   = cfdev.data('meta-type'),
			object_id   = cfdev.data('object-id');

		// --- Récupération de la valeur en fonction du type de champ ---
		let value;
		if (input.is('input[type="checkbox"]')) {
			// Cas : Checkbox (cochée = "on", décochée = "")
			value = input.is(':checked') ? input.val() || 'on' : '';
		}
		// else if (input.is('input[type="radio"]')) {
		// 	// Cas : Radio (récupérer la valeur du bouton coché)
		// 	value = $('.cfdev-input:checked', parent).val() || '';
		// }
		// else if (input.is('select[multiple]')) {
		// 	// Cas : Select multiple (récupérer toutes les valeurs sélectionnées)
		// 	value = input.val() || [];
		// }
		else {
			// Cas : Text, Select simple, Textarea, etc.
			value = input.val() || '';
		}

		// Sauvegarder le texte original du bouton
		const originalButtonText = that.text();
		if (that.data('original-text') === undefined) {
			that.data('original-text', originalButtonText);
		}

		const data = {
			action: 'cfdev_field_ajax_save',
			cfdev: {
				value:      value,
				field_id:   field_id,
				meta_type:  meta_type,
				object_id:  object_id,
				nonce:      Cfdev.nonce,
			}
		};

		// Désactiver le bouton pendant la requête
		that.prop('disabled', true).text(Cfdev.saving);

		$.post(Cfdev.ajax_url, data, function(_r) {
			// Animation plus forte : clignotement vert 3 fois
			const border_color = input.css('border-color');
			for (let i = 0; i < 3; i++) {
				input.animate({ borderColor: '#60b334' }, 200)
					.animate({ borderColor: border_color }, 200);
			}

			// Changer le texte du bouton en "Saved!"
			that.text(Cfdev.saved).prop('disabled', false);

			// Réinitialiser le texte si le champ est modifié
			input.on('input change', function() {
				that.text(that.data('original-text')).prop('disabled', false);
				input.off('input change'); // Éviter les doublons d'événements
			});
		}).fail(function() {
			// En cas d'erreur, rétablir le texte et activer le bouton
			that.text(that.data('original-text')).prop('disabled', false);
		});

		return false;
	});

	// Notice anchor links — scroll to the field, opening tabs/accordion/postbox if needed
	$(document).on('click', '.notice a[href^="#"]', function (e) {
		const $link   = $(this),
			anchor    = $link.attr('href'),
			fieldId   = anchor.replace(/^#/, '');
		let target    = $(anchor);

		// Fallback: group fields (checkboxes, radios…) expose id only on wrapper — find by prefix
		if (!target.length) {
			target = $('[id="' + fieldId + '"]').first();
		}

		if (!target.length) return;

		e.preventDefault();

		// Bundle sub-field: <abbr title="bundleId.rowIndex.fieldId"> beside the link
		const abbrTitle = $link.siblings('abbr[title]').attr('title') || '',
			parts         = abbrTitle.split('.');
		let $scrollTarget = target;

		if (parts.length === 3) {
			const $field = $('#' + parts[2] + '_' + parts[1]);
			if ($field.length) {
				$scrollTarget = $field;
			}
		}

		// Inputs with no visual position → use nearest table row
		if (
			$scrollTarget.is('input[type="hidden"]') ||
			$scrollTarget.hasClass('wp-editor-area') ||
			$scrollTarget.hasClass('cfdev-colorpicker')
		) {
			const $row = $scrollTarget.closest('tr, .form-field');
			if ($row.length) {
				$scrollTarget = $row;
			}
		}

		// Open collapsed WordPress postbox (meta box) — post.php only
		const $postbox = $scrollTarget.closest('.postbox');
		if ($postbox.length && $postbox.hasClass('closed')) {
			$postbox.find('.handlediv, .hndle').first().trigger('click');
		}

		// Open jQuery UI accordion panel containing the field
		let $accordion = $scrollTarget.closest('.js-cfdev-accordion');
		if (!$accordion.length) { $accordion = target.closest('.js-cfdev-accordion'); }
		if ($accordion.length) {
			let $panel = $scrollTarget.closest('.js-cfdev-accordion > div');
			if (!$panel.length) { $panel = target.closest('.js-cfdev-accordion > div'); }
			const panelIndex = $accordion.children('div').index($panel);
			if (panelIndex >= 0) { $accordion.accordion('option', 'active', panelIndex); }
		}

		// Open jQuery UI tab containing the field
		let $tabs = $scrollTarget.closest('.js-cfdev-tabs');
		if (!$tabs.length) { $tabs = target.closest('.js-cfdev-tabs'); }
		if ($tabs.length) {
			let $tabPanel = $scrollTarget.closest('.js-cfdev-tabs > div[id]');
			if (!$tabPanel.length) { $tabPanel = target.closest('.js-cfdev-tabs > div[id]'); }
			const tabIndex = $tabs.children('div[id]').index($tabPanel);
			if (tabIndex >= 0) { $tabs.tabs('option', 'active', tabIndex); }
		}

		// Scroll after animations (postbox open ~200ms, accordion/tabs ~300ms)
		setTimeout(function () {
			let offset = $scrollTarget.offset();

			// offset.top === 0 means element is still hidden (collapsed postbox or display:none)
			// Fall back to the postbox title bar, which is always visible
			if (!offset || offset.top === 0) {
				const $hndle = $scrollTarget.closest('.postbox').find('.hndle');
				if ($hndle.length) { offset = $hndle.offset(); }
			}

			if (offset && offset.top > 0) {
				$('html, body').animate({ scrollTop: offset.top - 80 }, 300);
			}

			if (!$scrollTarget.is('input[type="hidden"]') && !$scrollTarget.is('div, tr, td, fieldset')) {
				$scrollTarget.trigger('focus');
			}
		}, 400);
	});

	// Postbox toggle (term & user meta)
	$(document).on('click', '.cfdev-postbox-header', function () {
		$(this).closest('.cfdev-postbox').toggleClass('is-closed');
	});

});