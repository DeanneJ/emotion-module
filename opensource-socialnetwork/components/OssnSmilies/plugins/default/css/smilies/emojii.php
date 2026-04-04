.emojii-container {
	background: #fff;
	width: 360px;
	border: 1px solid #e0e0e0;
	position: fixed;
	z-index: 10000;
	box-shadow: 0 8px 24px rgba(0, 0, 0, .15);
	top: 30%;
	left: 50%;
	transform: translate(-50%, -30%);
	padding: 0;
	border-radius: 12px;
	overflow: hidden;
}

/* ===== Emotion Picker Header ===== */
.emotion-picker-header {
	background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
	padding: 10px 14px;
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.emotion-picker-status {
	color: white;
	font-size: 13px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.emotion-picker-status i {
	font-size: 14px;
}

.ep-emotion-badge {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 3px 10px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	color: #fff;
}

.ep-sentiment {
	font-size: 11px;
	padding: 2px 8px;
	border-radius: 10px;
	text-transform: capitalize;
	font-weight: 500;
}

.ep-sentiment.positive {
	background: rgba(5, 150, 105, 0.3);
	color: #d1fae5;
}

.ep-sentiment.negative {
	background: rgba(220, 38, 38, 0.3);
	color: #fecaca;
}

.ep-sentiment.neutral {
	background: rgba(255, 255, 255, 0.2);
	color: #e0e7ff;
}

.emojii-container .nav {
	padding: 4px 8px;
	background: #f9fafb;
	border-bottom: 1px solid #e5e7eb;
	display: flex;
	flex-wrap: wrap;
	gap: 2px;
}

.emojii-container .nav li {
	list-style: none;
}

.emojii-container .nav li.active a {
	background: #e0e7ff;
	border-color: #6366f1;
}

.emojii-container .emojii-content-area {
	padding: 5px;
}

.emojii-container .emojii-list {
	display: none;
	height: 200px;
	overflow: hidden;
	overflow-y: auto;
	padding: 4px;
}

.emojii-container .emojii-list li {
	display: inline-block;
	font-size: 22px;
	padding: 4px;
	border-radius: 6px;
	transition: all 0.15s ease;
}

.emojii-container .emojii-list li.emotion-allowed:hover {
	background: #e0e7ff;
	cursor: pointer;
	transform: scale(1.15);
}

.emojii-container .emojii-list li.emotion-blocked {
	opacity: 0.25;
	cursor: not-allowed;
	position: relative;
}

.emojii-container .emojii-list li.emotion-blocked:hover {
	opacity: 0.4;
	background: #fef2f2;
	transform: none;
}

.emojii-container .emojii-list-suggested {
	display: block;
}

.emojii-container .nav a {
	font-size: 18px;
}

.emojii-container .nav>li>a {
	padding: 6px 4px;
	border-radius: 6px;
	border: 1px solid transparent;
	transition: background 0.2s ease;
}

.emojii-container .nav>li>a:hover {
	background: #e5e7eb;
}

/* ===== Emoji Category Labels ===== */
.emojii-cat-label {
	font-size: 11px;
	font-weight: 600;
	color: #6b7280;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	padding: 6px 4px 4px;
	border-bottom: 1px solid #f3f4f6;
	margin-bottom: 4px;
}

/* ===== Emotion Reasoning ===== */
.emotion-reasoning {
	font-size: 12px;
	color: #6b7280;
	padding: 8px 10px;
	margin: 4px;
	background: #f9fafb;
	border-radius: 8px;
	border: 1px solid #e5e7eb;
	line-height: 1.4;
}

.emotion-reasoning i {
	color: #6366f1;
	margin-right: 4px;
}

/* ===== Loading State ===== */
.emotion-emoji-loading {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 40px 20px;
	color: #6366f1;
	gap: 12px;
}

.emotion-emoji-loading .spinner {
	width: 24px;
	height: 24px;
	border: 3px solid #e0e7ff;
	border-top-color: #6366f1;
	border-radius: 50%;
	animation: emojiSpin 0.6s linear infinite;
}

@keyframes emojiSpin {
	to { transform: rotate(360deg); }
}

.emotion-emoji-loading span {
	font-size: 13px;
	font-weight: 500;
}

/* ===== Empty State ===== */
.emotion-emoji-empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 30px 20px;
	text-align: center;
	color: #6b7280;
	gap: 8px;
}

.emotion-emoji-empty .empty-icon {
	font-size: 32px;
}

.emotion-emoji-empty span {
	font-size: 13px;
	line-height: 1.5;
}

.ossn-wall-container-control-menu-emojii-selector i {
	font-weight: initial;
}

.emojii-container-main {
	display: none;
}

.ossn-emojii-output {
	font-style: initial;
	font-size: 20px;
}

.ossn-comment-attach-photo .fa-smile,
.ossn-message-attach-photo .fa-smile {
	float: right;
	position: relative;
	margin-right: 5px;
	margin-top: 5px;
	width: 25px;
	height: 25px;
	padding: 5px;
	cursor: pointer;
	font-weight: 400;
}

.ossn-comment-attach-photo .fa-smile {
	margin-top: 3px;
	font-size: 18px;
	color: #999;
}

.comment-container .emojii-container-main {
	float: right;
	margin-right: 285px;
}

.message-emojii {
	float: right;
	position: relative;
	top: 105px;
}

.comment-container {
	z-index: initial;
}


/***************************************
	Add system fonts for consistent
	emoji appearance on all platforms
.ossn-wall-container {
	font-family: "PT sans", "Apple Color Emoji","Segoe UI Emoji","NotoColorEmoji","Segoe UI Symbol","Android Emoji","EmojiSymbols";
}

.ossn-wall-item {
	font-family: "PT sans", "Apple Color Emoji","Segoe UI Emoji","NotoColorEmoji","Segoe UI Symbol","Android Emoji","EmojiSymbols";
}
.message-inner {
	font-family: "PT sans", "Apple Color Emoji","Segoe UI Emoji","NotoColorEmoji","Segoe UI Symbol","Android Emoji","EmojiSymbols";
}
.ossn-form textarea {
	font-family: "PT sans", "Apple Color Emoji","Segoe UI Emoji","NotoColorEmoji","Segoe UI Symbol","Android Emoji","EmojiSymbols";
}

.ossn-message-box {
	font-family: "PT sans", "Apple Color Emoji","Segoe UI Emoji","NotoColorEmoji","Segoe UI Symbol","Android Emoji","EmojiSymbols";
}
.ossn-chat-containers {
	font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif, "Apple Color Emoji","Segoe UI Emoji","NotoColorEmoji","Segoe UI Symbol","Android Emoji","EmojiSymbols";
}
.friend-tab-item .friend-tab input[type='text'] {
	font-family: 'lucida grande',tahoma,verdana,arial,sans-serif, "Apple Color Emoji","Segoe UI Emoji","NotoColorEmoji","Segoe UI Symbol","Android Emoji","EmojiSymbols";
}

****************************************/

.ossn-chat-base {
	font-family: "Lucida Grande", Verdana, Arial, "Bitstream Vera Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "NotoColorEmoji", "Segoe UI Symbol", "Android Emoji", "EmojiSymbols";
}

body {
	font-family: "PT Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "NotoColorEmoji", "Segoe UI Symbol", "Android Emoji", "EmojiSymbols";
}

.smiles-close {
	float: right;
	margin: 10px 10px 0 0;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50%;
	width: 24px;
	height: 24px;
	text-align: center;
	line-height: 24px;
	cursor: pointer;
	transition: background 0.2s ease;
	position: absolute;
	right: 0;
	top: 0;
	z-index: 10;
}

.smiles-close:hover {
	background: rgba(255, 255, 255, 0.35);
}

.smiles-close i {
	margin: 0 auto;
	color: white;
	font-size: 12px;
}