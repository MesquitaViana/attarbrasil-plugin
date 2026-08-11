(function ($) {
	'use strict';

	$(document).on('click', '.abs-pick-svg', function (event) {
		event.preventDefault();

		var button = $(this);
		var row = button.closest('.abs-svg-row');
		var frame = wp.media({
			title: 'Selecionar SVG',
			button: { text: 'Usar este SVG' },
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			button.siblings('input').val(attachment.id);
			row.find('.abs-svg-preview').html(
				$('<img>', { src: attachment.url, alt: '' })
			);
		});

		frame.open();
	});

	function renderBundleVariations(row, payload) {
		var field = row.find('.abs-bundle-variation-field');
		var select = row.find('.abs-bundle-variation');
		var status = row.find('.abs-bundle-variation-status');
		select.empty().append($('<option>', { value: 0, text: 'Selecione uma variação' }));
		(payload.options || []).forEach(function (option) {
			select.append($('<option>', { value: option.id, text: option.label }));
		});
		status.text(payload.message || '').toggleClass('is-error', payload.mode === 'multiple' || payload.mode === 'unavailable');
		if (payload.mode === 'simple') {
			field.prop('hidden', true); select.val('0').prop('required', false); return;
		}
		field.prop('hidden', false);
		if (payload.mode === 'automatic' && payload.options.length) select.val(String(payload.options[0].id));
		else select.val('0');
		select.prop('required', payload.mode === 'multiple');
	}

	$(document).on('change', '.abs-bundle-product', function () {
		if (typeof absPdpAdmin === 'undefined') return;
		var row = $(this).closest('[data-abs-bundle-admin-row]');
		var productId = Number($(this).val()) || 0;
		if (!productId) {
			renderBundleVariations(row, { mode: 'simple', options: [], message: '' }); return;
		}
		row.find('.abs-bundle-variation-status').removeClass('is-error').text('Carregando variações...');
		$.post(absPdpAdmin.ajaxUrl, { action: 'abs_bundle_variations', nonce: absPdpAdmin.nonce, product_id: productId })
			.done(function (response) {
				if (!response || !response.success) {
					var message = response && response.data && response.data.message ? response.data.message : 'Não foi possível carregar as variações.';
					row.find('.abs-bundle-variation-status').addClass('is-error').text(message);
					return;
				}
				renderBundleVariations(row, response.data);
			})
			.fail(function (xhr) {
				var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Não foi possível carregar as variações.';
				row.find('.abs-bundle-variation-status').addClass('is-error').text(message);
			});
	});
}(jQuery));
