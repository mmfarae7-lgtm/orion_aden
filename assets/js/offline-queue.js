(function () {
  const QKEY = 'orion_offline_queue';
  let pendingCount = 0;

  function getQueue() {
    try { return JSON.parse(localStorage.getItem(QKEY) || '[]'); } catch(e) { return []; }
  }

  function saveQueue(q) {
    localStorage.setItem(QKEY, JSON.stringify(q));
    updateBadge(q.length);
  }

  function updateBadge(n) {
    pendingCount = n;
    let b = document.getElementById('offlineBadge');
    if (n > 0) {
      if (!b) {
        b = document.createElement('div');
        b.id = 'offlineBadge';
        b.style.cssText = 'position:fixed;bottom:70px;left:10px;z-index:9999;background:#ffc107;color:#000;border-radius:20px;padding:6px 14px;font-size:13px;font-weight:bold;box-shadow:0 2px 8px rgba(0,0,0,0.2);cursor:pointer;display:flex;align-items:center;gap:6px';
        b.addEventListener('click', processQueue);
        document.body.appendChild(b);
      }
      b.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a.5.5 0 0 1 .5.5v6h6a.5.5 0 0 1 0 1h-6v6a.5.5 0 0 1-1 0v-6h-6a.5.5 0 0 1 0-1h6v-6A.5.5 0 0 1 8 1z"/></svg> ' + n + ' عملية معلقة';
    } else {
      if (b) { b.remove(); }
    }
  }

  async function queuePost(url, data) {
    const q = getQueue();
    q.push({ url, data, time: Date.now() });
    saveQueue(q);
  }

  function fetchWithTimeout(url, opts, ms) {
    const ctrl = new AbortController();
    const id = setTimeout(function () { ctrl.abort(); }, ms || 8000);
    opts.signal = ctrl.signal;
    return fetch(url, opts).then(function (r) { clearTimeout(id); return r; }, function (e) { clearTimeout(id); throw e; });
  }

  async function processQueue() {
    const q = getQueue();
    if (!q.length) return;
    var badge = document.getElementById('offlineBadge');
    if (badge) badge.style.opacity = '0.5';
    var remaining = [];
    for (var i = 0; i < q.length; i++) {
      var item = q[i];
      try {
        var fd = new FormData();
        var keys = Object.keys(item.data);
        for (var j = 0; j < keys.length; j++) {
          fd.append(keys[j], item.data[keys[j]]);
        }
        var res = await fetchWithTimeout(item.url, { method: 'POST', body: fd }, 10000);
        if (!res.ok) { remaining.push(item); }
      } catch (e) {
        remaining.push(item);
      }
    }
    saveQueue(remaining);
    if (badge) badge.style.opacity = '1';
    if (!remaining.length) { window.location.reload(); }
  }

  async function trySubmit(form, submitter) {
    var fd = new FormData(form);
    if (submitter && submitter.name) { fd.append(submitter.name, submitter.value || ''); }
    var url = form.action || window.location.href;
    var data = {};
    fd.forEach(function (v, k) { data[k] = v; });

    try {
      var res = await fetchWithTimeout(url, { method: 'POST', body: fd }, 10000);
      if (res.ok) {
        var ct = res.headers.get('content-type') || '';
        if (ct.indexOf('application/json') !== -1) {
          var json = await res.json();
          if (json.success) { window.location.reload(); return; }
        }
        var html = await res.text();
        if (html.indexOf('flash') !== -1 || html.indexOf('alert') !== -1 || html.indexOf('تم') !== -1) {
          document.open(); document.write(html); document.close(); return;
        }
        window.location.href = res.url || url;
      } else {
        throw new Error('HTTP ' + res.status);
      }
    } catch (e) {
      await queuePost(url, data);
      var msg = document.createElement('div');
      msg.style.cssText = 'position:fixed;top:10px;left:50%;transform:translateX(-50%);z-index:99999;background:#17a2b8;color:#fff;padding:12px 24px;border-radius:8px;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.2)';
      msg.textContent = 'تم حفظ العملية وستُرفع تلقائياً عند عودة الاتصال';
      document.body.appendChild(msg);
      setTimeout(function () { msg.remove(); }, 4000);
    }
  }

  function getSubmitter(e) {
    if (e.submitter) return e.submitter;
    var btns = e.target.querySelectorAll('button[type=submit], input[type=submit]');
    for (var i = 0; i < btns.length; i++) {
      if (btns[i].matches(':focus') || btns[i] === document.activeElement) return btns[i];
    }
    return null;
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.method && form.method.toLowerCase() !== 'post') return;
    if (form.hasAttribute('data-noqueue')) return;
    e.preventDefault();
    trySubmit(form, getSubmitter(e));
  });

  window.addEventListener('online', function () {
    var w = document.getElementById('offlineWarning');
    if (w) w.remove();
    var q = getQueue();
    if (q.length) processQueue();
  });

  window.addEventListener('offline', function () {
    if (!document.getElementById('offlineWarning')) {
      var msg = document.createElement('div');
      msg.id = 'offlineWarning';
      msg.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:99998;background:#dc3545;color:#fff;text-align:center;padding:8px;font-size:14px;font-weight:bold';
      msg.textContent = 'لا يوجد اتصال بالإنترنت - سيتم حفظ التعديلات ورفعها تلقائياً';
      document.body.prepend(msg);
    }
  });

  var q = getQueue();
  if (q.length) updateBadge(q.length);
  if (navigator.onLine && q.length) processQueue();
})();
