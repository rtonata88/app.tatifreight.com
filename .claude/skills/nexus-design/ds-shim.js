/* ds-shim.js — development fallback for the generated component bundle.
 *
 * When this project is compiled as a design system, `_ds_bundle.js` publishes the
 * components on a global namespace and this file does nothing. Opened directly from
 * disk (no bundle yet), it loads every component source, strips its ESM wrapper and
 * publishes the same API on `window.NexusUI`, so the cards and UI kits render as-is.
 *
 * Load order: react, react-dom, @babel/standalone, _ds_bundle.js, ds-shim.js.
 * Set `window.__nxBase` to the relative path of the project root before loading.
 */
(function () {
  var FILES = [
    'components/core/Button.jsx',
    'components/core/IconButton.jsx',
    'components/core/StatusPill.jsx',
    'components/core/Badge.jsx',
    'components/core/Card.jsx',
    'components/core/Avatar.jsx',
    'components/forms/FormField.jsx',
    'components/forms/Input.jsx',
    'components/forms/Select.jsx',
    'components/forms/Textarea.jsx',
    'components/forms/Checkbox.jsx',
    'components/forms/Switch.jsx',
    'components/forms/RadioPills.jsx',
    'components/data/MetricCard.jsx',
    'components/data/StatCard.jsx',
    'components/data/DataTable.jsx',
    'components/data/DataGrid.jsx',
    'components/data/DossierRow.jsx',
    'components/data/ProgressMeter.jsx',
    'components/data/EmptyState.jsx',
    'components/data/Pagination.jsx',
    'components/data/SettingsRow.jsx',
    'components/navigation/Sidebar.jsx',
    'components/navigation/Topbar.jsx',
    'components/navigation/PageHeader.jsx',
    'components/navigation/SectionHeader.jsx',
    'components/navigation/Tabs.jsx',
    'components/navigation/Breadcrumb.jsx',
    'components/navigation/SettingsNav.jsx',
    'components/feedback/Alert.jsx',
    'components/feedback/Modal.jsx',
    'components/feedback/Toast.jsx',
    'components/feedback/ActivityList.jsx'
  ];

  /* Safe namespace resolver — used by every card and UI kit. Finds the compiled
     bundle's namespace if present, otherwise the shim's own. */
  window.__nxNS = function () {
    var keys = Object.keys(window);
    for (var i = 0; i < keys.length; i++) {
      try {
        var v = window[keys[i]];
        if (v && typeof v === 'object' && v.Button && v.StatusPill && v.DataTable) return v;
      } catch (e) { /* guarded: some window props throw on access */ }
    }
    return {};
  };

  /* The generated bundle, when it exists. Loaded here rather than with a <script src>
     so that an uncompiled project has no broken reference. */
  (function () {
    if (window.__nxBundleTried) return;
    window.__nxBundleTried = true;
    try {
      var b = new XMLHttpRequest();
      b.open('GET', (window.__nxBase || '') + '_ds_bundle.js', false);
      b.send();
      if (b.status === 200 && b.responseText) new Function(b.responseText)();
    } catch (e) { /* not compiled yet — the fallback below covers it */ }
  })();

  function existing() {
    var ns = window.__nxNS();
    return ns && ns.Button ? ns : null;
  }
  if (existing()) return;

  if (typeof Babel === 'undefined' || typeof React === 'undefined') {
    console.warn('[ds-shim] React and @babel/standalone must load first.');
    return;
  }

  var base = window.__nxBase || '';
  var NS = {};

  FILES.forEach(function (path) {
    var src;
    try {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', base + path, false);
      xhr.send();
      if (xhr.status && xhr.status >= 400) throw new Error('HTTP ' + xhr.status);
      src = xhr.responseText;
    } catch (e) {
      console.warn('[ds-shim] could not load ' + path, e);
      return;
    }

    var names = [];
    src.replace(/export\s+function\s+([A-Za-z0-9_$]+)/g, function (_, n) { names.push(n); return _; });
    if (!names.length) return;

    var stripped = src
      .replace(/^\s*import[\s\S]*?;\s*$/gm, '')
      .replace(/export\s+function/g, 'function');

    try {
      var code = Babel.transform(stripped, { presets: [['react', { runtime: 'classic' }]] }).code;
      var factory = new Function('React', code + '\nreturn {' + names.join(',') + '};');
      var mod = factory(React);
      Object.keys(mod).forEach(function (n) { NS[n] = mod[n]; });
    } catch (e) {
      console.warn('[ds-shim] could not evaluate ' + path, e);
    }
  });

  window.NexusUI = NS;

  /* Optional: UI-kit screens. Set window.__nxScreens to an array of paths before
     loading this file; they are published on window.NexusKit. */
  if (Array.isArray(window.__nxScreens)) {
    var KIT = {};
    window.NexusKit = KIT;
    window.__nxScreens.forEach(function (path) {
      var src;
      try {
        var x = new XMLHttpRequest();
        x.open('GET', base + path, false);
        x.send();
        if (x.status && x.status >= 400) throw new Error('HTTP ' + x.status);
        src = x.responseText;
      } catch (e) {
        console.warn('[ds-shim] could not load ' + path, e);
        return;
      }
      var names = [];
      src.replace(/export\s+function\s+([A-Za-z0-9_$]+)/g, function (_, n) { names.push(n); return _; });
      if (!names.length) return;
      var stripped = src
        .replace(/^\s*import[\s\S]*?;\s*$/gm, '')
        .replace(/export\s+(function|const|let|var|class)/g, '$1');
      try {
        var code = Babel.transform(stripped, { presets: [['react', { runtime: 'classic' }]] }).code;
        var factory = new Function('React', code + '\nreturn {' + names.join(',') + '};');
        var mod = factory(React);
        Object.keys(mod).forEach(function (n) { KIT[n] = mod[n]; });
      } catch (e) {
        console.warn('[ds-shim] could not evaluate ' + path, e);
      }
    });
  }
})();
