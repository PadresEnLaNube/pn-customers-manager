(function($){
  'use strict';

  var config = window.pnCmBudgetCsv || {};
  var ajaxUrl   = config.ajaxUrl || '';
  var nonce     = config.nonce || '';
  var exportUrl = config.exportUrl || '';
  var i18n      = config.i18n || {};

  var popupId   = 'pn-customers-manager-popup-pn_cm_budget-csv-import';
  var btnClass  = 'pn-customers-manager-btn';

  $(document).ready(function(){

    /* Export button */
    $('#pn-cm-budget-csv-export-btn').on('click', function(e){
      e.preventDefault();
      window.location.href = exportUrl;
    });

    /* Import button — open popup */
    $('#pn-cm-budget-csv-import-btn').on('click', function(e){
      e.preventDefault();
      openModal();
    });

    /* Elements */
    var fileInput = document.getElementById('pn-cm-budget-csv-file');
    var fileZone  = document.getElementById('pn-cm-budget-csv-file-zone');
    var fileName  = document.getElementById('pn-cm-budget-csv-file-name');
    var footer    = document.getElementById('pn-cm-budget-csv-footer');
    var loader    = document.getElementById('pn-cm-budget-csv-loader');
    var loaderTxt = document.getElementById('pn-cm-budget-csv-loader-text');
    var previewData = null;

    if (!fileInput) return;

    /* Modal open / close via plugin popup system */
    function openModal() {
      showStep(1);
      fileInput.value = '';
      fileName.textContent = '';
      previewData = null;
      pn_customers_manager_Popups.open(popupId);
    }

    function closeModal() {
      pn_customers_manager_Popups.close();
    }

    fileInput.addEventListener('change', function(){
      fileName.textContent = fileInput.files.length ? fileInput.files[0].name : '';
    });

    /* Drag & drop */
    fileZone.addEventListener('dragenter', function(e){ e.preventDefault(); e.stopPropagation(); fileZone.classList.add('pn-cm-csv-dragover'); });
    fileZone.addEventListener('dragover', function(e){ e.preventDefault(); e.stopPropagation(); });
    fileZone.addEventListener('dragleave', function(e){ e.preventDefault(); e.stopPropagation(); fileZone.classList.remove('pn-cm-csv-dragover'); });
    fileZone.addEventListener('drop', function(e){
      e.preventDefault();
      e.stopPropagation();
      fileZone.classList.remove('pn-cm-csv-dragover');
      var files = e.dataTransfer.files;
      if (files.length && files[0].name.toLowerCase().endsWith('.csv')) {
        fileInput.files = files;
        fileName.textContent = files[0].name;
      }
    });

    /* Steps */
    function showStep(n) {
      [1,2,3].forEach(function(i){
        document.getElementById('pn-cm-budget-csv-step-' + i).classList.toggle('pn-cm-csv-active', i === n);
      });
      loader.classList.remove('pn-cm-csv-active');
      renderFooter(n);
    }

    function showLoader(text) {
      [1,2,3].forEach(function(i){ document.getElementById('pn-cm-budget-csv-step-' + i).classList.remove('pn-cm-csv-active'); });
      loaderTxt.textContent = text || i18n.processing || 'Processing\u2026';
      loader.classList.add('pn-cm-csv-active');
      footer.innerHTML = '';
    }

    function renderFooter(step) {
      footer.innerHTML = '';
      if (step === 1) {
        footer.appendChild(link(i18n.cancel || 'Cancel', 'pn-customers-manager-popup-cancel pn-customers-manager-font-size-small', function(){ closeModal(); }));
        footer.appendChild(btn(i18n.selectFile || 'Select file', btnClass, function(){ fileInput.click(); }));
        footer.appendChild(btn(i18n.preview || 'Preview', btnClass, doPreview));
      }
      if (step === 2) {
        footer.appendChild(link(i18n.back || 'Back', 'pn-customers-manager-font-size-small', function(){ showStep(1); }));
        footer.appendChild(btn(i18n.importBtn || 'Import', btnClass, doImport));
      }
      if (step === 3) {
        footer.appendChild(btn(i18n.closeReload || 'Close & reload', btnClass, function(){ setTimeout(function(){ window.location.reload(); }, 100); }));
      }
    }

    function btn(label, cls, handler) {
      var a = document.createElement('a');
      a.href = '#'; a.className = cls; a.textContent = label;
      a.addEventListener('click', function(e){ e.preventDefault(); handler(); });
      return a;
    }

    function link(label, cls, handler) {
      var a = document.createElement('a');
      a.href = '#'; a.className = 'pn-customers-manager-text-decoration-none ' + cls; a.textContent = label;
      a.addEventListener('click', function(e){ e.preventDefault(); handler(); });
      return a;
    }

    /* Preview */
    function doPreview() {
      if (!fileInput.files.length) {
        alert(i18n.selectFile || 'Please select a CSV file first.');
        return;
      }
      showLoader(i18n.parsingCsv || 'Parsing CSV\u2026');

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_budget_csv_preview');
      fd.append('_wpnonce', nonce);
      fd.append('csv_file', fileInput.files[0]);

      fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(resp){
          if (!resp.success) { alert(resp.data || 'Error'); showStep(1); return; }
          previewData = resp.data;
          renderPreview(previewData);
          showStep(2);
        })
        .catch(function(err){ alert('Network error: ' + err); showStep(1); });
    }

    function renderPreview(data) {
      var wrap  = document.getElementById('pn-cm-budget-csv-preview-wrap');
      var count = document.getElementById('pn-cm-budget-csv-row-count');
      var displayHeader = data.display_header || data.header;
      if (!data.rows.length) {
        wrap.innerHTML = '<p style="padding:20px">' + esc(i18n.noDataRows || 'The CSV file has no data rows.') + '</p>';
        count.textContent = '';
        return;
      }
      var html = '<table><thead><tr>';
      displayHeader.forEach(function(h){ html += '<th>' + esc(h) + '</th>'; });
      html += '</tr></thead><tbody>';
      var max = Math.min(data.rows.length, 50);
      for (var i = 0; i < max; i++) {
        html += '<tr>';
        data.header.forEach(function(h){
          var val = data.rows[i][h] || '';
          if (h === '_items_json' && val.length > 80) {
            val = val.substring(0, 80) + '\u2026';
          }
          html += '<td>' + esc(val) + '</td>';
        });
        html += '</tr>';
      }
      html += '</tbody></table>';
      wrap.innerHTML = html;
      var msg = data.rows.length + ' ' + (i18n.rowsFound || 'rows found');
      if (data.rows.length > 50) msg += ' \u2014 ' + (i18n.showingFirst50 || 'showing first 50');
      count.textContent = msg;
    }

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    /* Import */
    function doImport() {
      if (!previewData || !previewData.rows.length) return;
      showLoader(i18n.importingBudgets || 'Importing budgets\u2026');

      var fd = new FormData();
      fd.append('action', 'pn_customers_manager_budget_csv_import');
      fd.append('_wpnonce', nonce);
      fd.append('rows', JSON.stringify(previewData.rows));

      fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(resp){
          if (!resp.success) { alert(resp.data || 'Error'); showStep(2); return; }
          renderResults(resp.data);
          showStep(3);
        })
        .catch(function(err){ alert('Network error: ' + err); showStep(2); });
    }

    function renderResults(data) {
      var el = document.getElementById('pn-cm-budget-csv-results');
      var html = '<div class="pn-cm-csv-results-success"><strong>' + data.created + '</strong> '
        + esc(i18n.budgetsCreated || 'budgets created successfully.') + '</div>';
      if (data.errors && data.errors.length) {
        html += '<div class="pn-cm-csv-results-errors"><strong>' + esc(i18n.errors || 'Errors:') + '</strong><ul>';
        data.errors.forEach(function(e){ html += '<li>' + esc(e) + '</li>'; });
        html += '</ul></div>';
      }
      el.innerHTML = html;
    }

  }); /* end document ready */
})(jQuery);
