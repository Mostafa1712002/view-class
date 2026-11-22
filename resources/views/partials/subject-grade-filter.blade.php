{{--
    Subject → grade cascade, app-wide. Mark a dependent <select> with
    data-grade-source="<subject field name>" and it keeps only the options whose
    grade is linked to the chosen subject(s) (subjects.grade_levels).
      - subject <option>/checkbox: data-grades="1,2,3"
      - dependent <option>: data-grade="4" (classes), or its value is the grade (1–12 selects)
    A subject with no linked grades, or no subject chosen, shows every option.
--}}
<script>
(function () {
    function gradesOf(el) { return (el.getAttribute('data-grades') || '').split(',').filter(Boolean); }

    document.querySelectorAll('select[data-grade-source]').forEach(function (dep) {
        var scope = dep.form || document;
        var sources = scope.querySelectorAll('[name="' + dep.getAttribute('data-grade-source') + '"]');
        if (!sources.length) return;
        var all = Array.prototype.slice.call(dep.options);

        function allowedGrades() {
            var allowed = [], any = false, open = false;
            sources.forEach(function (s) {
                var picked = s.tagName === 'SELECT'
                    ? Array.prototype.filter.call(s.options, function (o) { return o.selected && o.value; })
                    : (s.checked ? [s] : []);
                picked.forEach(function (o) {
                    any = true;
                    var g = gradesOf(o);
                    if (!g.length) open = true; // legacy subject with no linked grades → no filtering
                    allowed = allowed.concat(g);
                });
            });
            return (any && !open) ? allowed : null;
        }

        function apply(initial) {
            var allowed = allowedGrades(), current = dep.value;
            var keep = all.filter(function (o) {
                if (!allowed || !o.value) return true;
                if (initial && o.value === current) return true; // never drop a saved/filtered value on load
                return allowed.indexOf(o.getAttribute('data-grade') || o.value) !== -1;
            });
            // ponytail: legacy classes whose grade_level doesn't match the subject's grades would leave an
            // empty list — show everything instead of blocking the form. Fix the data, not this fallback.
            if (!keep.some(function (o) { return o.value; })) keep = all;
            dep.replaceChildren.apply(dep, keep);
            dep.value = keep.some(function (o) { return o.value === current; }) ? current : '';
            if (dep.value !== current) dep.dispatchEvent(new Event('change', { bubbles: true }));
        }

        sources.forEach(function (s) { s.addEventListener('change', function () { apply(false); }); });
        dep.addEventListener('focus', function () { apply(true); }); // catches "select all" links that skip change events
        apply(true);
    });
})();
</script>
