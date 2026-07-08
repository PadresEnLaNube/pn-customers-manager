/**
 * WhatsApp IA Admin – Conversations page behaviour.
 *
 * Auto-scrolls chat to the bottom on load.
 *
 * @since 1.0.42
 */
(function () {
  'use strict';

  function init() {
    // Auto-scroll chat to bottom
    var chat = document.getElementById('pn-cm-wa-chat');
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
