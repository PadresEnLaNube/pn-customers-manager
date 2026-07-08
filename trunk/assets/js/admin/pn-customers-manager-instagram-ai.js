/**
 * Instagram AI Admin – Conversations page behaviour.
 *
 * Auto-scrolls chat to the bottom on load.
 *
 * @since 1.0.60
 */
(function () {
  'use strict';

  function init() {
    // Auto-scroll chat to bottom
    var chat = document.getElementById('pn-cm-ig-chat');
    if (chat) {
      chat.scrollTop = chat.scrollHeight;
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
