/* =====================================================================
 *  Admin JS — editor toolbar & helpers (vanilla)
 * ===================================================================== */
(function () {
  'use strict';

  /* ---------------- Rich text editor toolbar ---------------- */
  const editor = document.getElementById('editor');
  const toolbar = document.getElementById('editor-toolbar');

  if (editor && toolbar) {
    toolbar.addEventListener('mousedown', function (e) {
      e.preventDefault(); // keep selection in the editor
    });

    toolbar.addEventListener('click', function (e) {
      const btn = e.target.closest('button');
      if (!btn) return;
      editor.focus();

      if (btn.id === 'btn-code') {
        wrapSelection('<pre><code>', '</code></pre>');
        return;
      }
      if (btn.id === 'btn-hr') {
        document.execCommand('insertHTML', false, '<hr>');
        return;
      }

      const block = btn.dataset.block;
      if (block) {
        document.execCommand('formatBlock', false, block);
        return;
      }

      const cmd = btn.dataset.cmd;
      if (cmd === 'createLink') {
        const url = prompt('Enter the link URL:', 'https://');
        if (url) document.execCommand('createLink', false, url);
        return;
      }
      if (cmd) {
        document.execCommand(cmd, false, btn.dataset.val || null);
      }
    });

    // Ctrl/Cmd shortcuts
    editor.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && !e.shiftKey) {
        if (e.key === 'b') { e.preventDefault(); document.execCommand('bold'); }
        if (e.key === 'i') { e.preventDefault(); document.execCommand('italic'); }
        if (e.key === 'k') { e.preventDefault(); const u = prompt('Link URL:', 'https://'); if (u) document.execCommand('createLink', false, u); }
      }
    });

    // Paste as clean HTML
    editor.addEventListener('paste', function (e) {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text/html')
                || (e.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertHTML', false, sanitizeForEditor(text));
    });
  }

  function wrapSelection(open, close) {
    const sel = window.getSelection();
    if (!sel.rangeCount) { document.execCommand('insertHTML', false, open + 'code' + close); return; }
    document.execCommand('insertHTML', false, open + sel.toString() + close);
  }

  function sanitizeForEditor(html) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const allowed = ['P','BR','H2','H3','H4','STRONG','B','EM','I','U','S','BLOCKQUOTE',
                     'UL','OL','LI','A','PRE','CODE','HR','IMG','SPAN'];
    (function walk(node) {
      Array.from(node.childNodes).forEach(child => {
        if (child.nodeType === 1) {
          if (!allowed.includes(child.tagName)) {
            const text = document.createTextNode(child.textContent);
            child.replaceWith(text);
            return;
          }
          Array.from(child.attributes).forEach(attr => {
            if (!['href','src','alt','title'].includes(attr.name)) child.removeAttribute(attr.name);
          });
          walk(child);
        }
      });
    })(doc.body);
    return doc.body.innerHTML;
  }

  /* ---------------- Auto-dismiss flash ---------------- */
  document.querySelectorAll('[data-flash]').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s, transform .4s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 5000);
  });

  /* ---------------- Confirm on destructive forms ---------------- */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  /* ---------------- Mobile sidebar toggle fallback ---------------- */
  var sideOpenBtn = document.querySelector('[data-admin-side-open]');
  var sideCloseBtn = document.querySelector('[data-admin-side-close]');
  var sideOverlay = document.querySelector('[data-admin-side-overlay]');
  var sidebar = document.getElementById('admin-sidebar');

  function openSidebar() {
    if (sidebar) sidebar.classList.add('translate-x-0');
    if (sideOverlay) { sideOverlay.classList.remove('hidden'); sideOverlay.style.display = 'block'; }
  }
  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('translate-x-0');
    if (sideOverlay) { sideOverlay.style.display = 'none'; }
  }

  if (sideOpenBtn) sideOpenBtn.addEventListener('click', openSidebar);
  if (sideCloseBtn) sideCloseBtn.addEventListener('click', closeSidebar);
  if (sideOverlay) sideOverlay.addEventListener('click', closeSidebar);
})();
