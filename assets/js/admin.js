(function ($) {
	'use strict';

	var admin = window.hpkPpAdmin || {};

	/* Character counter */
	$(document).on('input', '.hpk-pp-title-input', function () {
		var len = $(this).val().length;
		var $count = $(this).closest('p, td').find('.hpk-pp-char-current');
		$count.text(len);
		$(this).closest('p, td').find('.hpk-pp-char-count').toggleClass('is-over', len > 50);
	});

	/* Test API connection */
	$(document).on('click', '.hpk-pp-test-connection', function () {
		var $btn = $(this);
		var $result = $('.hpk-pp-test-result');
		$btn.prop('disabled', true).text(admin.i18n ? admin.i18n.testing : 'Test…');

		$.post(admin.ajaxUrl, {
			action: 'hpk_pp_test_connection',
			nonce: admin.nonce
		}).done(function (res) {
			var cls = res.success ? 'success' : 'error';
			var msg = res.data && res.data.message ? res.data.message : (res.success ? 'OK' : 'Erreur');
			$result.html('<p class="hpk-pp-notice ' + cls + '">' + msg + '</p>');
		}).fail(function () {
			$result.html('<p class="hpk-pp-notice error">Erreur réseau.</p>');
		}).always(function () {
			$btn.prop('disabled', false).text('Tester la connexion');
		});
	});

	/* Send now */
	$(document).on('click', '.hpk-pp-send-now', function () {
		var $box = $(this).closest('.hpk-pp-metabox');
		var postId = $box.data('post-id');
		var $notice = $box.find('.hpk-pp-ajax-notice');
		$notice.show().removeClass('success error').text(admin.i18n ? admin.i18n.sending : 'Envoi…');

		$.post(admin.ajaxUrl, {
			action: 'hpk_pp_send_now',
			nonce: admin.nonce,
			post_id: postId
		}).done(function (res) {
			if (res.success) {
				$notice.addClass('success').text(res.data.message || 'Succès');
			} else {
				$notice.addClass('error').text(res.data && res.data.message ? res.data.message : 'Erreur');
			}
		}).fail(function () {
			$notice.addClass('error').text('Erreur réseau.');
		});
	});

	/* Update now */
	$(document).on('click', '.hpk-pp-update-now', function () {
		var $box = $(this).closest('.hpk-pp-metabox');
		var postId = $box.data('post-id');
		var $notice = $box.find('.hpk-pp-ajax-notice');
		$notice.show().removeClass('success error').text(admin.i18n ? admin.i18n.sending : 'Mise à jour…');

		$.post(admin.ajaxUrl, {
			action: 'hpk_pp_update_now',
			nonce: admin.nonce,
			post_id: postId
		}).done(function (res) {
			if (res.success) {
				$notice.addClass('success').text(res.data.message || 'Succès');
			} else {
				$notice.addClass('error').text(res.data && res.data.message ? res.data.message : 'Erreur');
			}
		}).fail(function () {
			$notice.addClass('error').text('Erreur réseau.');
		});
	});

	/* Preview payload */
	$(document).on('click', '.hpk-pp-preview-payload', function () {
		var $box = $(this).closest('.hpk-pp-metabox');
		var postId = $box.data('post-id');
		var $preview = $box.find('.hpk-pp-payload-preview');

		$.post(admin.ajaxUrl, {
			action: 'hpk_pp_preview_payload',
			nonce: admin.nonce,
			post_id: postId
		}).done(function (res) {
			if (res.success && res.data.payload) {
				$preview.show().text(JSON.stringify(res.data.payload, null, 2));
			} else {
				$preview.show().text(res.data && res.data.message ? res.data.message : 'Erreur');
			}
		});
	});

	/* Copy shortcode */
	$(document).on('click', '.hpk-pp-copy-btn', function () {
		var text = $(this).data('copy');
		if (navigator.clipboard) {
			navigator.clipboard.writeText(text);
		} else {
			var $tmp = $('<textarea>').val(text).appendTo('body').select();
			document.execCommand('copy');
			$tmp.remove();
		}
		var $btn = $(this);
		var orig = $btn.text();
		$btn.text(admin.i18n ? admin.i18n.copied : 'Copié !');
		setTimeout(function () { $btn.text(orig); }, 1500);
	});

	/* Media picker for documents */
	$(document).on('click', '.hpk-pp-media-btn', function (e) {
		e.preventDefault();
		var $input = $(this).closest('.hpk-pp-doc-row').find('input[type="url"]');

		var frame = wp.media({
			title: 'Choisir un document',
			button: { text: 'Utiliser' },
			multiple: false,
			library: { type: ['image', 'application/pdf'] }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$input.val(attachment.url).trigger('change');
		});

		frame.open();
	});

	/* Add document row */
	function hpkPpDocRowHtml(inputName) {
		var isPublication = inputName === 'documents[]';
		var inputClass = isPublication ? 'large-text hpk-pp-doc-url' : 'widefat hpk-pp-doc-url';
		var mediaLabel = isPublication ? 'Média WP' : 'Média';
		return '<p class="hpk-pp-doc-row">' +
			'<input type="url" name="' + inputName + '" value="" class="' + inputClass + '" placeholder="https://" />' +
			'<button type="button" class="button hpk-pp-media-btn">' + mediaLabel + '</button>' +
			'<button type="button" class="button hpk-pp-remove-doc" title="Retirer" aria-label="Retirer ce document">&times;</button>' +
			'</p>';
	}

	window.hpkPpDocRowHtml = hpkPpDocRowHtml;

	$(document).on('click', '.hpk-pp-remove-doc', function (e) {
		e.preventDefault();
		var $docs = $(this).closest('.hpk-pp-documents');
		var $row = $(this).closest('.hpk-pp-doc-row');
		if ($docs.find('.hpk-pp-doc-row').length <= 1) {
			$row.find('.hpk-pp-doc-url').val('').trigger('change');
		} else {
			$row.remove();
		}
		$(document).trigger('hpk-pp-docs-changed');
	});

	$(document).on('click', '.hpk-pp-add-doc', function () {
		var $docs = $(this).closest('.hpk-pp-documents');
		var count = $docs.find('.hpk-pp-doc-row').length;
		if (count >= 5) {
			return;
		}
		var inputName = $docs.data('input-name') || '_panneaupocket_documents[]';
		$docs.find('.hpk-pp-add-doc').before(hpkPpDocRowHtml(inputName));
	});

	/* Logo picker */
	$(document).on('click', '.hpk-pp-logo-picker', function (e) {
		e.preventDefault();
		var frame = wp.media({
			title: 'Choisir un logo',
			button: { text: 'Utiliser' },
			multiple: false
		});
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			$('#hpk_pp_custom_logo').val(attachment.url).trigger('change');
		});
		frame.open();
	});

	$(document).on('click', '.hpk-pp-logo-clear', function (e) {
		e.preventDefault();
		$('#hpk_pp_custom_logo').val('').trigger('change');
	});

	function updateLogoPreview() {
		var $wrap = $('.hpk-pp-logo-preview');
		if (!$wrap.length) {
			return;
		}
		var defaultLogo = $wrap.data('default-logo') || (admin.defaultLogo || '');
		var useCustom = $('input[name="hpk_pp_use_custom_logo"]').is(':checked');
		var customUrl = $('#hpk_pp_custom_logo').val();
		var logoUrl = useCustom && customUrl ? customUrl : defaultLogo;
		var btnColor = $('input[name="hpk_pp_color_button"]').val() || '#ffffff';
		var primary = $('input[name="hpk_pp_color_primary"]').val() || '#0066cc';

		$wrap.find('.hpk-pp-logo-preview__btn').css('background', btnColor);
		$wrap.find('.hpk-pp-logo-preview__btn img').attr('src', logoUrl);
		$wrap.find('.hpk-pp-logo-preview__source').text(useCustom && customUrl ? 'Personnalisé' : 'PanneauPocket (défaut)');

		var $btnMeta = $wrap.find('[data-preview="btn-color"]');
		$btnMeta.find('.hpk-pp-logo-preview__chip').css('background', btnColor);
		$btnMeta.find('.hpk-pp-logo-preview__value').text(btnColor);

		var $primaryMeta = $wrap.find('[data-preview="primary-color"]');
		$primaryMeta.find('.hpk-pp-logo-preview__chip').css('background', primary);
		$primaryMeta.find('.hpk-pp-logo-preview__value').text(primary);
	}

	$(document).on('input change', '.hpk-pp-form--display input, .hpk-pp-form--display select', function () {
		var $input = $(this);
		if ($input.is('[type="color"]')) {
			$input.siblings('.hpk-pp-color-swatch').css('background-color', $input.val());
			$input.siblings('.hpk-pp-color-code').text($input.val());
		}
		updateLogoPreview();
	});

	updateLogoPreview();

})(jQuery);
