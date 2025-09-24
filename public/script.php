<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
use App\Config\Config;
Config::load(dirname(__DIR__));
$logoDefault = Config::string('LOGO_URL', '/logo.png');
$slug = '';
if (preg_match('#/scripts?/(.+)$#', $_SERVER['REQUEST_URI'] ?? '', $m)) { $slug = trim($m[1]); }
if ($slug === '' && isset($_GET['slug'])) { $slug = (string)$_GET['slug']; }
if (strpos($slug, '?') !== false) { $slug = explode('?', $slug, 2)[0]; }
$slug = rawurldecode(trim($slug));
// Best-effort fetch script meta for header settings
$header = ['logo' => $logoDefault, 'align' => 'left'];
try {
  if ($slug !== '') {
    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $api = $scheme . '://' . $host . '/api/index.php?route=' . rawurlencode('/scripts/slug/' . $slug);
    $ctx = stream_context_create(['http'=>['ignore_errors'=>true, 'method'=>'GET', 'timeout'=>1.5]]);
    $raw = @file_get_contents($api, false, $ctx);
    if ($raw) {
      $json = json_decode($raw, true);
      if (is_array($json)) {
        if (!empty($json['header_logo_url'])) $header['logo'] = (string)$json['header_logo_url'];
        if (!empty($json['header_align']) && in_array($json['header_align'], ['left','center'], true)) $header['align'] = $json['header_align'];
      }
    }
  }
} catch (\Throwable $e) { /* ignore */ }

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Script</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="color-scheme" content="light dark">
  <style>
    .field-error { color: #b91c1c; }
    html, body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
    :root { --brand-blue: #0b5aa6; --brand-green: #6bb31a; --brand-blue-600: #0a4e93; --brand-green-600: #5aa214; }
    .brand-btn { background-image: linear-gradient(90deg, var(--brand-blue), var(--brand-green)); }
    .brand-btn:hover { background-image: linear-gradient(90deg, var(--brand-blue-600), var(--brand-green-600)); }
    .brand-text { background-image: linear-gradient(90deg, var(--brand-blue), var(--brand-green)); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .focus-brand:focus { border-color: var(--brand-blue); outline: none; box-shadow: 0 0 0 3px rgba(11, 90, 166, 0.25); }
    .accent-brand { accent-color: var(--brand-blue); }
    .brand-blue { color: var(--brand-blue); }
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-[#f0f6ff] via-white to-[#f3fbec] text-slate-900">
  <div class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8">
    <header class="mb-6">
      <div class="flex flex-col gap-2">
        <img src="<?=htmlspecialchars($header['logo'], ENT_QUOTES)?>" alt="logo" class="<?= $header['align']==='center' ? 'self-center' : '' ?>" style="width:102.5px;height:auto"/>
        <p id="script-title" class="brand-blue text-base sm:text-lg font-semibold" style="text-align: <?= $header['align']==='center' ? 'center' : 'left' ?>;">Script</p>
      </div>
    </header>
    <form id="script-form" method="post" novalidate autocomplete="on">
      <div id="script-content" class="grid grid-cols-1 gap-6"></div>
    </form>
    
  </div>

  <script>
    const params = new URLSearchParams(location.search);
    let slug = params.get('slug') || '';
    if (!slug) {
      const m = location.pathname.match(/\/scripts?\/([^\/]+)\/?$/);
      if (m && m[1]) slug = decodeURIComponent(m[1]);
    }
    if (!slug) {
      console.error('Missing script slug in URL');
    }
    let token = sessionStorage.getItem('token') || '';
    // Debug helpers
    const __dbg = (...a)=>{ try { console.log('[script]', ...a); } catch(_){} };
    __dbg('init', { slug, tokenPresent: !!token, url: location.href });
    function api(path){ return '/api/index.php?route=' + encodeURIComponent(path); }
    async function fetchJSON(url, opts){
      const headers = { 'Content-Type':'application/json' };
      if (token) headers['Authorization'] = 'Bearer ' + token;
      __dbg('fetchJSON ->', url, { method: (opts&&opts.method)||'GET' });
      const r = await fetch(url, Object.assign({ headers }, opts||{}));
      if (!r.ok) { __dbg('fetchJSON !ok', url, r.status); throw new Error('Request failed: ' + r.status); }
      const j = await r.json();
      __dbg('fetchJSON ok', url, r.status, { keys: (j && typeof j==='object') ? Object.keys(j) : typeof j });
      return j;
    }

    function normalizePhone(p){ return String(p||'').replace(/[^0-9\+]/g,''); }
    function normalizeChoiceValue(s){
      return String(s||'')
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g,'')
        .replace(/[^a-z0-9]+/g,'')
        .trim();
    }

    function autoGrowTextarea(el){
      if (!el) return;
      try {
        el.style.overflowY = 'hidden';
        el.style.height = 'auto';
        el.style.height = (el.scrollHeight + 2) + 'px';
      } catch(_){ }
      // Run one more time on next tick to ensure radios selected after any late DOM updates
      setTimeout(()=>{
        try {
          const container = document.getElementById('script-content');
          const last = __lastAnswers || {};
          const yesNo = (k)=>{
            const val = prefill[k] ?? last[k]; if (val==null||String(val)=='') return;
            const radios = Array.from(container.querySelectorAll(`input[type="radio"][name="${k}"]`));
            if (!radios.length) return;
            const nval = normalizeChoiceValue(val); let matched=false;
            radios.forEach(r=>{ if (String(r.value)===String(val)) { r.checked=true; matched=true; try{r.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } });
            if (!matched) { radios.forEach(r=>{ if (normalizeChoiceValue(r.value)===nval) { r.checked=true; matched=true; try{r.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } }); }
            if (!matched) { if (nval==='yes') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='yes'); if (t) { t.checked=true; try{t.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } } else if (nval==='no') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='no'); if (t) { t.checked=true; try{t.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } } }
          };
          yesNo('ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash');
          yesNo('is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service');
          // Generic
          Object.keys(prefill||{}).forEach(k=> yesNo(k));
        } catch(_){ }
      }, 0);
    }

    function evalCond(cond, answers){
      const left = answers[cond.key];
      const op = cond.op || 'equals';
      const val = cond.value ?? '';
      if (op==='is_filled') return left !== undefined && left !== '' && !(Array.isArray(left)&&left.length===0);
      if (op==='is_empty') return left === undefined || left === '' || (Array.isArray(left)&&left.length===0);
      if (op==='equals') return (left ?? '') == val;
      if (op==='not_equals') return (left ?? '') != val;
      const sLeft = Array.isArray(left) ? left.join(',') : String(left ?? '');
      if (op==='contains') return sLeft.includes(val);
      if (op==='not_contains') return !sLeft.includes(val);
      const nLeft = Number(left);
      const nVal = Number(val);
      if (!Number.isFinite(nLeft) || !Number.isFinite(nVal)) return false;
      if (op==='gt') return nLeft > nVal;
      if (op==='lt') return nLeft < nVal;
      if (op==='gte') return nLeft >= nVal;
      if (op==='lte') return nLeft <= nVal;
      return false;
    }
    function shouldShowQuestion(q, answers){
      const basic = (!q.show_if_key) || ((answers[q.show_if_key] ?? '') == (q.show_if_value ?? ''));
      const conds = Array.isArray(q.conditions) ? q.conditions : [];
      if (!conds.length) return basic;
      const logic = (q.condition_logic||'AND').toUpperCase();
      const results = conds.map(c=>evalCond(c, answers));
      const complex = logic==='OR' ? results.some(Boolean) : results.every(Boolean);
      return basic && complex;
    }
    function shouldShowSection(sec, answers){
      const basic = (!sec.show_if_key) || ((answers[sec.show_if_key] ?? '') == (sec.show_if_value ?? ''));
      const conds = Array.isArray(sec.conditions) ? sec.conditions : [];
      if (!conds.length) return basic;
      const logic = (sec.condition_logic||'AND').toUpperCase();
      const results = conds.map(c=>evalCond(c, answers));
      const complex = logic==='OR' ? results.some(Boolean) : results.every(Boolean);
      return basic && complex;
    }
    function toFieldName(q){
      const label = (typeof q.label === 'string') ? q.label.trim() : '';
      if (label) return label.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
      return String(q.key||'q').toLowerCase().replace(/[^a-z0-9]+/g,'_');
    }
    function renderQuestionRow(secKey, q, answers){
      const row = document.createElement('div');
      row.className = 'mb-2';
      row.setAttribute('data-q-key', q.key);
      const visible = shouldShowQuestion(q, answers);
      row.style.display = visible ? '' : 'none';
      const hasLabel = typeof q.label === 'string' && q.label.trim() !== '';
      row.innerHTML = '';
      if (hasLabel) {
        const lbl = document.createElement('label');
        lbl.className = 'block text-sm font-semibold text-slate-700';
        lbl.textContent = q.label + (q.required ? ' ' : '');
        if (q.required) {
          const star = document.createElement('span');
          star.className = 'text-rose-600';
          star.textContent = '*';
          lbl.appendChild(star);
        }
        row.appendChild(lbl);
      }
      function normalizeOptionsList(input){
        if (Array.isArray(input)) {
          return input.map(it=>{
            if (it == null) return null;
            if (typeof it === 'string') { const s = it.trim(); return s ? { value: s, label: s } : null; }
            if (typeof it === 'object') {
              const lbl = String(it.label ?? it.text ?? it.name ?? it.value ?? '').trim();
              const val = String(it.value ?? lbl).trim();
              if (!lbl && !val) return null;
              return { value: val || lbl, label: lbl || val };
            }
            const s = String(it).trim();
            return s ? { value: s, label: s } : null;
          }).filter(Boolean);
        }
        if (typeof input === 'string') {
          return String(input).split('\n').map(s=>s.trim()).filter(Boolean).map(s=>({ value: s, label: s }));
        }
        return [];
      }
      function getOptionsSource(question){
        // Prefer explicit options; if empty, fall back to help as newline list
        if (Array.isArray(question.options) && question.options.length) return question.options;
        if (typeof question.options === 'string' && question.options.trim() !== '') return question.options;
        if (typeof question.help === 'string' && question.help.trim() !== '') return question.help;
        return [];
      }
      let control = null;
      const name = q.key; // Ensure public field names match script admin keys exactly
      if (q.type==='text') control = document.createElement('input');
      else if (q.type==='textarea') { control = document.createElement('textarea'); control.addEventListener('input', ()=>autoGrowTextarea(control)); }
      else if (q.type==='number') { control = document.createElement('input'); control.type='number'; }
      else if (q.type==='select') {
        control = document.createElement('select');
        // Placeholder option
        const ph = document.createElement('option'); ph.value=''; ph.textContent='Select one'; ph.disabled = true; ph.selected = true; control.appendChild(ph);
        const opts = normalizeOptionsList(getOptionsSource(q));
        opts.forEach(o=>{ const opt=document.createElement('option'); opt.value=o.value; opt.textContent=o.label; control.appendChild(opt); });
      }
      else if (q.type==='radio') {
        control = document.createElement('div');
        const opts = normalizeOptionsList(getOptionsSource(q));
        opts.forEach(o=>{ const lbl=document.createElement('label'); lbl.className='inline-flex items-center gap-2 mr-3'; const inp=document.createElement('input'); inp.type='radio'; inp.name=name; inp.value=o.value; lbl.appendChild(inp); lbl.appendChild(document.createTextNode(' '+o.label)); control.appendChild(lbl); });
      }
      else if (q.type==='checkbox') {
        control = document.createElement('div');
        const opts = normalizeOptionsList(getOptionsSource(q));
        opts.forEach(o=>{ const lbl=document.createElement('label'); lbl.className='inline-flex items-center gap-2 mr-3'; const inp=document.createElement('input'); inp.type='checkbox'; inp.value=o.value; lbl.appendChild(inp); lbl.appendChild(document.createTextNode(' '+o.label)); control.appendChild(lbl); });
      }
      else if (q.type==='yesno') {
        control = document.createElement('div');
        ['Yes','No'].forEach(o=>{ const lbl=document.createElement('label'); lbl.className='inline-flex items-center gap-2 mr-3'; const inp=document.createElement('input'); inp.type='radio'; inp.name=name; inp.value=o; lbl.appendChild(inp); lbl.appendChild(document.createTextNode(' '+o)); control.appendChild(lbl); });
      }
      else if (q.type==='date') { control = document.createElement('input'); control.type='date'; }
      else if (q.type==='state') {
        control = document.createElement('select');
        const ph = document.createElement('option'); ph.value=''; ph.textContent='Select state'; ph.disabled = true; ph.selected = true; control.appendChild(ph);
        const states = ['AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','IA','ID','IL','IN','KS','KY','LA','MA','MD','ME','MI','MN','MO','MS','MT','NC','ND','NE','NH','NJ','NM','NV','NY','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VA','VT','WA','WI','WV','WY','DC'];
        states.forEach(o=>{ const opt=document.createElement('option'); opt.value=o; opt.textContent=o; control.appendChild(opt); });
      }
      else if (q.type==='month') {
        control = document.createElement('select');
        const ph = document.createElement('option'); ph.value=''; ph.textContent='Select month'; ph.disabled = true; ph.selected = true; control.appendChild(ph);
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        months.forEach(o=>{ const opt=document.createElement('option'); opt.value=o; opt.textContent=o; control.appendChild(opt); });
      }
      else if (q.type==='day') {
        control = document.createElement('select');
        const ph = document.createElement('option'); ph.value=''; ph.textContent='Select day'; ph.disabled = true; ph.selected = true; control.appendChild(ph);
        for (let d=1; d<=31; d++){ const opt=document.createElement('option'); opt.value=String(d); opt.textContent=String(d); control.appendChild(opt); }
      }
      else if (q.type==='year') {
        control = document.createElement('select');
        const ph = document.createElement('option'); ph.value=''; ph.textContent='Select year'; ph.disabled = true; ph.selected = true; control.appendChild(ph);
        const start = new Date().getFullYear();
        for (let y=0; y<10; y++){ const opt=document.createElement('option'); const yy = String(start + y); opt.value=yy; opt.textContent=yy; control.appendChild(opt); }
      }
      else if (q.type==='info') {
        const info=document.createElement('div'); info.className='p-4 rounded-xl border border-sky-200 bg-sky-50 text-sky-900 text-sm leading-6';
        info.setAttribute('data-info-block','');
        info.setAttribute('data-info-template', q.help || '');
        info.innerHTML = renderInfoHTMLFromAnswers(q.help||'', answers);
        control=info;
      }
      if (!control) control = document.createElement('input');
      // Apply CRM field styling to tangible controls (not wrappers)
      if (control.tagName !== 'DIV') {
        control.classList?.add('mt-1','w-full','rounded-lg','border-slate-300','bg-slate-50','px-4','py-3','text-base','focus-brand');
        try { control.setAttribute('autocomplete','on'); } catch(_){}
      }
      control.setAttribute('data-control','');
      control.setAttribute('name', toFieldName(q));
      // Assign a stable form field name
      control.setAttribute('name', q.key);
      // Prefill initial value when provided
      const initial = (answers && Object.prototype.hasOwnProperty.call(answers, q.key)) ? answers[q.key] : undefined;
      if (initial !== undefined) {
        if (control.tagName==='DIV') {
          // radio / checkbox groups
          const radios = Array.from(control.querySelectorAll('input[type="radio"]'));
          if (radios.length) {
            let matched = false;
            // exact match first
            radios.forEach(inp=>{ const eq = (String(inp.value) == String(initial)); if (eq) { inp.checked = true; try{inp.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} matched = true; } });
            if (!matched) {
              const normInit = normalizeChoiceValue(initial);
              radios.forEach(inp=>{ if (normalizeChoiceValue(inp.value) === normInit) { inp.checked = true; try{inp.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} matched = true; } });
            }
            if (!matched) {
              const normInit = normalizeChoiceValue(initial);
              const yesSet = new Set(['yes','y','true','1']);
              const noSet = new Set(['no','n','false','0']);
              if (yesSet.has(normInit)) {
                const target = radios.find(r=>normalizeChoiceValue(r.value)==='yes'); if (target) { target.checked = true; try{target.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} matched = true; }
              } else if (noSet.has(normInit)) {
                const target = radios.find(r=>normalizeChoiceValue(r.value)==='no'); if (target) { target.checked = true; try{target.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} matched = true; }
              }
            }
          }
          const cbs = Array.from(control.querySelectorAll('input[type="checkbox"]'));
          if (cbs.length) {
            const vals = Array.isArray(initial) ? initial.map(String) : String(initial).split(',').map(s=>s.trim()).filter(Boolean);
            const normSet = new Set(vals.map(v=>normalizeChoiceValue(v)));
            cbs.forEach(cb=>{ const hit = normSet.has(normalizeChoiceValue(cb.value)) || vals.includes(cb.value); cb.checked = hit; });
          }
        } else if (control.tagName==='SELECT') {
          const init = String(initial);
          control.value = init;
          if (control.value !== init) {
            const normInit = normalizeChoiceValue(init);
            let foundVal = '';
            Array.from(control.options).forEach(opt=>{
              if (foundVal) return;
              if (normalizeChoiceValue(opt.value) === normInit || normalizeChoiceValue(opt.textContent) === normInit) foundVal = opt.value;
            });
            if (foundVal) {
              control.value = foundVal;
            } else {
              // Keep placeholder; do not inject unknown option to avoid wrong extra value
            }
          }
        } else {
          control.value = String(initial);
          if (control.tagName==='TEXTAREA') { autoGrowTextarea(control); }
        }
      }
      // Ensure empty textareas also auto-size once mounted
      if (control && control.tagName==='TEXTAREA') { setTimeout(()=>autoGrowTextarea(control)); }
      control.addEventListener('input', ()=>{ collectAnswersAndRefresh(secKey); });
      row.appendChild(control);
      // Render helper/content text below control for all types except when help is used as options fallback
      try {
        const helpRaw = q.help;
        const helpStr = (typeof helpRaw === 'string') ? helpRaw.trim() : '';
        let helpUsedAsOptions = false;
        if (helpStr && (q.type==='select' || q.type==='radio' || q.type==='checkbox')){
          const hasExplicitOptions = (Array.isArray(q.options) && q.options.length) || (typeof q.options === 'string' && q.options.trim() !== '');
          if (!hasExplicitOptions) helpUsedAsOptions = true;
        }
        // Do not duplicate for info-type; its control already renders the help content.
        if (helpStr && !helpUsedAsOptions && q.type !== 'info') {
          const help = document.createElement('div');
          help.className = 'mt-1 text-xs text-slate-600';
          help.setAttribute('data-info-block','');
          help.setAttribute('data-info-template', helpStr);
          help.innerHTML = renderInfoHTMLFromAnswers(helpStr, answers);
          row.appendChild(help);
        }
      } catch(_) { }
      return row;
    }
    function renderInfoFromAnswers(template, answers){
      let text = String(template || '');
      const get = (k)=>{
        if (k in answers) return answers[k];
        const alt = k.replace(/\s+/g, '_'); if (alt in answers) return answers[alt];
        const alt2 = k.replace(/_/g, ' '); if (alt2 in answers) return answers[alt2];
        return '';
      };
      const combineName = (firstKey, lastKey, fallbackKey)=>{
        const f = String(get(firstKey)||'').trim();
        const l = String(get(lastKey)||'').trim();
        const combined = [f,l].filter(Boolean).join(' ');
        if (combined) return combined;
        const fb = String(get(fallbackKey)||'').trim();
        return fb;
      };
      const customerFullName = combineName('customer_first_name','customer_last_name','customer_name');
      const agentFullName = combineName('agent_first_name','agent_last_name','agent_name');
      const map = {
        '<customer name>': customerFullName,
        '<customer_name>': customerFullName,
        '<customer_first_name>': get('customer_first_name'),
        '<customer_last_name>': get('customer_last_name'),
        '<agent name>': agentFullName,
        '<agent_name>': agentFullName,
        '<agent_full_name>': agentFullName,
        '<agent_first_name>': get('agent_first_name'),
        '<agent_last_name>': get('agent_last_name'),
        '<customer_phone>': get('customer_phone'),
        '<property_city>': get('property_city'),
        '<property_state>': get('property_state'),
        '<bedrooms_label>': (function(){ const b = get('bedrooms'); return b ? (b + ' Bedroom') : ''; })(),
        '<home_type_label>': (function(){ const t = get('home_type'); if (!t) return ''; return (/Home\s*$/i.test(t) ? t : (t + ' Home')); })(),
      };
      Object.keys(map).forEach(ph=>{ text = text.split(ph).join(map[ph]); });
      // Generic <q:key> syntax to pull any question by key
      text = text.replace(/<q:([a-zA-Z0-9_\-]+)>/g, (m, key)=> {
        let val = String(get(key));
        if (key === 'bedrooms' && val) return val + ' Bedroom';
        if (key === 'how_many_bedrooms_are_in_the_house' && val) return val + ' Bedroom';
        if (key === 'what_type_of_house_is_this' && val && !/\bHome\b/i.test(val)) return val + ' Home';
        return val;
      });
      return text;
    }
    function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function renderInfoHTMLFromAnswers(template, answers){
      // Escape first so we can safely inject HTML wrappers later
      let html = escapeHtml(String(template||''));
      const get = (k)=>{
        if (k in answers) return answers[k];
        const alt = k.replace(/\s+/g,'_'); if (alt in answers) return answers[alt];
        const alt2 = k.replace(/_/g,' '); if (alt2 in answers) return answers[alt2];
        return '';
      };
      // Named placeholders (escaped form in template)
      const replacements = {
        '&lt;customer name&gt;': String(get('customer_name')||''),
        '&lt;customer_name&gt;': String(get('customer_name')||''),
        '&lt;customer_first_name&gt;': String(get('customer_first_name')||''),
        '&lt;customer_last_name&gt;': String(get('customer_last_name')||''),
        '&lt;customer_phone&gt;': String(get('customer_phone')||''),
        '&lt;property_city&gt;': String(get('property_city')||''),
        '&lt;property_state&gt;': String(get('property_state')||''),
        '&lt;bedrooms_label&gt;': (function(){ const b=String(get('bedrooms')||''); return b? (b+' Bedroom'):''; })(),
        '&lt;home_type_label&gt;': (function(){ const t=String(get('home_type')||''); if(!t) return ''; return (/Home\s*$/i.test(t)?t:(t+' Home')); })(),
        '&lt;agent name&gt;': (function(){ const f=String(get('agent_first_name')||''); const l=String(get('agent_last_name')||''); const n=String(get('agent_name')||''); const c=[f,l].filter(Boolean).join(' '); return (c||n); })(),
        '&lt;agent_name&gt;': (function(){ const f=String(get('agent_first_name')||''); const l=String(get('agent_last_name')||''); const n=String(get('agent_name')||''); const c=[f,l].filter(Boolean).join(' '); return (c||n); })(),
        '&lt;agent_first_name&gt;': String(get('agent_first_name')||''),
        '&lt;agent_last_name&gt;': String(get('agent_last_name')||''),
      };
      // Apply named replacements with bold by default
      Object.keys(replacements).forEach(ph => {
        const val = escapeHtml(replacements[ph]||'');
        html = html.split(ph).join(val ? ('<strong>'+val+'</strong>') : '');
      });
      // Generic <q:key> placeholders
      html = html.replace(/&lt;q:([a-zA-Z0-9_\-]+)&gt;/g, (m,key)=>{
        let val = String(get(key)||'');
        if (key==='bedrooms' && val) val = val + ' Bedroom';
        if (key==='how_many_bedrooms_are_in_the_house' && val) val = val + ' Bedroom';
        if (key==='what_type_of_house_is_this' && val && !/\bHome\b/i.test(val)) val = val + ' Home';
        return val ? ('<strong>'+escapeHtml(val)+'</strong>') : '';
      });
      // Support admin-authored **bold** markup
      html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1<\/strong>');
      // Support special tags [[b]]...[[/b]] for bolding
      html = html.replace(/\[\[b\]\]([\s\S]*?)\[\[\/b\]\]/g, '<strong>$1<\/strong>');
      // Conservative auto-bold for labels ONLY at true line start (e.g., "Dial:")
      html = html.replace(/(^|\n)\s*([A-Za-z][A-Za-z0-9 _\/-]{0,30}:)/g, (m,p1,p2)=> p1 + '<strong>' + p2 + '<\/strong>');
      // Linkify phone numbers and add copy link
      try {
        const phoneRegex = /(?![^<]*?>)(\+?[0-9][0-9\-\.\s\(\)]{6,}[0-9])/g;
        html = html.replace(phoneRegex, (m, num)=>{
          const norm = normalizePhone(num);
          if (!norm || norm.length < 7) return m;
          const safe = escapeHtml(num);
          return '<a href="tel:'+norm+'" class="underline" data-phone-link>'+safe+'<\/a>'+
                 ' <span class="ml-1 text-[11px] underline cursor-pointer" data-copy-phone="'+norm+'">Copy<\/span>';
        });
      } catch(_){ }
      // Edit link to reveal specific sections: <edit:section_key>
      html = html.replace(/&lt;edit:([a-zA-Z0-9_\-]+)&gt;/g, (m, key)=> '<a href="#" class="underline" data-edit-section="'+key+'">Edit<\/a>');
      // Preserve newlines
      html = html.replace(/\n/g, '<br>');
      return html;
    }
    function collectAnswers(container){
      const answers = {};
      container.querySelectorAll('[data-q-key]').forEach(row=>{
        const key = row.getAttribute('data-q-key');
        const ctrl = row.querySelector('[data-control]') || row.querySelector('input,textarea,select');
        if (!ctrl) return;
        if (ctrl.tagName==='DIV') {
          const checkedRadio = row.querySelector('input[type="radio"]:checked');
          if (checkedRadio) answers[key] = checkedRadio.value;
          const checkedCbs = Array.from(row.querySelectorAll('input[type="checkbox"]:checked')).map(x=>x.value);
          if (checkedCbs.length) answers[key] = checkedCbs;
        } else if (ctrl.tagName==='SELECT') {
          answers[key] = ctrl.value || '';
        } else {
          answers[key] = ctrl.value || '';
        }
      });
      // Also capture named inputs that may exist on the page (compat with CRM fields)
      const extras = ['customer_name','customer_phone','property_city','property_state','bedrooms','home_type','agent_first_name','agent_last_name'];
      extras.forEach(n=>{ const el = document.querySelector(`[name="${n}"]`); if (el && !(n in answers)) answers[n] = el.value || ''; });
      // Fall back to prefill values for any missing keys so info blocks render on first load
      try {
        const pf = window.__scriptPrefill || {};
        Object.keys(pf).forEach(k=>{ if (!(k in answers) || answers[k] === '') answers[k] = pf[k]; });
      } catch(_){ }
      return answers;
    }
    function collectAnswersAndRefresh(secKey){
      const container = document.getElementById('script-content');
      const answers = collectAnswers(container);
      container.querySelectorAll('[data-q-key]').forEach(row=>{
        const qKey = row.getAttribute('data-q-key');
        const sec = container.querySelector(`[data-section-key]`);
        const q = window.__scriptQuestions[qKey];
        if (!q) return;
        const force = row.getAttribute('data-force-show') === '1';
        row.style.display = (force || shouldShowQuestion(q, answers)) ? '' : 'none';
      });
      // Update section-level visibility
      try {
        const sections = window.__scriptSections || {};
        Object.keys(sections).forEach(k=>{
          const node = container.querySelector(`[data-section-grid="${k}"]`);
          if (node) node.style.display = shouldShowSection(sections[k], answers) ? '' : 'none';
        });
      } catch(_){ }
      // Update info blocks with latest answers
      container.querySelectorAll('[data-info-block]').forEach(node=>{
        const tpl = node.getAttribute('data-info-template') || '';
        node.innerHTML = renderInfoHTMLFromAnswers(tpl, answers);
        // Wire edit:section_key links
        Array.from(node.querySelectorAll('[data-edit-section]')).forEach(a=>{
          a.addEventListener('click', (e)=>{ e.preventDefault(); const key = a.getAttribute('data-edit-section')||''; try { window.__revealSection && window.__revealSection[key] && window.__revealSection[key](); } catch(_){} });
        });
        // Wire copy phone click
        Array.from(node.querySelectorAll('[data-copy-phone]')).forEach(el=>{
          el.addEventListener('click', async (e)=>{
            e.preventDefault();
            const num = el.getAttribute('data-copy-phone') || '';
            try { await navigator.clipboard.writeText(num); } catch(_){
              try { const ta=document.createElement('textarea'); ta.value=num; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); } catch(__){}
            }
          });
        });
      });
    }
    function validate(container, structure){
      const answers = collectAnswers(container);
      const missing = [];
      const invalid = [];
      (structure.sections||[]).forEach(sec=>{
        (sec.questions||[]).forEach(q=>{
          if (!shouldShowQuestion(q, answers)) return;
          const val = answers[q.key];
          if (q.required && (val === undefined || val === '' || (Array.isArray(val) && val.length===0))) { missing.push(q.label||q.key); return; }
          if (q.type==='number'){
            const num = Number(val);
            if (!Number.isFinite(num)) { invalid.push(`${q.label||q.key}: not a number`); return; }
            if (q.min!==undefined && q.min!=='' && num < Number(q.min)) invalid.push(`${q.label||q.key}: < min ${q.min}`);
            if (q.max!==undefined && q.max!=='' && num > Number(q.max)) invalid.push(`${q.label||q.key}: > max ${q.max}`);
          } else if (typeof val === 'string'){
            if (q.min!==undefined && q.min!=='' && val.length < Number(q.min)) invalid.push(`${q.label||q.key}: length < ${q.min}`);
            if (q.max!==undefined && q.max!=='' && val.length > Number(q.max)) invalid.push(`${q.label||q.key}: length > ${q.max}`);
            if (q.pattern){ try { const re = new RegExp(q.pattern); if (!re.test(val)) invalid.push(`${q.label||q.key}: invalid format`); } catch(_){} }
          }
        });
      });
      const ok = missing.length===0 && invalid.length===0;
      return { ok, missing, invalid };
    }
    async function load(){
      const alreadyRedirected = params.get('auth') === '1';
      const redirectToLogin = ()=>{
        try { sessionStorage.removeItem('token'); } catch(_) {}
        if (alreadyRedirected) {
          // Avoid loop: render a lightweight prompt instead of redirecting again
          const container = document.getElementById('script-content');
          container.innerHTML = '<div class="p-4 rounded border bg-amber-50 text-amber-800">Session expired. <a class="underline" href="/admin.php?view=login&redirect='+encodeURIComponent(window.location.pathname+window.location.search)+'">Sign in</a> and reload.</div>';
          document.getElementById('script-title').textContent = 'Sign in required';
          return;
        }
        const url = new URL(window.location.href);
        url.searchParams.set('auth','1');
        const ret = encodeURIComponent(url.pathname + url.search);
        window.location.href = '/admin.php?view=login&redirect=' + ret;
      };
      // Auth gate: allow public access to any published script (endpoint returns 200 for published without auth)
      let isPublic = (slug === 'home');
      if (!isPublic) {
        try {
          const test = await fetch(api('/scripts/slug/' + encodeURIComponent(slug)));
          if (test.ok) isPublic = true;
        } catch(_) { /* ignore */ }
      }
      if (!isPublic) {
      if (!token) { redirectToLogin(); return; }
      try {
        const r = await fetch(api('/me'), { headers: { 'Authorization': 'Bearer ' + token } });
        if (!r.ok) { redirectToLogin(); return; }
      } catch (_) { redirectToLogin(); return; }
      }
      // Enforcement: block by phone suppression and geo allowlist if configured
      try {
        const settings = await fetchJSON(api('/settings'));
        const suppressed = new Set((settings.suppressed_phones||[]).map(String));
        const allowedZips = new Set((settings.geo_allowed_zips||[]).map(String));
        // Named lists: if script specifies a list, use that list instead of global
        const sMeta = await fetchJSON(api('/scripts/slug/' + encodeURIComponent(slug)));
        const geoListName = (sMeta.geo_list||'').trim();
        const suppListName = (sMeta.suppression_list||'').trim();
        const geoMode = (sMeta.geo_mode||'allow');
        if (geoListName && Array.isArray(settings.geo_lists)){
          const found = settings.geo_lists.find(x=> (x.name||'').trim()===geoListName);
          if (found) { allowedZips.clear(); (found.zips||[]).forEach(z=>allowedZips.add(String(z))); }
        }
        if (suppListName && Array.isArray(settings.suppression_lists)){
          const found = settings.suppression_lists.find(x=> (x.name||'').trim()===suppListName);
          if (found) { suppressed.clear(); (found.phones||[]).forEach(p=>suppressed.add(String(p))); }
        }
        const phoneParam = normalizePhone(params.get('phone'));
        const zipParam = String(params.get('zip')||'').trim();
        // Expose for later post-prefill enforcement
        window.__allowedZips = allowedZips; window.__suppressedPhones = suppressed;
        if (phoneParam && suppressed.has(phoneParam)) {
          document.getElementById('script-title').textContent = 'Access blocked';
          document.getElementById('script-content').innerHTML = '<div class="p-4 border rounded bg-rose-50 text-rose-700">This phone is suppressed.</div>';
          return;
        }
        if (allowedZips.size>0 && zipParam && ((geoMode==='allow' && !allowedZips.has(zipParam)) || (geoMode==='deny' && allowedZips.has(zipParam)))) {
          document.getElementById('script-title').textContent = 'Not available in your area';
          document.getElementById('script-content').innerHTML = '<div class="p-4 border rounded bg-amber-50 text-amber-800">This script is not available for the provided ZIP.</div>';
          return;
        }
      } catch(_) { /* proceed if settings not available */ }
      const s = await fetchJSON(api('/scripts/slug/' + encodeURIComponent(slug)));
      window.__scriptStructure = s;
      window.__scriptQuestions = {};
      document.getElementById('script-title').textContent = s.title || slug;
      // Render page intro if provided
      if (s.intro) {
        const top = document.createElement('div');
        top.className = 'max-w-6xl mx-auto p-4 pt-0';
        top.innerHTML = `<div class="mb-2 p-3 bg-sky-50 border border-sky-200 text-sky-900 text-sm rounded">${(s.intro||'')}</div>`;
        document.body.insertBefore(top, document.body.firstChild.nextSibling);
      }
      // Compose prefill answers from URL params and API lookups by phone
      const prefill = {};
      let __lastAnswers = null; // holds last saved script answers for fuzzy prefill
      const __original = {}; // snapshot before alias/fuzzy mapping
      const urlPhone = normalizePhone(params.get('phone'));
      const agentFirst = params.get('agentFirstName') || params.get('agent_first_name') || '';
      const agentLast = params.get('agentLastName') || params.get('agent_last_name') || '';
      if (agentFirst) prefill['agent_first_name'] = agentFirst;
      if (agentLast) prefill['agent_last_name'] = agentLast;
      if (urlPhone) prefill['customer_phone'] = urlPhone;
      __dbg('params', { urlPhone, agentFirst, agentLast });

      // Try to fetch existing records to hydrate remaining fields
      // Always attempt DB prefill by phone; endpoints now allow public reads
      if (urlPhone) {
        try {
          __dbg('prefill:lookup:start', { urlPhone, slug });
          const [leadRes, contactRes, lastResBySlug, lastResAny] = await Promise.all([
            fetchJSON(api('/leads') + '&q=' + encodeURIComponent(urlPhone)),
            fetchJSON(api('/contacts') + '&q=' + encodeURIComponent(urlPhone)),
            fetchJSON(api('/script-responses/lookup') + '&phone=' + encodeURIComponent(urlPhone) + (slug?('&slug='+encodeURIComponent(slug)):'') ),
            fetchJSON(api('/script-responses/lookup') + '&phone=' + encodeURIComponent(urlPhone))
          ]);
          __dbg('prefill:lookup:done', {
            leadsCount: Array.isArray((leadRes||{}).items)? leadRes.items.length : 0,
            contactsCount: Array.isArray((contactRes||{}).items)? contactRes.items.length : 0,
            lastBySlug: !!(lastResBySlug && lastResBySlug.answers),
            lastAny: !!(lastResAny && lastResAny.answers)
          });
          const leads = Array.isArray(leadRes.items) ? leadRes.items : [];
          const contacts = Array.isArray(contactRes.items) ? contactRes.items : [];
          const pickLead = leads.find(x=>normalizePhone(x.customer_phone)===urlPhone) || leads[0];
          const pickContact = contacts.find(x=>normalizePhone(x.phone)===urlPhone) || contacts[0];
          if (pickLead) {
            if (pickLead.customer_name) {
              prefill['customer_name'] = pickLead.customer_name;
              // Best effort split
              const parts = String(pickLead.customer_name).trim().split(/\s+/);
              if (parts.length) prefill['customer_first_name'] = parts[0];
              if (parts.length>1) prefill['customer_last_name'] = parts.slice(1).join(' ');
            }
            if (pickLead.customer_phone) prefill['customer_phone'] = normalizePhone(pickLead.customer_phone);
            if (pickLead.property_city) prefill['property_city'] = pickLead.property_city;
            if (pickLead.property_state) prefill['property_state'] = pickLead.property_state;
          } else if (pickContact) {
            if (pickContact.name) {
              prefill['customer_name'] = pickContact.name;
              const parts = String(pickContact.name).trim().split(/\s+/);
              if (parts.length) prefill['customer_first_name'] = parts[0];
              if (parts.length>1) prefill['customer_last_name'] = parts.slice(1).join(' ');
            }
            if (pickContact.phone) prefill['customer_phone'] = normalizePhone(pickContact.phone);
          }
          // Merge answers: start with most recent across all slugs, then overlay slug-specific
          const ansAny = (lastResAny && typeof lastResAny.answers === 'object') ? lastResAny.answers : null;
          const ansSlug = (lastResBySlug && typeof lastResBySlug.answers === 'object') ? lastResBySlug.answers : null;
          const mergedLast = Object.assign({}, ansAny || {}, ansSlug || {});
          if (Object.keys(mergedLast).length) {
            __lastAnswers = mergedLast;
            Object.assign(prefill, mergedLast);
            if (!prefill['customer_phone'] && prefill['best_callback_number']) { prefill['customer_phone'] = normalizePhone(prefill['best_callback_number']); }
            if (!prefill['best_callback_number'] && prefill['customer_phone']) { prefill['best_callback_number'] = normalizePhone(prefill['customer_phone']); }
            // Derive split/combined names
            const cf = String(prefill['customer_first_name']||'').trim();
            const cl = String(prefill['customer_last_name']||'').trim();
            const cn = String(prefill['customer_name']||'').trim();
            if (!cn && (cf||cl)) prefill['customer_name'] = [cf,cl].filter(Boolean).join(' ');
            if ((!cf || !cl) && cn) {
              const parts = cn.split(/\s+/);
              if (!cf && parts.length) prefill['customer_first_name'] = parts[0];
              if (!cl && parts.length>1) prefill['customer_last_name'] = parts.slice(1).join(' ');
            }
            __dbg('prefill:mergedLast:keys', Object.keys(mergedLast));
          }
          __dbg('prefill:values', {
            ok_addr_cash: prefill['ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash'],
            listed_agent: prefill['is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service']
          });
        } catch (_) { /* ignore prefill failures */ }
      }

      // If no explicit zip param, enforce geo after we have prefill
      try {
        const az = window.__allowedZips || new Set();
        if (az.size > 0) {
          const zipParam = String(params.get('zip')||'').trim();
          const effectiveZip = zipParam || String(prefill['property_zip']||'').trim();
          if (effectiveZip && ((geoMode==='allow' && !az.has(effectiveZip)) || (geoMode==='deny' && az.has(effectiveZip)))) {
            document.getElementById('script-title').textContent = 'Not available in your area';
            document.getElementById('script-content').innerHTML = '<div class="p-4 border rounded bg-amber-50 text-amber-800">This script is not available for the provided ZIP.</div>';
            return;
          }
        }
      } catch(_){}

      // Helper: fuzzy map prior answers to current question by label tokens
      const fuzzyAnswerForKey = (answersObj, qkey) => {
        try {
          const ans = answersObj || {};
          const keys = Object.keys(ans);
          if (!keys.length) return undefined;
          const normKey = String(qkey||'').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,' ').trim();
          if (!normKey) return undefined;
          const tokens = normKey.split(/\s+/).filter(t=>t.length>2);
          let bestKey = null; let bestScore = 0;
          for (const k of keys){
            const kt = String(k||'').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').split(/[^a-z0-9]+/).filter(t=>t.length>2);
            const score = tokens.reduce((s,t)=> s + (kt.includes(t)?1:0), 0);
            if (score > bestScore){ bestScore = score; bestKey = k; }
          }
          if (bestScore >= 2) return ans[bestKey];
          return undefined;
        } catch(_) { return undefined; }
      };
      const fuzzyAnswerForLabel = (answersObj, label) => {
        try {
          const ans = answersObj || {};
          const keys = Object.keys(ans);
          if (!keys.length) return undefined;
          const norm = String(label||'').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,' ').trim();
          if (!norm) return undefined;
          const tokens = norm.split(/\s+/).filter(t=>t.length>2);
          let bestKey = null; let bestScore = 0;
          for (const k of keys){
            const kt = k.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').split(/[^a-z0-9]+/).filter(t=>t.length>2);
            const score = tokens.reduce((s,t)=> s + (kt.includes(t)?1:0), 0);
            if (score > bestScore){ bestScore = score; bestKey = k; }
          }
          if (bestScore >= 2) return ans[bestKey]; // relaxed threshold
          return undefined;
        } catch(_) { return undefined; }
      };
      // Map common fields and fuzzy map previous answers when keys changed
      (s.sections||[]).forEach(sec=>{
        (sec.questions||[]).forEach(q=>{
          const label = (q.label||'').toString().toLowerCase();
          const help = (q.help||'').toString().toLowerCase();
          const text = (label + ' ' + help).trim();
          if (!text) return;
          if (!Object.prototype.hasOwnProperty.call(__original, q.key) && Object.prototype.hasOwnProperty.call(prefill, q.key)) { __original[q.key] = prefill[q.key]; }
          if (prefill['customer_phone'] && (text.includes('phone') || text.includes('callback') || text.includes('call back')) && !['yesno','radio','checkbox'].includes(q.type)) prefill[q.key] = prefill['customer_phone'];
          if ((text.includes('bathroom') || text.includes('bath rooms')) && prefill['how_many_bathrooms_are_in_the_house']) prefill[q.key] = prefill['how_many_bathrooms_are_in_the_house'];
          if ((text.includes('bedroom') || text.includes('bed rooms')) && prefill['how_many_bedrooms_are_in_the_house']) prefill[q.key] = prefill['how_many_bedrooms_are_in_the_house'];
          if ((text.includes('home type') || text.includes('type of house')) && prefill['what_type_of_house_is_this']) prefill[q.key] = prefill['what_type_of_house_is_this'];
          if ((text.includes('condition') || text.includes('condition of the home')) && prefill['what_is_the_condition_of_the_home']) prefill[q.key] = prefill['what_is_the_condition_of_the_home'];
          if (label.includes('first name') && prefill['customer_first_name']) prefill[q.key] = prefill['customer_first_name'];
          else if (label.includes('last name') && prefill['customer_last_name']) prefill[q.key] = prefill['customer_last_name'];
          else if (prefill['customer_name'] && (label.includes('full name') || (label.includes('name') && !label.includes('agent')))) prefill[q.key] = prefill['customer_name'];
          const labelHasWordCity = /\bcity\b/.test(label);
          const labelHasWordState = /\bstate\b/.test(label);
          if (prefill['property_city'] && (q.key==='property_city' || (labelHasWordCity && !['yesno','radio','checkbox'].includes(q.type)))) prefill[q.key] = prefill['property_city'];
          if (prefill['property_state'] && (q.key==='property_state' || q.type==='state' || (labelHasWordState && !/real\s+estate/.test(label) && !['yesno','radio','checkbox'].includes(q.type)))) prefill[q.key] = prefill['property_state'];
          if (prefill['agent_first_name'] && text.includes('agent') && text.includes('first')) prefill[q.key] = prefill['agent_first_name'];
          if (prefill['agent_last_name'] && text.includes('agent') && text.includes('last')) prefill[q.key] = prefill['agent_last_name'];
          // Specific aliases based on provided data set
          if ((/ok\s*,?\s*i\s*need\s*your\s*street\s*address/i.test(text) || text.includes('street address') || text.includes('address, city, state and zip')) && prefill['ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash']) {
            prefill[q.key] = prefill['ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash'];
          }
          if ((text.includes('listed') || text.includes('real estate agent') || text.includes('real estate service')) && prefill['is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service']) {
            prefill[q.key] = prefill['is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service'];
          }
          // If still empty, fuzzy map by key, then by label from last saved answers
          if ((prefill[q.key] === undefined || prefill[q.key] === '') && __lastAnswers){
            const vKey = fuzzyAnswerForKey(__lastAnswers, q.key);
            if (vKey !== undefined && vKey !== null && String(vKey) !== '') prefill[q.key] = vKey;
          }
          if ((prefill[q.key] === undefined || prefill[q.key] === '') && __lastAnswers){
            const v = fuzzyAnswerForLabel(__lastAnswers, q.label||q.key);
            if (v !== undefined && v !== null && String(v) !== '') prefill[q.key] = v;
          }
          if ((prefill[q.key] === undefined || prefill[q.key] === '') && __lastAnswers && help){
            const v2 = fuzzyAnswerForLabel(__lastAnswers, help);
            if (v2 !== undefined && v2 !== null && String(v2) !== '') prefill[q.key] = v2;
          }
        });
      });

      __dbg('prefill:compare', {
        ok_addr_cash: { original: __original['ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash'], final: prefill['ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash'] },
        listed_agent: { original: __original['is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service'], final: prefill['is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service'] }
      });

      const container = document.getElementById('script-content');
      container.innerHTML = '';
      // Expose prefill globally so info blocks can re-render with values not in form controls
      try { window.__scriptPrefill = prefill; } catch(_){ }
      __dbg('render:start', { sections: (s.sections||[]).length, prefillKeys: Object.keys(prefill) });
      (s.sections || []).forEach(sec => {
        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-1 md:grid-cols-3 gap-4';
        const secKey = String(sec.key||'');
        grid.setAttribute('data-section-grid', secKey);

        // Left: section form (2 cols)
        const left = document.createElement('section');
        left.className = 'rounded-xl border border-slate-200/70 bg-white shadow-lg p-4 sm:p-5 md:col-span-2';
        left.setAttribute('data-section-key', sec.key||'');
        const title = document.createElement('h3'); title.className = 'text-lg font-semibold'; title.textContent = sec.label || sec.key; left.appendChild(title);
        if (sec.intro) {
          const intro = document.createElement('div');
          intro.className='mt-2 text-sm text-slate-600';
          // Support dynamic placeholders in section intro (e.g., <agent_first_name>)
          intro.setAttribute('data-info-block','');
          intro.setAttribute('data-info-template', sec.intro || '');
          intro.innerHTML = renderInfoHTMLFromAnswers(sec.intro || '', prefill);
          left.appendChild(intro);
        }
        const list = document.createElement('div'); list.className='mt-3 space-y-4';
        const questions = Array.isArray(sec.questions)? sec.questions: [];
        window.__scriptSections = window.__scriptSections || {};
        if (secKey) window.__scriptSections[secKey] = sec;
        const hasEditFlag = questions.some(q=> /<edit>/i.test(String(q.label||'')) || /<edit>/i.test(String(q.help||'')) );
        const isHidden = !!sec.hidden;
        // Determine if any question in this section has prefilled data
        const sectionHasPrefill = questions.some(q=>{ const v = prefill[q.key]; return !(v===undefined || v===null || String(v).trim()===''); });
        // Collapse only when the section is marked hidden AND there is existing data to avoid blank screens
        const collapsed = isHidden && !hasEditFlag && sectionHasPrefill;

        // Build visible and hidden lists based on question.hidden flag
        const visibleList = document.createElement('div'); visibleList.className = 'space-y-4';
        const hiddenList = document.createElement('div'); hiddenList.className = 'space-y-4';
        questions.forEach(q=>{
          window.__scriptQuestions[q.key]=q;
          const row = renderQuestionRow(sec.key, q, prefill);
          const shouldHideQ = !!q.hidden && sectionHasPrefill; // hide only if there is data; otherwise show
          if (shouldHideQ) { row.setAttribute('data-hidden-question','1'); hiddenList.appendChild(row); } else visibleList.appendChild(row);
        });

        // expose reveal map
        window.__revealSection = window.__revealSection || {};
        const reveal = ()=>{
          if (list.childNodes.length===1 && list.firstChild && list.firstChild.getAttribute && list.firstChild.getAttribute('data-collapsed')==='1'){
            list.innerHTML=''; list.appendChild(visibleList); Array.from(hiddenList.childNodes).forEach(n=>{ n.setAttribute('data-force-show','1'); visibleList.appendChild(n); }); return;
          }
          Array.from(hiddenList.childNodes).forEach(n=>{ n.setAttribute('data-force-show','1'); visibleList.appendChild(n); });
        };
        const secAlias = String(sec.label||'').toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
        if (secKey) window.__revealSection[secKey] = reveal;
        if (secAlias) window.__revealSection[secAlias] = reveal;

        if (collapsed){
          // Hidden section collapsed to an edit link only
          const row = document.createElement('div');
          row.className = 'p-3 rounded border bg-slate-50 text-slate-700 text-sm';
          row.setAttribute('data-collapsed','1');
          const a = document.createElement('a'); a.href='#'; a.textContent = 'Edit'; a.className='underline';
          a.addEventListener('click', (e)=>{ e.preventDefault(); reveal(); });
          row.appendChild(document.createTextNode('Section is hidden. ')); row.appendChild(a);
          list.appendChild(row);
        } else {
          list.appendChild(visibleList);
          if (hasEditFlag && hiddenList.childNodes.length){
            const lr = buildEditRevealRow();
            list.appendChild(lr);
          }
        }

        function buildEditRevealRow(){
          const linkRow = document.createElement('div');
          linkRow.className = 'mt-2 text-sm text-slate-600';
          const a = document.createElement('a'); a.href='#'; a.textContent='Edit'; a.className='underline';
          a.addEventListener('click', (e)=>{ e.preventDefault();
            // Reveal hidden questions by appending their rows
            Array.from(hiddenList.childNodes).forEach(n=>visibleList.appendChild(n));
            linkRow.remove();
          });
          linkRow.appendChild(a);
          return linkRow;
        }
        left.appendChild(list);

        grid.appendChild(left);

        // Right: static agent notes from admin (separate box), if provided
        if ((sec.agent_notes||'').trim() !== ''){
          const right = document.createElement('aside'); right.className='md:col-span-1';
          const note = document.createElement('div');
          note.className = 'p-3 rounded border border-amber-300 bg-amber-50 text-amber-800 text-sm whitespace-pre-wrap';
          (function(){
            const raw = sec.agent_notes || '';
            // Minimal sanitization: escape, then unescape only our bold tags
            const escapeHtml = s=>s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
            let html = escapeHtml(raw);
            // [[b]]...[[/b]] -> <strong>...</strong>
            html = html.replace(/\[\[b\]\]([\s\S]*?)\[\[\/b\]\]/g, '<strong>$1</strong>');
            // **...** -> <strong>...</strong>  (avoid matching inside words)
            html = html.replace(/\*\*([^*][\s\S]*?)\*\*/g, '<strong>$1</strong>');
            note.innerHTML = html;
          })();
          right.appendChild(note);
          grid.appendChild(right);
        }

        // Apply section-level conditional visibility
        const visibleNow = shouldShowSection(sec, prefill);
        grid.style.display = visibleNow ? '' : 'none';
        container.appendChild(grid);
      });
      const submitBar = document.createElement('div');
      submitBar.className='mt-6';
      submitBar.innerHTML = '<button id="submit-script" class="inline-flex justify-center rounded-lg brand-btn px-5 py-2.5 font-medium text-white shadow focus:outline-none">Save to CRM</button>';
      container.appendChild(submitBar);
      // Ensure radios/selects are checked according to prefill in case earlier mapping missed
      try {
        const yesSet = new Set(['yes','y','true','1']);
        const noSet = new Set(['no','n','false','0']);
        container.querySelectorAll('[data-q-key]').forEach(row=>{
          const key = row.getAttribute('data-q-key');
          const val = prefill[key];
          if (val === undefined || val === null || String(val) === '') return;
          const radios = Array.from(row.querySelectorAll('input[type="radio"]'));
          if (radios.length){
            let matched=false; const nval = normalizeChoiceValue(val);
            radios.forEach(r=>{ r.checked = (String(r.value) == String(val)); matched = matched || r.checked; });
            if (!matched){ radios.forEach(r=>{ if (normalizeChoiceValue(r.value) === nval) { r.checked = true; matched = true; } }); }
            if (!matched){
              if (nval==='yes') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='yes'); if (t) t.checked=true; }
              else if (nval==='no') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='no'); if (t) t.checked=true; }
            }
          }
          const select = row.querySelector('select');
          if (select){ const init = String(val); const ninit = normalizeChoiceValue(init); let found=''; Array.from(select.options).forEach(o=>{ if (!found && (o.value===init || normalizeChoiceValue(o.value)===ninit || normalizeChoiceValue(o.textContent)===ninit)) found=o.value; }); if (found){ select.value=found; } }
        });
        // Final targeted fixes by phrase matching
        const last = __lastAnswers || {};
        function forceByPhrase(phrase, keyName){
          const val = prefill[keyName] ?? last[keyName];
          if (val === undefined || val === null || String(val) === '') return;
          const nodes = Array.from(container.querySelectorAll('[data-q-key]'));
          nodes.forEach(row=>{
            const text = (row.textContent||'').toLowerCase();
            if (!text.includes(phrase)) return;
            const radios = Array.from(row.querySelectorAll('input[type="radio"]'));
            if (!radios.length) return;
            const nval = normalizeChoiceValue(val);
            let matched=false;
            radios.forEach(r=>{ if (String(r.value) == String(val)) { r.checked=true; matched=true; } });
            if (!matched){ radios.forEach(r=>{ if (normalizeChoiceValue(r.value)===nval) { r.checked=true; matched=true; } }); }
            if (!matched){
              if (nval==='yes') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='yes'); if (t) t.checked=true; }
              else if (nval==='no') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='no'); if (t) t.checked=true; }
            }
            __dbg('enforce:phrase', { phrase, keyName, val, matched });
          });
        }
        forceByPhrase('ok, i need your street address, city, state, and zip', 'ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash');
        forceByPhrase('is the property currently listed through a real estate agent', 'is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service');

        // Final key-based enforcement for known radios
        function forceByKey(keyName){
          const val = prefill[keyName] ?? last[keyName];
          if (val === undefined || val === null || String(val) === '') return;
          const radios = Array.from(container.querySelectorAll(`input[type="radio"][name="${keyName}"]`));
          if (!radios.length) return;
          const nval = normalizeChoiceValue(val);
          let matched=false;
          radios.forEach(r=>{ if (String(r.value) == String(val)) { r.checked=true; matched=true; try{r.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } });
          if (!matched){ radios.forEach(r=>{ if (normalizeChoiceValue(r.value)===nval) { r.checked=true; matched=true; try{r.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } }); }
          if (!matched){ if (nval==='yes') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='yes'); if (t) { t.checked=true; try{t.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } }
            else if (nval==='no') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='no'); if (t) { t.checked=true; try{t.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } } }
          __dbg('enforce:key', { keyName, val, matched, radios: radios.map(r=>r.value) });
        }
        forceByKey('ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash');
        forceByKey('is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service');
        // Generic enforcement for all radio groups named by key
        try {
          Object.keys(prefill||{}).forEach(k=>{
            const val = prefill[k]; if (val===undefined || val===null || String(val)==='') return;
            const sel = `input[type="radio"][name="${k}"]`;
            const radios = Array.from(container.querySelectorAll(sel));
            if (!radios.length) return;
            const nval = normalizeChoiceValue(val);
            let matched=false;
            radios.forEach(r=>{ if (String(r.value)===String(val)) { r.checked=true; matched=true; try{r.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } });
            if (!matched) { radios.forEach(r=>{ if (normalizeChoiceValue(r.value)===nval) { r.checked=true; matched=true; try{r.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } }); }
            if (!matched) { if (nval==='yes') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='yes'); if (t) { t.checked=true; try{t.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } }
              else if (nval==='no') { const t=radios.find(r=>normalizeChoiceValue(r.value)==='no'); if (t) { t.checked=true; try{t.dispatchEvent(new Event('input',{bubbles:true}));}catch(_){} } } }
            __dbg('enforce:generic', { key: k, val, matched, radios: radios.map(r=>r.value) });
          });
        } catch(_){ }

        // Final snapshot for the two critical radios
        try {
          const snap = (key)=>{
            const radios = Array.from(container.querySelectorAll(`input[type="radio"][name="${key}"]`));
            const sel = radios.find(r=>r.checked);
            __dbg('enforce:snapshot', { key, prefill: prefill[key], selected: sel ? sel.value : null, options: radios.map(r=>r.value) });
          };
          snap('ok_i_need_your_street_address_city_state_and_zip_is_this_the_property_that_you_are_seeking_cash');
          snap('is_the_property_currently_listed_through_a_real_estate_agent_or_other_real_estate_service');
        } catch(_){ }
      } catch(_){ }
      // After initial render, refresh to apply conditions and info placeholders with prefill values
      collectAnswersAndRefresh();
      document.getElementById('submit-script').addEventListener('click', async (e)=>{
        e.preventDefault();
        const v = validate(container, s);
        if (!v.ok) {
          const msgs = [];
          if (v.missing.length) msgs.push('Missing: '+v.missing.join(', '));
          if (v.invalid.length) msgs.push('Invalid: '+v.invalid.join('; '));
          alert(msgs.join('\n'));
          return;
        }
        const answers = collectAnswers(container);
        // Ensure agent names are captured even if no explicit question exists
        if (!answers.agent_first_name && agentFirst) answers.agent_first_name = agentFirst;
        if (!answers.agent_last_name && agentLast) answers.agent_last_name = agentLast;
        // Normalize combined names for backend compatibility and analytics
        if (!answers.customer_name) {
          const cf = String(answers.customer_first_name||'').trim();
          const cl = String(answers.customer_last_name||'').trim();
          const combined = [cf,cl].filter(Boolean).join(' ');
          if (combined) answers.customer_name = combined;
        }
        // Normalize agent name for backend compatibility only (not displayed)
        if (!answers.agent_name) {
          const af = String(answers.agent_first_name||'').trim();
          const al = String(answers.agent_last_name||'').trim();
          const combinedAgent = [af,al].filter(Boolean).join(' ');
          if (combinedAgent) answers.agent_name = combinedAgent;
        }
        const res = await fetchJSON(api('/script-responses'), { method:'POST', body: JSON.stringify({ slug, answers }) });
        alert('Saved. ID: ' + (res.id||''));
      });
    }

    // No per-agent note editing UI; notes are configured in Admin and displayed per section

    load().catch((err)=>{ try { __dbg('load:error', err && (err.message||err)); } catch(_){} document.getElementById('script-title').textContent='Script not found'; });
  </script>
</body>
</html>


