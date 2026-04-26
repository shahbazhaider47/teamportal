<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI         =& get_instance();
$currentUid = (int)($CI->session->userdata('user_id') ?? 0);

$aiEnabled = false;
try {
    $CI->load->library('Ai_chat');
    $aiEnabled = $CI->ai_chat->isEnabled();
} catch (Exception $e) {
    $aiEnabled = false;
}
?>
<!-- ═══════════════════════════════════════════════════════════════════════
     RCM AI CHAT WIDGET  ·  application/views/apps/ai/widget.php
     Dependencies: marked@9, DOMPurify@3, Tabler Icons (ti-*)
════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── Design tokens ─────────────────────────────────────────────────────── */
:root {
  --ai-p:          #146969;
  --ai-p-dk:       #056464;
  --ai-p-lt:       #e7f0fd;
  --ai-p-glow:     rgba(32,96,200,.28);
  --ai-ok:         #1faa55;
  --ai-warn:       #e08b00;
  --ai-err:        #d03030;
  --ai-ink:        #1a1d23;
  --ai-ink2:       #5a6070;
  --ai-ink3:       #9aa0ad;
  --ai-line:       #e4e7ed;
  --ai-surface:    #ffffff;
  --ai-surface2:   #f4f6f9;
  --ai-surface3:   #edf0f5;
  --ai-r:          14px;
  --ai-r-sm:       8px;
  --ai-shadow:     0 12px 44px rgba(0,0,0,.13), 0 2px 8px rgba(0,0,0,.07);
  --ai-z:          1055;
  --ai-w:          400px;
  --ai-h:          560px;
}

/* ── Trigger button ────────────────────────────────────────────────────── */
#rcmAiTrigger {
  position:        fixed;
  bottom:          1.6rem;
  right:           1.6rem;
  z-index:         var(--ai-z);
  width:           52px;
  height:          52px;
  border-radius:   50%;
  background:      var(--ai-p);
  color:           #fff;
  border:          none;
  cursor:          pointer;
  display:         flex;
  align-items:     center;
  justify-content: center;
  box-shadow:      0 4px 16px var(--ai-p-glow), 0 1px 4px rgba(0,0,0,.15);
  transition:      transform .18s cubic-bezier(.34,1.56,.64,1),
                   box-shadow .18s ease;
  outline:         none;
}
#rcmAiTrigger:hover  { transform: scale(1.1);  box-shadow: 0 6px 22px var(--ai-p-glow); }
#rcmAiTrigger:active { transform: scale(.96); }
#rcmAiTrigger:focus-visible {
  outline: 3px solid var(--ai-p-lt);
  outline-offset: 3px;
}
#rcmAiTrigger .ai-trig-icon { font-size: 1.35rem; transition: transform .22s cubic-bezier(.34,1.56,.64,1); }
#rcmAiTrigger.ai-open-state .ai-trig-icon { transform: rotate(90deg); }

.ai-badge {
  position:        absolute;
  top:             -2px;
  right:           -2px;
  background:      var(--ai-err);
  color:           #fff;
  border-radius:   50%;
  min-width:       18px;
  height:          18px;
  font-size:       .6rem;
  font-weight:     700;
  display:         none;
  align-items:     center;
  justify-content: center;
  padding:         0 3px;
  border:          2px solid var(--ai-surface);
  line-height:     1;
}

/* ── Chat window ───────────────────────────────────────────────────────── */
#rcmAiWindow {
  position:        fixed;
  bottom:          4.9rem;
  right:           1.6rem;
  z-index:         calc(var(--ai-z) - 1);
  width:           var(--ai-w);
  height:          var(--ai-h);
  max-width:       calc(100vw - 2rem);
  max-height:      calc(100dvh - 7rem);
  min-width:       300px;
  min-height:      360px;
  background:      var(--ai-surface);
  border-radius:   var(--ai-r);
  box-shadow:      var(--ai-shadow);
  border:          1px solid var(--ai-line);
  display:         flex;
  flex-direction:  column;
  overflow:        hidden;
  opacity:         0;
  transform:       scale(.93) translateY(16px);
  pointer-events:  none;
  transition:      opacity .24s ease, transform .24s cubic-bezier(.34,1.3,.64,1);
  resize:          none; /* JS resize instead */
}
#rcmAiWindow.ai-open {
  opacity:         1;
  transform:       scale(1) translateY(0);
  pointer-events:  all;
}

/* ── Header ────────────────────────────────────────────────────────────── */
.ai-head {
  background:      var(--ai-p);
  color:           #fff;
  padding:         .55rem .85rem;
  display:         flex;
  align-items:     center;
  gap:             .5rem;
  flex-shrink:     0;
  cursor:          default;
  user-select:     none;
}
.ai-head-ava {
  width:           30px;
  height:          30px;
  border-radius:   50%;
  background:      rgba(255,255,255,.18);
  display:         flex;
  align-items:     center;
  justify-content: center;
  font-size:       .95rem;
  flex-shrink:     0;
}
.ai-head-info   { flex: 1; min-width: 0; }
.ai-head-name   { font-size: .82rem; font-weight: 700; line-height: 1.2; }
.ai-head-status {
  font-size:    .67rem;
  opacity:      .78;
  display:      flex;
  align-items:  center;
  gap:          .28rem;
  margin-top:   .05rem;
}
.ai-dot {
  width:         6px;
  height:        6px;
  border-radius: 50%;
  background:    #4cd97a;
  flex-shrink:   0;
  transition:    background .3s;
}
.ai-dot.thinking { background: #f5a623; animation: aiPulse 1s infinite; }
.ai-dot.error    { background: var(--ai-err); }
.ai-dot.offline  { background: var(--ai-ink3); }
@keyframes aiPulse {
  0%,100% { opacity: 1; }
  50%      { opacity: .4; }
}
.ai-hbtn {
  background:    none;
  border:        none;
  color:         rgba(255,255,255,.68);
  cursor:        pointer;
  width:         28px;
  height:        28px;
  display:       flex;
  align-items:   center;
  justify-content: center;
  border-radius: 6px;
  font-size:     .88rem;
  transition:    background .12s, color .12s;
  flex-shrink:   0;
}
.ai-hbtn:hover       { background: rgba(255,255,255,.16); color: #fff; }
.ai-hbtn:focus-visible { outline: 2px solid rgba(255,255,255,.55); outline-offset: 1px; }

/* ── Alert banner (disabled state) ────────────────────────────────────── */
.ai-alert {
  margin:        .6rem .75rem 0;
  padding:       .5rem .7rem;
  border-radius: var(--ai-r-sm);
  font-size:     .78rem;
  display:       flex;
  align-items:   flex-start;
  gap:           .45rem;
  flex-shrink:   0;
  line-height:   1.5;
}
.ai-alert.warn   { background: #fef9e7; color: #7d5a00; border: 1px solid #f5dc7a; }
.ai-alert.danger { background: #fef0f0; color: #8b1f1f; border: 1px solid #f5c0c0; }
.ai-alert i      { flex-shrink: 0; margin-top: 1px; font-size: .85rem; }
.ai-alert a      { color: inherit; font-weight: 600; }

/* ── Messages ──────────────────────────────────────────────────────────── */
#aiMessages {
  flex:            1;
  overflow-y:      auto;
  overflow-x:      hidden;
  padding:         .8rem .85rem;
  display:         flex;
  flex-direction:  column;
  gap:             .55rem;
  scroll-behavior: smooth;
  overscroll-behavior: contain;
}
#aiMessages::-webkit-scrollbar       { width: 3px; }
#aiMessages::-webkit-scrollbar-track { background: transparent; }
#aiMessages::-webkit-scrollbar-thumb { background: var(--ai-line); border-radius: 3px; }

/* New-message pill */
#aiScrollPill {
  position:        absolute;
  bottom:          5.5rem;
  left:            50%;
  transform:       translateX(-50%) translateY(6px);
  background:      var(--ai-p);
  color:           #fff;
  font-size:       .7rem;
  font-weight:     600;
  padding:         .22rem .65rem;
  border-radius:   20px;
  cursor:          pointer;
  box-shadow:      0 2px 10px var(--ai-p-glow);
  opacity:         0;
  pointer-events:  none;
  transition:      opacity .2s, transform .2s;
  z-index:         5;
  white-space:     nowrap;
}
#aiScrollPill.visible {
  opacity:         1;
  transform:       translateX(-50%) translateY(0);
  pointer-events:  all;
}

/* ── Welcome screen ────────────────────────────────────────────────────── */
.ai-welcome {
  text-align:  center;
  padding:     1.25rem .5rem .75rem;
  color:       var(--ai-ink2);
  font-size:   .8rem;
  line-height: 1.65;
}
.ai-welcome-ava {
  width:           48px;
  height:          48px;
  border-radius:   50%;
  background:      var(--ai-p-lt);
  color:           var(--ai-p);
  display:         flex;
  align-items:     center;
  justify-content: center;
  font-size:       1.3rem;
  margin:          0 auto .65rem;
}
.ai-welcome h6 {
  font-size:     .85rem;
  font-weight:   700;
  color:         var(--ai-ink);
  margin-bottom: .2rem;
}

/* ── Quick prompts ─────────────────────────────────────────────────────── */
.ai-qwrap {
  display:     flex;
  flex-wrap:   wrap;
  gap:         .3rem;
  padding:     0 .85rem .55rem;
  flex-shrink: 0;
}
.ai-qbtn {
  font-size:     .7rem;
  padding:       .2rem .55rem;
  border-radius: 20px;
  border:        1px solid var(--ai-line);
  background:    var(--ai-surface2);
  color:         var(--ai-ink2);
  cursor:        pointer;
  transition:    border-color .14s, background .14s, color .14s;
  white-space:   nowrap;
  line-height:   1.5;
}
.ai-qbtn:hover {
  border-color: var(--ai-p);
  background:   var(--ai-p-lt);
  color:        var(--ai-p);
}

/* ── Bubbles ───────────────────────────────────────────────────────────── */
.ai-bubble {
  max-width:     83%;
  padding:       .5rem .72rem;
  border-radius: 12px;
  font-size:     .81rem;
  line-height:   1.55;
  word-break:    break-word;
  position:      relative;
  animation:     aiBounceIn .2s cubic-bezier(.34,1.4,.64,1);
}
@keyframes aiBounceIn {
  from { opacity: 0; transform: translateY(8px) scale(.97); }
  to   { opacity: 1; transform: none; }
}

/* User bubble */
.ai-bubble.user {
  background:                 var(--ai-p);
  color:                      #fff;
  align-self:                 flex-end;
  border-bottom-right-radius: 3px;
  white-space:                pre-wrap;
}

/* Assistant bubble */
.ai-bubble.assistant {
  background:                var(--ai-surface3);
  color:                     var(--ai-ink);
  align-self:                flex-start;
  border-bottom-left-radius: 3px;
}

/* Error bubble */
.ai-bubble.error {
  background:                #fef2f2;
  color:                     var(--ai-err);
  align-self:                flex-start;
  border:                    1px solid #fecaca;
  border-bottom-left-radius: 3px;
  white-space:               pre-wrap;
}

/* Bubble footer row */
.ai-bubble-foot {
  display:     flex;
  align-items: center;
  gap:         .4rem;
  margin-top:  .3rem;
}
.ai-time {
  font-size:  .62rem;
  opacity:    .45;
  flex:       1;
}
.ai-bubble.user .ai-time { color: rgba(255,255,255,.8); text-align: right; }

/* Copy button */
.ai-copy-btn {
  background:    none;
  border:        none;
  padding:       0 2px;
  cursor:        pointer;
  color:         var(--ai-ink3);
  font-size:     .72rem;
  display:       flex;
  align-items:   center;
  gap:           .2rem;
  border-radius: 4px;
  transition:    color .12s, background .12s;
  opacity:       0;
  transition:    opacity .15s;
}
.ai-bubble.assistant:hover .ai-copy-btn { opacity: 1; }
.ai-copy-btn:hover  { color: var(--ai-p); background: rgba(32,96,200,.08); }
.ai-copy-btn.copied { color: var(--ai-ok); opacity: 1; }

/* ── Markdown styles ───────────────────────────────────────────────────── */
.ai-md { line-height: 1.62; }
.ai-md > *:first-child { margin-top: 0 !important; }
.ai-md > *:last-child  { margin-bottom: 0 !important; }
.ai-md p               { margin: 0 0 .48rem; }
.ai-md h1,.ai-md h2,.ai-md h3 {
  font-size: .83rem; font-weight: 700;
  margin: .55rem 0 .28rem; color: var(--ai-ink);
}
.ai-md h1 { font-size: .9rem; }
.ai-md ul,.ai-md ol { padding-left: 1.15rem; margin: .28rem 0 .48rem; }
.ai-md li  { margin-bottom: .18rem; }
.ai-md strong { font-weight: 700; color: var(--ai-ink); }
.ai-md em     { font-style: italic; }
.ai-md code {
  background:    rgba(0,0,0,.07);
  padding:       .08rem .3rem;
  border-radius: 3px;
  font-size:     .76rem;
  font-family:   'SFMono-Regular', Consolas, monospace;
}
.ai-md pre {
  background:    rgba(0,0,0,.07);
  padding:       .5rem .65rem;
  border-radius: 6px;
  overflow-x:    auto;
  margin:        .35rem 0;
}
.ai-md pre code { background: none; padding: 0; font-size: .75rem; }
.ai-md blockquote {
  border-left: 3px solid var(--ai-p);
  margin:      .35rem 0;
  padding-left:.55rem;
  color:       var(--ai-ink2);
  font-style:  italic;
}
.ai-md hr { border: none; border-top: 1px solid var(--ai-line); margin: .4rem 0; }
.ai-md a  { color: var(--ai-p); text-decoration: underline; }
.ai-md table { border-collapse: collapse; width: 100%; margin: .35rem 0; font-size: .76rem; }
.ai-md th { background: var(--ai-p-lt); color: var(--ai-p-dk); font-weight: 600; }
.ai-md th,.ai-md td { border: 1px solid var(--ai-line); padding: .25rem .45rem; text-align: left; }
.ai-md tr:nth-child(even) { background: rgba(0,0,0,.025); }

/* ── Typing indicator ──────────────────────────────────────────────────── */
.ai-typing {
  align-self:    flex-start;
  display:       flex;
  align-items:   center;
  gap:           4px;
  padding:       .45rem .65rem;
  background:    var(--ai-surface3);
  border-radius: 12px;
  border-bottom-left-radius: 3px;
}
.ai-typing span {
  width:         5px;
  height:        5px;
  background:    var(--ai-ink3);
  border-radius: 50%;
  animation:     aiDot 1s infinite ease-in-out;
}
.ai-typing span:nth-child(2) { animation-delay: .2s; }
.ai-typing span:nth-child(3) { animation-delay: .4s; }
@keyframes aiDot {
  0%,60%,100% { transform: translateY(0);    opacity: .45; }
  30%          { transform: translateY(-5px); opacity: 1;   }
}

/* ── Date divider ──────────────────────────────────────────────────────── */
.ai-divider {
  display:     flex;
  align-items: center;
  gap:         .5rem;
  font-size:   .64rem;
  color:       var(--ai-ink3);
  margin:      .1rem 0;
}
.ai-divider::before,.ai-divider::after {
  content:    '';
  flex:       1;
  height:     1px;
  background: var(--ai-line);
}

/* ── Input area ────────────────────────────────────────────────────────── */
.ai-input-wrap {
  border-top:   1px solid var(--ai-line);
  padding:      .5rem .7rem .55rem;
  display:      flex;
  flex-direction: column;
  gap:          .3rem;
  flex-shrink:  0;
  background:   var(--ai-surface);
}
.ai-input-row {
  display:      flex;
  align-items:  flex-end;
  gap:          .4rem;
}
.ai-textarea {
  flex:          1;
  border:        1px solid var(--ai-line);
  border-radius: var(--ai-r-sm);
  padding:       .42rem .6rem;
  font-size:     .8rem;
  font-family:   inherit;
  outline:       none;
  line-height:   1.5;
  max-height:    100px;
  overflow-y:    auto;
  transition:    border-color .16s, box-shadow .16s;
  color:         var(--ai-ink);
  background:    var(--ai-surface);
}
.ai-textarea:focus {
  border-color: var(--ai-p);
  box-shadow:   0 0 0 3px rgba(32,96,200,.1);
}
.ai-textarea::placeholder { color: var(--ai-ink3); }
.ai-textarea:disabled     { background: var(--ai-surface2); color: var(--ai-ink3); cursor: not-allowed; }
.ai-send {
  width:           34px;
  height:          34px;
  border-radius:   var(--ai-r-sm);
  background:      var(--ai-p);
  border:          none;
  color:           #fff;
  cursor:          pointer;
  display:         flex;
  align-items:     center;
  justify-content: center;
  flex-shrink:     0;
  transition:      background .14s, transform .12s;
  font-size:       .88rem;
}
.ai-send:hover:not(:disabled) { background: var(--ai-p-dk); transform: scale(1.06); }
.ai-send:active:not(:disabled){ transform: scale(.94); }
.ai-send:disabled { background: var(--ai-line); color: var(--ai-ink3); cursor: not-allowed; }

/* Input meta row */
.ai-input-meta {
  display:         flex;
  justify-content: space-between;
  align-items:     center;
  font-size:       .62rem;
  color:           var(--ai-ink3);
  padding:         0 .1rem;
}
.ai-char-count.near-limit { color: var(--ai-warn); }
.ai-char-count.at-limit   { color: var(--ai-err);  }
.ai-hint { opacity: .7; }

/* ── Footer ────────────────────────────────────────────────────────────── */
.ai-foot {
  display:         flex;
  align-items:     center;
  justify-content: center;
  gap:             .3rem;
  padding:         .18rem .75rem .32rem;
  font-size:       .62rem;
  color:           var(--ai-ink3);
  flex-shrink:     0;
  border-top:      1px solid var(--ai-line);
}
.ai-foot-model {
  font-weight:   600;
  color:         var(--ai-p);
  max-width:     160px;
  overflow:      hidden;
  text-overflow: ellipsis;
  white-space:   nowrap;
}

/* ── Responsive ────────────────────────────────────────────────────────── */
@media (max-width: 480px) {
  #rcmAiWindow {
    width:         100vw !important;
    height:        100dvh !important;
    max-width:     100vw;
    max-height:    100dvh;
    bottom:        0 !important;
    right:         0 !important;
    border-radius: 0;
  }
  #rcmAiTrigger   { bottom: 1.1rem; right: 1.1rem; }
}
</style>

<!-- ── Trigger ───────────────────────────────────────────────────────── -->
<button id="rcmAiTrigger"
        aria-label="Open RCM AI Assistant"
        title="RCM AI Assistant"
        aria-expanded="false"
        aria-controls="rcmAiWindow">
  <i class="ti ti-robot ai-trig-icon" id="aiTrigIcon"></i>
  <span class="ai-badge" id="aiBadge"></span>
</button>

<!-- ── Chat window ───────────────────────────────────────────────────── -->
<div id="rcmAiWindow" role="dialog" aria-modal="true" aria-label="RCM AI Assistant">

  <!-- New-message scroll pill -->
  <div id="aiScrollPill">↓ New message</div>

  <!-- Header -->
  <div class="ai-head">
    <div class="ai-head-ava"><i class="ti ti-robot"></i></div>
    <div class="ai-head-info">
      <div class="ai-head-name">RCM Assistant</div>
      <div class="ai-head-status">
        <span class="ai-dot" id="aiDot"></span>
        <span id="aiStatusTxt">Ready</span>
      </div>
    </div>
    <button class="ai-hbtn" id="aiClearBtn" title="New conversation" aria-label="New conversation">
      <i class="ti ti-refresh"></i>
    </button>
    <button class="ai-hbtn" id="aiMinBtn" title="Minimise" aria-label="Minimise">
      <i class="ti ti-minus"></i>
    </button>
    <button class="ai-hbtn" id="aiCloseBtn" title="Close" aria-label="Close">
      <i class="ti ti-x"></i>
    </button>
  </div>

  <!-- Disabled banner -->
  <?php if (!$aiEnabled): ?>
  <div class="ai-alert warn">
    <i class="ti ti-alert-triangle"></i>
    <span>
      AI assistant is not enabled.
      <?php if (function_exists('staff_can') && staff_can('viewsystem', 'general')): ?>
        <a href="<?= site_url('settings?group=ai') ?>">Configure in Settings →</a>
      <?php else: ?>
        Contact your administrator.
      <?php endif; ?>
    </span>
  </div>
  <?php endif; ?>

  <!-- Messages -->
  <div id="aiMessages" role="log" aria-live="polite" aria-label="Chat messages">
    <div class="ai-welcome" id="aiWelcome">
      <div class="ai-welcome-ava bg-light-primary"><i class="ti ti-message-chatbot"></i></div>
      <h6>RCM AI Assistant</h6>
      <p>Ask me about ICD-10 codes, CPT codes,<br>denials, AR workflows, and more.</p>
    </div>
  </div>

  <!-- Quick prompts -->
  <div class="ai-qwrap" id="aiQuickWrap">
    <button class="ai-qbtn" data-prompt="What is the difference between a rejected claim and a denied claim and how does each affect revenue?">Rejection vs Denial (impact)</button>
    <button class="ai-qbtn" data-prompt="Why do claims get denied even when authorization was approved?">Auth but still denied</button>
    <button class="ai-qbtn" data-prompt="How do I identify the root cause of recurring claim denials?">Root cause of denials</button>
    <button class="ai-qbtn" data-prompt="What is the fastest way to reduce days in AR without increasing workload?">Reduce AR days</button>
    <button class="ai-qbtn" data-prompt="Why are claims paid less than expected and how do I detect underpayments?">Underpayments detection</button>
  </div>

  <!-- Input -->
  <div class="ai-input-wrap">
    <div class="ai-input-row">
      <textarea class="ai-textarea"
                id="aiInput"
                rows="1"
                maxlength="800"
                placeholder="Ask about billing, denials, CPT codes…"
                aria-label="Message"
                <?= !$aiEnabled ? 'disabled' : '' ?>></textarea>
      <button class="ai-send" id="aiSendBtn" aria-label="Send" <?= !$aiEnabled ? 'disabled' : '' ?>>
        <i class="ti ti-send" style="font-size:.82rem"></i>
      </button>
    </div>
    <div class="ai-input-meta">
      <span class="ai-hint">Shift+Enter for new line</span>
      <span class="ai-char-count" id="aiCharCount">0 / 800</span>
    </div>
  </div>

  <!-- Footer -->
  <div class="ai-foot">
    <span>Powered by</span>
    <span class="ai-foot-model" id="aiFootModel">
      <?= $aiEnabled ? 'AI' : 'Not configured' ?>
    </span>
    <span>· verify critical info</span>
  </div>

</div>

<!-- Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>

<script>
(function () {
  'use strict';

  /* ── Config from PHP ──────────────────────────────────────────────────── */
  const SEND_URL  = '<?= site_url('ai_chat/send') ?>';
  const ENABLED   = <?= $aiEnabled ? 'true' : 'false' ?>;
  const SELF_UID  = <?= (int)$currentUid ?>;
  const STORE_KEY = 'rcm_ai_v2_' + SELF_UID;
  const MAX_HIST  = 20;
  const MAX_CHARS = 2000;

  /* ── Configure marked ─────────────────────────────────────────────────── */
  if (window.marked) {
    marked.setOptions({
      breaks:   true,   // single newline → <br>
      gfm:      true,   // GitHub Flavored Markdown (tables, strikethrough)
      pedantic: false,
      renderer: (function () {
        const r = new marked.Renderer();
        // Open links in new tab safely
        r.link = function (href, title, text) {
          const t = title ? ` title="${title}"` : '';
          return `<a href="${href}"${t} target="_blank" rel="noopener noreferrer">${text}</a>`;
        };
        return r;
      })(),
    });
  }

  function mdToHtml(text) {
    if (!window.marked) return escHtml(text).replace(/\n/g, '<br>');
    const raw = marked.parse(text);
    return window.DOMPurify
      ? DOMPurify.sanitize(raw, { USE_PROFILES: { html: true } })
      : raw;
  }

  /* ── State ────────────────────────────────────────────────────────────── */
  let isOpen      = false;
  let isBusy      = false;
  let unread      = 0;
  let history     = loadHistory();
  let initialized = false;
  let lastModel   = '';

  /* ── DOM ──────────────────────────────────────────────────────────────── */
  const trigger   = document.getElementById('rcmAiTrigger');
  const trigIcon  = document.getElementById('aiTrigIcon');
  const badge     = document.getElementById('aiBadge');
  const win       = document.getElementById('rcmAiWindow');
  const msgs      = document.getElementById('aiMessages');
  const welcome   = document.getElementById('aiWelcome');
  const quickWrap = document.getElementById('aiQuickWrap');
  const input     = document.getElementById('aiInput');
  const sendBtn   = document.getElementById('aiSendBtn');
  const clearBtn  = document.getElementById('aiClearBtn');
  const minBtn    = document.getElementById('aiMinBtn');
  const closeBtn  = document.getElementById('aiCloseBtn');
  const dot       = document.getElementById('aiDot');
  const statusTxt = document.getElementById('aiStatusTxt');
  const footModel = document.getElementById('aiFootModel');
  const charCount = document.getElementById('aiCharCount');
  const scrollPill= document.getElementById('aiScrollPill');

  /* ── Session storage ──────────────────────────────────────────────────── */
  function loadHistory() {
    try {
      const raw = sessionStorage.getItem(STORE_KEY);
      if (!raw) return [];
      const p = JSON.parse(raw);
      return Array.isArray(p) ? p : [];
    } catch (e) { return []; }
  }

  function saveHistory() {
    try {
      sessionStorage.setItem(STORE_KEY, JSON.stringify(history.slice(-MAX_HIST)));
    } catch (e) { /* quota — ignore */ }
  }

  function historyForServer() {
    return history.map(function (m) {
      return { role: m.role, content: m.content };
    });
  }

  /* ── Open / close ─────────────────────────────────────────────────────── */
  function openChat() {
    isOpen = true;
    win.classList.add('ai-open');
    trigger.classList.add('ai-open-state');
    trigger.setAttribute('aria-expanded', 'true');

    unread = 0;
    badge.style.display = 'none';
    badge.textContent   = '';

    if (!initialized) {
      initialized = true;
      if (history.length > 0) {
        hideWelcome();
        history.forEach(function (m) {
          renderBubble(m.role, m.content, m.time, m.model, false);
        });
      }
      scrollBottom();
    }

    setTimeout(function () { if (ENABLED && input) input.focus(); }, 220);
  }

  function closeChat() {
    isOpen = false;
    win.classList.remove('ai-open');
    trigger.classList.remove('ai-open-state');
    trigger.setAttribute('aria-expanded', 'false');
  }

  function toggleChat() { isOpen ? closeChat() : openChat(); }

  // Public API
  window.RcmAiChat = { open: openChat, close: closeChat, toggle: toggleChat };

  trigger.addEventListener('click', toggleChat);
  closeBtn.addEventListener('click', closeChat);
  minBtn.addEventListener('click', closeChat);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) closeChat();
  });

  /* ── Clear conversation ───────────────────────────────────────────────── */
  clearBtn.addEventListener('click', function () {
    if (isBusy) return;
    history = [];
    sessionStorage.removeItem(STORE_KEY);
    msgs.querySelectorAll('.ai-bubble,.ai-typing,.ai-divider')
        .forEach(function (el) { el.remove(); });
    showWelcome();
    setStatus('ready');
    updateFootModel('');
  });

  /* ── Send ─────────────────────────────────────────────────────────────── */
  async function send() {
    if (!ENABLED || isBusy) return;
    const text = input.value.trim();
    if (!text) return;

    isBusy = true;
    input.value = '';
    updateCharCount();
    autoResize();
    setSendEnabled(false);
    hideWelcome();

    const t = nowTime();
    history.push({ role: 'user', content: text, time: t });
    saveHistory();
    renderBubble('user', text, t);

    const typing = appendTyping();
    setStatus('thinking');

    try {
      const fd = new FormData();
      fd.append('message', text);
      fd.append('history', JSON.stringify(historyForServer().slice(0, -1)));

      const res = await fetch(SEND_URL, { method: 'POST', body: fd });

      if (!res.ok) throw new Error('HTTP ' + res.status);

      const data = await res.json();
      typing.remove();

      if (data.success) {
        const rt    = nowTime();
        const model = data.model || '';
        history.push({ role: 'assistant', content: data.reply, time: rt, model: model });
        saveHistory();
        renderBubble('assistant', data.reply, rt, model);
        setStatus('ready');
        updateFootModel(model);

        if (!isOpen) {
          unread++;
          badge.textContent   = unread > 9 ? '9+' : String(unread);
          badge.style.display = 'flex';
        }
      } else {
        typing.remove();
        renderBubble('error', data.error || 'Something went wrong. Please try again.');
        setStatus('error');
      }

    } catch (err) {
      typing.remove();
      const msg = err.message.startsWith('HTTP')
        ? 'Server error (' + err.message + '). Please try again.'
        : 'Network error — check your connection.';
      renderBubble('error', msg);
      setStatus('error');
    }

    isBusy = false;
    setSendEnabled(true);
    scrollBottom();
    if (isOpen && input) input.focus();
  }

  sendBtn.addEventListener('click', send);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
  });

  /* ── Quick prompts ────────────────────────────────────────────────────── */
  quickWrap && quickWrap.addEventListener('click', function (e) {
    const btn = e.target.closest('.ai-qbtn');
    if (!btn || !ENABLED) return;
    input.value = btn.dataset.prompt || '';
    updateCharCount();
    send();
  });

  /* ── Render bubble ────────────────────────────────────────────────────── */
  function renderBubble(role, content, timeStr, model, scroll) {
    scroll = scroll !== false;

    const wrap = document.createElement('div');
    wrap.className = 'ai-bubble ' + (
      role === 'user'  ? 'user'  :
      role === 'error' ? 'error' : 'assistant'
    );

    // Content
    if (role === 'assistant') {
      const md = document.createElement('div');
      md.className = 'ai-md';
      md.innerHTML = mdToHtml(content);
      wrap.appendChild(md);
    } else {
      wrap.appendChild(document.createTextNode(content));
    }

    // Footer row (time + copy)
    const foot = document.createElement('div');
    foot.className = 'ai-bubble-foot';

    const time = document.createElement('span');
    time.className   = 'ai-time';
    time.textContent = timeStr || nowTime();
    foot.appendChild(time);

    if (role === 'assistant') {
      const copyBtn = document.createElement('button');
      copyBtn.className   = 'ai-copy-btn';
      copyBtn.title       = 'Copy response';
      copyBtn.setAttribute('aria-label', 'Copy response');
      copyBtn.innerHTML   = '<i class="ti ti-copy"></i>';
      copyBtn.addEventListener('click', function () {
        navigator.clipboard.writeText(content).then(function () {
          copyBtn.innerHTML   = '<i class="ti ti-check"></i>';
          copyBtn.classList.add('copied');
          setTimeout(function () {
            copyBtn.innerHTML = '<i class="ti ti-copy"></i>';
            copyBtn.classList.remove('copied');
          }, 1800);
        }).catch(function () {
          // Fallback for older browsers
          const ta = document.createElement('textarea');
          ta.value = content;
          ta.style.position = 'fixed';
          ta.style.opacity  = '0';
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        });
      });
      foot.appendChild(copyBtn);
    }

    wrap.appendChild(foot);
    msgs.appendChild(wrap);

    if (scroll) {
      const nearBottom = isNearBottom();
      scrollBottom();
      if (!nearBottom && !isOpen) showScrollPill();
    }

    return wrap;
  }

  /* ── Typing indicator ─────────────────────────────────────────────────── */
  function appendTyping() {
    const d = document.createElement('div');
    d.className = 'ai-typing';
    d.innerHTML = '<span></span><span></span><span></span>';
    msgs.appendChild(d);
    scrollBottom();
    return d;
  }

  /* ── Welcome / quick-prompts ──────────────────────────────────────────── */
  function hideWelcome() {
    if (welcome)   welcome.style.display   = 'none';
    if (quickWrap) quickWrap.style.display = 'none';
  }
  function showWelcome() {
    if (welcome)   welcome.style.display   = '';
    if (quickWrap) quickWrap.style.display = 'flex';
  }

  /* ── Scroll helpers ───────────────────────────────────────────────────── */
  function scrollBottom() {
    msgs.scrollTop = msgs.scrollHeight;
  }
  function isNearBottom() {
    return msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight < 80;
  }
  function showScrollPill() {
    scrollPill.classList.add('visible');
  }
  function hideScrollPill() {
    scrollPill.classList.remove('visible');
  }

  msgs.addEventListener('scroll', function () {
    if (isNearBottom()) hideScrollPill();
  });
  scrollPill.addEventListener('click', function () {
    scrollBottom();
    hideScrollPill();
  });

  /* ── UI helpers ───────────────────────────────────────────────────────── */
  function setSendEnabled(on) {
    sendBtn.disabled = !on;
    input.disabled   = !on;
  }

  function setStatus(state) {
    const map = {
      ready    : ['#4cd97a', 'Ready',     ''],
      thinking : ['#f5a623', 'Thinking…', 'thinking'],
      error    : [null,      'Error',     'error'],
      offline  : [null,      'Offline',   'offline'],
    };
    const [color, text, cls] = map[state] || map.ready;
    dot.style.background = color || 'var(--ai-err)';
    statusTxt.textContent = text;
    dot.className = 'ai-dot' + (cls ? ' ' + cls : '');
  }

  function updateFootModel(model) {
    lastModel = model || lastModel;
    footModel.textContent = lastModel || (ENABLED ? 'AI' : 'Not configured');
  }

  function updateCharCount() {
    const len = input.value.length;
    charCount.textContent = len + ' / ' + MAX_CHARS;
    charCount.className   = 'ai-char-count'
      + (len >= MAX_CHARS        ? ' at-limit'   :
         len >= MAX_CHARS * 0.85 ? ' near-limit' : '');
  }

  function autoResize() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 100) + 'px';
  }

  input.addEventListener('input', function () {
    updateCharCount();
    autoResize();
  });


  /* ── Utility ──────────────────────────────────────────────────────────── */
  function nowTime() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* ── Init ─────────────────────────────────────────────────────────────── */
  if (!ENABLED) {
    setStatus('offline');
  }
  updateCharCount();

})();
</script>