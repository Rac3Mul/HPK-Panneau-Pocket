(function ($) {
	'use strict';

	var cfg = window.hpkPpPublication || {};
	var editorId = cfg.editorId || 'hpk_pp_publication_content';

	function getEditorContent() {
		if (typeof tinymce !== 'undefined') {
			var editor = tinymce.get(editorId);
			if (editor && !editor.isHidden()) {
				return editor.getContent();
			}
		}
		var $ta = $('#' + editorId);
		return $ta.length ? $ta.val() : '';
	}

	function stripTags(html) {
		var div = document.createElement('div');
		div.innerHTML = html || '';
		return (div.textContent || div.innerText || '').trim();
	}

	function updatePreview() {
		var $form = $('.hpk-pp-publication-form');
		if (!$form.length) {
			return;
		}

		var title = $.trim($form.find('.hpk-pp-preview-title').val());
		var type = $form.find('.hpk-pp-preview-type').val();
		var content = getEditorContent();
		var i18n = cfg.i18n || {};

		var $titleDisplay = $('.hpk-pp-preview-title-display');
		$titleDisplay.text(title || i18n.placeholderTitle || 'Écrivez le titre');
		$titleDisplay.toggleClass('is-placeholder', !title);

		var $contentDisplay = $('.hpk-pp-preview-content-display');
		if (stripTags(content)) {
			$contentDisplay.html(content);
		} else {
			$contentDisplay.html('<p class="hpk-pp-phone-preview__placeholder">' + (i18n.placeholderContent || 'Votre message apparaîtra ici…') + '</p>');
		}

		var $badge = $('.hpk-pp-preview-type-badge');
		if ('alert' === type) {
			$badge.text(i18n.typeAlert || 'Alerte').attr('data-type', 'alert');
		} else {
			$badge.text(i18n.typeInfo || 'Information').attr('data-type', 'info');
		}

		var $docsDisplay = $('.hpk-pp-preview-docs-display');
		$docsDisplay.empty();
		$form.find('.hpk-pp-doc-url').each(function () {
			var url = $.trim($(this).val());
			if (!url) {
				return;
			}
			var lower = url.toLowerCase();
			if (/\.(jpe?g|png|gif|webp)(\?|$)/i.test(lower)) {
				$docsDisplay.append(
					$('<div class="hpk-pp-preview-doc hpk-pp-preview-doc--image">').append(
						$('<img>', { src: url, alt: '' })
					)
				);
			} else if (/\.pdf(\?|$)/i.test(lower)) {
				$docsDisplay.append(
					$('<div class="hpk-pp-preview-doc hpk-pp-preview-doc--pdf">').text('PDF')
				);
			} else {
				$docsDisplay.append(
					$('<div class="hpk-pp-preview-doc hpk-pp-preview-doc--file">').text(url.split('/').pop())
				);
			}
		});
	}

	function bindEditor() {
		if (typeof tinymce !== 'undefined') {
			tinymce.on('AddEditor', function (e) {
				if (e.editor.id === editorId) {
					e.editor.on('keyup change undo redo SetContent', updatePreview);
				}
			});
			var existing = tinymce.get(editorId);
			if (existing) {
				existing.on('keyup change undo redo SetContent', updatePreview);
			}
		}

		$(document).on('input', '#' + editorId, updatePreview);
	}

	$(function () {
		if (!$('.hpk-pp-publication').length) {
			return;
		}

		bindEditor();

		$(document).on('input change', '.hpk-pp-publication-form .hpk-pp-preview-title, .hpk-pp-publication-form .hpk-pp-preview-type, .hpk-pp-publication-form .hpk-pp-doc-url', updatePreview);

		// Quicktags (HTML mode) sync.
		if (typeof QTags !== 'undefined') {
			var orig = QTags.buttonClick;
			QTags.buttonClick = function () {
				var result = orig.apply(this, arguments);
				updatePreview();
				return result;
			};
		}

		updatePreview();
	});

})(jQuery);
