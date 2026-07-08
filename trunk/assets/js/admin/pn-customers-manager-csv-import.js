(function(){
  'use strict';

  var config = window.pnCmCsvImport || {};
  var ajaxUrl = config.ajaxUrl || '';
  var nonce   = config.nonce || '';
  var context = config.context || 'admin';
  var i18n    = config.i18n || {};

  var btnPrimaryClass = context === 'admin' ? 'button button-primary' : 'pn-customers-manager-btn';
  var btnClass        = context === 'admin' ? 'button' : 'pn-customers-manager-btn';

  /* Inject Import button (admin) or bind trigger (frontend) */
  if (context === 'admin') {
    var addNewBtn = document.querySelector('.page-title-action');
    if (addNewBtn) {
      var importBtn = document.createElement('a');
      importBtn.href = '#';
      importBtn.className = 'page-title-action pn-cm-csv-import-btn';
      importBtn.innerHTML = '<span class="dashicons dashicons-upload"></span> ' + (i18n.importCsv || 'Import CSV');
      addNewBtn.parentNode.insertBefore(importBtn, addNewBtn.nextSibling);
      importBtn.addEventListener('click', function(e){ e.preventDefault(); openModal(); });
    }
  } else {
    var triggerBtn = document.getElementById('pn-cm-csv-import-trigger');
    if (triggerBtn) {
      triggerBtn.addEventListener('click', function(e){ e.preventDefault(); openModal(); });
    }
  }

  /* Elements */
  var overlay   = document.getElementById('pn-cm-csv-overlay');
  var closeBtn  = document.getElementById('pn-cm-csv-close');
  var fileInput = document.getElementById('pn-cm-csv-file');
  var fileZone  = document.getElementById('pn-cm-csv-file-zone');
  var fileName  = document.getElementById('pn-cm-csv-file-name');
  var footer    = document.getElementById('pn-cm-csv-footer');
  var loader    = document.getElementById('pn-cm-csv-loader');
  var loaderTxt = document.getElementById('pn-cm-csv-loader-text');
  var previewData = null;

  if (!overlay) return;

  /* Modal */
  function openModal() {
    showStep(1);
    fileInput.value = '';
    fileName.textContent = '';
    previewData = null;
    overlay.classList.add('pn-cm-csv-open');
  }

  function closeModal() {
    overlay.classList.remove('pn-cm-csv-open');
  }

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });

  /* Drag & drop */
  ['dragenter','dragover'].forEach(function(evt){
    fileZone.addEventListener(evt, function(e){ e.preventDefault(); fileZone.classList.add('pn-cm-csv-dragover'); });
  });
  ['dragleave','drop'].forEach(function(evt){
    fileZone.addEventListener(evt, function(e){ e.preventDefault(); fileZone.classList.remove('pn-cm-csv-dragover'); });
  });
  fileZone.addEventListener('drop', function(e){
    var files = e.dataTransfer.files;
    if (files.length && files[0].name.toLowerCase().endsWith('.csv')) {
      fileInput.files = files;
      fileName.textContent = files[0].name;
    }
  });
  fileInput.addEventListener('change', function(){
    fileName.textContent = fileInput.files.length ? fileInput.files[0].name : '';
  });

  /* Steps */
  function showStep(n) {
    [1,2,3].forEach(function(i){
      document.getElementById('pn-cm-csv-step-' + i).classList.toggle('pn-cm-csv-active', i === n);
    });
    loader.classList.remove('pn-cm-csv-active');
    renderFooter(n);
  }

  function showLoader(text) {
    [1,2,3].forEach(function(i){ document.getElementById('pn-cm-csv-step-' + i).classList.remove('pn-cm-csv-active'); });
    loaderTxt.textContent = text || i18n.processing || 'Processing\u2026';
    loader.classList.add('pn-cm-csv-active');
    footer.innerHTML = '';
  }

  function renderFooter(step) {
    footer.innerHTML = '';
    if (step === 1) {
      footer.appendChild(btn(i18n.preview || 'Preview', btnPrimaryClass, doPreview));
    }
    if (step === 2) {
      footer.appendChild(btn(i18n.back || 'Back', btnClass, function(){ showStep(1); }));
      footer.appendChild(btn(i18n.importBtn || 'Import', btnPrimaryClass, doImport));
    }
    if (step === 3) {
      footer.appendChild(btn(i18n.closeReload || 'Close & reload', btnPrimaryClass, function(){ window.location.reload(); }));
    }
  }

  function btn(label, cls, handler) {
    var b = document.createElement('button');
    b.type = 'button'; b.className = cls; b.textContent = label;
    b.addEventListener('click', handler);
    return b;
  }

  /* Preview */
  function doPreview() {
    if (!fileInput.files.length) {
      alert(i18n.selectFile || 'Please select a CSV file first.');
      return;
    }
    showLoader(i18n.parsingCsv || 'Parsing CSV\u2026');

    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_csv_preview');
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
    var wrap  = document.getElementById('pn-cm-csv-preview-wrap');
    var count = document.getElementById('pn-cm-csv-row-count');
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
      data.header.forEach(function(h){ html += '<td>' + esc(data.rows[i][h] || '') + '</td>'; });
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
    showLoader(i18n.importingOrganizations || 'Importing organizations\u2026');

    var fd = new FormData();
    fd.append('action', 'pn_customers_manager_csv_import');
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
    var el = document.getElementById('pn-cm-csv-results');
    var html = '<div class="pn-cm-csv-results-success"><strong>' + data.created + '</strong> '
      + esc(i18n.organizationsCreated || 'organizations created successfully.') + '</div>';
    if (data.errors && data.errors.length) {
      html += '<div class="pn-cm-csv-results-errors"><strong>' + esc(i18n.errors || 'Errors:') + '</strong><ul>';
      data.errors.forEach(function(e){ html += '<li>' + esc(e) + '</li>'; });
      html += '</ul></div>';
    }
    el.innerHTML = html;
  }

})();
