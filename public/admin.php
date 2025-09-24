<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Callcenter CRM Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="color-scheme" content="light dark" />
  <link rel="icon" href="/public/favicon.ico" />
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
  <div class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200 flex items-center justify-between px-3 py-2">
    <button id="btn-menu" class="p-2 rounded border"><span class="sr-only">Menu</span>☰</button>
    <div class="font-semibold">CRM Admin</div>
    <div></div>
  </div>
  <div class="flex min-h-screen w-full md:pt-0 pt-10">
    <aside id="sidebar" class="w-64 bg-white border-r border-slate-200 hidden md:block fixed md:static inset-y-0 left-0 z-40">
      <div class="p-4 font-semibold">CRM Admin</div>
      <nav class="px-2 space-y-1 text-sm">
        <a href="#" data-view="dashboard" class="block px-3 py-2 rounded hover:bg-slate-100">Dashboard</a>
        <a href="#" data-view="contacts" class="block px-3 py-2 rounded hover:bg-slate-100">Contacts</a>
        <a href="#" data-view="leads" class="block px-3 py-2 rounded hover:bg-slate-100">Leads</a>
        <a href="#" data-view="agents" class="block px-3 py-2 rounded hover:bg-slate-100">Agents</a>
        <a href="#" data-view="scripts" class="block px-3 py-2 rounded hover:bg-slate-100">Scripts</a>
        <a href="#" data-view="campaigns" class="block px-3 py-2 rounded hover:bg-slate-100">Campaigns</a>
        <a href="#" data-view="calls" class="block px-3 py-2 rounded hover:bg-slate-100">Calls</a>
        <a href="#" data-view="dnc" class="block px-3 py-2 rounded hover:bg-slate-100">DNC</a>
        <a href="#" data-view="reports" class="block px-3 py-2 rounded hover:bg-slate-100">Reports</a>
        <a href="#" data-view="data" class="block px-3 py-2 rounded hover:bg-slate-100">Data</a>
        <a href="#" data-view="schedule" class="block px-3 py-2 rounded hover:bg-slate-100">Schedule</a>
        <a href="#" data-view="callbacks" class="block px-3 py-2 rounded hover:bg-slate-100">Callback Board</a>
        <a href="#" data-view="qa-rubrics" class="block px-3 py-2 rounded hover:bg-slate-100">QA Rubrics</a>
        <a href="#" data-view="howto" class="block px-3 py-2 rounded hover:bg-slate-100">How To</a>
        <a href="#" data-view="settings" class="block px-3 py-2 rounded hover:bg-slate-100">Settings</a>
        <a href="#" data-view="geo" class="block px-3 py-2 rounded hover:bg-slate-100">Geo Restriction</a>
        <a href="#" data-view="suppression" class="block px-3 py-2 rounded hover:bg-slate-100">Phone Suppression</a>
        <a href="#" data-view="billing" class="block px-3 py-2 rounded hover:bg-slate-100">Billing</a>
        <a href="#" data-view="accounts" class="block px-3 py-2 rounded hover:bg-slate-100">Accounts</a>
        <a href="#" data-view="payments" class="block px-3 py-2 rounded hover:bg-slate-100">Payments</a>
        <a href="/payments_admin.php" data-view="payments-admin" target="_blank" class="block px-3 py-2 rounded hover:bg-slate-100">Payments Admin</a>
        <a href="/subscriptions_admin.php" data-view="subscriptions-admin" target="_blank" class="block px-3 py-2 rounded hover:bg-slate-100">Subscriptions Admin</a>
        <a href="#" data-view="magic" class="block px-3 py-2 rounded hover:bg-slate-100">Send Magic Link</a>
        <a href="#" id="btn-logout" class="block px-3 py-2 rounded hover:bg-slate-100 text-rose-600">Logout</a>
      </nav>
    </aside>
    <main class="flex-1 p-6">
      <h1 id="title" class="text-xl font-semibold mb-4">Dashboard</h1>
      <div id="root" class="space-y-6">
        <div id="view-login" class="">
          <div class="max-w-sm">
            <div class="p-6 bg-white border rounded space-y-3">
              <div>
                <label class="block text-sm">Email</label>
                <input id="login-email" class="px-3 py-2 border rounded w-full" value="admin@example.com" />
              </div>
              <div>
                <label class="block text-sm">Password</label>
                <input id="login-password" type="password" class="px-3 py-2 border rounded w-full" value="ChangeMe123!" />
              </div>
              <button id="btn-login" class="px-3 py-2 bg-blue-600 text-white rounded w-full">Sign in</button>
              <div id="login-error" class="text-rose-600 text-sm hidden"></div>
            </div>
          </div>
        </div>
        <div id="view-dashboard" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 bg-white rounded border border-slate-200"><div class="text-slate-500 text-sm">Inbound Today</div><div id="metric-inbound" class="text-2xl font-bold">0</div></div>
          <div class="p-4 bg-white rounded border border-slate-200"><div class="text-slate-500 text-sm">Outbound Today</div><div id="metric-outbound" class="text-2xl font-bold">0</div></div>
          <div class="p-4 bg-white rounded border border-slate-200"><div class="text-slate-500 text-sm">Avg Handle Time</div><div id="metric-aht" class="text-2xl font-bold">0s</div></div>
        </div>
        <div id="view-contacts" class="hidden">
          <div class="flex items-center gap-2 mb-3">
            <input id="search-contacts" placeholder="Search name or phone" class="px-3 py-2 border rounded w-64" />
            <button id="btn-new-contact" class="px-3 py-2 bg-blue-600 text-white rounded">New</button>
          </div>
          <div id="contacts-list" class="bg-white border rounded"></div>
        </div>
        <div id="view-leads" class="hidden">
          <div class="flex items-center gap-2 mb-3">
            <input id="search-leads" placeholder="Search name, phone, city, state" class="px-3 py-2 border rounded w-80" />
            <button id="btn-refresh-leads" class="px-3 py-2 bg-blue-600 text-white rounded">Refresh</button>
          </div>
          <div id="leads-list" class="bg-white border rounded"></div>
        </div>
        <div id="view-agents" class="hidden">
          <button id="btn-new-agent" class="px-3 py-2 bg-blue-600 text-white rounded mb-3">Invite Agent</button>
          <div id="agents-list" class="bg-white border rounded"></div>
        </div>
        <div id="view-campaigns" class="hidden">
          <button id="btn-new-campaign" class="px-3 py-2 bg-blue-600 text-white rounded mb-3">New Campaign</button>
          <div id="campaigns-list" class="bg-white border rounded"></div>
        </div>
        <div id="view-calls" class="hidden">
          <div class="flex items-center gap-2 mb-3">
            <input id="filter-date" type="date" class="px-3 py-2 border rounded" />
            <select id="filter-direction" class="px-3 py-2 border rounded"><option value="">All</option><option value="inbound">Inbound</option><option value="outbound">Outbound</option></select>
          </div>
          <div id="calls-list" class="bg-white border rounded"></div>
        </div>
        <div id="view-scripts" class="hidden">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1 space-y-2">
              <div class="flex items-center gap-2">
                <input id="script-slug" placeholder="slug" class="px-3 py-2 border rounded w-40" />
                <input id="script-title" placeholder="title" class="px-3 py-2 border rounded w-56" />
              </div>
              <div class="grid grid-cols-1 gap-2 mt-2 text-sm">
                <div class="flex items-center gap-2">
                  <label class="w-36">Header logo URL</label>
                  <input id="script-header-logo" placeholder="/logo.png or https://..." class="px-3 py-2 border rounded w-full" />
                </div>
                <div class="flex items-center gap-2">
                  <label class="w-36">Upload logo</label>
                  <input id="script-logo-file" type="file" accept="image/*" class="px-3 py-2 border rounded w-full" />
                  <button id="btn-upload-logo" class="px-2 py-2 bg-slate-700 text-white rounded">Upload</button>
                </div>
                <div class="flex items-center gap-2">
                  <label class="w-36">Header alignment</label>
                  <select id="script-header-align" class="px-3 py-2 border rounded w-56">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                  </select>
                </div>
              </div>
              <div class="flex gap-2">
                <button id="btn-new-script" class="px-3 py-2 bg-blue-600 text-white rounded">New</button>
                <button id="btn-save-script" class="px-3 py-2 bg-emerald-600 text-white rounded">Save</button>
                <button id="btn-load-by-slug" class="px-3 py-2 bg-slate-600 text-white rounded">Load</button>
                <button id="btn-migrate-names" class="px-3 py-2 bg-amber-600 text-white rounded" title="Backfill split names in existing responses">Migrate Split Names</button>
              </div>
              <div class="flex items-center gap-3 text-sm mt-2">
                <label class="inline-flex items-center gap-2"><input type="checkbox" id="script-published"> <span>Published</span></label>
                <span id="script-version" class="text-slate-500"></span>
              </div>
              <div class="mt-2">
                <label class="block text-sm mb-1">Page Intro (shown at top of script)</label>
                <textarea id="script-intro" class="w-full border rounded p-2 text-sm" rows="3" placeholder="Intro text...\nSupports plain text."></textarea>
              </div>
              <div class="mt-2">
                <label class="block text-sm mb-1">Geo allowlist (named) for this script</label>
                <input id="script-geo-list" list="geo-list-names" class="px-3 py-2 border rounded w-full" placeholder="Enter or choose a geo list name" />
                <datalist id="geo-list-names"></datalist>
              </div>
              <div class="mt-2">
                <label class="block text-sm mb-1">Geo mode</label>
                <select id="script-geo-mode" class="px-3 py-2 border rounded w-full">
                  <option value="allow">Allow only zips in list</option>
                  <option value="deny">Hide if zip in list</option>
                </select>
              </div>
              <div class="mt-2">
                <label class="block text-sm mb-1">Phone suppression list (named)</label>
                <input id="script-suppression-list" list="supp-list-names" class="px-3 py-2 border rounded w-full" placeholder="Enter or choose a suppression list name" />
                <datalist id="supp-list-names"></datalist>
              </div>
              <div id="scripts-list" class="bg-white border rounded"></div>
            </div>
            <div class="md:col-span-1">
              <div class="bg-white border rounded p-3 space-y-3">
                <div class="flex items-center justify-between">
                  <h3 class="font-semibold">Sections</h3>
                  <div class="flex items-center gap-2">
                    <button id="btn-undo" title="Undo (Cmd/Ctrl+Z)" class="px-2 py-1 bg-slate-200 rounded text-sm">Undo</button>
                    <button id="btn-redo" title="Redo (Shift+Cmd/Ctrl+Z)" class="px-2 py-1 bg-slate-200 rounded text-sm">Redo</button>
                    <button id="btn-add-section" class="px-2 py-1 bg-blue-600 text-white rounded text-sm">Add Section</button>
                  </div>
                </div>
                <div id="sections" class="space-y-3"></div>
                <div class="border-t pt-3">
                  <h4 class="font-semibold mb-2">Templates</h4>
                  <div class="grid grid-cols-2 gap-2 text-sm" id="template-buttons"></div>
                </div>
              </div>
            </div>
            <div class="md:col-span-1">
              <div class="bg-white border rounded p-3">
                <h3 class="font-semibold mb-2">Preview</h3>
                <div id="preview" class="space-y-3"></div>
              </div>
              <div class="bg-white border rounded p-3 mt-3">
                <h3 class="font-semibold mb-2">Test Conditions</h3>
                <div class="text-xs text-slate-600 mb-2">Enter JSON map of answers e.g. {"q_key":"Yes","q_age":35}</div>
                <textarea id="test-answers" class="w-full border rounded p-2 text-sm" rows="4">{}</textarea>
              </div>
            </div>
          </div>
        </div>
        <div id="view-dnc" class="hidden">
          <div class="flex items-center gap-2 mb-3">
            <input id="dnc-phone" placeholder="Phone number" class="px-3 py-2 border rounded" />
            <button id="btn-add-dnc" class="px-3 py-2 bg-rose-600 text-white rounded">Add to DNC</button>
          </div>
          <div id="dnc-list" class="bg-white border rounded"></div>
        </div>
        <div id="view-reports" class="hidden">
          <div class="mb-3 flex items-center gap-2">
            <label class="text-sm">Timeframe:</label>
            <select id="rep-since" class="border rounded p-2 text-sm">
              <option value="1">Today</option>
              <option value="7">7 days</option>
              <option value="30" selected>30 days</option>
              <option value="90">90 days</option>
            </select>
            <button id="rep-export-all" class="px-3 py-2 text-sm rounded bg-emerald-700 text-white">Download CSVs</button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Agent Performance</h3><div id="report-agent" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Agent Leaderboard (30 days)</h3><div id="report-agent-leader" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Billing</h3><div id="report-billing" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Campaign Effectiveness</h3><div id="report-campaign" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">SLA (last hour)</h3><div id="report-sla" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Agent Summary (30 days)</h3><div id="report-agent-summary" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Daily Volume (30 days)</h3><div id="report-daily-volume" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Script Performance (90 days)</h3><div id="report-script-perf" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Script Effectiveness (90 days)</h3><div id="report-script-eff" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">DNC Impact (30 days)</h3><div id="report-dnc" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">DNC Trend (30 days)</h3><div id="report-dnc-trend" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Campaign Pacing (30 days)</h3><div id="report-campaign-pacing" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Disposition Analysis (90 days)</h3><div id="report-disposition" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded md:col-span-2"><h3 class="font-semibold mb-2">Hourly Heatmap (7 days)</h3><div id="report-heatmap" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded md:col-span-2">
              <h3 class="font-semibold mb-2">Script Responses</h3>
              <div class="flex flex-wrap gap-2 mb-3">
                <select id="sr-slug" class="border rounded p-2 text-sm"></select>
                <select id="sr-key" class="border rounded p-2 text-sm"><option value="">Select a question key</option></select>
                <select id="sr-since" class="border rounded p-2 text-sm"><option value="1">Today</option><option value="7">7 days</option><option value="30">30 days</option><option value="90" selected>90 days</option><option value="180">180 days</option><option value="365">365 days</option></select>
                <button id="sr-run" class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Run</button>
                <button id="sr-all-run" class="px-3 py-2 text-sm rounded bg-slate-700 text-white">Load All Responses</button>
                <button id="sr-all-csv" class="px-3 py-2 text-sm rounded bg-emerald-700 text-white">Download CSV</button>
                <input id="sr-start" type="date" class="border rounded p-2 text-sm" />
                <input id="sr-end" type="date" class="border rounded p-2 text-sm" />
                <input id="sr-agent" class="border rounded p-2 text-sm" placeholder="agent (id or name)" />
                <input id="sr-q" class="border rounded p-2 text-sm" placeholder="contains text" />
                <button id="sr-all-csv-server" class="px-3 py-2 text-sm rounded bg-blue-700 text-white">Download Server CSV</button>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><h4 class="font-medium mb-1">Summary by Day</h4><div id="sr-summary" class="text-sm"></div></div>
                <div><h4 class="font-medium mb-1">Answer Distribution</h4><div id="sr-dist" class="text-sm"></div></div>
                <div class="md:col-span-2"><h4 class="font-medium mb-1">All Responses</h4><div id="sr-all" class="text-sm"></div></div>
              </div>
            </div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Service Level</h3><div id="report-sl" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Abandon Rate</h3><div id="report-abandon" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Ring No Answer Rate</h3><div id="report-rna" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Occupancy</h3><div id="report-occupancy" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Wait Time Distribution</h3><div id="report-waits" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Schedule Adherence</h3><div id="report-adherence" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Callback Compliance</h3><div id="report-callback" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">FCR</h3><div id="report-fcr" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">QA Scorecards</h3><div id="report-qa" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Funnel</h3><div id="report-funnel" class="text-sm"></div></div>
            <div class="p-4 bg-white border rounded"><h3 class="font-semibold mb-2">Geo Compliance</h3><div id="report-geo" class="text-sm"></div></div>
          </div>
        </div>
        <div id="view-settings" class="hidden">
          <div class="p-4 bg-white border rounded space-y-3">
            <div>
              <label class="block text-sm">JWT Issuer</label>
              <input id="settings-issuer" class="px-3 py-2 border rounded w-80" />
            </div>
            <div>
              <label class="block text-sm">Allowed Origins (comma)</label>
              <input id="settings-origins" class="px-3 py-2 border rounded w-96" />
            </div>
            <div class="pt-2">
              <h3 class="font-semibold mb-2">Role Access</h3>
              <div id="rbac-matrix" class="space-y-2 text-sm"></div>
              <div class="text-xs text-slate-500">Check which Admin pages each role can access.</div>
            </div>
            <button id="btn-save-settings" class="px-3 py-2 bg-emerald-600 text-white rounded">Save</button>
          </div>
        </div>
        <div id="view-geo" class="hidden">
          <div class="p-4 bg-white border rounded space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="font-semibold">Geo Restriction Lists</h3>
                <div class="text-xs text-slate-600">Create and manage ZIP code lists. Upload CSVs or add zips manually.</div>
              </div>
              <button id="btn-new-geo-list" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">+ New ZIP List</button>
            </div>
            <div id="geo-lists" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
            <h3 class="font-semibold">Geo Restriction (ZIP allowlist)</h3>
            <div class="text-xs text-slate-600">Upload a CSV with one ZIP per row. Only these ZIPs will be allowed to view scripts (if list is empty, no restriction).</div>
            <input id="geo-list-name" class="px-3 py-2 border rounded w-64" placeholder="List name (e.g., Northeast)" />
            <input type="file" id="file-geo" accept=".csv,text/csv" />
            <div class="flex gap-2">
              <button id="btn-upload-geo" class="px-3 py-2 bg-blue-600 text-white rounded">Upload</button>
              <button id="btn-clear-geo" class="px-3 py-2 bg-slate-600 text-white rounded">Clear</button>
            </div>
            <pre id="geo-preview" class="text-xs bg-slate-50 p-2 rounded hidden"></pre>
          </div>
        </div>
        <div id="view-suppression" class="hidden">
          <div class="p-4 bg-white border rounded space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="font-semibold">Phone Suppression Lists</h3>
                <div class="text-xs text-slate-600">Create and manage phone suppression lists. Upload CSVs or add phones manually.</div>
              </div>
              <button id="btn-new-supp-list" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">+ New Phone List</button>
            </div>
            <div id="supp-lists" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
            <h3 class="font-semibold">Phone Suppression</h3>
            <div class="text-xs text-slate-600">Upload a CSV with one phone number per row. Suppressed phones will be blocked from script access.</div>
            <input id="supp-list-name" class="px-3 py-2 border rounded w-64" placeholder="List name (e.g., Test Block)" />
            <input type="file" id="file-supp" accept=".csv,text/csv" />
            <div class="flex gap-2">
              <button id="btn-upload-supp" class="px-3 py-2 bg-blue-600 text-white rounded">Upload</button>
              <button id="btn-clear-supp" class="px-3 py-2 bg-slate-600 text-white rounded">Clear</button>
            </div>
            <pre id="supp-preview" class="text-xs bg-slate-50 p-2 rounded hidden"></pre>
          </div>
        </div>
        <div id="view-data" class="hidden">
          <div class="space-y-6">
            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Calls</h3>
              <form id="form-call" class="flex flex-wrap gap-2 mb-3">
                <select name="direction" class="border rounded p-2 text-sm"><option>inbound</option><option selected>outbound</option></select>
                <input name="agent_id" class="border rounded p-2 text-sm" placeholder="agent_id" />
                <input name="campaign_id" class="border rounded p-2 text-sm" placeholder="campaign_id" />
                <input name="contact_phone" class="border rounded p-2 text-sm" placeholder="contact_phone" />
                <input name="handle_time_s" class="border rounded p-2 text-sm" placeholder="handle_time_s" />
                <input name="started_at_ts" class="border rounded p-2 text-sm" placeholder="started_at_ts (epoch)" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-calls" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Staffing</h3>
              <form id="form-staffing" class="flex flex-wrap gap-2 mb-3">
                <input name="agent_id" class="border rounded p-2 text-sm" placeholder="agent_id" />
                <select name="state" class="border rounded p-2 text-sm"><option>staffed</option><option>aux</option><option>break</option></select>
                <input name="ts" class="border rounded p-2 text-sm" placeholder="ts (epoch)" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-staffing" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Schedules</h3>
              <form id="form-schedules" class="flex flex-wrap gap-2 mb-3">
                <input name="agent_id" class="border rounded p-2 text-sm" placeholder="agent_id" />
                <input name="shift_start_ts" class="border rounded p-2 text-sm" placeholder="shift_start_ts" />
                <input name="shift_end_ts" class="border rounded p-2 text-sm" placeholder="shift_end_ts" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-schedules" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Callbacks</h3>
              <form id="form-callbacks" class="flex flex-wrap gap-2 mb-3">
                <input name="id" class="border rounded p-2 text-sm" placeholder="callback id" />
                <input name="agent_id" class="border rounded p-2 text-sm" placeholder="agent_id" />
                <input name="due_ts" class="border rounded p-2 text-sm" placeholder="due_ts" />
                <input name="completed_ts" class="border rounded p-2 text-sm" placeholder="completed_ts (optional)" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create/Update</button>
              </form>
              <div id="list-callbacks" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Queue Events</h3>
              <form id="form-queue" class="flex flex-wrap gap-2 mb-3">
                <input name="call_id" class="border rounded p-2 text-sm" placeholder="call_id" />
                <input name="queue" class="border rounded p-2 text-sm" placeholder="queue" />
                <select name="event" class="border rounded p-2 text-sm"><option>enqueued</option><option>answered</option><option>abandoned</option></select>
                <input name="ts" class="border rounded p-2 text-sm" placeholder="ts" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-queue" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Dial Results</h3>
              <form id="form-dial" class="flex flex-wrap gap-2 mb-3">
                <input name="call_id" class="border rounded p-2 text-sm" placeholder="call_id" />
                <select name="result" class="border rounded p-2 text-sm"><option>connected</option><option>rna</option><option>busy</option><option>fail</option></select>
                <input name="ts" class="border rounded p-2 text-sm" placeholder="ts" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-dial" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Resolutions</h3>
              <form id="form-reso" class="flex flex-wrap gap-2 mb-3">
                <input name="call_id" class="border rounded p-2 text-sm" placeholder="call_id" />
                <select name="resolved" class="border rounded p-2 text-sm"><option value="1">true</option><option value="0">false</option></select>
                <input name="ts" class="border rounded p-2 text-sm" placeholder="ts" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-reso" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">QA</h3>
              <form id="form-qa" class="flex flex-wrap gap-2 mb-3">
                <input name="call_id" class="border rounded p-2 text-sm" placeholder="call_id" />
                <input name="agent_id" class="border rounded p-2 text-sm" placeholder="agent_id" />
                <input name="score" class="border rounded p-2 text-sm" placeholder="score" />
                <input name="rubric" class="border rounded p-2 text-sm" placeholder='rubric (JSON) {"greeting":5}' />
                <input name="ts" class="border rounded p-2 text-sm" placeholder="ts" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-qa" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Funnel Events</h3>
              <form id="form-funnel" class="flex flex-wrap gap-2 mb-3">
                <input name="lead_id" class="border rounded p-2 text-sm" placeholder="lead_id" />
                <input name="stage" class="border rounded p-2 text-sm" placeholder="stage" />
                <input name="ts" class="border rounded p-2 text-sm" placeholder="ts" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-funnel" class="text-sm"></div>
            </div>

            <div class="p-4 bg-white border rounded">
              <h3 class="font-semibold mb-2">Geo Checks</h3>
              <form id="form-geo" class="flex flex-wrap gap-2 mb-3">
                <input name="call_id" class="border rounded p-2 text-sm" placeholder="call_id" />
                <input name="phone_geo" class="border rounded p-2 text-sm" placeholder="phone_geo" />
                <select name="allowed" class="border rounded p-2 text-sm"><option value="1">true</option><option value="0">false</option></select>
                <input name="ts" class="border rounded p-2 text-sm" placeholder="ts" />
                <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
              </form>
              <div id="list-geo" class="text-sm"></div>
            </div>
          </div>
        </div>
        <div id="view-howto" class="hidden">
          <div class="p-4 bg-white border rounded space-y-4">
            <h2 class="text-lg font-semibold">How To: Seed Data & Webhooks</h2>
            <p class="text-sm">Use webhooks or Admin → Data forms to populate data for reports.</p>
            <h3 class="font-semibold">Webhook Secret</h3>
            <p class="text-sm">Set X-Webhook-Secret to the value in config.php WEBHOOK_SECRET.</p>
            <h3 class="font-semibold">Base URL</h3>
            <p class="text-sm">Base: <code class="bg-slate-100 px-1">https://demo.crm.allcashhomebuyersnetwork.com/api/index.php?route=</code></p>
            <h3 class="font-semibold">Webhooks</h3>
            <ul class="list-disc pl-5 text-sm space-y-2">
              <li><b>/webhooks/call-event</b>: direction, agent_id, campaign_id, contact_phone, handle_time_s, started_at_ts</li>
              <li><b>/webhooks/staffing</b>: agent_id, state (staffed|aux|break), ts</li>
              <li><b>/webhooks/schedule</b>: agent_id, shift_start_ts, shift_end_ts</li>
              <li><b>/webhooks/callback</b>: id, agent_id, due_ts, completed_ts (optional)</li>
              <li><b>/webhooks/queue-event</b>: call_id, queue, event (enqueued|answered|abandoned), ts</li>
              <li><b>/webhooks/dial-result</b>: call_id, result (connected|rna|busy|fail), ts</li>
              <li><b>/webhooks/resolution</b>: call_id, resolved (true|false), ts</li>
              <li><b>/webhooks/qa</b>: call_id, agent_id, score, rubric (JSON), ts</li>
              <li><b>/webhooks/funnel</b>: lead_id, stage (contacted|qualified|appointment|offer|deal), ts</li>
              <li><b>/webhooks/geo-check</b>: call_id, phone_geo, allowed (true|false), ts</li>
            </ul>
            <h3 class="font-semibold">cURL Template</h3>
            <pre class="text-xs bg-slate-50 p-2 rounded overflow-auto">curl -sS -X POST '.../api/index.php?route=/webhooks/call-event' \
 -H 'Content-Type: application/json' \
 -H 'X-Webhook-Secret: YOUR_SECRET' \
 -d '{"direction":"outbound","agent_id":"A1","campaign_id":"C1","contact_phone":"+15551230001","handle_time_s":120,"started_at_ts":1699999999}'
</pre>
            <h3 class="font-semibold">Admin Pages for Manual Entry</h3>
            <ul class="list-disc pl-5 text-sm space-y-1">
              <li>Admin → Data → Calls</li>
              <li>Admin → Data → Staffing</li>
              <li>Admin → Data → Schedules</li>
              <li>Admin → Data → Callbacks</li>
              <li>Admin → Data → Queue Events</li>
              <li>Admin → Data → Dial Results</li>
              <li>Admin → Data → Resolutions</li>
              <li>Admin → Data → QA</li>
              <li>Admin → Data → Funnel Events</li>
              <li>Admin → Data → Geo Checks</li>
            </ul>
            <h3 class="font-semibold">Seeder Script</h3>
            <p class="text-sm">Run: <code class="bg-slate-100 px-1">php tools/webhook_seed.php</code> (uses SEED_BASE and SEED_SECRET from config.php)</p>
          </div>
        </div>
        <div id="view-schedule" class="hidden">
          <div class="p-4 bg-white border rounded">
            <h3 class="font-semibold mb-2">Agent Schedules</h3>
            <form id="form-schedule-new" class="flex flex-wrap gap-2 mb-3">
              <input name="agent_id" class="border rounded p-2 text-sm" placeholder="agent_id" />
              <input name="shift_start_ts" class="border rounded p-2 text-sm" placeholder="shift_start_ts (epoch)" />
              <input name="shift_end_ts" class="border rounded p-2 text-sm" placeholder="shift_end_ts (epoch)" />
              <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Add Shift</button>
            </form>
            <div id="sched-list" class="text-sm"></div>
          </div>
        </div>

        <div id="view-callbacks" class="hidden">
          <div class="p-4 bg-white border rounded">
            <h3 class="font-semibold mb-2">Callback Board</h3>
            <form id="form-callback-new" class="flex flex-wrap gap-2 mb-3">
              <input name="id" class="border rounded p-2 text-sm" placeholder="callback id" />
              <input name="agent_id" class="border rounded p-2 text-sm" placeholder="agent_id" />
              <input name="due_ts" class="border rounded p-2 text-sm" placeholder="due_ts (epoch)" />
              <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Create</button>
            </form>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div><h4 class="font-medium mb-1">Due</h4><div id="cb-due" class="text-sm"></div></div>
              <div><h4 class="font-medium mb-1">Overdue</h4><div id="cb-overdue" class="text-sm"></div></div>
              <div><h4 class="font-medium mb-1">Completed</h4><div id="cb-done" class="text-sm"></div></div>
            </div>
          </div>
        </div>

        <div id="view-qa-rubrics" class="hidden">
          <div class="p-4 bg-white border rounded">
            <h3 class="font-semibold mb-2">QA Rubrics</h3>
            <form id="form-qarubric" class="flex flex-wrap gap-2 mb-3">
              <input name="name" class="border rounded p-2 text-sm" placeholder="rubric name" />
              <input name="rubric" class="border rounded p-2 text-sm" placeholder='rubric JSON {"greeting":5}' />
              <button class="px-3 py-2 text-sm rounded bg-slate-800 text-white">Save</button>
            </form>
            <div id="qa-rubrics-list" class="text-sm"></div>
          </div>
        </div>
        <div id="view-accounts" class="hidden">
          <div class="p-4 bg-white border rounded">
            <h3 class="font-semibold mb-2">Accounts</h3>
            <div id="accounts-table" class="text-sm"></div>
          </div>
        </div>
        <div id="view-payments" class="hidden">
          <div class="p-4 bg-white border rounded">
            <h3 class="font-semibold mb-2">Payments</h3>
            <div id="payments-table" class="text-sm"></div>
          </div>
        </div>
      <div id="view-magic" class="hidden">
        <div class="p-4 bg-white border rounded space-y-3">
          <h3 class="font-semibold">Send Magic Link</h3>
          <div class="flex items-center gap-2">
            <input id="magic-email" class="px-3 py-2 border rounded w-80" placeholder="client email" />
            <input id="magic-exp" type="number" min="1" max="1440" value="15" class="px-3 py-2 border rounded w-24" title="Expiry in minutes" />
            <button id="btn-send-magic" class="px-3 py-2 bg-blue-600 text-white rounded">Send</button>
          </div>
          <div id="magic-result" class="text-sm text-slate-600"></div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    let token = sessionStorage.getItem('token') || localStorage.getItem('token') || '';
    function setToken(t){
      token = t || '';
      sessionStorage.setItem('token', token);
      try { localStorage.setItem('token', token); } catch(_){}
    }
    function api(path){ return '/api/index.php?route=' + encodeURIComponent(path); }
    function formatEST(input){
      if(input===undefined||input===null||input==='') return '';
      let d=null;
      if(typeof input==='number'){
        const ms = input < 1e12 ? input*1000 : input; d=new Date(ms);
      } else if(typeof input==='string'){
        const s=input.trim();
        if(/^\d+$/.test(s)){ const n=parseInt(s,10); const ms=n<1e12?n*1000:n; d=new Date(ms); }
        else { const t=Date.parse(s); if(!isNaN(t)) d=new Date(t); }
      } else if(input instanceof Date){ d=input; }
      if(!d||isNaN(d.getTime())) return String(input);
      try{ return new Intl.DateTimeFormat('en-US',{ timeZone:'America/New_York', year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit' }).format(d); }
      catch(_){ return d.toLocaleString('en-US',{ timeZone:'America/New_York' }); }
    }
    const views = Array.from(document.querySelectorAll('[id^="view-"]'));

    // Extract reports loader into a function to avoid recursive event dispatch
    async function loadReportsView(){
      const SINCE = (document.getElementById('rep-since')?.value || '30');
      try {
        const a = await fetchJSON(api('/reports/agent-performance') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-agent').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Calls</th><th class=\"text-left p-2\">Avg AHT</th></tr></thead><tbody>${a.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.calls}</td><td class=\"p-2\">${i.avg_handle_time_s}s</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-agent').textContent = 'No data'; }
      try {
        const al = await fetchJSON(api('/reports/agent-leaderboard') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-agent-leader').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Calls</th><th class=\"text-left p-2\">Avg AHT</th><th class=\"text-left p-2\">P50</th><th class=\"text-left p-2\">P90</th></tr></thead><tbody>${al.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.calls}</td><td class=\"p-2\">${i.avg_handle_time_s}s</td><td class=\"p-2\">${i.p50_handle_time_s}s</td><td class=\"p-2\">${i.p90_handle_time_s}s</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-agent-leader').textContent = 'No data'; }
      try {
        const c = await fetchJSON(api('/reports/campaign-effectiveness') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-campaign').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Campaign</th><th class=\"text-left p-2\">Calls</th></tr></thead><tbody>${c.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.campaign_id}</td><td class=\"p-2\">${i.calls}</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-campaign').textContent = 'No data'; }
      try {
        const s = await fetchJSON(api('/reports/sla'));
        document.getElementById('report-sla').innerHTML = `<div class=\"text-sm\">24h: ${s.last24h_calls} calls, Avg AHT: ${s.avg_handle_time_s}s</div>`;
      } catch(_) { document.getElementById('report-sla').textContent = 'No data'; }
      try {
        const as = await fetchJSON(api('/reports/agent-summary') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-agent-summary').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Calls</th><th class=\"text-left p-2\">Avg AHT</th><th class=\"text-left p-2\">DNC hits</th></tr></thead><tbody>${as.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.calls}</td><td class=\"p-2\">${i.avg_handle_time_s}s</td><td class=\"p-2\">${i.dnc_hits}</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-agent-summary').textContent = 'No data'; }
      try {
        const dv = await fetchJSON(api('/reports/daily-volume') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-daily-volume').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Date</th><th class=\"text-left p-2\">Calls</th><th class=\"text-left p-2\">Avg AHT</th></tr></thead><tbody>${dv.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.date}</td><td class=\"p-2\">${i.calls}</td><td class=\"p-2\">${i.avg_handle_time_s}s</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-daily-volume').textContent = 'No data'; }
      try {
        const sp = await fetchJSON(api('/reports/script-performance') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-script-perf').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Script</th><th class=\"text-left p-2\">Leads</th></tr></thead><tbody>${sp.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.slug||'(unknown)'}</td><td class=\"p-2\">${i.leads}</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-script-perf').textContent = 'No data'; }
      try {
        const se = await fetchJSON(api('/reports/script-effectiveness') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-script-eff').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Script</th><th class=\"text-left p-2\">Responses</th><th class=\"text-left p-2\">Leads</th><th class=\"text-left p-2\">Conv%</th></tr></thead><tbody>${se.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.slug||'(unknown)'}</td><td class=\"p-2\">${i.responses}</td><td class=\"p-2\">${i.leads}</td><td class=\"p-2\">${i.conversion_pct}%</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-script-eff').textContent = 'No data'; }
      try {
        const dn = await fetchJSON(api('/reports/dnc-impact') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-dnc').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">DNC</th><th class=\"text-left p-2\">Calls</th><th class=\"text-left p-2\">Avg AHT</th></tr></thead><tbody>${dn.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.is_dnc ? 'True' : 'False'}</td><td class=\"p-2\">${i.calls}</td><td class=\"p-2\">${i.avg_handle_time_s}s</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-dnc').textContent = 'No data'; }
      try {
        const dnt = await fetchJSON(api('/reports/dnc-trend') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-dnc-trend').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Date</th><th class=\"text-left p-2\">DNC</th><th class=\"text-left p-2\">Calls</th></tr></thead><tbody>${dnt.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.date}</td><td class=\"p-2\">${i.is_dnc ? 'True' : 'False'}</td><td class=\"p-2\">${i.calls}</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-dnc-trend').textContent = 'No data'; }
      try {
        const cp = await fetchJSON(api('/reports/campaign-pacing') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-campaign-pacing').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Campaign</th><th class=\"text-left p-2\">Date</th><th class=\"text-left p-2\">Calls</th><th class=\"text-left p-2\">Goal/Day</th><th class=\"text-left p-2\">Achieved%</th></tr></thead><tbody>${cp.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.campaign_id}</td><td class=\"p-2\">${i.date}</td><td class=\"p-2\">${i.calls}</td><td class=\"p-2\">${i.goal_per_day ?? ''}</td><td class=\"p-2\">${i.achieved_pct ?? ''}</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-campaign-pacing').textContent = 'No data'; }
      try {
        const da = await fetchJSON(api('/reports/disposition-analysis') + `&since_days=${encodeURIComponent(SINCE)}`);
        document.getElementById('report-disposition').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Value</th><th class=\"text-left p-2\">Count</th></tr></thead><tbody>${da.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.value}</td><td class=\"p-2\">${i.count}</td></tr>`).join('')}</tbody></table>`;
      } catch(_) { document.getElementById('report-disposition').textContent = 'No data'; }
      // Billing card (create checkout from here as admin shortcut)
      try {
        const token = sessionStorage.getItem('token')||'';
        const accs = await fetchJSON(api('/admin/accounts'));
        const wrap = document.getElementById('report-billing');
        if (wrap) {
          let html = '';
          html += '<div class="flex flex-wrap items-center gap-2 mb-2">';
          html += '<select id="rb-acc" class="border rounded p-2 text-sm">'+accs.items.map(a=>`<option value="${a.id}">${a.name} (${a.email})</option>`).join('')+'</select>';
          html += '<input id="rb-amt" type="number" class="border rounded p-2 w-28 text-sm" placeholder="$ Amount" min="1" />';
          html += '<button id="rb-go" class="px-3 py-2 text-sm rounded bg-emerald-700 text-white">Checkout</button>';
          html += '</div>';
          html += '<div class="text-[11px] text-slate-500">Creates a Stripe Checkout session for selected account.</div>';
          wrap.innerHTML = html;
          document.getElementById('rb-go').onclick = async ()=>{
            const account_id = (document.getElementById('rb-acc')).value;
            const dollars = parseFloat((document.getElementById('rb-amt')).value||'0');
            if (!(dollars>0)) { alert('Enter amount'); return; }
            const res = await fetchJSON(api('/payments/checkout'), { method:'POST', body: JSON.stringify({ account_id, amount_cents: Math.round(dollars*100) }) });
            if (res.url) window.location.href = res.url;
          };
        }
      } catch(_) { const wb = document.getElementById('report-billing'); if (wb) wb.textContent = 'Billing unavailable'; }
      try {
        const hm = await fetchJSON(api('/reports/hourly-heatmap?since_days=7'));
        const grid = Array.from({length:7}, ()=>Array.from({length:24}, ()=>0));
        hm.items.forEach(it=>{ const r=(it.dow||1)-1; const c=it.hour||0; if (grid[r] && grid[r][c] !== undefined) grid[r][c]=it.calls; });
        const days=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        const table = `<table class=\"w-full text-xs\"><thead><tr><th></th>${Array.from({length:24},(_,h)=>`<th class=\"p-1\">${h}</th>`).join('')}</tr></thead><tbody>`+
          grid.map((row,r)=>`<tr><td class=\"p-1\">${days[r]}</td>${row.map(v=>`<td class=\"p-1 text-center\">${v||''}</td>`).join('')}</tr>`).join('')+
          `</tbody></table>`;
        document.getElementById('report-heatmap').innerHTML = table;
      } catch(_) { document.getElementById('report-heatmap').textContent = 'No data'; }

      // Restore additional reports
      try { const sl = await fetchJSON(api('/reports/service-level') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-sl').innerHTML = `<div>Served: ${sl.served}, Within ${sl.threshold_s}s: ${sl.within_threshold}, SL: ${sl.service_level_pct}% | Abandoned: ${sl.abandoned}</div>`; } catch(_){ const x=document.getElementById('report-sl'); if(x) x.textContent='No data'; }
      try { const ar = await fetchJSON(api('/reports/abandon-rate') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-abandon').innerHTML = `<div>Abandon rate: ${ar.abandon_rate_pct}% (Abandoned ${ar.abandoned} of ${ar.abandoned+ar.served})</div>`; } catch(_){ const x=document.getElementById('report-abandon'); if(x) x.textContent='No data'; }
      try { const rr = await fetchJSON(api('/reports/rna-rate') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-rna').innerHTML = `<div>RNA rate: ${rr.rna_rate_pct}% (RNA ${rr.rna} of ${rr.attempts})</div>`; } catch(_){ const x=document.getElementById('report-rna'); if(x) x.textContent='No data'; }
      try { const oc = await fetchJSON(api('/reports/occupancy') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-occupancy').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Talk (s)</th><th class=\"text-left p-2\">Staffed (s)</th><th class=\"text-left p-2\">Occupancy%</th></tr></thead><tbody>${(oc.items||[]).map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.talk_s}</td><td class=\"p-2\">${i.staffed_s}</td><td class=\"p-2\">${i.occupancy_pct ?? ''}</td></tr>`).join('')}</tbody></table>`; } catch(_){ const x=document.getElementById('report-occupancy'); if(x) x.textContent='No data'; }
      try { const wt = await fetchJSON(api('/reports/wait-time-distribution') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-waits').innerHTML = `<div>P50: ${wt.p50_s}s, P90: ${wt.p90_s}s (n=${wt.count})</div>`; } catch(_){ const x=document.getElementById('report-waits'); if(x) x.textContent='No data'; }
      try { const ad = await fetchJSON(api('/reports/schedule-adherence') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-adherence').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Scheduled (s)</th><th class=\"text-left p-2\">Staffed (s)</th><th class=\"text-left p-2\">Adherence%</th></tr></thead><tbody>${(ad.items||[]).map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.scheduled_s}</td><td class=\"p-2\">${i.staffed_s}</td><td class=\"p-2\">${i.adherence_pct ?? ''}</td></tr>`).join('')}</tbody></table>`; } catch(_){ const x=document.getElementById('report-adherence'); if(x) x.textContent='No data'; }
      try { const cb = await fetchJSON(api('/reports/callback-compliance') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-callback').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Due</th><th class=\"text-left p-2\">Completed</th><th class=\"text-left p-2\">On-time%</th></tr></thead><tbody>${(cb.items||[]).map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.due}</td><td class=\"p-2\">${i.completed}</td><td class=\"p-2\">${i.on_time_pct}</td></tr>`).join('')}</tbody></table>`; } catch(_){ const x=document.getElementById('report-callback'); if(x) x.textContent='No data'; }
      try { const fcr = await fetchJSON(api('/reports/fcr') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-fcr').innerHTML = `<div>FCR: ${fcr.items?.[0]?.pct ?? 0}% (Resolved ${fcr.items?.[0]?.resolved ?? 0} of ${fcr.items?.[0]?.total ?? 0})</div>`; } catch(_){ const x=document.getElementById('report-fcr'); if(x) x.textContent='No data'; }
      try { const qa = await fetchJSON(api('/reports/qa-scorecards') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-qa').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Avg Score</th><th class=\"text-left p-2\">Count</th></tr></thead><tbody>${(qa.items||[]).map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.avg_score}</td><td class=\"p-2\">${i.count}</td></tr>`).join('')}</tbody></table>`; } catch(_){ const x=document.getElementById('report-qa'); if(x) x.textContent='No data'; }
      try { const fn = await fetchJSON(api('/reports/funnel') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-funnel').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Stage</th><th class=\"text-left p-2\">Count</th></tr></thead><tbody>${(fn.items||[]).map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.stage}</td><td class=\"p-2\">${i.count}</td></tr>`).join('')}</tbody></table>`; } catch(_){ const x=document.getElementById('report-funnel'); if(x) x.textContent='No data'; }
      try { const gc = await fetchJSON(api('/reports/geo-compliance') + `&since_days=${encodeURIComponent(SINCE)}`); document.getElementById('report-geo').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Allowed</th><th class=\"text-left p-2\">Count</th></tr></thead><tbody>${(gc.items||[]).map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.allowed?'Yes':'No'}</td><td class=\"p-2\">${i.count}</td></tr>`).join('')}</tbody></table>`; } catch(_){ const x=document.getElementById('report-geo'); if(x) x.textContent='No data'; }

      // Mobile responsiveness: enable horizontal scroll and compact tables
      try {
        document.querySelectorAll('#view-reports [id^="report-"]').forEach(el=>{
          el.classList.add('overflow-x-auto');
          el.querySelectorAll('table').forEach(t=>{ t.classList.add('min-w-[640px]','text-xs','sm:text-sm'); });
        });
      } catch(_){ }
    }

    function onViewShown(view){
      try {
        if (view === 'reports') {
          loadReportsView();
        }
        if (view === 'scripts') {
          try { loadScripts(); } catch(_){ }
        }
      } catch(_) { }
    }

    document.querySelectorAll('a[data-view]').forEach(a => {
      a.addEventListener('click', e => {
        e.preventDefault();
        const view = a.getAttribute('data-view');
        document.getElementById('title').textContent = view.charAt(0).toUpperCase() + view.slice(1);
        views.forEach(v => v.classList.toggle('hidden', v.id !== 'view-' + view));
        onViewShown(view);
      });
    });

    function show(view){ views.forEach(v => v.classList.toggle('hidden', v.id !== 'view-' + view)); onViewShown(view); }

    function getQueryParam(name){
      const params = new URLSearchParams(location.search);
      return params.get(name) || '';
    }
    const initialView = (getQueryParam('view') || '').toLowerCase();
    const redirectUrl = getQueryParam('redirect');
    if (!token) { show(initialView || 'login'); } else { show(initialView || 'dashboard'); if (redirectUrl) { try { window.location.href = redirectUrl; } catch(_){} } }
    // If user deep-linked directly to Scripts/Reports view, ensure lists/datalists are hydrated
    if ((initialView || '') === 'scripts') { try { loadScripts(); } catch(_){} }
    if ((initialView || '') === 'reports') { try { const nav = document.querySelector('[data-view="reports"]'); if (nav) nav.dispatchEvent(new Event('click')); } catch(_){} }
    hydrateAccess();

    async function fetchJSON(url, opts) {
      const headers = { 'Content-Type': 'application/json' };
      if (token) headers['Authorization'] = 'Bearer ' + token;
      const r = await fetch(url, Object.assign({ headers }, opts || {}));
      if (!r.ok) {
        if (r.status === 401) {
          setToken('');
          show('login');
          throw new Error('Unauthorized');
        }
        throw new Error('Request failed');
      }
      return await r.json();
    }

    // RBAC view control
    let allowedViews = null; let currentRole = 'agent';
    async function hydrateAccess(){
      try {
        const me = await fetchJSON(api('/me'));
        currentRole = me.role || 'agent';
      } catch(_) { currentRole = 'agent'; }
      try {
        const s = await fetchJSON(api('/settings'));
        allowedViews = (s.rbac_allowed_views||{})[currentRole] || null;
      } catch(_) { allowedViews = null; }
      if (Array.isArray(allowedViews)){
        document.querySelectorAll('[data-view]').forEach(a=>{
          const v = a.getAttribute('data-view');
          const ok = allowedViews.includes(v);
          // Hide nav items the role cannot see
          a.classList.toggle('hidden', !ok);
          // Only force-hide disallowed panels; do NOT force-show allowed ones
          const panel = document.getElementById('view-'+v);
          if (panel && !ok) panel.classList.add('hidden');
        });
        // Re-assert the current view after RBAC adjustments so only one is visible
        const currentTitle = (document.getElementById('title')?.textContent || 'dashboard').toLowerCase();
        const currentView = currentTitle.split(' ')[0];
        show(currentView || 'dashboard');
      }
    }

    // Settings load/save (RBAC)
    async function loadSettings(){
      const ROLES = ['admin','supervisor','agent'];
      const VIEWS_BASE = ['dashboard','contacts','leads','agents','scripts','campaigns','calls','dnc','reports','data','schedule','callbacks','qa-rubrics','howto','settings','geo','suppression','billing','accounts','payments','payments-admin','subscriptions-admin'];
      let data = { jwt_issuer:'', cors_allowed_origins:[], rbac_allowed_views:{} };
      try { data = await fetchJSON(api('/settings')); } catch(_){ }
      const issuerEl = document.getElementById('settings-issuer'); if (issuerEl) issuerEl.value = data.jwt_issuer || '';
      const originsEl = document.getElementById('settings-origins'); if (originsEl) originsEl.value = (data.cors_allowed_origins||[]).join(',');
      const wrap = document.getElementById('rbac-matrix'); if (!wrap) return; wrap.innerHTML='';
      // Build dynamic views list = baseline union saved RBAC views
      const viewSet = new Set(VIEWS_BASE);
      const rbac = (data.rbac_allowed_views||{});
      Object.keys(rbac).forEach(role=>{
        (Array.isArray(rbac[role])?rbac[role]:[]).forEach(v=>{ if (v && typeof v === 'string') viewSet.add(v); });
      });
      const VIEWS_RENDER = Array.from(viewSet);
      ROLES.forEach(role=>{
        const row = document.createElement('div'); row.className='flex items-center gap-3 flex-wrap';
        const title = document.createElement('div'); title.className='w-28 font-medium'; title.textContent = role; row.appendChild(title);
        VIEWS_RENDER.forEach(view=>{
          const label = document.createElement('label'); label.className='inline-flex items-center gap-1';
          const cb = document.createElement('input'); cb.type='checkbox'; cb.setAttribute('data-rbac-role', role); cb.setAttribute('data-rbac-view', view);
          const allowed = (data.rbac_allowed_views||{})[role] || [];
          if (allowed.includes(view)) cb.checked = true;
          label.appendChild(cb); label.appendChild(document.createTextNode(' '+view));
          row.appendChild(label);
        });
        wrap.appendChild(row);
      });

      document.getElementById('btn-save-settings')?.addEventListener('click', async ()=>{
        const issuer = document.getElementById('settings-issuer').value.trim();
        const origins = (document.getElementById('settings-origins').value||'').split(',').map(s=>s.trim()).filter(Boolean);
        const allowed = { admin:[], supervisor:[], agent:[] };
        wrap.querySelectorAll('[data-rbac-role]')?.forEach(cb=>{
          const role = cb.getAttribute('data-rbac-role');
          const view = cb.getAttribute('data-rbac-view');
          if (cb.checked) allowed[role].push(view);
        });
        const geoName = (document.getElementById('geo-list-name')?.value||'').trim();
        const geoZips = (document.getElementById('geo-preview')?.textContent||'').split(/\s+/).map(s=>s.trim()).filter(Boolean);
        const suppName = (document.getElementById('supp-list-name')?.value||'').trim();
        const suppPhones = (document.getElementById('supp-preview')?.textContent||'').split(/\s+/).map(s=>s.trim()).filter(Boolean);
        const payload = { jwt_issuer: issuer, cors_allowed_origins: origins, rbac_allowed_views: allowed };
        if (geoZips.length) payload.geo_lists = [{ name: geoName || 'default', zips: geoZips }];
        if (suppPhones.length) payload.suppression_lists = [{ name: suppName || 'default', phones: suppPhones }];
        await fetchJSON(api('/settings'), { method:'POST', body: JSON.stringify(payload) });
        alert('Settings saved');
        hydrateAccess();
      }, { once:true });
    }

    async function loadDashboard() {
      try {
        const m = await fetchJSON(api('/metrics'));
        document.getElementById('metric-inbound').textContent = m.inbound_today;
        document.getElementById('metric-outbound').textContent = m.outbound_today;
        document.getElementById('metric-aht').textContent = m.avg_handle_time_s + 's';
      } catch (e) {}
    }

    // Simple list loaders for UI (minimal demo)
    async function loadContacts(){
      const res = await fetchJSON(api('/contacts') + '&limit=20');
      document.getElementById('contacts-list').innerHTML = `<table class="w-full text-sm"><thead><tr><th class="text-left p-2">Name</th><th class="text-left p-2">Phone</th></tr></thead><tbody>${res.items.map(i=>`<tr class="border-t"><td class="p-2">${i.name}</td><td class="p-2">${i.phone}</td></tr>`).join('')}</tbody></table>`;
    }
    async function loadAgents(){
      const res = await fetchJSON(api('/agents'));
      document.getElementById('agents-list').innerHTML = `<table class="w-full text-sm"><thead><tr><th class="text-left p-2">Name</th><th class="text-left p-2">Email</th><th class="text-left p-2">Role</th></tr></thead><tbody>${res.items.map(i=>`<tr class="border-t"><td class="p-2">${i.name}</td><td class="p-2">${i.email}</td><td class="p-2">${i.role}</td></tr>`).join('')}</tbody></table>`;
    }
    async function loadCampaigns(){
      const res = await fetchJSON(api('/campaigns'));
      document.getElementById('campaigns-list').innerHTML = `<table class="w-full text-sm"><thead><tr><th class="text-left p-2">Name</th><th class="text-left p-2">Status</th></tr></thead><tbody>${res.items.map(i=>`<tr class="border-t"><td class="p-2">${i.name}</td><td class="p-2">${i.status}</td></tr>`).join('')}</tbody></table>`;
    }
    async function loadCalls(){
      const dir = document.getElementById('filter-direction').value;
      const url = api('/calls') + '&limit=50' + (dir?('&direction='+encodeURIComponent(dir)):'');
      const res = await fetchJSON(url);
      document.getElementById('calls-list').innerHTML = `<table class="w-full text-sm"><thead><tr><th class="text-left p-2">When</th><th class="text-left p-2">Dir</th><th class="text-left p-2">Phone</th><th class="text-left p-2">AHT</th><th class="text-left p-2">DNC</th></tr></thead><tbody>${res.items.map(i=>`<tr class="border-t"><td class="p-2">${i.started_at||''}</td><td class="p-2">${i.direction}</td><td class="p-2">${i.contact_phone}</td><td class="p-2">${i.handle_time_s}s</td><td class="p-2">${i.is_dnc? 'Yes':'No'}</td></tr>`).join('')}</tbody></table>`;
    }
    async function loadDnc(){
      const res = await fetchJSON(api('/dnc') + '&limit=50');
      document.getElementById('dnc-list').innerHTML = `<table class="w-full text-sm"><thead><tr><th class="text-left p-2">Phone</th><th class="text-left p-2">Added</th></tr></thead><tbody>${res.items.map(i=>`<tr class="border-t"><td class="p-2">${i.phone}</td><td class="p-2">${i.created_at||''}</td></tr>`).join('')}</tbody></table>`;
    }
    document.querySelector('[data-view="contacts"]').addEventListener('click', loadContacts);
    document.querySelector('[data-view="agents"]').addEventListener('click', loadAgents);
    document.querySelector('[data-view="campaigns"]').addEventListener('click', loadCampaigns);
    document.querySelector('[data-view="calls"]').addEventListener('click', ()=>{ loadCalls().catch(()=>{}); });
    document.getElementById('filter-direction').addEventListener('change', ()=>{ loadCalls().catch(()=>{}); });
    document.querySelector('[data-view="dnc"]').addEventListener('click', loadDnc);
    document.getElementById('btn-add-dnc').addEventListener('click', async ()=>{
      const phone = (document.getElementById('dnc-phone').value||'').trim();
      if (!phone) { alert('Enter a phone number'); return; }
      try {
        await fetchJSON(api('/dnc'), { method:'POST', body: JSON.stringify({ phone }) });
        alert('Added to DNC');
        await loadDnc();
      } catch(e) { alert('Failed to add to DNC'); }
    });
    document.querySelector('[data-view="scripts"]').addEventListener('click', loadScripts);
    document.querySelector('[data-view="settings"]').addEventListener('click', ()=>{ loadSettings().catch(()=>{}); });
    document.querySelector('[data-view="magic"]').addEventListener('click', ()=>{ show('magic'); });
    // Billing/Accounts/Payments simple loaders
    document.querySelector('[data-view="billing"]').addEventListener('click', ()=>{ show('reports');
      const nav = document.querySelector('[data-view="reports"]'); if (nav) nav.dispatchEvent(new Event('click')); });
    document.querySelector('[data-view="accounts"]').addEventListener('click', async ()=>{
      show('accounts');
      try{
        const res = await fetchJSON(api('/admin/accounts'));
        const rows = res.items.map(a=>`<tr class=\"border-t\"><td class=\"p-2\">${a.name}</td><td class=\"p-2\">${a.email}</td><td class=\"p-2\">${a.type}</td><td class=\"p-2\">$${(a.balance_cents/100).toFixed(2)}</td></tr>`).join('');
        document.getElementById('accounts-table').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Name</th><th class=\"text-left p-2\">Email</th><th class=\"text-left p-2\">Type</th><th class=\"text-left p-2\">Balance</th></tr></thead><tbody>${rows}</tbody></table>`;
      }catch(_){ document.getElementById('accounts-table').textContent = 'Failed to load accounts'; }
    });
    document.querySelector('[data-view="payments"]').addEventListener('click', async ()=>{
      show('payments');
      try{
        // Prefer live Stripe data; fallback to DB
        let res = null; let okStripe = true;
        try { res = await fetchJSON(api('/admin/payments/stripe')); }
        catch(_){ okStripe = false; res = await fetchJSON(api('/admin/payments')); }
        const rows = (res.items||[]).map(p=>`<tr class=\"border-t\"><td class=\"p-2\">$${(p.amount_cents/100).toFixed(2)}</td><td class=\"p-2\">${p.currency}</td><td class=\"p-2\">${p.account_id}</td><td class=\"p-2\">${formatEST(p.ts)||''}</td></tr>`).join('');
        document.getElementById('payments-table').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Amount</th><th class=\"text-left p-2\">Currency</th><th class=\"text-left p-2\">Account</th><th class=\"text-left p-2\">When</th></tr></thead><tbody>${rows}</tbody></table>`;
      }catch(_){ document.getElementById('payments-table').textContent = 'Failed to load payments'; }
    });
    // Open Payments Admin external tool in a new tab without switching SPA view
    const pa = document.querySelector('[data-view="payments-admin"]');
    if (pa) pa.addEventListener('click', (e)=>{ e.preventDefault(); window.open('/payments_admin.php','_blank'); });
    const sa = document.querySelector('[data-view="subscriptions-admin"]');
    if (sa) sa.addEventListener('click', (e)=>{ e.preventDefault(); window.open('/subscriptions_admin.php','_blank'); });
    document.querySelector('[data-view="geo"]').addEventListener('click', ()=>{ show('geo'); renderGeoSuppLists(); });
    document.querySelector('[data-view="suppression"]').addEventListener('click', ()=>{ show('suppression'); renderGeoSuppLists(); });
    document.getElementById('btn-new-campaign')?.addEventListener('click', async ()=>{
      const name = (prompt('Campaign name')||'').trim();
      if (!name) return;
      const description = (prompt('Description (optional)')||'').trim();
      const status = (prompt('Status (draft/active/paused/completed)','draft')||'draft').trim();
      try {
        await fetchJSON(api('/campaigns'), { method:'POST', body: JSON.stringify({ name, description, status }) });
        await loadCampaigns();
        alert('Campaign created');
      } catch(_) { alert('Failed to create campaign'); }
    });

    async function loadLeads(){
      const q = document.getElementById('search-leads').value.trim();
      const url = api('/leads') + '&limit=50' + (q?('&q='+encodeURIComponent(q)):'' );
      const res = await fetchJSON(url);
      document.getElementById('leads-list').innerHTML = `<table class="w-full text-sm"><thead><tr><th class="text-left p-2">When</th><th class="text-left p-2">Agent</th><th class="text-left p-2">Customer</th><th class="text-left p-2">Phone</th><th class="text-left p-2">City</th><th class="text-left p-2">State</th><th class="text-left p-2">Disposition</th></tr></thead><tbody>${res.items.map(i=>`<tr class="border-t"><td class="p-2">${i.created_at||''}</td><td class="p-2">${i.agent_name}</td><td class="p-2">${i.customer_name}</td><td class="p-2">${i.customer_phone}</td><td class="p-2">${i.property_city}</td><td class="p-2">${i.property_state}</td><td class="p-2">${i.disposition}</td></tr>`).join('')}</tbody></table>`;
    }
    document.querySelector('[data-view="leads"]').addEventListener('click', loadLeads);
    document.querySelector('[data-view="reports"]').addEventListener('click', loadReportsView);

    document.getElementById('btn-refresh-leads').addEventListener('click', loadLeads);

    document.getElementById('btn-login').addEventListener('click', async () => {
      const email = document.getElementById('login-email').value.trim();
      const password = document.getElementById('login-password').value;
      const err = document.getElementById('login-error');
      err.classList.add('hidden');
      try {
        const res = await fetchJSON(api('/login'), { method: 'POST', body: JSON.stringify({ email, password }) });
        setToken(res.access_token);
        // Re-hydrate role-based view access immediately after login
        try { await hydrateAccess(); } catch(_){}
        if (redirectUrl) { window.location.href = redirectUrl; return; }
        show('dashboard');
        loadDashboard();
      } catch (e) {
        err.textContent = 'Login failed';
        err.classList.remove('hidden');
      }
    });

    document.getElementById('btn-logout').addEventListener('click', async (e) => {
      e.preventDefault();
      setToken('');
      const titleEl = document.getElementById('title'); if (titleEl) titleEl.textContent = 'Login';
      try { await hydrateAccess(); } catch(_){}
      show('login');
    });

    document.getElementById('btn-send-magic')?.addEventListener('click', async ()=>{
      const email = (document.getElementById('magic-email').value||'').trim();
      if(!email){ alert('Enter email'); return; }
      const mins = Math.max(1, Math.min(1440, parseInt(document.getElementById('magic-exp').value||'15',10)));
      try{
        const res = await fetchJSON(api('/client/magic/start'), { method:'POST', body: JSON.stringify({ email, expires_minutes: mins }) });
        const tgt = document.getElementById('magic-result');
        tgt.textContent = (res.emailed ? 'Magic link emailed.' : ('Magic link: '+res.link)) + ` (expires in ${res.expires_minutes||mins} min)`;
      }catch(_){ alert('Failed to send link'); }
    });

    // Invite Agent (quick prompt-based)
    document.getElementById('btn-new-agent')?.addEventListener('click', async ()=>{
      const name = (prompt('Agent full name')||'').trim();
      const email = (prompt('Agent email')||'').trim().toLowerCase();
      if (!email) { alert('Email is required'); return; }
      const role = (prompt('Role (admin/supervisor/agent)','agent')||'agent').trim();
      const password = (prompt('Temporary password (leave blank to auto-generate)','')||'');
      try {
        await fetchJSON(api('/agents'), { method:'POST', body: JSON.stringify({ name, email, role, password }) });
        alert('Agent invited');
        await loadAgents();
      } catch (e) {
        alert('Failed to invite agent');
      }
    });

    async function loadScripts(){
      const res = await fetchJSON(api('/scripts'));
      document.getElementById('scripts-list').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Slug</th><th class=\"text-left p-2\">Title</th><th class=\"text-left p-2\">Ver</th><th class=\"text-left p-2\">Published</th><th class=\"text-left p-2\">Actions</th></tr></thead><tbody>${res.items.map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.slug}</td><td class=\"p-2\">${i.title}</td><td class=\"p-2\">${i.version||1}</td><td class=\"p-2\">${i.published?'Yes':'No'}</td><td class=\"p-2\"><button data-load-slug=\"${i.slug}\" class=\"px-2 py-1 text-xs rounded bg-blue-600 text-white\">Load</button> <button data-view-slug=\"${i.slug}\" class=\"px-2 py-1 text-xs rounded bg-slate-700 text-white\">View</button> <button data-pub-id=\"${i.id}\" data-pub=${i.published?'0':'1'} class=\"px-2 py-1 text-xs rounded ${i.published?'bg-slate-600':'bg-emerald-600'} text-white\">${i.published?'Unpublish':'Publish'}</button></td></tr>`).join('')}</tbody></table>`;
      document.querySelectorAll('[data-load-slug]').forEach(b=>b.addEventListener('click', async ()=>{
        document.getElementById('script-slug').value = b.getAttribute('data-load-slug')||'';
        await document.getElementById('btn-load-by-slug').click();
      }));
      document.querySelectorAll('[data-pub-id]').forEach(b=>b.addEventListener('click', async ()=>{
        const id = b.getAttribute('data-pub-id');
        const pub = b.getAttribute('data-pub')==='1';
        await fetchJSON(api('/scripts/'+id+'/publish'), { method:'POST', body: JSON.stringify({ published: pub }) });
        await loadScripts();
      }));
      // View button: open script in new window
      document.querySelectorAll('[data-view-slug]').forEach(b=>b.addEventListener('click', (e)=>{
        e.preventDefault();
        const slug = b.getAttribute('data-view-slug');
        if (!slug) return;
        const url = '/scripts/' + encodeURIComponent(slug);
        try { window.open(url, '_blank'); } catch(_){ window.location.href = url; }
      }));
      // Populate datalists from settings
      try {
        const s = await fetchJSON(api('/settings'));
        const geo = Array.isArray(s.geo_lists)? s.geo_lists.map(x=>x.name).filter(Boolean): [];
        const supp = Array.isArray(s.suppression_lists)? s.suppression_lists.map(x=>x.name).filter(Boolean): [];
        const geoDL = document.getElementById('geo-list-names'); if (geoDL) geoDL.innerHTML = geo.map(n=>`<option value="${n}"></option>`).join('');
        const suppDL = document.getElementById('supp-list-names'); if (suppDL) suppDL.innerHTML = supp.map(n=>`<option value="${n}"></option>`).join('');
      } catch(_){ }
    }

    let dragContext = null;
    let builderHistory = [];
    let builderFuture = [];
    function applySections(newData, record=true){
      if (record) { try { builderHistory.push(currentSections()); builderFuture = []; } catch(_){} }
      renderSections(newData);
    }
    function resetHistory(){ builderHistory = []; builderFuture = []; try { builderHistory.push(currentSections()); } catch(_){} }
    function doUndo(){ if (!builderHistory.length) return; const current = currentSections(); const prev = builderHistory.pop(); builderFuture.push(current); renderSections(prev); }
    function doRedo(){ if (!builderFuture.length) return; const current = currentSections(); const next = builderFuture.pop(); builderHistory.push(current); renderSections(next); }
    function getType(el){ return el.hasAttribute('data-builder-question') ? 'question' : 'section'; }
    function clearSelectionIn(container, type){
      container.querySelectorAll(`[data-builder-${type}][data-selected="1"]`).forEach(n=>{ n.removeAttribute('data-selected'); n.classList.remove('ring-2','ring-blue-500'); });
    }
    function toggleSelect(node, multi, type){
      const parent = type==='section' ? document.getElementById('sections') : node.parentElement;
      if (!multi) clearSelectionIn(parent, type);
      if (node.hasAttribute('data-selected')) { node.removeAttribute('data-selected'); node.classList.remove('ring-2','ring-blue-500'); }
      else { node.setAttribute('data-selected','1'); node.classList.add('ring-2','ring-blue-500'); }
    }
    function makeDraggable(el){
      // Drag is only allowed when user grabs the explicit handle (for sections). For questions, allow anywhere.
      const handle = el.querySelector('[data-drag-handle]');
      const isQuestionNode = el.hasAttribute('data-builder-question');
      const enableDrag = ()=>{ el.setAttribute('draggable','true'); el.setAttribute('data-allow-drag','1'); };
      const disableDrag = ()=>{ el.removeAttribute('data-allow-drag'); if (!isQuestionNode) el.removeAttribute('draggable'); };
      if (handle){
        handle.addEventListener('pointerdown', (e)=>{ e.stopPropagation(); enableDrag(); });
        handle.addEventListener('pointerup', disableDrag);
        // Fallbacks for browsers without Pointer Events
        handle.addEventListener('mousedown', (e)=>{ e.stopPropagation(); enableDrag(); });
        handle.addEventListener('mouseup', disableDrag);
        handle.addEventListener('touchstart', (e)=>{ enableDrag(); });
        handle.addEventListener('touchend', disableDrag);
      } else {
        el.setAttribute('draggable','true'); // fallback
      }
      el.addEventListener('dragstart', (e)=>{
        const isQuestion = el.hasAttribute('data-builder-question');
        if (isQuestion) {
          // Allow drag from anywhere within the question block
          enableDrag();
        } else {
        if (el.getAttribute('data-allow-drag') !== '1' && handle) { e.preventDefault(); return; }
        }
        const isQ = isQuestion || (e.target && e.target.closest && e.target.closest('[data-builder-question]'));
        const typeEl = isQ ? 'question' : 'section';
        let container;
        if (typeEl === 'section') {
          container = document.getElementById('sections');
        } else {
          // For questions, use the specific section's questions wrapper
          const qNode = el.hasAttribute('data-builder-question') ? el : (e.target && e.target.closest ? e.target.closest('[data-builder-question]') : null);
          const secWrap = qNode ? qNode.closest('[data-builder-section]') : (el.closest ? el.closest('[data-builder-section]') : null);
          container = secWrap ? secWrap.querySelector('[data-questions]') : (qNode ? qNode.parentElement : el.parentElement);
        }
        let nodes = Array.from(container.querySelectorAll(`[data-builder-${typeEl}][data-selected="1"]`));
        if (!nodes.length || !nodes.includes(el)) { clearSelectionIn(container, typeEl); el.setAttribute('data-selected','1'); el.classList.add('ring-2','ring-blue-500'); nodes=[el]; }
        // Capture source section and from-index for questions
        let sourceSectionKey = '';
        let fromIndex = 0;
        if (typeEl === 'question'){
          const secWrap = container.closest('[data-builder-section]');
          sourceSectionKey = secWrap ? secWrap.getAttribute('data-key') : '';
          const all = Array.from(container.querySelectorAll('[data-builder-question]'));
          const idxs = nodes.map(n=>all.indexOf(n)).filter(i=>i>=0).sort((a,b)=>a-b);
          fromIndex = idxs.length ? idxs[0] : 0;
        }
        dragContext = { type: typeEl, nodes, sourceContainer: container, sourceSectionKey, fromIndex };
        try { e.dataTransfer.setData('text/plain', typeEl); } catch(_){ }
        e.dataTransfer.effectAllowed = 'move';
        try {
          const ghost = document.createElement('div');
          ghost.className = 'px-2 py-1 bg-amber-100 border border-amber-300 text-amber-800 text-xs rounded shadow';
          const label = (typeEl==='question' ? 'question' : 'section');
          ghost.textContent = `Moving ${nodes.length} ${label}${nodes.length>1?'s':''}`;
          document.body.appendChild(ghost);
          e.dataTransfer.setDragImage(ghost, 0, 0);
          setTimeout(()=>ghost.remove(), 0);
        } catch(_){ }
        el.classList.add('opacity-50');
      });
      el.addEventListener('dragend', ()=>{ el.classList.remove('opacity-50'); dragContext=null; disableDrag(); });
    }
    let insertIndicator = null;
    function showInsertIndicator(container, beforeNode){
      if (!insertIndicator){
        insertIndicator = document.createElement('div');
        insertIndicator.style.height = '2px';
        insertIndicator.style.background = '#0ea5e9';
        insertIndicator.style.margin = '2px 0';
      }
      if (beforeNode){
        container.insertBefore(insertIndicator, beforeNode);
      } else {
        container.appendChild(insertIndicator);
      }
    }
    function clearInsertIndicator(){
      if (insertIndicator && insertIndicator.parentElement){ insertIndicator.parentElement.removeChild(insertIndicator); }
      insertIndicator = null;
    }
    function getInsertIndex(container, clientY, movingNodes){
      const all = Array.from(container.querySelectorAll('[data-builder-question]'));
      const candidates = all.filter(n=>!movingNodes.includes(n));
      for (let i=0;i<candidates.length;i++){
        const rect = candidates[i].getBoundingClientRect();
        const mid = rect.top + rect.height/2;
        if (clientY < mid) return i;
      }
      return candidates.length;
    }
    function showInsertIndicatorAtIndex(container, index){
      const all = Array.from(container.querySelectorAll('[data-builder-question]'));
      // Translate candidate index to actual node position by skipping moving nodes if any
      let count = 0; let beforeNode = null;
      for (let i=0;i<all.length;i++){
        if (dragContext && dragContext.nodes && dragContext.nodes.includes(all[i])) continue;
        if (count === index) { beforeNode = all[i]; break; }
        count++;
      }
      showInsertIndicator(container, beforeNode);
    }
    function reorderQuestionsByIndex(sections, sourceSectionKey, movingKeys, destSectionKey, toIndex){
      const data = JSON.parse(JSON.stringify(sections));
      const src = data.find(s=>s.key===sourceSectionKey);
      const dst = data.find(s=>s.key===destSectionKey);
      if (!src || !dst) return sections;
      const keyToIdxSrc = new Map();
      src.questions.forEach((q,i)=>keyToIdxSrc.set(q.key, i));
      const moving = movingKeys.map(k=>({ key:k, idx:keyToIdxSrc.get(k) })).filter(x=>Number.isInteger(x.idx)).sort((a,b)=>a.idx-b.idx);
      if (!moving.length) return sections;
      const movingObjs = moving.map(x=>src.questions[x.idx]);
      // Remove from source
      for (let i=moving.length-1;i>=0;i--){ src.questions.splice(moving[i].idx, 1); }
      // Clamp destination index
      const insertIdx = Math.max(0, Math.min(dst.questions.length, toIndex));
      dst.questions.splice(insertIdx, 0, ...movingObjs);
      return data;
    }
    function makeDropZone(container, selector, type){
      const onDragOver = (e)=>{ e.preventDefault(); e.dataTransfer.dropEffect='move';
        if (!dragContext || dragContext.type !== type) return;
        const moving = dragContext.nodes.filter(n=>n.parentElement);
        const idx = getInsertIndex(container, e.clientY, moving);
        showInsertIndicatorAtIndex(container, idx);
      };
      container.addEventListener('dragover', onDragOver, true);
      container.addEventListener('dragover', onDragOver);
      container.addEventListener('dragleave', ()=>{ clearInsertIndicator(); });
      container.addEventListener('drop', (e)=>{
        e.preventDefault(); e.stopPropagation();
        if (!dragContext || dragContext.type !== type) return;
        const secWrap = container.closest('[data-builder-section]');
        const destSectionKey = secWrap ? secWrap.getAttribute('data-key') : '';
        const movingKeys = dragContext.nodes.map(n=>n.getAttribute('data-key'));
        const data = currentSections();
        const toIndex = getInsertIndex(container, e.clientY, dragContext.nodes);
        const newData = reorderQuestionsByIndex(data, dragContext.sourceSectionKey || destSectionKey, movingKeys, destSectionKey, toIndex);
        applySections(newData, true);
        clearInsertIndicator();
      });
    }

    function currentSections(){
      const wraps = Array.from(document.querySelectorAll('[data-builder-section]'));
      return wraps.map(w=>({
        key: w.getAttribute('data-key'),
        label: w.querySelector('[data-field="label"]').value.trim(),
        hidden: !!w.querySelector('[data-field="hidden"]')?.checked,
        intro: (w.querySelector('[data-field="intro"]')?.value || '').trim(),
        agent_notes: (w.querySelector('[data-field="agent_notes"]')?.value || '').trim(),
        show_if_key: (w.querySelector('[data-field="sec-show-if-key"]')?.value || '').trim(),
        show_if_value: (w.querySelector('[data-field="sec-show-if-value"]')?.value || '').trim(),
        condition_logic: (w.querySelector('[data-field="sec-cond-logic"]')?.value || 'AND'),
        conditions: Array.from(w.querySelectorAll('[data-sec-conditions] [data-cond-row]')).map(r=>({
          key: (r.querySelector('[data-field="sec-cond-key"]')?.value || '').trim(),
          op: (r.querySelector('[data-field="sec-cond-op"]')?.value || 'equals'),
          value: (r.querySelector('[data-field="sec-cond-value"]')?.value || '').trim(),
        })).filter(c=>c.key!=='') ,
        questions: Array.from(w.querySelectorAll('[data-builder-question]')).map(q=>({
          key: q.getAttribute('data-key'),
          label: q.querySelector('[data-field="q-label"]').value.trim(),
          hidden: !!q.querySelector('[data-field="q-hidden"]')?.checked,
          type: q.querySelector('[data-field="q-type"]').value,
          required: q.querySelector('[data-field="q-required"]').checked,
          help: q.querySelector('[data-field="q-help"]').value.trim(),
          bold_phrases: (q.querySelector('[data-field="q-bold"]').value||'').split(',').map(s=>s.trim()).filter(Boolean),
          options: (q.querySelector('[data-field="q-options"]').value||'').split('\n').map(s=>s.trim()).filter(Boolean),
          show_if_key: (q.querySelector('[data-field="q-show-if-key"]').value||'').trim(),
          show_if_value: (q.querySelector('[data-field="q-show-if-value"]').value||'').trim(),
          min: (q.querySelector('[data-field="q-min"]').value||'').trim(),
          max: (q.querySelector('[data-field="q-max"]').value||'').trim(),
          pattern: (q.querySelector('[data-field="q-pattern"]').value||'').trim(),
          condition_logic: (q.querySelector('[data-field="q-cond-logic"]')?.value || 'AND'),
          conditions: Array.from(q.querySelectorAll('[data-conditions] [data-cond-row]')).map(r=>({
            key: (r.querySelector('[data-field="cond-key"]')?.value || '').trim(),
            op: (r.querySelector('[data-field="cond-op"]')?.value || 'equals'),
            value: (r.querySelector('[data-field="cond-value"]')?.value || '').trim(),
          })).filter(c=>c.key!=='')
        }))
      }));
    }
    function reorderQuestionsData(sections, sourceSectionKey, movingKeys, destSectionKey, beforeKey){
      const data = JSON.parse(JSON.stringify(sections));
      const src = data.find(s=>s.key===sourceSectionKey);
      const dst = data.find(s=>s.key===destSectionKey);
      if (!src || !dst) return sections;
      const movingSet = new Set(movingKeys);
      // Capture moving question objects in original order from source
      const movingQs = src.questions.filter(q=>movingSet.has(q.key));
      // Remove from source
      src.questions = src.questions.filter(q=>!movingSet.has(q.key));
      // Determine insertion index in destination
      let insertIdx = dst.questions.length;
      if (beforeKey){
        const idx = dst.questions.findIndex(q=>q.key===beforeKey);
        if (idx >= 0) insertIdx = idx;
      }
      // If moving within same section and beforeKey was one of moving items, adjust to next valid position
      if (src === dst && beforeKey && movingSet.has(beforeKey)){
        const firstIdxInSrc = dst.questions.findIndex(q=>!movingSet.has(q.key));
        if (firstIdxInSrc >= 0) insertIdx = Math.min(insertIdx, firstIdxInSrc);
        else insertIdx = dst.questions.length;
      }
      // Insert
      dst.questions.splice(insertIdx, 0, ...movingQs);
      return data;
    }
    function renderQuestion(q){
      const d = document.createElement('div');
      d.className='border rounded p-2 bg-white border-slate-300 border-l-4 pl-2';
      d.setAttribute('data-builder-question','');
      d.setAttribute('data-key', q.key || ('q_'+Math.random().toString(36).slice(2,8)));
      // Make question draggable; we'll gate actual dragstart to the handle only
      d.setAttribute('draggable','true');
      makeDraggable(d);
      d.innerHTML = `<div class=\"grid grid-cols-1 gap-2\">
          <div class=\"flex items-center gap-2 bg-slate-100 border border-slate-200 rounded px-2 py-1\">
            <span data-drag-handle class=\"cursor-move text-slate-400\" title=\"Drag\">≡</span>
            <span class=\"text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-slate-700 text-white\">Question</span>
            <input data-field=\"q-label\" class=\"px-2 py-1 border rounded w-full\" placeholder=\"Question label\" value=\"${q.label||''}\">
          </div>
          <div class="flex items-center gap-3 text-xs">
            <label class="inline-flex items-center gap-2"><input type="checkbox" data-field="q-hidden" ${q.hidden?'checked':''}> <span>Hidden</span></label>
            <span class="text-slate-500">Toggle via &lt;edit&gt; link runtime</span>
          </div>
          <select data-field="q-type" class="px-2 py-1 border rounded">
            <option value="text" ${q.type==='text'?'selected':''}>Short Text</option>
            <option value="textarea" ${q.type==='textarea'?'selected':''}>Long Text</option>
            <option value="number" ${q.type==='number'?'selected':''}>Number</option>
            <option value="select" ${q.type==='select'?'selected':''}>Select</option>
            <option value="radio" ${q.type==='radio'?'selected':''}>Radio</option>
            <option value="checkbox" ${q.type==='checkbox'?'selected':''}>Checkbox</option>
            <option value="yesno" ${q.type==='yesno'?'selected':''}>Yes/No</option>
            <option value="date" ${q.type==='date'?'selected':''}>Date</option>
            <option value="state" ${q.type==='state'?'selected':''}>State Dropdown</option>
            <option value="month" ${q.type==='month'?'selected':''}>Month Dropdown</option>
            <option value="day" ${q.type==='day'?'selected':''}>Day Dropdown</option>
            <option value="year" ${q.type==='year'?'selected':''}>Year Dropdown</option>
            <option value="info" ${q.type==='info'?'selected':''}>Info Block</option>
          </select>
          <div class="grid grid-cols-4 gap-2 text-xs">
            <label class="inline-flex items-center gap-1"><input type="radio" name="${q.key}_preset" data-preset="none" ${!q.pattern&&!q.min&&!q.max?'checked':''}> None</label>
            <label class="inline-flex items-center gap-1"><input type="radio" name="${q.key}_preset" data-preset="email"> Email</label>
            <label class="inline-flex items-center gap-1"><input type="radio" name="${q.key}_preset" data-preset="phone"> Phone</label>
            <label class="inline-flex items-center gap-1"><input type="radio" name="${q.key}_preset" data-preset="numeric"> Numeric</label>
          </div>
          <label class="inline-flex items-center gap-2"><input type="checkbox" data-field="q-required" ${q.required?'checked':''}> <span>Required</span></label>
          <label class="text-xs text-slate-600">Content / helper text</label>
          <textarea data-field="q-help" class="px-2 py-1 border rounded w-full font-sans text-sm" rows="3" placeholder="Supports placeholders like <customer name>, <bedrooms_label>, <home_type_label>, <property_city>, <property_state>, <customer_phone>">${q.help||''}</textarea>
          <label class="text-xs text-slate-600">Bold phrases (comma-separated)</label>
          <input data-field="q-bold" class="px-2 py-1 border rounded w-full font-sans text-sm" placeholder="e.g., Great news!, Dial:" value="${(q.bold_phrases||[]).join(', ')}" />
          <textarea data-field="q-options" class="px-2 py-1 border rounded font-mono text-xs" placeholder="Options (one per line)">${(q.options||[]).join('\n')}</textarea>
          <div class="grid grid-cols-2 gap-2">
            <select data-field="q-show-if-key" class="px-2 py-1 border rounded"><option value="">Show if question key</option></select>
            <input data-field="q-show-if-value" class="px-2 py-1 border rounded" placeholder="equals value" value="${q.show_if_value||''}">
          </div>
          <div class="space-y-2 border rounded p-2">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Conditional visibility</span>
              <select data-field="q-cond-logic" class="px-2 py-1 border rounded text-xs">
                <option value="AND" ${(q.condition_logic||'AND')==='AND'?'selected':''}>All (AND)</option>
                <option value="OR" ${(q.condition_logic||'AND')==='OR'?'selected':''}>Any (OR)</option>
              </select>
            </div>
            <div data-conditions class="space-y-2"></div>
            <button data-action="add-cond" class="px-2 py-1 bg-slate-200 rounded text-xs">Add Condition</button>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <input data-field="q-min" class="px-2 py-1 border rounded" placeholder="Min (len/number)" value="${q.min||''}">
            <input data-field="q-max" class="px-2 py-1 border rounded" placeholder="Max (len/number)" value="${q.max||''}">
            <input data-field="q-pattern" class="px-2 py-1 border rounded" placeholder="Regex pattern" value="${q.pattern||''}">
          </div>
          <div class="flex items-center gap-2">
            <button data-action="add-q-below" class="px-2 py-1 bg-blue-600 text-white rounded text-sm">Add Q Below</button>
            <button data-action="del-q" class="ml-auto px-2 py-1 bg-rose-600 text-white rounded text-sm">Delete Question</button>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-500">
            <div>Placeholder: <code><span data-ph></span></code></div>
            <button type="button" data-action="copy-ph" class="px-1.5 py-0.5 border rounded">Copy</button>
          </div>
        </div>`;
      d.querySelector('[data-action="del-q"]').addEventListener('click', ()=>{ d.remove(); refreshPreview(); try { refreshKeyDropdowns(); } catch(_){} });
      // Auto-grow for help and options textareas
      (function(){ const ta = d.querySelector('[data-field="q-help"]'); if (ta){ autoGrowTextarea(ta); ta.addEventListener('input', ()=>autoGrowTextarea(ta)); } })();
      (function(){ const ta = d.querySelector('[data-field="q-options"]'); if (ta){ autoGrowTextarea(ta); ta.addEventListener('input', ()=>autoGrowTextarea(ta)); } })();
      d.querySelector('[data-action="add-q-below"]').addEventListener('click', ()=>{
        const qKey = d.getAttribute('data-key');
        const secEl = d.closest('[data-builder-section]');
        const secKey = secEl ? secEl.getAttribute('data-key') : null;
        const data = currentSections();
        const sec = secKey ? data.find(s=>s.key===secKey) : null;
        if (!sec) return;
        sec.questions = sec.questions || [];
        const idx = sec.questions.findIndex(q=>q.key===qKey);
        const newQ = { key: genKey('q'), label:'', type:'text', required:false, help:'', bold_phrases:[], options:[] };
        if (idx >= 0) sec.questions.splice(idx+1, 0, newQ); else sec.questions.push(newQ);
        applySections(data, true);
      });
      ['q-label','q-type','q-required','q-help','q-options','q-bold'].forEach(f=>{
        const el = d.querySelector(`[data-field="${f}"]`);
        el.addEventListener('input', ()=>{
          if (f === 'q-label'){
            const label = el.value;
            const base = sanitizeKeyFromLabel(label);
            if (base){
              const newKey = ensureUniqueQuestionKey(base, d);
              d.setAttribute('data-key', newKey);
              const phSpan = d.querySelector('[data-ph]'); if (phSpan) phSpan.textContent = `<q:${newKey}>`;
              try { refreshKeyDropdowns(); } catch(_){}
            }
          }
          refreshPreview();
        });
        el.addEventListener('change', refreshPreview);
      });
      ['q-show-if-key','q-show-if-value'].forEach(f=>{
        d.querySelector(`[data-field="${f}"]`).addEventListener('input', refreshPreview);
        d.querySelector(`[data-field="${f}"]`).addEventListener('change', refreshPreview);
      });
      // Populate the show-if dropdown for this question now
      try { populateKeySelect(d.querySelector('select[data-field="q-show-if-key"]'), d.closest('[data-builder-section]'), 'Show if question key');
        const currentVal = `${q.show_if_key||''}`; if (currentVal) { const sel = d.querySelector('select[data-field="q-show-if-key"]'); if (sel) sel.value = currentVal; }
      } catch(_){ }
      // Conditions UI
      function renderCondRow(cond){
        const row = document.createElement('div');
        row.className = 'grid gap-2 items-start grid-cols-1 sm:grid-cols-2 md:grid-cols-4';
        row.setAttribute('data-cond-row','');
        row.innerHTML = `
          <select data-field="cond-key" class="px-2 py-1 border rounded col-span-2"><option value="">Question key</option></select>
          <select data-field="cond-op" class="px-2 py-1 border rounded w-full">
            <option value="equals" ${cond.op==='equals'?'selected':''}>= equals</option>
            <option value="not_equals" ${cond.op==='not_equals'?'selected':''}>≠ not equals</option>
            <option value="contains" ${cond.op==='contains'?'selected':''}>contains</option>
            <option value="not_contains" ${cond.op==='not_contains'?'selected':''}>not contains</option>
            <option value="gt" ${cond.op==='gt'?'selected':''}>&gt;</option>
            <option value="lt" ${cond.op==='lt'?'selected':''}>&lt;</option>
            <option value="gte" ${cond.op==='gte'?'selected':''}>&ge;</option>
            <option value="lte" ${cond.op==='lte'?'selected':''}>&le;</option>
            <option value="is_filled" ${cond.op==='is_filled'?'selected':''}>is filled</option>
            <option value="is_empty" ${cond.op==='is_empty'?'selected':''}>is empty</option>
          </select>
          <div class="flex gap-2 items-start md:items-center flex-col md:flex-row">
            <input data-field="cond-value" class="px-2 py-1 border rounded w-full" placeholder="Value" value="${cond.value||''}" ${cond.op&&cond.op.startsWith('is_')?'disabled':''}>
            <button data-action="del-cond" class="px-2 py-1 bg-rose-100 text-rose-700 rounded text-xs shrink-0">Remove</button>
          </div>
        `;
        row.querySelector('[data-action="del-cond"]').addEventListener('click', ()=>{ row.remove(); refreshPreview(); try { refreshKeyDropdowns(); } catch(_){} });
        ['cond-key','cond-op','cond-value'].forEach(f=>{
          row.querySelector(`[data-field="${f}"]`).addEventListener('input', refreshPreview);
          row.querySelector(`[data-field="${f}"]`).addEventListener('change', (ev)=>{
            if (f==='cond-op'){
              const op = ev.target.value;
              const v = row.querySelector('[data-field="cond-value"]');
              if (op.startsWith('is_')) { v.value=''; v.disabled = true; } else { v.disabled = false; }
            }
            refreshPreview();
          });
        });
        // Populate keys dropdown and set current
        try { populateKeySelect(row.querySelector('select[data-field="cond-key"]'), 'Question key');
          const current = `${cond.key||''}`; if (current) { const sel = row.querySelector('select[data-field="cond-key"]'); if (sel) sel.value = current; }
        } catch(_){ }
        return row;
      }
      const condWrap = d.querySelector('[data-conditions]');
      (q.conditions||[]).forEach(c=>condWrap.appendChild(renderCondRow(c)));
      d.querySelector('[data-action="add-cond"]').addEventListener('click', ()=>{ condWrap.appendChild(renderCondRow({ key:'', op:'equals', value:'' })); refreshPreview(); });
      d.querySelector('[data-drag-handle]')?.addEventListener('click', (e)=>{ e.preventDefault(); toggleSelect(d, (e.metaKey||e.ctrlKey||e.shiftKey), 'question'); });
      d.querySelectorAll('[data-preset]').forEach(inp=>{
        inp.addEventListener('change', ()=>{
          const preset = inp.getAttribute('data-preset');
          const minEl = d.querySelector('[data-field="q-min"]');
          const maxEl = d.querySelector('[data-field="q-max"]');
          const patEl = d.querySelector('[data-field="q-pattern"]');
          if (preset==='email'){ minEl.value=''; maxEl.value=''; patEl.value='^\\S+@\\S+\\.\\S+$'; }
          else if (preset==='phone'){ minEl.value=''; maxEl.value=''; patEl.value='^\\+?[0-9\\-\\s]{7,15}$'; }
          else if (preset==='numeric'){ minEl.value=''; maxEl.value=''; patEl.value='^[-+]?[0-9]*\\.?[0-9]+$'; }
          else { minEl.value=''; maxEl.value=''; patEl.value=''; }
          refreshPreview();
        });
      });
      // Set and copy placeholder helper
      const ph = `<q:${d.getAttribute('data-key')}>`;
      const phSpan = d.querySelector('[data-ph]'); if (phSpan) phSpan.textContent = ph;
      d.querySelector('[data-action="copy-ph"]').addEventListener('click', ()=>{
        try { navigator.clipboard.writeText(ph); } catch(_){ /* noop */ }
      });
      return d;
    }
    function renderSections(sections){
      const container = document.getElementById('sections');
      container.innerHTML = '';
      (sections||[]).forEach(sec=>{
        const w = document.createElement('div');
        w.setAttribute('data-builder-section','');
        w.setAttribute('data-key', sec.key || ('sec_'+Math.random().toString(36).slice(2,8)));
        w.className='rounded p-2 space-y-2 bg-blue-50 border border-blue-400 shadow-sm';
        makeDraggable(w);
        w.innerHTML = `<div class="flex items-center gap-2 bg-slate-200 border border-slate-300 rounded px-2 py-1">
            <span data-drag-handle class="cursor-move text-slate-500" title="Drag">≡</span>
            <span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-blue-700 text-white">Section</span>
            <input data-field="label" class="px-2 py-1 border rounded w-full" placeholder="Section label" value="${sec.label||''}">
            <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" data-field="hidden" ${sec.hidden?'checked':''}> <span>Hidden</span></label>
            <button data-action="add-q" class="px-2 py-1 bg-blue-600 text-white rounded text-sm">Add Q</button>
            <button data-action="add-info" class="px-2 py-1 bg-slate-600 text-white rounded text-sm">Add Info</button>
            <button data-action="del-sec" class="px-2 py-1 bg-rose-600 text-white rounded text-sm">Delete</button>
          </div>
          <div class="mt-2">
            <input data-field="intro" class="px-2 py-1 border rounded w-full" placeholder="Section intro (optional)" value="${sec.intro||''}">
          </div>
          <div class="mt-2">
            <label class="block text-xs text-slate-600 mb-1">Agent notes (shown to agents)</label>
            <div class="flex items-center gap-2 mb-1">
              <button type="button" class="px-2 py-1 text-xs rounded border bg-slate-50 hover:bg-slate-100" data-notes-bold>Bold</button>
              <span class="text-[11px] text-slate-500">Use **bold** or [[b]]...[[/b]]</span>
            </div>
            <textarea data-field="agent_notes" class="w-full border rounded p-2 text-sm" rows="8" placeholder="Guidance, tips, talking points">${sec.agent_notes||''}</textarea>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <select data-field="sec-show-if-key" class="px-2 py-1 border rounded"><option value="">Show if question key</option></select>
            <input data-field="sec-show-if-value" class="px-2 py-1 border rounded" placeholder="equals value" value="${sec.show_if_value||''}">
          </div>
          <div class="space-y-2 border rounded p-2">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Section conditional visibility</span>
              <select data-field="sec-cond-logic" class="px-2 py-1 border rounded text-xs">
                <option value="AND" ${(sec.condition_logic||'AND')==='AND'?'selected':''}>All (AND)</option>
                <option value="OR" ${(sec.condition_logic||'AND')==='OR'?'selected':''}>Any (OR)</option>
              </select>
            </div>
            <div data-sec-conditions class="space-y-2"></div>
            <button data-action="sec-add-cond" class="px-2 py-1 bg-slate-200 rounded text-xs">Add Condition</button>
          </div>
          <div data-questions class="space-y-2"></div>`;
        container.appendChild(w);
        const qWrap = w.querySelector('[data-questions]');
        // Auto-grow agent notes textarea
        (function(){
          const notes = w.querySelector('[data-field="agent_notes"]');
          if (notes){
            autoGrowTextarea(notes);
            notes.addEventListener('input', ()=>autoGrowTextarea(notes));
            const boldBtn = w.querySelector('[data-notes-bold]');
            if (boldBtn){
              boldBtn.addEventListener('click', ()=>{
                try {
                  const start = notes.selectionStart|0;
                  const end = notes.selectionEnd|0;
                  const sel = notes.value.substring(start, end);
                  const wrapped = sel ? ('**'+sel+'**') : '**bold**';
                  if (notes.setRangeText) notes.setRangeText(wrapped, start, end, 'end');
                  else notes.value = notes.value.slice(0,start) + wrapped + notes.value.slice(end);
                  notes.dispatchEvent(new Event('input', { bubbles: true }));
                  notes.focus();
                  try { notes.selectionStart = notes.selectionEnd = start + wrapped.length; } catch(_){ }
                } catch(_){ }
              });
            }
          }
        })();
        // Populate section-level show-if keys
        try { const sel = w.querySelector('select[data-field="sec-show-if-key"]'); if (sel) { populateKeySelect(sel, 'Show if question key'); if (sec.show_if_key) sel.value = sec.show_if_key; } } catch(_){ }
        // Section conditions UI
        function renderSecCondRow(cond){
          const row = document.createElement('div');
          row.className = 'grid gap-2 items-start grid-cols-1 sm:grid-cols-2 md:grid-cols-4';
          row.setAttribute('data-cond-row','');
          row.innerHTML = `
            <select data-field="sec-cond-key" class="px-2 py-1 border rounded col-span-2"><option value="">Question key</option></select>
            <select data-field="sec-cond-op" class="px-2 py-1 border rounded w-full">
              <option value="equals" ${cond.op==='equals'?'selected':''}>= equals</option>
              <option value="not_equals" ${cond.op==='not_equals'?'selected':''}>≠ not equals</option>
              <option value="contains" ${cond.op==='contains'?'selected':''}>contains</option>
              <option value="not_contains" ${cond.op==='not_contains'?'selected':''}>not contains</option>
              <option value="gt" ${cond.op==='gt'?'selected':''}>&gt;</option>
              <option value="lt" ${cond.op==='lt'?'selected':''}>&lt;</option>
              <option value="gte" ${cond.op==='gte'?'selected':''}>&ge;</option>
              <option value="lte" ${cond.op==='lte'?'selected':''}>&le;</option>
              <option value="is_filled" ${cond.op==='is_filled'?'selected':''}>is filled</option>
              <option value="is_empty" ${cond.op==='is_empty'?'selected':''}>is empty</option>
            </select>
            <div class="flex gap-2 items-start md:items-center flex-col md:flex-row">
              <input data-field="sec-cond-value" class="px-2 py-1 border rounded w-full" placeholder="Value" value="${cond.value||''}" ${cond.op&&cond.op.startsWith('is_')?'disabled':''}>
              <button data-action="sec-del-cond" class="px-2 py-1 bg-rose-100 text-rose-700 rounded text-xs shrink-0">Remove</button>
            </div>
          `;
          row.querySelector('[data-action="sec-del-cond"]').addEventListener('click', ()=>{ row.remove(); refreshPreview(); try { refreshKeyDropdowns(); } catch(_){} });
          ['sec-cond-key','sec-cond-op','sec-cond-value'].forEach(f=>{
            row.querySelector(`[data-field="${f}"]`).addEventListener('input', refreshPreview);
            row.querySelector(`[data-field="${f}"]`).addEventListener('change', (ev)=>{
              if (f==='sec-cond-op'){
                const op = ev.target.value;
                const v = row.querySelector('[data-field="sec-cond-value"]');
                if (op.startsWith('is_')) { v.value=''; v.disabled = true; } else { v.disabled = false; }
              }
              refreshPreview();
            });
          });
          try { const ksel = row.querySelector('select[data-field="sec-cond-key"]'); populateKeySelect(ksel, 'Question key'); if (cond.key) ksel.value = cond.key; } catch(_){ }
          return row;
        }
        const secCondWrap = w.querySelector('[data-sec-conditions]');
        (sec.conditions||[]).forEach(c=>secCondWrap.appendChild(renderSecCondRow(c)));
        w.querySelector('[data-action="sec-add-cond"]').addEventListener('click', ()=>{ secCondWrap.appendChild(renderSecCondRow({ key:'', op:'equals', value:'' })); refreshPreview(); });
        ['sec-show-if-key','sec-show-if-value','sec-cond-logic'].forEach(f=>{ const el=w.querySelector(`[data-field="${f}"]`); el?.addEventListener('input', refreshPreview); el?.addEventListener('change', refreshPreview); });
        makeDropZone(qWrap, '[data-builder-question]', 'question');
        // Allow dropping a question anywhere within the section wrapper
        w.addEventListener('dragover', (e)=>{
          if (!dragContext || dragContext.type !== 'question') return;
          // If the event is within the questions wrapper, let qWrap handle it
          if (e.target && e.target.closest && e.target.closest('[data-questions]') === qWrap) return;
          e.preventDefault(); e.stopPropagation();
          const idx = getInsertIndex(qWrap, e.clientY, dragContext.nodes);
          showInsertIndicatorAtIndex(qWrap, idx);
        }, true);
        w.addEventListener('drop', (e)=>{
          if (!dragContext || dragContext.type !== 'question') return;
          // If drop is within the questions wrapper, let qWrap handle it
          if (e.target && e.target.closest && e.target.closest('[data-questions]') === qWrap) return;
          e.preventDefault(); e.stopPropagation();
          const destSectionKey = w.getAttribute('data-key');
          const movingKeys = dragContext.nodes.map(n=>n.getAttribute('data-key'));
          const data = currentSections();
          const toIndex = getInsertIndex(qWrap, e.clientY, dragContext.nodes);
          const newData = reorderQuestionsByIndex(data, dragContext.sourceSectionKey || destSectionKey, movingKeys, destSectionKey, toIndex);
          applySections(newData, true);
          clearInsertIndicator();
        }, true);
        (sec.questions||[]).forEach(q=>qWrap.appendChild(renderQuestion(q)));
        w.querySelector('[data-action="add-q"]').addEventListener('click', ()=>{
          const data = currentSections();
          const sk = w.getAttribute('data-key');
          const target = data.find(s=>s.key===sk);
          if (target) { target.questions = target.questions||[]; target.questions.push({ key: 'q_'+Math.random().toString(36).slice(2,8), label:'', type:'text', required:false, help:'', options:[] }); }
          applySections(data, true);
        });
        w.querySelector('[data-action="add-info"]').addEventListener('click', ()=>{
          const data = currentSections();
          const sk = w.getAttribute('data-key');
          const target = data.find(s=>s.key===sk);
          if (target) {
            target.questions = target.questions || [];
            target.questions.push({
              key: 'q_'+Math.random().toString(36).slice(2,8),
              label: 'Info',
              type: 'info',
              required: false,
              help: 'Enter info text...\nYou can use placeholders like <customer name>, <customer_phone>, <property_city>, <property_state>, <bedrooms_label>, <home_type_label>',
              options: []
            });
          }
          applySections(data, true);
        });
        w.querySelector('[data-action="del-sec"]').addEventListener('click', ()=>{
          const data = currentSections().filter(s=>s.key !== w.getAttribute('data-key'));
          applySections(data, true);
        });
        w.querySelector('[data-drag-handle]')?.addEventListener('click', (e)=>{ e.preventDefault(); toggleSelect(w, (e.metaKey||e.ctrlKey||e.shiftKey), 'section'); });
      });
      makeDropZone(container, '[data-builder-section]', 'section');
      refreshPreview();
      renderTemplates();
      // After rendering, auto-grow all relevant textareas with existing content
      try {
        document.querySelectorAll('[data-field="q-help"], [data-field="q-options"], [data-field="agent_notes"], #script-intro')
          .forEach(el=>{ try { autoGrowTextarea(el); } catch(_){} });
      } catch(_){ }
      // Populate all key dropdowns
      try { refreshKeyDropdowns(); } catch(_){ }
    }
    function getFirstSelectedSectionKey(){
      const sel = document.querySelector('[data-builder-section][data-selected="1"]');
      return sel ? sel.getAttribute('data-key') : null;
    }
    function insertQuestionInto(sectionKey, q){
      const data = currentSections();
      let secKey = sectionKey;
      if (!secKey && data.length) secKey = data[0].key;
      let sec = data.find(s=>s.key===secKey);
      if (!sec) { data.push({ key: genKey('sec'), label:'Section', questions: [] }); sec = data[data.length-1]; }
      sec.questions = sec.questions || [];
      sec.questions.push(Object.assign({ key: genKey('q'), label:'', type:'text', required:false, help:'', bold_phrases:[], options:[] }, q));
      applySections(data, true);
    }
    async function renderTemplates(){
      const wrap = document.getElementById('template-buttons'); if (!wrap) return; wrap.innerHTML='';
      // Static quick templates
      const quick = [
        { name:'Customer First Name', q:{ key:'customer_first_name', label:'Customer First Name', type:'text', required:true, pattern:'' } },
        { name:'Customer Last Name', q:{ key:'customer_last_name', label:'Customer Last Name', type:'text', required:true, pattern:'' } },
        { name:'Agent First Name', q:{ key:'agent_first_name', label:'Agent First Name', type:'text', required:false } },
        { name:'Agent Last Name', q:{ key:'agent_last_name', label:'Agent Last Name', type:'text', required:false } },
        { name:'Email', q:{ label:'Email', type:'text', required:true, pattern:'^\\S+@\\S+\\.\\S+$' } },
        { name:'Phone', q:{ label:'Phone', type:'text', required:true, pattern:'^\\+?[0-9\\-\\s]{7,15}$' } },
        { name:'Address', q:{ label:'Address', type:'textarea', required:false } },
        { name:'Bedrooms', q:{ label:'Number of bedrooms', type:'number', required:false, min:'0' } },
        { name:'Yes/No', q:{ label:'Confirm', type:'yesno', required:false } },
        { name:'Select State', q:{ label:'State', type:'select', options:['AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA'] } },
      ];
      quick.forEach(t=>{
        const b = document.createElement('button');
        b.className='px-2 py-1 border rounded hover:bg-slate-50';
        b.textContent = t.name;
        b.addEventListener('click', ()=>{ insertQuestionInto(getFirstSelectedSectionKey(), t.q); });
        wrap.appendChild(b);
      });
      // Server templates
      try {
        const res = await fetchJSON(api('/script-templates'));
        if ((res.items||[]).length){
          const header = document.createElement('div'); header.className='col-span-2 text-xs text-slate-500 mt-2'; header.textContent='Saved Templates'; wrap.appendChild(header);
          res.items.forEach(t=>{
            const b = document.createElement('button');
            b.className='px-2 py-1 border rounded hover:bg-slate-50';
            b.textContent = (t.category?`[${t.category}] `:'') + (t.name||'Template');
            b.addEventListener('click', ()=>{ insertQuestionInto(getFirstSelectedSectionKey(), t.question||{}); });
            wrap.appendChild(b);
          });
        }
      } catch(_){}
      // Save current question as template (if exactly one question selected)
      const selQ = document.querySelector('[data-builder-question][data-selected="1"]');
      if (selQ){
        const bar = document.createElement('div'); bar.className='col-span-2 flex items-center gap-2 mt-2';
        bar.innerHTML = '<input id="tpl-name" placeholder="Template name" class="px-2 py-1 border rounded w-40 text-sm"><input id="tpl-cat" placeholder="Category" class="px-2 py-1 border rounded w-32 text-sm" value="General"><button id="btn-save-tpl" class="px-2 py-1 bg-emerald-600 text-white rounded text-sm">Save Template</button>';
        wrap.appendChild(bar);
        document.getElementById('btn-save-tpl').addEventListener('click', async ()=>{
          const name = (document.getElementById('tpl-name').value||'').trim();
          const category = (document.getElementById('tpl-cat').value||'General').trim();
          if (!name) { alert('Enter template name'); return; }
          // Extract question JSON from selected node
          const qNode = selQ;
          const q = {
            key: 'q_template',
            label: qNode.querySelector('[data-field="q-label"]').value.trim(),
            type: qNode.querySelector('[data-field="q-type"]').value,
            required: qNode.querySelector('[data-field="q-required"]').checked,
            help: qNode.querySelector('[data-field="q-help"]').value.trim(),
            options: (qNode.querySelector('[data-field="q-options"]').value||'').split('\n').map(s=>s.trim()).filter(Boolean),
            show_if_key: (qNode.querySelector('[data-field="q-show-if-key"]').value||'').trim(),
            show_if_value: (qNode.querySelector('[data-field="q-show-if-value"]').value||'').trim(),
            min: (qNode.querySelector('[data-field="q-min"]').value||'').trim(),
            max: (qNode.querySelector('[data-field="q-max"]').value||'').trim(),
            pattern: (qNode.querySelector('[data-field="q-pattern"]').value||'').trim(),
            condition_logic: (qNode.querySelector('[data-field="q-cond-logic"]').value||'AND'),
            conditions: Array.from(qNode.querySelectorAll('[data-conditions] [data-cond-row]')).map(r=>({
              key: (r.querySelector('[data-field="cond-key"]').value||'').trim(),
              op: (r.querySelector('[data-field="cond-op"]').value||'equals'),
              value: (r.querySelector('[data-field="cond-value"]').value||'').trim(),
            })).filter(c=>c.key!=='')
          };
          await fetchJSON(api('/script-templates'), { method:'POST', body: JSON.stringify({ name, category, question: q }) });
          alert('Template saved');
          renderTemplates();
        });
      }
    }
    function parseTestAnswers(){
      try { return JSON.parse(document.getElementById('test-answers')?.value || '{}') || {}; } catch(_) { return {}; }
    }
    function autoGrowTextarea(el){
      if (!el) return;
      el.style.overflowY = 'hidden';
      el.style.height = 'auto';
      el.style.height = (el.scrollHeight + 2) + 'px';
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
    function refreshPreview(){
      const sections = currentSections();
      const answers = parseTestAnswers();
      const p = document.getElementById('preview');
      p.innerHTML='';
      sections.forEach(sec=>{
        const hasEditFlag = (sec.questions||[]).some(q=> /<edit>/i.test(q.label||'') || /<edit>/i.test(q.help||''));
        if (!shouldShowSection(sec, answers)) { return; }
        if (sec.hidden && !hasEditFlag) {
          // Skip rendering hidden sections unless <edit> placeholder is present
          return;
        }
        const s = document.createElement('section');
        s.className='p-3 border rounded space-y-4';
        s.innerHTML = `<h4 class="font-semibold mb-2">${sec.label||sec.key}</h4>`;
        sec.questions.forEach(q=>{
          if (!shouldShowQuestion(q, answers)) return;
          const row = document.createElement('div');
          row.className='mb-4';
          row.innerHTML = `<label class="block text-sm font-semibold">${q.label||q.key}</label>`;
          if (q.type==='text') row.innerHTML += '<input class="px-2 py-1 border rounded w-full">';
          else if (q.type==='textarea') row.innerHTML += '<textarea class="px-2 py-1 border rounded w-full"></textarea>';
          else if (q.type==='number') row.innerHTML += '<input type="number" class="px-2 py-1 border rounded w-full">';
          else if (q.type==='select') row.innerHTML += `<select class=\"px-2 py-1 border rounded w-full\">${q.options.map(o=>`<option>${o}</option>`).join('')}</select>`;
          else if (q.type==='radio') row.innerHTML += q.options.map(o=>`<label class=\"inline-flex items-center gap-2 mr-3\"><input type=\"radio\" name=\"${q.key}\"> ${o}</label>`).join('');
          else if (q.type==='checkbox') row.innerHTML += q.options.map(o=>`<label class=\"inline-flex items-center gap-2 mr-3\"><input type=\"checkbox\"> ${o}</label>`).join('');
          else if (q.type==='yesno') row.innerHTML += '<label class="inline-flex items-center gap-2 mr-3"><input type="radio" name="'+q.key+'"> Yes</label><label class="inline-flex items-center gap-2"><input type="radio" name="'+q.key+'"> No</label>';
          else if (q.type==='date') row.innerHTML += '<input type="date" class="px-2 py-1 border rounded">';
          else if (q.type==='state') row.innerHTML += `<select class=\"px-2 py-1 border rounded w-full\">${['AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','IA','ID','IL','IN','KS','KY','LA','MA','MD','ME','MI','MN','MO','MS','MT','NC','ND','NE','NH','NJ','NM','NV','NY','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VA','VT','WA','WI','WV','WY','DC'].map(o=>`<option>${o}</option>`).join('')}</select>`;
          else if (q.type==='month') row.innerHTML += `<select class=\"px-2 py-1 border rounded w-full\">${['January','February','March','April','May','June','July','August','September','October','November','December'].map(o=>`<option>${o}</option>`).join('')}</select>`;
          else if (q.type==='day') row.innerHTML += `<select class=\"px-2 py-1 border rounded w-full\">${Array.from({length:31},(_,i)=>i+1).map(o=>`<option>${o}</option>`).join('')}</select>`;
          else if (q.type==='year') row.innerHTML += `<select class=\"px-2 py-1 border rounded w-full\">${Array.from({length:10},(_,i)=>new Date().getFullYear()+i).map(o=>`<option>${o}</option>`).join('')}</select>`;
          else if (q.type==='info') row.innerHTML += `<div class=\"text-xs text-slate-600\">${q.help||''}${(q.bold_phrases&&q.bold_phrases.length)?`\n(Bold: ${q.bold_phrases.join(', ')})`:''}</div>`;
          s.appendChild(row);
        });
        p.appendChild(s);
      });
    }
    document.getElementById('test-answers')?.addEventListener('input', ()=>refreshPreview());
    document.getElementById('btn-save-script')?.addEventListener('click', async ()=>{
      const slug = document.getElementById('script-slug').value.trim();
      const title = document.getElementById('script-title').value.trim();
      const sections = currentSections();
      const published = document.getElementById('script-published').checked;
      const intro = document.getElementById('script-intro')?.value || '';
      const geoList = document.getElementById('script-geo-list')?.value.trim() || '';
      const suppList = document.getElementById('script-suppression-list')?.value.trim() || '';
      const geoMode = document.getElementById('script-geo-mode')?.value || 'allow';
      const headerLogo = document.getElementById('script-header-logo')?.value.trim() || '';
      const headerAlign = document.getElementById('script-header-align')?.value || 'left';
      await fetchJSON(api('/scripts'), { method:'POST', body: JSON.stringify({ slug, title, sections, published, intro, geo_list: geoList, suppression_list: suppList, geo_mode: geoMode, header_logo_url: headerLogo, header_align: headerAlign }) });
      await loadScripts();
      alert('Saved');
    });

    // Upload logo handler
    document.getElementById('btn-upload-logo')?.addEventListener('click', async (e)=>{
      e.preventDefault();
      const input = document.getElementById('script-logo-file');
      if (!input || !input.files || !input.files[0]) { alert('Choose an image'); return; }
      const fd = new FormData();
      fd.append('file', input.files[0]);
      try {
        const res = await fetch(api('/scripts/upload-logo'), { method: 'POST', body: fd });
        if (!res.ok) { alert('Upload failed'); return; }
        const j = await res.json();
        if (j && j.url) { document.getElementById('script-header-logo').value = j.url; alert('Uploaded'); }
      } catch(_) { alert('Upload failed'); }
    });
    document.getElementById('btn-add-section')?.addEventListener('click', ()=>{
      const data = currentSections();
      data.push({ key: 'sec_'+Math.random().toString(36).slice(2,8), label:'New Section', questions: [] });
      applySections(data, true);
    });
    document.getElementById('btn-new-script')?.addEventListener('click', ()=>{
      document.getElementById('script-slug').value='';
      document.getElementById('script-title').value='';
      // Seed with default split name questions
      applySections([
        { key: genKey('sec'), label: 'Customer', questions: [
          { key: 'customer_first_name', label: 'Customer First Name', type: 'text', required: true, help: '', bold_phrases: [], options: [] },
          { key: 'customer_last_name', label: 'Customer Last Name', type: 'text', required: true, help: '', bold_phrases: [], options: [] },
          { key: 'customer_phone', label: 'Customer Phone', type: 'text', required: true, help: '', bold_phrases: [], options: [], pattern: '^\\+?[0-9\\-\\s]{7,15}$' }
        ]},
        { key: genKey('sec'), label: 'Agent', questions: [
          { key: 'agent_first_name', label: 'Agent First Name', type: 'text', required: false, help: '', bold_phrases: [], options: [] },
          { key: 'agent_last_name', label: 'Agent Last Name', type: 'text', required: false, help: '', bold_phrases: [], options: [] }
        ]}
      ], true);
      resetHistory();
    });
    document.getElementById('btn-load-by-slug')?.addEventListener('click', async ()=>{
      const slug = document.getElementById('script-slug').value.trim();
      if (!slug) return;
      const s = await fetchJSON(api('/scripts/slug/' + encodeURIComponent(slug)));
      document.getElementById('script-title').value = s.title || '';
      document.getElementById('script-published').checked = !!s.published;
      document.getElementById('script-version').textContent = s.version ? ('Version ' + s.version) : '';
      document.getElementById('script-header-logo').value = s.header_logo_url || '';
      document.getElementById('script-header-align').value = (s.header_align==='center')?'center':'left';
      if (document.getElementById('script-intro')) document.getElementById('script-intro').value = s.intro || '';
      if (document.getElementById('script-geo-list')) document.getElementById('script-geo-list').value = s.geo_list || '';
      if (document.getElementById('script-geo-mode')) document.getElementById('script-geo-mode').value = s.geo_mode || 'allow';
      if (document.getElementById('script-suppression-list')) document.getElementById('script-suppression-list').value = s.suppression_list || '';
      applySections(s.sections || [], false);
      resetHistory();
    });
    document.getElementById('btn-migrate-names')?.addEventListener('click', async ()=>{
      if (!confirm('Run migration to backfill split names in script responses?')) return;
      try {
        const res = await fetchJSON(api('/migrations/split-names'), { method:'POST' });
        alert('Migration complete. Updated: ' + (res.updated||0));
      } catch(_) { alert('Migration failed'); }
    });
    document.getElementById('btn-undo')?.addEventListener('click', ()=>doUndo());
    document.getElementById('btn-redo')?.addEventListener('click', ()=>doRedo());
    // Keyboard shortcuts: Cmd/Ctrl + ArrowUp/ArrowDown to move; Delete to remove; Cmd/Ctrl + D to duplicate
    function genKey(prefix){ return prefix + '_' + Math.random().toString(36).slice(2,8); }
    function sanitizeKeyFromLabel(label){
      return String(label||'').toLowerCase().trim().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
    }
    function ensureUniqueQuestionKey(base, currentNode){
      let candidate = base || 'q';
      const nodes = Array.from(document.querySelectorAll('[data-builder-question]'));
      const used = new Set(nodes.filter(n=>n!==currentNode).map(n=>n.getAttribute('data-key')).filter(Boolean));
      if (!used.has(candidate)) return candidate;
      for (let i=2; i<1000; i++){
        const cand = candidate + '_' + i;
        if (!used.has(cand)) return cand;
      }
      return candidate + '_' + Math.random().toString(36).slice(2,5);
    }
    function getSelected(){
      const selectedSections = new Set(Array.from(document.querySelectorAll('[data-builder-section][data-selected="1"]')).map(n=>n.getAttribute('data-key')));
      const selectedQuestions = new Map();
      Array.from(document.querySelectorAll('[data-builder-question][data-selected="1"]')).forEach(n=>{
        const sec = n.closest('[data-builder-section]');
        if (!sec) return;
        const sk = sec.getAttribute('data-key');
        if (!selectedQuestions.has(sk)) selectedQuestions.set(sk, new Set());
        selectedQuestions.get(sk).add(n.getAttribute('data-key'));
      });
      return { selectedSections, selectedQuestions };
    }
    function moveArrayUp(items, isSelected){
      for (let i=1;i<items.length;i++){
        if (isSelected(items[i]) && !isSelected(items[i-1])){
          const tmp = items[i-1]; items[i-1] = items[i]; items[i] = tmp; i = Math.max(0, i-2);
        }
      }
    }
    function moveArrayDown(items, isSelected){
      for (let i=items.length-2;i>=0;i--){
        if (isSelected(items[i]) && !isSelected(items[i+1])){
          const tmp = items[i+1]; items[i+1] = items[i]; items[i] = tmp; i = Math.min(items.length-2, i+2);
        }
      }
    }
    function deepClone(obj){ return JSON.parse(JSON.stringify(obj)); }
    document.addEventListener('keydown', (e)=>{
      const inScripts = !document.getElementById('view-scripts').classList.contains('hidden');
      if (!inScripts) return;
      const tag = (e.target && e.target.tagName) ? e.target.tagName.toUpperCase() : '';
      const isEditable = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable;
      const meta = e.metaKey || e.ctrlKey;
      if (!meta && isEditable) return; // allow typing
      if (meta && e.key.toLowerCase()==='z') { if (e.shiftKey) doRedo(); else doUndo(); e.preventDefault(); return; }
      const { selectedSections, selectedQuestions } = getSelected();
      if (!selectedSections.size && selectedQuestions.size===0) return;
      const data = currentSections();
      let changed = false;
      // Move Up/Down
      if (meta && (e.key === 'ArrowUp' || e.key === 'ArrowDown')){
        if ([...selectedQuestions.values()].some(set=>set.size>0)){
          data.forEach(sec=>{
            const set = selectedQuestions.get(sec.key);
            if (!set || set.size===0) return;
            const arr = sec.questions;
            const selFn = (q)=> set.has(q.key);
            if (e.key==='ArrowUp') moveArrayUp(arr, selFn); else moveArrayDown(arr, selFn);
          });
        } else if (selectedSections.size){
          const selFn = (s)=> selectedSections.has(s.key);
          if (e.key==='ArrowUp') moveArrayUp(data, selFn); else moveArrayDown(data, selFn);
        }
        changed = true;
      }
      // Delete
      if (e.key === 'Delete' || e.key === 'Backspace'){
        let newData = [];
        data.forEach(sec=>{
          if (selectedSections.has(sec.key)) { changed = true; return; }
          const set = selectedQuestions.get(sec.key);
          if (set && set.size){
            sec.questions = sec.questions.filter(q=>!set.has(q.key));
            changed = true;
          }
          newData.push(sec);
        });
        if (changed) newData = newData.filter(sec=>true); // normalize
        if (changed) { applySections(newData, true); e.preventDefault(); return; }
      }
      // Duplicate
      if (meta && (e.key.toLowerCase() === 'd')){
        const newData = [];
        data.forEach(sec=>{
          const set = selectedQuestions.get(sec.key);
          const isSecSelected = selectedSections.has(sec.key);
          newData.push(sec);
          if (isSecSelected){
            const copy = deepClone(sec);
            copy.key = genKey('sec');
            copy.questions = (copy.questions||[]).map(q=>({ ...q, key: genKey('q') }));
            newData.push(copy);
            changed = true;
          } else if (set && set.size){
            const qNew = [];
            sec.questions.forEach(q=>{
              qNew.push(q);
              if (set.has(q.key)) { const qc = deepClone(q); qc.key = genKey('q'); qNew.push(qc); changed = true; }
            });
            sec.questions = qNew;
          }
        });
        if (changed) { applySections(newData, true); e.preventDefault(); return; }
      }
      if (changed){ renderSections(data); e.preventDefault(); }
    });

    // Clipboard copy/paste across scripts
    function copySelection(){
      const { selectedSections, selectedQuestions } = getSelected();
      const data = currentSections();
      if (selectedSections.size){
        const secs = data.filter(s=>selectedSections.has(s.key)).map(s=>deepClone(s));
        sessionStorage.setItem('scriptBuilderClipboard', JSON.stringify({ type:'sections', items: secs }));
        return true;
      }
      const items = [];
      data.forEach(sec=>{
        const set = selectedQuestions.get(sec.key);
        if (set && set.size){
          sec.questions.forEach(q=>{ if (set.has(q.key)) items.push(deepClone(q)); });
        }
      });
      if (items.length){ sessionStorage.setItem('scriptBuilderClipboard', JSON.stringify({ type:'questions', items })); return true; }
      return false;
    }
    function pasteSelection(){
      const raw = sessionStorage.getItem('scriptBuilderClipboard'); if (!raw) return false;
      const clip = JSON.parse(raw);
      const data = currentSections();
      const { selectedSections, selectedQuestions } = getSelected();
      if (clip.type==='sections'){
        const insertIndex = data.findIndex(s=>selectedSections.has(s.key));
        const clones = clip.items.map(s=>({ ...deepClone(s), key: genKey('sec'), questions: (s.questions||[]).map(q=>({ ...q, key: genKey('q') })) }));
        if (insertIndex>=0) data.splice(insertIndex+1, 0, ...clones); else data.push(...clones);
        applySections(data, true); return true;
      }
      if (clip.type==='questions'){
        let targetSectionKey = null;
        if (selectedSections.size) targetSectionKey = [...selectedSections][0];
        if (!targetSectionKey && selectedQuestions.size) targetSectionKey = [...selectedQuestions.keys()][0];
        if (!targetSectionKey) targetSectionKey = (data[data.length-1]?.key) || null;
        if (!targetSectionKey) { data.push({ key: genKey('sec'), label:'Pasted', questions: [] }); targetSectionKey = data[data.length-1].key; }
        const sec = data.find(s=>s.key===targetSectionKey);
        if (!sec) return false;
        sec.questions = sec.questions||[];
        clip.items.forEach(q=>{ sec.questions.push({ ...deepClone(q), key: genKey('q') }); });
        applySections(data, true); return true;
      }
      return false;
    }
    document.addEventListener('keydown', (e)=>{
      const inScripts = !document.getElementById('view-scripts').classList.contains('hidden');
      if (!inScripts) return;
      const meta = e.metaKey || e.ctrlKey;
      if (!meta) return;
      if (e.key.toLowerCase()==='c'){ if (copySelection()) e.preventDefault(); }
      if (e.key.toLowerCase()==='v'){ if (pasteSelection()) e.preventDefault(); }
    });

    function parseCsvLines(text){
      return text.split(/\r?\n/).map(l=>l.trim()).filter(Boolean);
    }
    document.getElementById('btn-upload-geo')?.addEventListener('click', ()=>{
      const f = document.getElementById('file-geo').files[0]; if (!f) { alert('Choose CSV'); return; }
      const name = (document.getElementById('geo-list-name')?.value||'').trim() || 'default';
      const r = new FileReader(); r.onload = async ()=>{
        const zipsAll = parseCsvLines(r.result).map(s=>s.trim()).filter(Boolean);
        const zips = zipsAll;
        const el = document.getElementById('geo-preview'); el.textContent = zips.slice(0,50).join('\n'); el.classList.remove('hidden');
        try {
          // Merge with existing lists instead of overwriting
          const settings = await fetchJSON(api('/settings'));
          const lists = Array.isArray(settings.geo_lists) ? settings.geo_lists.slice() : [];
          const idx = lists.findIndex(x=> (String(x.name||'').trim()) === name);
          if (idx >= 0) { lists[idx] = { name, zips }; } else { lists.push({ name, zips }); }
          await fetchJSON(api('/settings'), { method:'POST', body: JSON.stringify({ geo_lists: lists }) });
          // Refresh UI lists and datalists
          try { await renderGeoSuppLists(); } catch(_){}
          try {
            const s = await fetchJSON(api('/settings'));
            const geo = Array.isArray(s.geo_lists)? s.geo_lists.map(x=>x.name).filter(Boolean): [];
            const geoDL = document.getElementById('geo-list-names'); if (geoDL) geoDL.innerHTML = geo.map(n=>`<option value="${n}"></option>`).join('');
          } catch(_){}
          alert('Geo list saved');
        } catch(_){ alert('Failed to save'); }
      }; r.readAsText(f);
    });
    document.getElementById('btn-clear-geo')?.addEventListener('click', ()=>{ const el = document.getElementById('geo-preview'); el.textContent=''; el.classList.add('hidden'); });
    document.getElementById('btn-upload-supp')?.addEventListener('click', ()=>{
      const f = document.getElementById('file-supp').files[0]; if (!f) { alert('Choose CSV'); return; }
      const name = (document.getElementById('supp-list-name')?.value||'').trim() || 'default';
      const r = new FileReader(); r.onload = async ()=>{
        const phonesAll = parseCsvLines(r.result).map(s=>s.replace(/[^0-9\+]/g,'')).filter(Boolean);
        const phones = phonesAll;
        const el = document.getElementById('supp-preview'); el.textContent = phones.slice(0,50).join('\n'); el.classList.remove('hidden');
        try { await fetchJSON(api('/settings'), { method:'POST', body: JSON.stringify({ suppression_lists: [{ name, phones }] }) }); alert('Suppression list saved'); } catch(_){ alert('Failed to save'); }
      }; r.readAsText(f);
    });
    document.getElementById('btn-clear-supp')?.addEventListener('click', ()=>{ const el = document.getElementById('supp-preview'); el.textContent=''; el.classList.add('hidden'); });

    // Save only geo list
    document.getElementById('btn-save-geo')?.addEventListener('click', async ()=>{
      const name = (document.getElementById('geo-list-name')?.value||'').trim() || 'default';
      const zips = (document.getElementById('geo-preview')?.textContent||'').split(/\s+/).map(s=>s.trim()).filter(Boolean);
      if (!zips.length) { alert('Upload a CSV first'); return; }
      try {
        const settings = await fetchJSON(api('/settings'));
        const lists = Array.isArray(settings.geo_lists) ? settings.geo_lists.slice() : [];
        const idx = lists.findIndex(x=> (String(x.name||'').trim()) === name);
        if (idx >= 0) { lists[idx] = { name, zips }; } else { lists.push({ name, zips }); }
        await fetchJSON(api('/settings'), { method:'POST', body: JSON.stringify({ geo_lists: lists }) });
        try { await renderGeoSuppLists(); } catch(_){}
        try {
          const s = await fetchJSON(api('/settings'));
          const geo = Array.isArray(s.geo_lists)? s.geo_lists.map(x=>x.name).filter(Boolean): [];
          const geoDL = document.getElementById('geo-list-names'); if (geoDL) geoDL.innerHTML = geo.map(n=>`<option value="${n}"></option>`).join('');
        } catch(_){}
      alert('Geo list saved');
      } catch(_){ alert('Failed to save'); }
    });
    // Save only suppression list
    document.getElementById('btn-save-supp')?.addEventListener('click', async ()=>{
      const name = (document.getElementById('supp-list-name')?.value||'').trim() || 'default';
      const phones = (document.getElementById('supp-preview')?.textContent||'').split(/\s+/).map(s=>s.trim()).filter(Boolean);
      if (!phones.length) { alert('Upload a CSV first'); return; }
      await fetchJSON(api('/settings'), { method:'POST', body: JSON.stringify({ suppression_lists: [{ name, phones }] }) });
      alert('Suppression list saved');
    });

    async function renderGeoSuppLists(){
      try {
        const s = await fetchJSON(api('/settings'));
        const geoWrap = document.getElementById('geo-lists');
        if (geoWrap){
          const lists = Array.isArray(s.geo_lists)? s.geo_lists: [];
          geoWrap.innerHTML = lists.map(it=>{
            const count = (it.zips||[]).length;
            return `<div class=\"p-3 border rounded flex items-start justify-between\"><div><div class=\"font-semibold text-sm\">${it.name||'(unnamed)'}<\/div><div class=\"text-[11px] text-slate-500\">${count} ZIPs<\/div></div><div class=\"flex gap-2\"><button class=\"px-2 py-1 text-xs rounded bg-blue-600 text-white\" data-geo-manage=\"${it.name}\">Manage<\/button><button class=\"px-2 py-1 text-xs rounded bg-slate-600 text-white\" data-geo-edit=\"${it.name}\">Edit<\/button><button class=\"px-2 py-1 text-xs rounded bg-rose-600 text-white\" data-geo-del=\"${it.name}\">Delete<\/button></div></div>`;
          }).join('');
        }
        const suppWrap = document.getElementById('supp-lists');
        if (suppWrap){
          const lists = Array.isArray(s.suppression_lists)? s.suppression_lists: [];
          suppWrap.innerHTML = lists.map(it=>{
            const count = (it.phones||[]).length;
            return `<div class=\"p-3 border rounded flex items-start justify-between\"><div><div class=\"font-semibold text-sm\">${it.name||'(unnamed)'}<\/div><div class=\"text-[11px] text-slate-500\">${count} phones<\/div></div><div class=\"flex gap-2\"><button class=\"px-2 py-1 text-xs rounded bg-blue-600 text-white\" data-supp-manage=\"${it.name}\">Manage<\/button><button class=\"px-2 py-1 text-xs rounded bg-slate-600 text-white\" data-supp-edit=\"${it.name}\">Edit<\/button><button class=\"px-2 py-1 text-xs rounded bg-rose-600 text-white\" data-supp-del=\"${it.name}\">Delete<\/button></div></div>`;
          }).join('');
        }
        // Wire delete
        document.querySelectorAll('[data-geo-del]')?.forEach(b=>b.addEventListener('click', async ()=>{
          const name = b.getAttribute('data-geo-del');
          const settings = await fetchJSON(api('/settings'));
          const left = (settings.geo_lists||[]).filter(x=> (x.name||'')!==name);
          await fetchJSON(api('/settings'), { method:'POST', body: JSON.stringify({ geo_lists: left }) });
          await renderGeoSuppLists();
        }));
        document.querySelectorAll('[data-supp-del]')?.forEach(b=>b.addEventListener('click', async ()=>{
          const name = b.getAttribute('data-supp-del');
          const settings = await fetchJSON(api('/settings'));
          const left = (settings.suppression_lists||[]).filter(x=> (x.name||'')!==name);
          await fetchJSON(api('/settings'), { method:'POST', body: JSON.stringify({ suppression_lists: left }) });
          await renderGeoSuppLists();
        }));
        // New list buttons
        document.getElementById('btn-new-geo-list')?.addEventListener('click', ()=>{ document.getElementById('geo-list-name').value = ''; document.getElementById('file-geo').value=''; document.getElementById('geo-preview').textContent=''; document.getElementById('geo-preview').classList.add('hidden'); });
        document.getElementById('btn-new-supp-list')?.addEventListener('click', ()=>{ document.getElementById('supp-list-name').value = ''; document.getElementById('file-supp').value=''; document.getElementById('supp-preview').textContent=''; document.getElementById('supp-preview').classList.add('hidden'); });
      } catch(_){ }
    }
    function populateKeySelect(selectEl, sectionEl, placeholder){
      if (!selectEl || !sectionEl) return;
      const questions = Array.from(sectionEl.querySelectorAll('[data-builder-question]'));
      const current = selectEl.value;
      const opts = [{ value: '', label: placeholder||'Show if question key' }];
      questions.forEach(qn => {
        const key = qn.getAttribute('data-key') || '';
        const lbl = (qn.querySelector('[data-field="q-label"]')?.value || key);
        if (key) opts.push({ value: key, label: `${lbl} (${key})` });
      });
      selectEl.innerHTML = opts.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
      if (current && opts.some(o=>o.value===current)) selectEl.value = current;
    }
    function refreshKeyDropdowns(){
      const selects = Array.from(document.querySelectorAll('select[data-field="q-show-if-key]"]'));
      // Fix selector typo if any; fallback
      const sels = selects.length ? selects : Array.from(document.querySelectorAll('select[data-field="q-show-if-key"]'));
      sels.forEach(sel => populateKeySelect(sel, sel.closest('[data-builder-section]'), 'Show if question key'));
    }
    function getAllQuestions(){
      const nodes = Array.from(document.querySelectorAll('[data-builder-question]'));
      const out = [];
      nodes.forEach(qn=>{ const key = qn.getAttribute('data-key')||''; const lbl = (qn.querySelector('[data-field="q-label"]')?.value||key); if (key) out.push({ key, label: lbl }); });
      return out;
    }
    function populateKeySelect(selectEl, placeholder){
      if (!selectEl) return;
      const all = getAllQuestions();
      const current = selectEl.value;
      const opts = [{ value: '', label: (placeholder||'Select key') }];
      all.forEach(({key,label})=>{ opts.push({ value:key, label: `${label} (${key})` }); });
      selectEl.innerHTML = opts.map(o=>`<option value="${o.value}">${o.label}</option>`).join('');
      if (current && opts.some(o=>o.value===current)) selectEl.value = current;
    }
    function refreshKeyDropdowns(){
      const showIfSelects = Array.from(document.querySelectorAll('select[data-field="q-show-if-key"]'));
      const condKeySelects = Array.from(document.querySelectorAll('select[data-field="cond-key"]'));
      [...showIfSelects, ...condKeySelects].forEach(sel => populateKeySelect(sel, sel.getAttribute('data-field')==='q-show-if-key' ? 'Show if question key' : 'Question key'));
    }

    document.querySelector('[data-view="data"]').addEventListener('click', async ()=>{
      function formatEST(input){
        if (input === undefined || input === null || input === '') return '';
        let d = null;
        if (typeof input === 'number') {
          const ms = input < 1e12 ? input * 1000 : input;
          d = new Date(ms);
        } else if (typeof input === 'string') {
          const s = input.trim();
          if (/^\d+$/.test(s)) {
            const n = parseInt(s, 10);
            const ms = n < 1e12 ? n * 1000 : n;
            d = new Date(ms);
          } else {
            const t = Date.parse(s);
            if (!isNaN(t)) d = new Date(t);
          }
        } else if (input instanceof Date) {
          d = input;
        }
        if (!d || isNaN(d.getTime())) return String(input);
        try {
          return new Intl.DateTimeFormat('en-US', {
            timeZone: 'America/New_York',
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
          }).format(d);
        } catch(_) {
          return d.toLocaleString('en-US', { timeZone: 'America/New_York' });
        }
      }
      const fmt = (v)=>formatEST(v);
      async function loadList(id, url, map){ try { const res = await fetchJSON(api(url)); document.getElementById(id).innerHTML = `<table class=\"w-full text-sm\"><thead><tr>${map.head.map(h=>`<th class=\"text-left p-2\">${h}</th>`).join('')}<th class=\"text-left p-2\"></th></tr></thead><tbody>${(res.items||[]).map(it=>`<tr class=\"border-t\">${map.cols.map(c=>`<td class=\"p-2\">${fmt(c(it))||''}</td>`).join('')}<td class=\"p-2\"><button data-del=\"${map.delPath}/${it.id}\" class=\"px-2 py-1 border rounded text-rose-700\">Delete</button></td></tr>`).join('')}</tbody></table>`; } catch(_){ document.getElementById(id).textContent = 'No data'; } }
      document.addEventListener('click', async (e)=>{ const b=e.target.closest('[data-del]'); if(!b) return; e.preventDefault(); const path=b.getAttribute('data-del'); if(!confirm('Delete item?')) return; try{ await fetchJSON(api(path), { method:'DELETE' }); b.closest('tr')?.remove(); } catch(_){ alert('Delete failed'); } });

      await Promise.all([
        loadList('list-calls','/admin/calls&limit=50',{ head:['When','Agent','Dir','Phone','AHT','Campaign'], cols:[i=>i.started_at,i=>i.agent_id,i=>i.direction,i=>i.contact_phone,i=>i.handle_time_s,i=>i.campaign_id], delPath:'/admin/calls' }),
        loadList('list-staffing','/admin/staffing',{ head:['When','Agent','State'], cols:[i=>i.ts,i=>i.agent_id,i=>i.state], delPath:'/admin/staffing' }),
        loadList('list-schedules','/admin/schedules',{ head:['Agent','Start','End'], cols:[i=>i.agent_id,i=>i.shift_start,i=>i.shift_end], delPath:'/admin/schedules' }),
        loadList('list-callbacks','/admin/callbacks',{ head:['Callback ID','Agent','Due','Completed'], cols:[i=>i.callback_id,i=>i.agent_id,i=>i.due,i=>i.completed], delPath:'/admin/callbacks' }),
        loadList('list-queue','/admin/queue-events',{ head:['When','Call','Queue','Event'], cols:[i=>i.ts,i=>i.call_id,i=>i.queue,i=>i.event], delPath:'/admin/queue-events' }),
        loadList('list-dial','/admin/dial-results',{ head:['When','Call','Result'], cols:[i=>i.ts,i=>i.call_id,i=>i.result], delPath:'/admin/dial-results' }),
        loadList('list-reso','/admin/resolutions',{ head:['When','Call','Resolved'], cols:[i=>i.ts,i=>String(i.resolved)], delPath:'/admin/resolutions' }),
        loadList('list-qa','/admin/qa',{ head:['When','Agent','Call','Score'], cols:[i=>i.ts,i=>i.agent_id,i=>i.call_id,i=>i.score], delPath:'/admin/qa' }),
        loadList('list-funnel','/admin/funnel',{ head:['When','Lead','Stage'], cols:[i=>i.ts,i=>i.lead_id,i=>i.stage], delPath:'/admin/funnel' }),
        loadList('list-geo','/admin/geo-checks',{ head:['When','Call','Geo','Allowed'], cols:[i=>i.ts,i=>i.call_id,i=>i.phone_geo,i=>String(i.allowed)], delPath:'/admin/geo-checks' }),
      ]);

      bindForm('form-call','/admin/calls', fd=>({ direction:fd.get('direction'), agent_id:fd.get('agent_id'), campaign_id:fd.get('campaign_id'), contact_phone:fd.get('contact_phone'), handle_time_s:parseInt(fd.get('handle_time_s')||'0',10), started_at_ts:parseInt(fd.get('started_at_ts')||String(Math.floor(Date.now()/1000)),10) }));
      bindForm('form-staffing','/admin/staffing', fd=>({ agent_id:fd.get('agent_id'), state:fd.get('state'), ts:parseInt(fd.get('ts')||String(Math.floor(Date.now()/1000)),10) }));
      bindForm('form-schedules','/admin/schedules', fd=>({ agent_id:fd.get('agent_id'), shift_start_ts:parseInt(fd.get('shift_start_ts')||String(Math.floor(Date.now()/1000)),10), shift_end_ts:parseInt(fd.get('shift_end_ts')||String(Math.floor(Date.now()/1000)+3600),10) }));
      bindForm('form-callbacks','/admin/callbacks', fd=>({ id:fd.get('id'), agent_id:fd.get('agent_id'), due_ts:parseInt(fd.get('due_ts')||String(Math.floor(Date.now()/1000)+900),10), completed_ts:fd.get('completed_ts')?parseInt(fd.get('completed_ts'),10):undefined }));
      bindForm('form-queue','/admin/queue-events', fd=>({ call_id:fd.get('call_id'), queue:fd.get('queue'), event:fd.get('event'), ts:parseInt(fd.get('ts')||String(Math.floor(Date.now()/1000)),10) }));
      bindForm('form-dial','/admin/dial-results', fd=>({ call_id:fd.get('call_id'), result:fd.get('result'), ts:parseInt(fd.get('ts')||String(Math.floor(Date.now()/1000)),10) }));
      bindForm('form-reso','/admin/resolutions', fd=>({ call_id:fd.get('call_id'), resolved:fd.get('resolved')==='1', ts:parseInt(fd.get('ts')||String(Math.floor(Date.now()/1000)),10) }));
      bindForm('form-qa','/admin/qa', fd=>{ let rubric=null; try{ rubric=JSON.parse(fd.get('rubric')||'null'); }catch(_){ rubric=null; } return { call_id:fd.get('call_id'), agent_id:fd.get('agent_id'), score:parseFloat(fd.get('score')||'0'), rubric, ts:parseInt(fd.get('ts')||String(Math.floor(Date.now()/1000)),10) }; });
      bindForm('form-funnel','/admin/funnel', fd=>({ lead_id:fd.get('lead_id'), stage:fd.get('stage'), ts:parseInt(fd.get('ts')||String(Math.floor(Date.now()/1000)),10) }));
      bindForm('form-geo','/admin/geo-checks', fd=>({ call_id:fd.get('call_id'), phone_geo:fd.get('phone_geo'), allowed:fd.get('allowed')==='1', ts:parseInt(fd.get('ts')||String(Math.floor(Date.now()/1000)),10) }));
    });

    // Simple week calendar (Schedule)
    function renderWeekCalendar(shifts){
      const start = new Date(); start.setHours(0,0,0,0); const day0 = start.getTime() - start.getDay()*86400000;
      const hours = Array.from({length:24},(_,h)=>h);
      const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      const grid = document.createElement('div'); grid.className='overflow-auto';
      grid.innerHTML = `<table class=\"w-full text-xs\"><thead><tr><th></th>${days.map(d=>`<th class=\"p-1\">${d}</th>`).join('')}</tr></thead><tbody>${hours.map(h=>`<tr><td class=\"p-1\">${h}:00</td>${days.map((_,d)=>`<td class=\"border p-1 align-top\" data-cell=\"${d}-${h}\"></td>`).join('')}</tr>`).join('')}</tbody></table>`;
      const cells = grid.querySelectorAll('[data-cell]');
      const byCell = (ts)=>{ const dt=new Date(ts); const d=dt.getDay(); const h=dt.getHours(); return `${d}-${h}`; };
      // place shifts
      const seen = {};
      (shifts||[]).forEach(s=>{
        const cellId = byCell(Date.parse(s.shift_start||s.start||new Date()));
        const cell = grid.querySelector(`[data-cell=\"${cellId}\"]`); if (!cell) return;
        const b = document.createElement('div'); b.className='mb-1 p-1 rounded text-[11px] text-white'; b.style.background='#0ea5e9'; b.draggable=true;
        b.textContent = `${s.agent_id||''}`; b.dataset.id = s.id; b.dataset.start = s.shift_start||''; b.dataset.end = s.shift_end||'';
        cell.appendChild(b);
        const key = `${s.agent_id}|${cellId}`; if (seen[key]) { b.style.background='#f97316'; b.title='Overlap'; } else { seen[key]=true; }
        b.addEventListener('dragstart', ev=>{ ev.dataTransfer.setData('text/plain', JSON.stringify({ id:s.id })); });
      });
      cells.forEach(c=>c.addEventListener('dragover', e=>{ e.preventDefault(); }));
      cells.forEach(c=>c.addEventListener('drop', async e=>{
        e.preventDefault(); try { const data = JSON.parse(e.dataTransfer.getData('text/plain')); const id = data.id; const [d,h] = (c.getAttribute('data-cell')||'0-0').split('-').map(x=>parseInt(x,10)); const base = new Date(); base.setHours(0,0,0,0); const target = new Date(base.getTime() - base.getDay()*86400000 + d*86400000); target.setHours(h,0,0,0); const startTs = Math.floor(target.getTime()/1000); const endTs = startTs + 3600; await fetchJSON(api(`/admin/schedules/${id}`), { method:'PUT', body: JSON.stringify({ shift_start_ts:startTs, shift_end_ts:endTs }) }); document.querySelector('[data-view="schedule"]').click(); } catch(_){ alert('Failed to move'); }
      }));
      const holder = document.getElementById('sched-list'); holder.innerHTML=''; holder.appendChild(grid);
    }

    document.querySelector('[data-view="schedule"]').addEventListener('click', async ()=>{
      try { const res = await fetchJSON(api('/admin/schedules') + '&limit=100'); renderWeekCalendar(res.items||[]); } catch(_){ document.getElementById('sched-list').textContent='No data'; }
      const f = document.getElementById('form-schedule-new'); if (f){ f.onsubmit = async (e)=>{ e.preventDefault(); const fd=new FormData(f); const payload={ agent_id:fd.get('agent_id'), shift_start_ts:parseInt(fd.get('shift_start_ts')||String(Math.floor(Date.now()/1000)),10), shift_end_ts:parseInt(fd.get('shift_end_ts')||String(Math.floor(Date.now()/1000)+3600),10) }; try{ await fetchJSON(api('/admin/schedules'), { method:'POST', body: JSON.stringify(payload) }); alert('Shift added'); document.querySelector('[data-view="schedule"]').click(); } catch(_){ alert('Failed'); } }; }
    });

    document.querySelector('[data-view="callbacks"]').addEventListener('click', async ()=>{
      const render = (sel, items)=>{ document.getElementById(sel).innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">ID</th><th class=\"text-left p-2\">Agent</th><th class=\"text-left p-2\">Due</th><th class=\"text-left p-2\">Reassign</th><th class=\"text-left p-2\">Action</th></tr></thead><tbody>${items.map(i=>{ const overdue = i.completed? false : (new Date(i.due||0).getTime() < Date.now()); return `<tr class=\"border-t ${overdue? 'bg-rose-50':''}\"><td class=\"p-2\">${i.callback_id}</td><td class=\"p-2\">${i.agent_id}</td><td class=\"p-2\">${i.due||''}</td><td class=\"p-2\"><input data-cb-agent=\"${i.id}\" class=\"border rounded p-1 text-xs\" placeholder=\"agent_id\" /></td><td class=\"p-2\">${i.completed? '' : `<button data-cb-complete=\"${i.callback_id}\" class=\"px-2 py-1 border rounded\">Complete</button> <button data-cb-save=\"${i.id}\" class=\"px-2 py-1 border rounded\">Save</button>`}</td></tr>`; }).join('')}</tbody></table>`; };
      try { const res = await fetchJSON(api('/admin/callbacks') + '&limit=200'); const items = res.items||[]; const nowTs = Date.now(); const due = items.filter(i=>!i.completed && new Date(i.due||0).getTime() >= nowTs); const over = items.filter(i=>!i.completed && new Date(i.due||0).getTime() < nowTs); const done = items.filter(i=>!!i.completed); render('cb-due', due); render('cb-overdue', over); render('cb-done', done); } catch(_){ ['cb-due','cb-overdue','cb-done'].forEach(id=>document.getElementById(id).textContent='No data'); }
      const f = document.getElementById('form-callback-new'); if (f){ f.onsubmit = async (e)=>{ e.preventDefault(); const fd=new FormData(f); const payload={ id:fd.get('id'), agent_id:fd.get('agent_id'), due_ts:parseInt(fd.get('due_ts')||String(Math.floor(Date.now()/1000)+900),10) }; try{ await fetchJSON(api('/admin/callbacks'), { method:'POST', body: JSON.stringify(payload) }); alert('Callback saved'); document.querySelector('[data-view="callbacks"]').click(); } catch(_){ alert('Failed'); } }; }
      document.addEventListener('click', async (e)=>{ const b=e.target.closest('[data-cb-complete]'); if (b){ const id=b.getAttribute('data-cb-complete'); try{ await fetchJSON(api('/admin/callbacks/complete'), { method:'POST', body: JSON.stringify({ id, completed_ts: Math.floor(Date.now()/1000) }) }); document.querySelector('[data-view="callbacks"]').click(); } catch(_){ alert('Failed to complete'); } }
        const s=e.target.closest('[data-cb-save]'); if (s){ const row=s.closest('tr'); const rid=s.getAttribute('data-cb-save'); const agentInput=row.querySelector(`[data-cb-agent=\"${rid}\"]`); const agent = agentInput?.value||''; try{ await fetchJSON(api(`/admin/callbacks/${rid}`), { method:'PUT', body: JSON.stringify({ agent_id: agent }) }); alert('Saved'); } catch(_){ alert('Save failed'); } }
      }, { once:true });
    });

    document.querySelector('[data-view="qa-rubrics"]').addEventListener('click', async ()=>{
      try { const res = await fetchJSON(api('/admin/qa-rubrics')); document.getElementById('qa-rubrics-list').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Name</th><th class=\"text-left p-2\">Updated</th><th class=\"text-left p-2\">Delete</th></tr></thead><tbody>${(res.items||[]).map(i=>`<tr class=\"border-t\"><td class=\"p-2\">${i.name}</td><td class=\"p-2\">${formatEST(i.updated_at)||''}</td><td class=\"p-2\"><button data-del-rubric=\"${i.id}\" class=\"px-2 py-1 border rounded\">Delete</button></td></tr>`).join('')}</tbody></table>`; } catch(_){ document.getElementById('qa-rubrics-list').textContent='No data'; }
      const f = document.getElementById('form-qarubric'); if (f){ f.onsubmit = async (e)=>{ e.preventDefault(); const fd=new FormData(f); let rubric=null; try{ rubric=JSON.parse(fd.get('rubric')||'null'); }catch(_){ alert('Invalid rubric JSON'); return; } const payload={ name:fd.get('name'), rubric }; try{ await fetchJSON(api('/admin/qa-rubrics'), { method:'POST', body: JSON.stringify(payload) }); alert('Rubric saved'); document.querySelector('[data-view="qa-rubrics"]').click(); } catch(_){ alert('Failed'); } }; }
      document.addEventListener('click', async (e)=>{ const b=e.target.closest('[data-del-rubric]'); if (!b) return; const id=b.getAttribute('data-del-rubric'); if (!confirm('Delete rubric?')) return; try{ await fetchJSON(api(`/admin/qa-rubrics/${id}`), { method:'DELETE' }); document.querySelector('[data-view="qa-rubrics"]').click(); } catch(_){ alert('Failed to delete'); } }, { once:true });
    });

    document.getElementById('rep-export-all')?.addEventListener('click', ()=>{
      const blocks = [
        'report-agent','report-agent-leader','report-campaign','report-sla','report-agent-summary','report-daily-volume','report-script-perf','report-script-eff','report-dnc','report-dnc-trend','report-campaign-pacing','report-disposition','report-heatmap'
      ];
      const toCsv = (table)=>{
        const rows = Array.from(table.querySelectorAll('tr')).map(tr=>Array.from(tr.querySelectorAll('th,td')).map(td=>`"${String(td.textContent||'').replace(/"/g,'""')}"`).join(','));
        return rows.join('\n');
      };
      blocks.forEach(id=>{
        const el = document.getElementById(id); if(!el) return; const table = el.querySelector('table'); if(!table) return;
        const csv = toCsv(table);
        const blob = new Blob([csv], {type:'text/csv'});
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `${id}-${Date.now()}.csv`; document.body.appendChild(a); a.click(); a.remove();
      });
    });

    document.getElementById('btn-menu')?.addEventListener('click', ()=>{
      const sb = document.getElementById('sidebar');
      if (!sb) return;
      if (sb.classList.contains('hidden')) { sb.classList.remove('hidden'); } else { sb.classList.add('hidden'); }
    });

    // Populate Script Responses dropdowns (scripts and keys)
    try {
      const selSlug = document.getElementById('sr-slug');
      const selKey = document.getElementById('sr-key');
      if (selSlug && selSlug.options.length === 0) {
        const buildKeys = (script)=>{
          const opts = ['<option value=\"\">Select a question key</option>'];
          try { (script.sections||[]).forEach(sec=>{ (sec.questions||[]).forEach(q=>{ if (q && q.key) opts.push(`<option value=\"${q.key}\">${q.key}</option>`); }); }); } catch(_){ }
          selKey.innerHTML = opts.join('');
        };
        fetchJSON(api('/scripts'))
          .then(scr => {
            const items = (scr.items||[]);
            selSlug.innerHTML = `<option value=\"\">All scripts</option>` + items.map(s=>`<option value=\"${s.slug}\">${s.title||s.slug}</option>`).join('');
          })
          .catch(_=>{ /* ignore */ });
        selSlug.onchange = async ()=>{
          const slug = selSlug.value;
          if (!slug) { selKey.innerHTML = '<option value=\"\">Select a question key</option>'; return; }
          try { const sc = await fetchJSON(api(`/scripts/slug/${encodeURIComponent(slug)}`)); buildKeys(sc); } catch(_){ selKey.innerHTML = '<option value=\"\">Select a question key</option>'; }
        };
      }
    } catch(_) { /* ignore */ }

    // Script Responses handlers
    (function(){
      const btnRun = document.getElementById('sr-run');
      const btnAll = document.getElementById('sr-all-run');
      const btnCsvServer = document.getElementById('sr-all-csv-server');
      if (btnRun) btnRun.onclick = async ()=>{
        const slug = document.getElementById('sr-slug')?.value || '';
        const key = document.getElementById('sr-key')?.value || '';
        const days = document.getElementById('sr-since')?.value || '90';
        try {
          const s = await fetchJSON(api('/reports/script-responses/summary') + `&since_days=${encodeURIComponent(days)}` + (slug?`&slug=${encodeURIComponent(slug)}`:''));
          document.getElementById('sr-summary').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Slug</th><th class=\"text-left p-2\">Date</th><th class=\"text-left p-2\">Responses</th></tr></thead><tbody>${(s.items||[]).map(r=>`<tr class=\"border-t\"><td class=\"p-2\">${r.slug}</td><td class=\"p-2\">${r.date}</td><td class=\"p-2\">${r.responses}</td></tr>`).join('')}</tbody></table>`;
        } catch(_){ document.getElementById('sr-summary').textContent='No data'; }
        if (key) {
          try {
            const d = await fetchJSON(api('/reports/script-responses/distribution') + `&since_days=${encodeURIComponent(days)}` + (slug?`&slug=${encodeURIComponent(slug)}`:'' ) + `&key=${encodeURIComponent(key)}`);
            document.getElementById('sr-dist').innerHTML = `<table class=\"w-full text-sm\"><thead><tr><th class=\"text-left p-2\">Value</th><th class=\"text-left p-2\">Count</th></tr></thead><tbody>${(d.items||[]).map(r=>`<tr class=\"border-t\"><td class=\"p-2\">${r.value}</td><td class=\"p-2\">${r.count}</td></tr>`).join('')}</tbody></table>`;
          } catch(_){ document.getElementById('sr-dist').textContent='No data'; }
        } else { document.getElementById('sr-dist').textContent='Select a question key to see distribution'; }
      };
      if (btnAll) btnAll.onclick = async ()=>{
        const slug = document.getElementById('sr-slug')?.value || '';
        const days = document.getElementById('sr-since')?.value || '30';
        const cont = document.getElementById('sr-all'); cont.textContent='Loading...';
        try { let after=''; let rows=[]; for (let i=0;i<5;i++){ const url = api('/reports/script-responses/all') + `&since_days=${encodeURIComponent(days)}` + (slug?`&slug=${encodeURIComponent(slug)}`:'') + `&limit=100` + (after?`&after=${encodeURIComponent(after)}`:''); const res = await fetchJSON(url); rows=rows.concat(res.items||[]); if(!res.next) break; after=res.next; } if(!rows.length){ cont.textContent='No data'; return; } const keys = Array.from(rows.reduce((set,r)=>{ Object.keys(r.answers||{}).forEach(k=>set.add(k)); return set; }, new Set())); const head=['When','Slug'].concat(keys); const html = `<div class=\"overflow-auto\"><table class=\"w-full text-xs\"><thead><tr>${head.map(h=>`<th class=\"text-left p-2\">${h}</th>`).join('')}</tr></thead><tbody>` + rows.map(r=>`<tr class=\"border-t\"><td class=\"p-2\">${r.created_at||''}</td><td class=\"p-2\">${r.slug}</td>${keys.map(k=>`<td class=\"p-2\">${(r.answers&&r.answers[k])?String(r.answers[k]):''}</td>`).join('')}</tr>`).join('') + `</tbody></table></div>`; cont.innerHTML=html; window.__srAllRows=rows; } catch(_){ cont.textContent='Failed to load'; }
      };
      if (btnCsvServer) btnCsvServer.onclick = ()=>{
        const slug = document.getElementById('sr-slug')?.value || '';
        const start = (document.getElementById('sr-start')?.value||'').trim();
        const end = (document.getElementById('sr-end')?.value||'').trim();
        const agent = (document.getElementById('sr-agent')?.value||'').trim();
        const q = (document.getElementById('sr-q')?.value||'').trim();
        const params = new URLSearchParams(); if(slug) params.set('slug', slug); if(start) params.set('start', start); if(end) params.set('end', end); if(agent) params.set('agent', agent); if(q) params.set('q', q);
        const url = api('/reports/script-responses/export.csv') + '&' + params.toString();
        fetch(url, { headers: sessionStorage.getItem('token')? { 'Authorization': 'Bearer '+sessionStorage.getItem('token') } : {} })
          .then(r=>r.blob()).then(blob=>{ const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=`script-responses-${Date.now()}.csv`; document.body.appendChild(a); a.click(); a.remove(); }).catch(()=>alert('Failed to download CSV'));
      };
    })();
  </script>
</body>
</html>


