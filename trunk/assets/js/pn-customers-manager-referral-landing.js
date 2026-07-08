(function() {
  'use strict';

  var popupId = 'pn-cm-qr-referral-landing-popup';

  function openPopup() {
    if (typeof pn_customers_manager_Popups !== 'undefined') {
      pn_customers_manager_Popups.open(popupId);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', openPopup);
  } else {
    openPopup();
  }
})();
