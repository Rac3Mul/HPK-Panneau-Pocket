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
	var mediaFrame;
	$(document).on('click', '.hpk-pp-media-btn', function (e) {
		e.preventDefault();
		var $input = $(this).closest('.hpk-pp-doc-row').find('input[type="url"]');

		if (mediaFrame) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media({
			title: 'Choisir un document',
			button: { text: 'Utiliser' },
			multiple: false,
			library: { type: ['image', 'application/pdf'] }
		});

		mediaFrame.on('select', function () {
			var attachment = mediaFrame.state().get('selection').first().toJSON();
			$input.val(attachment.url);
		});

		mediaFrame.open();
	});

	/* Add document row */
	$(document).on('click', '.hpk-pp-add-doc', function () {
		var $docs = $(this).closest('.hpk-pp-documents');
		var count = $docs.find('.hpk-pp-doc-row').length;
		if (count >= 5) return;
		$docs.find('.hpk-pp-add-doc').before(
			'<p class="hpk-pp-doc-row"><input type="url" name="_panneaupocket_documents[]" value="" class="widefat" placeholder="https://" />' +
			'<button type="button" class="button hpk-pp-media-btn">Média</button></p>'
		);
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
			$('#hpk_pp_custom_logo').val(attachment.url);
		});
		frame.open();
	});

})(jQuery);
