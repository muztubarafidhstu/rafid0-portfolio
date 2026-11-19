/* Portfolio — admin panel interactions */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var maxUpload = parseInt(document.body.getAttribute('data-max-upload') || '0', 10);

  function fmt(bytes) {
    if (!bytes) return '0 B';
    var u = ['B', 'KB', 'MB', 'GB'], i = Math.min(3, Math.floor(Math.log(bytes) / Math.log(1024)));
    return (bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + u[i];
  }

  /* Theme */
  $$('[data-theme-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var n = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', n);
      try { localStorage.setItem('theme', n); } catch (e) {}
    });
  });

  /* Sidebar on mobile */
  var side = $('#admin-side');
  $$('[data-admin-menu]').forEach(function (b) {
    b.addEventListener('click', function (e) { e.stopPropagation(); side.classList.toggle('open'); });
  });
  document.addEventListener('click', function (e) { if (side && side.classList.contains('open') && !side.contains(e.target)) side.classList.remove('open'); });

  /* Confirm before destructive actions */
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });
  $$('[data-confirm-click]').forEach(function (b) {
    b.addEventListener('click', function (e) { if (!confirm(b.getAttribute('data-confirm-click'))) e.preventDefault(); });
  });

  /* Dropzones with previews */
  $$('[data-dropzone]').forEach(function (zone) {
    var input = $('input[type=file]', zone);
    var form = zone.closest('form');
    var previews = form ? $('[data-previews]', form) : null;
    var store = (typeof DataTransfer !== 'undefined') ? new DataTransfer() : null;

    function sync() {
      if (store) input.files = store.files;
      render();
    }
    function render() {
      if (!previews) return;
      previews.innerHTML = '';
      Array.prototype.forEach.call(input.files, function (file, i) {
        var item = document.createElement('div');
        item.className = 'preview-item';
        var tooBig = maxUpload && file.size > maxUpload;
        if (/^image\//.test(file.type)) {
          var img = document.createElement('img');
          img.src = URL.createObjectURL(file);
          img.onload = function () { URL.revokeObjectURL(img.src); };
          item.appendChild(img);
        } else {
          var ext = document.createElement('span');
          ext.className = 'file-icon ' + (/pdf$/i.test(file.name) ? 'pdf' : /^video\//.test(file.type) ? 'zip' : 'doc');
          ext.textContent = (file.name.split('.').pop() || '').toUpperCase().slice(0, 4);
          item.appendChild(ext);
        }
        var cap = document.createElement('small');
        cap.textContent = file.name + ' · ' + fmt(file.size) + (tooBig ? ' — too large for your hosting (' + fmt(maxUpload) + ')' : '');
        if (tooBig) item.classList.add('bad');
        item.appendChild(cap);
        if (store) {
          var rm = document.createElement('button');
          rm.type = 'button'; rm.className = 'preview-remove'; rm.setAttribute('aria-label', 'Remove'); rm.textContent = '×';
          rm.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            var dt = new DataTransfer();
            Array.prototype.forEach.call(store.files, function (f, j) { if (j !== i) dt.items.add(f); });
            store = dt; sync();
          });
          item.appendChild(rm);
        }
        previews.appendChild(item);
      });
      zone.classList.toggle('has-files', input.files.length > 0);
    }

    input.addEventListener('change', function () {
      if (store) {
        Array.prototype.forEach.call(input.files, function (f) { store.items.add(f); });
        if (!input.multiple && store.files.length > 1) { var dt = new DataTransfer(); dt.items.add(store.files[store.files.length - 1]); store = dt; }
        sync();
      } else render();
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('drag'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('drag'); });
    });
    zone.addEventListener('drop', function (e) {
      if (!e.dataTransfer || !e.dataTransfer.files.length) return;
      if (store) {
        Array.prototype.forEach.call(e.dataTransfer.files, function (f) { store.items.add(f); });
        sync();
      } else { input.files = e.dataTransfer.files; render(); }
    });
  });

  /* Submit forms with files via XHR to show progress */
  $$('[data-upload-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var total = 0;
      $$('input[type=file]', form).forEach(function (i) { Array.prototype.forEach.call(i.files || [], function (f) { total += f.size; }); });
      if (!total || !window.FormData) return; // normal submit
      e.preventDefault();
      if (maxUpload && total > maxUpload) {
        if (!confirm('These files total ' + fmt(total) + ', but your hosting accepts about ' + fmt(maxUpload) + ' per save. The upload will probably fail.\n\nTry anyway?')) return;
      }
      var box = $('[data-progress]', form) || $('[data-progress]');
      var bar = box ? $('span', box) : null, label = box ? $('b', box) : null;
      var btns = $$('button[type=submit]', form);
      btns.forEach(function (b) { b.disabled = true; });
      if (box) box.hidden = false;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', form.getAttribute('action') || location.href);
      xhr.setRequestHeader('X-Upload-XHR', '1');
      xhr.upload.addEventListener('progress', function (ev) {
        if (!ev.lengthComputable) return;
        var p = Math.round(ev.loaded / ev.total * 100);
        if (bar) bar.style.width = p + '%';
        if (label) label.textContent = p + '%' + (p === 100 ? ' — processing…' : '');
      });
      xhr.onload = function () {
        var data = null;
        try { data = JSON.parse(xhr.responseText); } catch (err) {}
        if (data && data.redirect) { location.href = data.redirect; return; }
        btns.forEach(function (b) { b.disabled = false; });
        if (box) box.hidden = true;
        alert((data && data.error) || 'Upload failed (server responded with status ' + xhr.status + '). Try smaller files or fewer at once.');
      };
      xhr.onerror = function () {
        btns.forEach(function (b) { b.disabled = false; });
        if (box) box.hidden = true;
        alert('Upload failed — check your internet connection and try again.');
      };
      xhr.send(new FormData(form));
    });
  });

  /* Settings tabs */
  var stabs = $$('[data-stab]');
  function showStab(name) {
    if (!$('[data-spanel="' + name + '"]')) name = 'general';
    stabs.forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-stab') === name); });
    $$('[data-spanel]').forEach(function (p) { p.hidden = p.getAttribute('data-spanel') !== name; });
  }
  if (stabs.length) {
    stabs.forEach(function (t) {
      t.addEventListener('click', function (e) { e.preventDefault(); showStab(t.getAttribute('data-stab')); history.replaceState(null, '', '#' + t.getAttribute('data-stab')); });
    });
    showStab((location.hash || '#general').slice(1));
  }

  /* Accent colour swatches */
  $$('[data-swatch]').forEach(function (s) {
    s.addEventListener('click', function () {
      var input = s.parentNode.querySelector('input[type=color]');
      input.value = s.getAttribute('data-swatch');
      document.documentElement.style.setProperty('--accent', input.value);
    });
  });
  $$('input[type=color][name=accent_color]').forEach(function (i) {
    i.addEventListener('input', function () { document.documentElement.style.setProperty('--accent', i.value); });
  });

  /* Warn about unsaved changes on edit forms */
  $$('form.edit-layout').forEach(function (f) {
    var dirty = false;
    f.addEventListener('input', function () { dirty = true; });
    f.addEventListener('change', function () { dirty = true; });
    f.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });
})();
