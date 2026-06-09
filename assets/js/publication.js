(function ($) {
	'use strict';

	var cfg = window.hpkPpPublication || {};
	var editorId = cfg.editorId || 'hpk_pp_publication_content';
	var $emojiPopover = null;

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

	function hasHtmlContent(html) {
		if (!html) {
			return false;
		}
		if (stripTags(html)) {
			return true;
		}
		return /<img[\s>]/i.test(html);
	}

	function isImageUrl(url) {
		return /\.(jpe?g|png|gif|webp|svg)(\?|$)/i.test(url.toLowerCase());
	}

	function getDocumentUrls($form) {
		var urls = [];
		$form.find('.hpk-pp-doc-url').each(function () {
			var url = $.trim($(this).val());
			if (url) {
				urls.push(url);
			}
		});
		return urls;
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
		if (hasHtmlContent(content)) {
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
		getDocumentUrls($form).forEach(function (url) {
			if (isImageUrl(url)) {
				$docsDisplay.append(
					$('<div class="hpk-pp-preview-doc hpk-pp-preview-doc--image">').append(
						$('<img>', { src: url, alt: '' })
					)
				);
			} else if (/\.pdf(\?|$)/i.test(url)) {
				$docsDisplay.append(
					$('<div class="hpk-pp-preview-doc hpk-pp-preview-doc--pdf">').text('PDF')
				);
			} else if (url) {
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

	function insertIntoInput($input, text) {
		if (!$input.length) {
			return;
		}
		var el = $input.get(0);
		var start = el.selectionStart || 0;
		var end = el.selectionEnd || 0;
		var value = $input.val();
		$input.val(value.slice(0, start) + text + value.slice(end));
		el.selectionStart = el.selectionEnd = start + text.length;
		$input.trigger('input');
	}

	function insertIntoEditor(text) {
		if (typeof tinymce !== 'undefined') {
			var editor = tinymce.get(editorId);
			if (editor && !editor.isHidden()) {
				editor.insertContent(text);
				updatePreview();
				return;
			}
		}
		var $ta = $('#' + editorId);
		insertIntoInput($ta, text);
	}

	function closeEmojiPopover() {
		if ($emojiPopover) {
			$emojiPopover.remove();
			$emojiPopover = null;
		}
	}

	function openEmojiPopover($trigger) {
		closeEmojiPopover();
		var emojis = cfg.emojis || ['😀', '😊', '👍', '❤️', '🎉', '📢', '⚠️'];
		var targetSelector = $trigger.data('target');
		var targetEditor = $trigger.data('targetEditor');

		$emojiPopover = $('<div class="hpk-pp-emoji-popover" role="listbox"></div>');
		emojis.forEach(function (emoji) {
			$emojiPopover.append(
				$('<button type="button" class="hpk-pp-emoji-item" role="option"></button>').text(emoji)
			);
		});

		$('body').append($emojiPopover);
		var offset = $trigger.offset();
		$emojiPopover.css({
			top: offset.top + $trigger.outerHeight() + 6,
			left: offset.left
		});

		$emojiPopover.on('click', '.hpk-pp-emoji-item', function (e) {
			e.preventDefault();
			var emoji = $(this).text();
			if (targetEditor) {
				insertIntoEditor(emoji);
			} else if (targetSelector) {
				insertIntoInput($(targetSelector), emoji);
			}
			closeEmojiPopover();
		});
	}

	function findDocInputForUrl($form) {
		var $empty = null;
		$form.find('.hpk-pp-doc-url').each(function () {
			if (!$.trim($(this).val()) && !$empty) {
				$empty = $(this);
			}
		});
		if ($empty) {
			return $empty;
		}
		var count = $form.find('.hpk-pp-doc-row').length;
		if (count >= 5) {
			return null;
		}
		var inputName = $form.find('.hpk-pp-documents').data('input-name') || 'documents[]';
		var $row = $(
			'<p class="hpk-pp-doc-row"><input type="url" name="' + inputName + '" value="" class="large-text hpk-pp-doc-url" placeholder="https://" />' +
			'<button type="button" class="button hpk-pp-media-btn">Média WP</button></p>'
		);
		$form.find('.hpk-pp-add-doc').before($row);
		return $row.find('.hpk-pp-doc-url');
	}

	function pickLibraryImage(url) {
		var $form = $('.hpk-pp-publication-form');
		var $input = findDocInputForUrl($form);
		if (!$input) {
			window.alert('Maximum 5 documents.');
			return;
		}
		$input.val(url).trigger('change');
		$('.hpk-pp-library-pick').removeClass('is-selected');
		$('.hpk-pp-library-pick').filter(function () {
			return $(this).data('url') === url;
		}).addClass('is-selected');
	}

	$(function () {
		if (!$('.hpk-pp-publication').length) {
			return;
		}

		bindEditor();

		$(document).on('input change', '.hpk-pp-publication-form .hpk-pp-preview-title, .hpk-pp-publication-form .hpk-pp-preview-type, .hpk-pp-publication-form .hpk-pp-doc-url', updatePreview);

		$(document).on('click', '.hpk-pp-emoji-trigger', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openEmojiPopover($(this));
		});

		$(document).on('click', function (e) {
			if ($emojiPopover && !$(e.target).closest('.hpk-pp-emoji-popover, .hpk-pp-emoji-trigger').length) {
				closeEmojiPopover();
			}
		});

		$(document).on('click', '.hpk-pp-library-pick', function (e) {
			e.preventDefault();
			pickLibraryImage($(this).data('url'));
		});

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
