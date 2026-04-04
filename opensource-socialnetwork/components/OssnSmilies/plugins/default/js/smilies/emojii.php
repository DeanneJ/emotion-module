Ossn.RegisterStartupFunction(function () {

    $(document).ready(function () {

        // ======================================================================
        // EMOTION-AWARE EMOJI PICKER
        // Replaces the static emoji array with AI-filtered emojis from emo-app
        // ======================================================================

        var EmotionEmojiAPI = 'http://localhost:8000/api/v1';

        // A. append the emotion-aware emoji container to end of document
        // **********************************************************
        $('body').append(
            '<div id="master-moji">' +
            '<input type="hidden" id="master-moji-anchor" value="">' +
            '<div class="dropdown emojii-container-main">' +
            '  <div class="emojii-container" data-active="suggested">' +
            '    <a class="smiles-close" href="javascript:void(0);"><i class="fa fa-times"></i></a>' +
            '    <div class="emotion-picker-header">' +
            '      <span class="emotion-picker-status" id="emotion-picker-status">' +
            '        <i class="fa fa-brain"></i> Click to analyze post emotions' +
            '      </span>' +
            '    </div>' +
            '    <ul class="nav nav-tabs"></ul>' +
            '    <div class="emojii-content-area"></div>' +
            '  </div>' +
            '</div>' +
            '</div>'
        );

        /**
         * Populate the emoji picker with emotion-filtered emojis
         * Called after API returns suggestions
         */
        function populateEmotionEmojis(data) {
            var $container = $('#master-moji .emojii-container');
            var $tabs = $container.find('.nav-tabs');
            var $content = $container.find('.emojii-content-area');

            $tabs.empty();
            $content.empty();

            // Status bar
            var topEmotion = data.top_emotion || 'neutral';
            var sentiment = data.sentiment || 'neutral';
            var sentimentClass = sentiment === 'positive' ? 'positive' : (sentiment === 'negative' ? 'negative' : 'neutral');

            $('#emotion-picker-status').html(
                '<span class="ep-emotion-badge" style="background:' + (OssnEmotions.colorMap[topEmotion] || '#95A5A6') + '">' +
                (OssnEmotions.emojiMap[topEmotion] || '🔵') + ' ' + topEmotion.charAt(0).toUpperCase() + topEmotion.slice(1) +
                '</span>' +
                '<span class="ep-sentiment ' + sentimentClass + '">' + sentiment + '</span>'
            );

            // Tab 1: Suggested emojis (primary)
            var suggested = data.suggested_emojis || [];
            if (suggested.length > 0) {
                $tabs.append("<li class='ossn-emojii-tab active' data-type='suggested'><a class='emojii' href='javascript:void(0);' title='Suggested'>✨</a></li>");
                var $suggestedList = $('<div class="emojii-list emojii-list-suggested" style="display:block"></div>');
                for (var i = 0; i < suggested.length; i++) {
                    $suggestedList.append("<li class='emojii emotion-allowed' title='Suggested for this emotion'>" + suggested[i] + "</li>");
                }
                $content.append($suggestedList);
            }

            // Tab 2: Allowed emojis (extended)
            var allowed = data.allowed_emojis || [];
            if (allowed.length > 0) {
                var firstAllowed = allowed[0] || '😀';
                $tabs.append("<li class='ossn-emojii-tab' data-type='allowed'><a class='emojii' href='javascript:void(0);' title='All Allowed'>👍</a></li>");
                var $allowedList = $('<div class="emojii-list emojii-list-allowed"></div>');
                for (var i = 0; i < allowed.length; i++) {
                    $allowedList.append("<li class='emojii emotion-allowed' title='Appropriate for this context'>" + allowed[i] + "</li>");
                }
                $content.append($allowedList);
            }

            // Tab 3: Categories (grouped by emotion category)
            var categories = data.emoji_categories || {};
            var catKeys = Object.keys(categories);
            for (var c = 0; c < catKeys.length; c++) {
                var catName = catKeys[c];
                var catEmojis = categories[catName];
                if (catEmojis && catEmojis.length > 0) {
                    var catIcon = catEmojis[0];
                    var catLabel = catName.replace(/_/g, ' ');
                    $tabs.append("<li class='ossn-emojii-tab' data-type='cat-" + catName + "'><a class='emojii' href='javascript:void(0);' title='" + catLabel + "'>" + catIcon + "</a></li>");
                    var $catList = $('<div class="emojii-list emojii-list-cat-' + catName + '"></div>');
                    $catList.append("<div class='emojii-cat-label'>" + catLabel + "</div>");
                    for (var e = 0; e < catEmojis.length; e++) {
                        $catList.append("<li class='emojii emotion-allowed'>" + catEmojis[e] + "</li>");
                    }
                    $content.append($catList);
                }
            }

            // Tab 4: Blocked emojis (shown dimmed as info)
            var blocked = data.blocked_emojis || [];
            if (blocked.length > 0) {
                $tabs.append("<li class='ossn-emojii-tab' data-type='blocked'><a class='emojii' href='javascript:void(0);' title='Blocked (Inappropriate)'>🚫</a></li>");
                var $blockedList = $('<div class="emojii-list emojii-list-blocked"></div>');
                $blockedList.append("<div class='emojii-cat-label' style='color:#dc2626'>🚫 Inappropriate for current emotion</div>");
                for (var i = 0; i < blocked.length; i++) {
                    $blockedList.append("<li class='emojii emotion-blocked' title='Inappropriate for this context'>" + blocked[i] + "</li>");
                }
                $content.append($blockedList);
            }

            // Show reasoning if available
            if (data.reasoning) {
                $content.append("<div class='emotion-reasoning'><i class='fa fa-info-circle'></i> " + data.reasoning + "</div>");
            }
        }

        /**
         * Show loading state in emoji picker
         */
        function showEmojiLoading() {
            var $content = $('#master-moji .emojii-content-area');
            var $tabs = $('#master-moji .nav-tabs');
            $tabs.empty();
            $content.empty();
            $content.append(
                '<div class="emotion-emoji-loading">' +
                '<div class="spinner"></div>' +
                '<span>Analyzing emotions & filtering emojis...</span>' +
                '</div>'
            );
            $('#emotion-picker-status').html('<i class="fa fa-spinner fa-spin"></i> Analyzing text...');
        }

        /**
         * Show empty/no-text state
         */
        function showNoTextState() {
            var $content = $('#master-moji .emojii-content-area');
            var $tabs = $('#master-moji .nav-tabs');
            $tabs.empty();
            $content.empty();
            $content.append(
                '<div class="emotion-emoji-empty">' +
                '<span class="empty-icon">📝</span>' +
                '<span>No text found. Looking for image emotions...</span>' +
                '</div>'
            );
            $('#emotion-picker-status').html('<i class="fa fa-spinner fa-spin"></i> Checking for image emotions...');
        }

        /**
         * Populate emoji picker from AffectNet image emotion data.
         * Maps face emotions to a curated set of relevant emojis.
         */
        function populateImageEmotionEmojis(imageData) {
            var emotion = imageData.predicted_emotion || 'neutral';
            var confidence = Math.round((imageData.confidence || 0) * 100);
            var probs = imageData.all_probabilities || {};

            // Face emotion → emoji suggestions mapping
            var faceEmotionEmojis = {
                'happy':    { suggested: ['😊','😄','😁','🥰','😍','🤗','😎','🎉','👏','💯','❤️','🥳'], icon: '😊' },
                'sad':      { suggested: ['😢','😔','💔','🥺','😞','😿','💙','🫂','😥','🤧'], icon: '😢' },
                'anger':    { suggested: ['😡','😤','💢','🤬','👊','💥','🔥','😠'], icon: '😡' },
                'surprise': { suggested: ['😲','😮','🤯','😱','🫢','❗','⚡','👀','😳','🙀'], icon: '😲' },
                'fear':     { suggested: ['😨','😰','😱','🫣','😧','💀','🙈','😬','🥶'], icon: '😨' },
                'disgust':  { suggested: ['🤢','🤮','😖','😒','👎','🙅','😑','💩'], icon: '🤢' },
                'contempt': { suggested: ['😏','🙄','😒','💅','😤','🤨','😑','👀'], icon: '😏' },
                'neutral':  { suggested: ['😐','🙂','👍','💭','🤔','😶','✌️','👌','💬','📸'], icon: '😐' }
            };

            var emojiSet = faceEmotionEmojis[emotion] || faceEmotionEmojis['neutral'];
            var emojiIcon = (typeof OssnEmotions !== 'undefined' && OssnEmotions.faceEmojiMap)
                ? (OssnEmotions.faceEmojiMap[emotion] || '📸') : (emojiSet.icon || '📸');
            var emojiColor = (typeof OssnEmotions !== 'undefined' && OssnEmotions.faceColorMap)
                ? (OssnEmotions.faceColorMap[emotion] || '#95A5A6') : '#95A5A6';

            // Update status
            $('#emotion-picker-status').html(
                '<span class="ep-emotion-badge" style="background:' + emojiColor + '">' +
                emojiIcon + ' ' + emotion.charAt(0).toUpperCase() + emotion.slice(1) +
                '</span>' +
                '<span class="ep-sentiment neutral">📸 Face ' + confidence + '%</span>'
            );

            var $container = $('#master-moji .emojii-container');
            var $tabs = $container.find('.nav-tabs');
            var $content = $container.find('.emojii-content-area');
            $tabs.empty();
            $content.empty();

            // Tab 1: Suggested for detected face emotion
            $tabs.append("<li class='ossn-emojii-tab active' data-type='suggested'><a class='emojii' href='javascript:void(0);' title='Suggested for face emotion'>✨</a></li>");
            var $suggestedList = $('<div class="emojii-list emojii-list-suggested" style="display:block"></div>');
            $suggestedList.append("<div class='emojii-cat-label'>📸 Suggested for " + emotion + " face</div>");
            for (var i = 0; i < emojiSet.suggested.length; i++) {
                $suggestedList.append("<li class='emojii emotion-allowed' title='Suggested for " + emotion + "'>" + emojiSet.suggested[i] + "</li>");
            }
            $content.append($suggestedList);

            // Tab 2: All face emotions with their top emojis
            $tabs.append("<li class='ossn-emojii-tab' data-type='all-faces'><a class='emojii' href='javascript:void(0);' title='All Emotions'>👤</a></li>");
            var $allList = $('<div class="emojii-list emojii-list-all-faces"></div>');
            // Sort by probability descending
            var sortedEmotions = Object.keys(probs).sort(function(a, b) { return probs[b] - probs[a]; });
            for (var j = 0; j < sortedEmotions.length; j++) {
                var em = sortedEmotions[j];
                var pct = Math.round(probs[em] * 100);
                var emSet = faceEmotionEmojis[em] || faceEmotionEmojis['neutral'];
                $allList.append("<div class='emojii-cat-label'>" + (emSet.icon || '🔵') + " " + em.charAt(0).toUpperCase() + em.slice(1) + " (" + pct + "%)</div>");
                var showCount = (em === emotion) ? emSet.suggested.length : Math.min(4, emSet.suggested.length);
                for (var k = 0; k < showCount; k++) {
                    $allList.append("<li class='emojii emotion-allowed'>" + emSet.suggested[k] + "</li>");
                }
            }
            $content.append($allList);
        }

        /**
         * Try to use image emotion data for emoji picker when no text is available.
         * Returns true if image data was found and used, false otherwise.
         */
        function tryImageEmotionForEmojis(anchor) {
            if (typeof OssnEmotions === 'undefined' || !OssnEmotions.imageCache) return false;

            var element = $(anchor);
            var wallItem = element.closest('.ossn-wall-item');
            if (wallItem.length === 0) return false;

            var guid = wallItem.attr('id').replace('activity-item-', '');
            if (!guid || isNaN(guid)) return false;

            var imgData = OssnEmotions.getImageEmotionForPost(guid);
            if (imgData) {
                populateImageEmotionEmojis(imgData);
                return true;
            }

            // Image data might still be loading — check if post has an image
            if (wallItem.find('.ossn-wall-image-container img').length > 0) {
                // Trigger analysis if not already started
                OssnEmotions.analyzePostImage(guid);

                // Poll for result
                var pollCount = 0;
                var pollInterval = setInterval(function() {
                    pollCount++;
                    var data = OssnEmotions.getImageEmotionForPost(guid);
                    if (data) {
                        clearInterval(pollInterval);
                        populateImageEmotionEmojis(data);
                    } else if (pollCount > 20) {
                        clearInterval(pollInterval);
                        // Final fallback: show generic state
                        var $content = $('#master-moji .emojii-content-area');
                        $content.empty();
                        $content.append(
                            '<div class="emotion-emoji-empty">' +
                            '<span class="empty-icon">📸</span>' +
                            '<span>Image analysis timed out. Post any emoji you like!</span>' +
                            '</div>'
                        );
                        $('#emotion-picker-status').html('<i class="fa fa-brain"></i> Image analysis unavailable');
                    }
                }, 500);
                return true; // We're handling it (async)
            }

            return false;
        }

        /**
         * Extract the PARENT POST text for emotion analysis.
         * When emoji picker is opened from a comment, we analyze the post being commented on.
         * When opened from the wall post composer, we analyze the text being composed.
         * When opened from messages/chat, we analyze the message text.
         */
        function getPostTextForAnalysis(anchor) {
            var element = $(anchor);
            if (element.length === 0) return '';

            // 1. Comment box: find the parent wall post's text
            var wallItem = element.closest('.ossn-wall-item');
            if (wallItem.length > 0) {
                var postContent = wallItem.find('.post-contents p');
                if (postContent.length > 0) {
                    return postContent.text().trim();
                }
            }

            // 2. Wall post composer: use the text being typed
            var domEl = element.get(0);
            if (domEl.isContentEditable) {
                return domEl.textContent.trim();
            } else if (domEl.tagName === 'TEXTAREA' || domEl.tagName === 'INPUT') {
                return element.val().trim();
            }
            return '';
        }

        // switch between emoji group tabs in container
        $('body').on('click', '.ossn-emojii-tab', function (e) {
            e.preventDefault();
            var type = $(this).attr('data-type');
            $('.emojii-list').hide();
            $('.emojii-list-' + type).show();
            $('.ossn-emojii-tab').removeClass('active');
            $(this).addClass('active');
        });


        // B. add clickable smiley icon to several input fields
        // ****************************************************

        // 1. comment
        // the button/icon is added now using ossn_extend_view('comments/attachment/buttons', 'smilies/comment/button'); to avoid the issue for dynamically loaded posts/comments $arsalanshah

        // 2. wall post
        // inserted as registered menu item in Ossn_com

        // 3. messages page:
        if ($('.message-form-form').length) {
            $('<div class="ossn-message-attach-photo"><i class="fa fa-smile"></i></div>').prependTo('.message-form-form .controls');
        }

        // 4. chatbox
        // inserted by OssnChat component

        // 5. textareas managed by tinymce
        // done by additional button in editor (initialized by TextareaSupport component)

        // 6. textareas managed by summernote
        // done by additional button in editor (initialized by Forum component)


        // C. open emoji box from several page locations
        // *********************************************

        // 1. comment
        $('body').on('click', '.ossn-comment-attach-photo .fa-smile', function (e) {
            $parent = $(this).parent().parent().parent();
            Ossn.OpenEmojiBox('#' + $parent.find('.comment-box').attr('id'));
        });

        // 2. wall post
        $('body').on('click', '.ossn-wall-container-control-menu-emojii-selector', function (e) {
            Ossn.OpenEmojiBox('.ossn-wall-container-data textarea');
        });

        // 3. message
        $('body').on('click', '.ossn-message-attach-photo .fa-smile', function (e) {
            Ossn.OpenEmojiBox('.message-form-form textarea');
        });

        // 4. chatbox
        // handled by 'OnClick' in Chat component

        // 5. tinymce
        // by click on toolbar button

        // 6. summernote
        // by click on toolbar button

        Ossn.OpenEmojiBox = function (anchor) {
            if ($('#master-moji .emojii-container-main').is(":hidden")) {
                $('#master-moji-anchor').val(anchor);
                $('#master-moji .emojii-container-main').show();

                $('.ossn-halt').attr('style', 'height:' + $(document).height() + 'px;');
                $('.ossn-halt').show();

                // ---- Emotion-aware filtering based on POST text ----
                var postText = getPostTextForAnalysis(anchor);
                if (postText.length > 0) {
                    showEmojiLoading();
                    // Call emo-app API to analyze the post text and get filtered emojis
                    $.ajax({
                        url: EmotionEmojiAPI + '/emojis/suggest',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ text: postText, threshold: 0.3 }),
                        timeout: 15000,
                        success: function(data) {
                            populateEmotionEmojis(data);
                        },
                        error: function() {
                            $('#emotion-picker-status').html('<i class="fa fa-exclamation-triangle"></i> Emotion API unavailable');
                            showNoTextState();
                        }
                    });
                } else {
                    // No text — try image emotion data for this post
                    if (!tryImageEmotionForEmojis(anchor)) {
                        showNoTextState();
                    }
                }
            } else {
                $('#master-moji-anchor').val('');
                $('#master-moji .emojii-container-main').hide();
                $('.ossn-halt').hide();
            }
        }
        $('body').on('click', '.smiles-close', function () {
            $('#master-moji .emojii-container-main').hide();
            $('.ossn-halt').hide();
        });

        // [FIX] Track caret per comment box instead of one global range
        var savedRangesCommentBoxes = {};

        // Save caret position whenever user interacts with a contenteditable comment box
        $('body').on('keyup mouseup', '.comment-box[contenteditable="true"]', function () {
            var sel = window.getSelection();
            if (sel.rangeCount > 0) {
                savedRangesCommentBoxes[this.id] = sel.getRangeAt(0);
            }
        });

        // [NOTE] Prevent focus loss when clicking emojis (mousedown instead of click)
        $('body').on('mousedown', '#master-moji .emojii-list li', function (e) {
            e.preventDefault();
        });

        // D. insert emoji depending on anchor (only allowed emojis)
        // ***********************************
        $('body').on('click', '#master-moji .emojii-list li.emotion-allowed', function (e) {
            e.preventDefault();
            var type = $(this).html();
            var anchor = $('#master-moji-anchor').val();
            var element = $(anchor);

            // [FIX] Rewritten insertAtCaret with caret restore per element
            function insertAtCaret(el, text) {
                if (el.length === 0) return;
                var domEl = el.get(0);

                // Handle contenteditable
                if (domEl.isContentEditable) {
                    domEl.focus(); // [FIX] Ensure focus before inserting
                    var sel = window.getSelection();

                    // Restore saved range for this element
                    var savedRange = savedRangesCommentBoxes[domEl.id];
                    if (savedRange) {
                        sel.removeAllRanges();
                        sel.addRange(savedRange);
                    }

                    // Fallback: move caret to end if no range
                    if (sel.rangeCount === 0) {
                        var range = document.createRange();
                        range.selectNodeContents(domEl);
                        range.collapse(false);
                        sel.addRange(range);
                    }

                    var range = sel.getRangeAt(0);

                    // Add space if needed
                    var needsSpace = false;
                    if (range.startContainer.nodeType === 3) {
                        var charBefore = range.startContainer.textContent.charAt(range.startOffset - 1);
                        needsSpace = charBefore && !/\s/.test(charBefore);
                    }

                    var node = document.createTextNode((needsSpace ? ' ' : '') + text);
                    range.deleteContents();
                    range.insertNode(node);

                    // Move caret after emoji
                    range.setStartAfter(node);
                    range.setEndAfter(node);
                    sel.removeAllRanges();
                    sel.addRange(range);

                    // Update saved range
                    savedRangesCommentBoxes[domEl.id] = range;
                }

                // Handle input/textarea
                else if (typeof domEl.selectionStart === "number") {
                    var start = domEl.selectionStart;
                    var end = domEl.selectionEnd;
                    var val = el.val();
                    var needsSpace = start > 0 && !/\s/.test(val.charAt(start - 1));
                    var newText = (needsSpace ? ' ' : '') + text;
                    el.val(val.substring(0, start) + newText + val.substring(end));
                    domEl.selectionStart = domEl.selectionEnd = start + newText.length;
                }

                // Fallback: just append
                else {
                    el.val(el.val() + ' ' + text);
                }

                el.focus();
            }

            // 1. Comments (contenteditable)
            if (anchor.substring(0, 12) === '#comment-box') {
                insertAtCaret(element, type);
            }

            // 2. Wall posts, messages, chatboxes
            else if (anchor !== '.ossn-editor' && anchor !== '#forum-editor') {
                insertAtCaret(element, type);
            }

            // 5. TinyMCE
            if (anchor === '.ossn-editor') {
                var editorId = $('.ossn-editor').attr('id');
                tinymce.get(editorId).execCommand('mceInsertContent', false, ' ' + type);
            }

            // 6. Summernote
            if (anchor === '#forum-editor') {
                $(anchor).summernote('editor.restoreRange');
                $(anchor).summernote('editor.focus');
                $(anchor).summernote('editor.insertText', ' ' + type);
            }
			// TODO: to avoid too many anchor comparisons here, the classification of editor managed textareas should 
			// be the same all over Ossn in the future
            
        });
    });
});
