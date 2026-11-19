/* Student exam player: server-synced timer, autosave, anti-cheat exit logging. Vanilla JS. */
(function () {
    var root = document.getElementById('sp-player');
    if (!root) return;
    var d = root.dataset, msg = JSON.parse(d.msg), csrf = d.csrf;
    var screens = [].slice.call(root.querySelectorAll('.sp-screen'));
    var dots = [].slice.call(root.querySelectorAll('.sp-dot'));
    var timerEl = document.getElementById('sp-timer'), statusEl = document.getElementById('sp-status');
    var cur = 0, ending = false, deadline = Date.now() + parseInt(d.remaining, 10) * 1000, warned5 = false, warned1 = false;

    function toast(text, icon) {
        if (window.Swal) { Swal.fire({ toast: true, position: 'top', icon: icon || 'warning', title: text, showConfirmButton: false, timer: 4000 }); }
        else { statusEl.textContent = text; }
    }
    function post(url, body) {
        return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(body || {}) });
    }

    function show(i) {
        cur = Math.max(0, Math.min(screens.length - 1, i));
        screens.forEach(function (s, k) { s.classList.toggle('active', k === cur); });
        dots.forEach(function (b, k) { b.classList.toggle('current', k === cur); });
        document.getElementById('sp-prev').disabled = cur === 0;
        document.getElementById('sp-next').disabled = cur === screens.length - 1;
        window.scrollTo({ top: 0 });
    }
    function refreshDots() {
        screens.forEach(function (s, k) {
            var qs = s.querySelectorAll('[data-item]'), all = true;
            [].forEach.call(qs, function (q) { if (!q.querySelector('input:checked')) all = false; });
            dots[k].classList.toggle('answered', all && qs.length > 0);
        });
    }

    function tick() {
        var left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
        var m = Math.floor(left / 60), s = left % 60;
        timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        timerEl.className = 'sp-timer' + (left <= 60 ? ' danger' : left <= 300 ? ' warn' : '');
        if (left <= 300 && left > 60 && !warned5) { warned5 = true; toast(msg.warn5); }
        if (left <= 60 && left > 0 && !warned1) { warned1 = true; toast(msg.warn1); }
        if (left === 0 && !ending) { finish(true); }
    }

    function finish(auto) {
        if (ending) return;
        ending = true;
        if (auto) toast(msg.time_up, 'info');
        post(d.finishUrl, {}).then(function (r) { return r.json(); })
            .then(function (j) { window.location = j.result_url || d.resultUrl; })
            .catch(function () { window.location = d.resultUrl; });
    }

    function confirmFinish() {
        var open = dots.filter(function (b) { return !b.classList.contains('answered'); }).length;
        var text = msg.confirm_text + (open ? ' ' + msg.unanswered.replace(':n', open) : '');
        if (window.Swal) {
            Swal.fire({ title: msg.confirm_title, text: text, icon: 'question', showCancelButton: true, confirmButtonText: msg.yes, cancelButtonText: msg.cancel })
                .then(function (r) { if (r.isConfirmed) finish(false); });
        } else if (window.confirm(msg.confirm_title + '\n' + text)) { finish(false); }
    }

    root.addEventListener('change', function (e) {
        var inp = e.target;
        if (inp.type !== 'radio') return;
        refreshDots();
        statusEl.textContent = '';
        post(d.answerUrl, { item_id: parseInt(inp.dataset.item, 10), option: parseInt(inp.value, 10) })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (x) {
                if (x.ok) { deadline = Date.now() + x.j.remaining_seconds * 1000; return; }
                if (x.j.reason === 'late' || x.j.reason === 'closed') { ending = true; toast(x.j.message); setTimeout(function () { window.location = x.j.result_url; }, 1200); }
                else { statusEl.textContent = x.j.message || msg.save_failed; }
            })
            .catch(function () { statusEl.textContent = msg.save_failed; });
    });

    dots.forEach(function (b) { b.addEventListener('click', function () { show(parseInt(b.dataset.go, 10)); }); });
    document.getElementById('sp-prev').addEventListener('click', function () { show(cur - 1); });
    document.getElementById('sp-next').addEventListener('click', function () { show(cur + 1); });
    document.getElementById('sp-finish').addEventListener('click', confirmFinish);

    // Zoomable images
    var zoom = document.getElementById('sp-zoom');
    root.addEventListener('click', function (e) {
        if (e.target.classList.contains('sp-zoomable')) { zoom.querySelector('img').src = e.target.src; zoom.classList.add('on'); }
    });
    zoom.addEventListener('click', function () { zoom.classList.remove('on'); });

    // Server re-sync every 30 s (also finalizes an overdue attempt on the server).
    setInterval(function () {
        if (ending) return;
        fetch(d.heartbeatUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j.status !== 'in_progress') { ending = true; window.location = j.result_url; return; }
                deadline = Date.now() + j.remaining_seconds * 1000;
            }).catch(function () {});
    }, 30000);

    // Anti-cheat: only when the exam sets a maximum number of exits.
    window.addEventListener('beforeunload', function (e) { if (!ending) { e.preventDefault(); e.returnValue = msg.exit_warn; } });
    if (d.antiCheat === '1') {
        var lastExit = 0;
        var logExit = function () {
            if (ending || Date.now() - lastExit < 1500) return;
            lastExit = Date.now();
            var fd = new FormData(); fd.append('_token', csrf);
            if (navigator.sendBeacon) { navigator.sendBeacon(d.exitUrl, fd); }
            else { fetch(d.exitUrl, { method: 'POST', body: fd, credentials: 'same-origin', keepalive: true }); }
        };
        document.addEventListener('visibilitychange', function () { if (document.hidden) logExit(); });
        window.addEventListener('blur', logExit);
        // The exit endpoint may auto-end the attempt: notice it on the next heartbeat / when back in the tab.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden || ending) return;
            fetch(d.heartbeatUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (j) { if (j.status !== 'in_progress') { ending = true; window.location = j.result_url; } }).catch(function () {});
        });
    }

    show(0); refreshDots(); tick(); setInterval(tick, 1000);
})();
