<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProcessMaker') · BPM Designer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { padding-bottom: 60px; background:#f5f6f8; }
        .navbar-brand { font-weight: 700; }
        .page-wrap { padding: 1.5rem; }
        .nav-pills .nav-link.active { background-color: #4f46e5; }
        .nav-pills .nav-link { color:#4f46e5; }
        /* AI chat dock */
        #ai-dock { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1040; }
        #ai-toggle { border-radius: 0; }
        #ai-panel { display:none; background:#fff; border-top: 2px solid #4f46e5; max-height: 50vh; }
        #ai-messages { height: 240px; overflow-y: auto; background:#fafafe; }
        .ai-msg { margin-bottom:.5rem; }
        .ai-msg .bubble { padding:.5rem .75rem; border-radius:.75rem; display:inline-block; max-width: 85%; white-space: pre-wrap; }
        .ai-msg.user { text-align:right; }
        .ai-msg.user .bubble { background:#4f46e5; color:#fff; }
        .ai-msg.bot .bubble { background:#eceefb; color:#1f2937; }
        .flow-canvas { position: relative; height: 600px; background:#fff; border:1px solid #dee2e6; border-radius:.5rem; overflow:hidden;
            background-image: radial-gradient(#e3e6ef 1px, transparent 1px); background-size: 20px 20px; }
        .flow-node { position:absolute; width:160px; background:#fff; border:2px solid #4f46e5; border-radius:.5rem;
            padding:.5rem; cursor:grab; box-shadow:0 2px 6px rgba(0,0,0,.08); user-select:none; }
        .flow-node.initial { border-color:#16a34a; }
        .flow-node .node-title { font-weight:600; font-size:.85rem; }
        .flow-node .node-actions { position:absolute; top:-10px; right:-10px; display:flex; gap:2px; }
        .flow-node .port { width:14px; height:14px; background:#4f46e5; border-radius:50%; position:absolute;
            left:50%; transform:translateX(-50%); cursor:crosshair; }
        .flow-node .port.out { bottom:-8px; }
        .flow-node .port.in { top:-8px; background:#16a34a; }
        svg.flow-edges { position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; }
        svg.flow-edges path { stroke:#6b7280; stroke-width:2; fill:none; pointer-events:stroke; cursor:pointer; }
        svg.flow-edges path:hover { stroke:#4f46e5; stroke-width:3; }
    </style>
    @stack('head')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:#312e81;">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('projects.index') }}"><i class="bi bi-diagram-3"></i> ProcessMaker</a>
        @if(isset($project) && $project->exists)
        <div class="navbar-nav me-auto">
            <ul class="nav nav-pills">
                <li class="nav-item"><a class="nav-link text-white" href="{{ route('projects.show', $project) }}">Progetto</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="{{ route('flow.index', $project) }}">Flusso</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="{{ route('db.index', $project) }}">Struttura DB</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="{{ route('cases.index', $project) }}">Casi</a></li>
            </ul>
        </div>
        <span class="navbar-text text-white-50">{{ $project->name }}</span>
        @endif
    </div>
</nav>

<div class="page-wrap">
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}
            <button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</div>

@if(isset($project) && $project->exists)
<div id="ai-dock">
    <button id="ai-toggle" class="btn btn-primary w-100 text-start" style="background:#4f46e5;border:none;">
        <i class="bi bi-robot"></i> Assistente IA — chiedi suggerimenti sul processo
    </button>
    <div id="ai-panel" class="p-3">
        <div id="ai-messages" class="p-2 mb-2 rounded">
            <div class="ai-msg bot"><span class="bubble">Ciao! Posso analizzare il processo «{{ $project->name }}» e darti suggerimenti su flusso, task e database. Come posso aiutarti?</span></div>
        </div>
        <form id="ai-form" class="d-flex gap-2">
            <input id="ai-input" class="form-control" placeholder="Scrivi un messaggio..." autocomplete="off">
            <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
        </form>
    </div>
</div>
<script>
    window.AI_CHAT_URL = "{{ route('ai.chat', $project) }}";
    window.AI_PAGE = "@yield('ai_page', 'generale')";
    window.CSRF = document.querySelector('meta[name=csrf-token]').content;
    (function () {
        const toggle = document.getElementById('ai-toggle');
        const panel = document.getElementById('ai-panel');
        const form = document.getElementById('ai-form');
        const input = document.getElementById('ai-input');
        const box = document.getElementById('ai-messages');
        toggle.addEventListener('click', () => {
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        });
        function add(role, text) {
            const d = document.createElement('div');
            d.className = 'ai-msg ' + role;
            const b = document.createElement('span');
            b.className = 'bubble';
            b.textContent = text;
            d.appendChild(b);
            box.appendChild(d);
            box.scrollTop = box.scrollHeight;
        }
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = input.value.trim();
            if (!msg) return;
            add('user', msg);
            input.value = '';
            add('bot', '…');
            const loading = box.lastChild;
            try {
                const res = await fetch(window.AI_CHAT_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.CSRF },
                    body: JSON.stringify({ message: msg, page: window.AI_PAGE }),
                });
                const data = await res.json();
                loading.querySelector('.bubble').textContent = data.reply || 'Nessuna risposta.';
            } catch (err) {
                loading.querySelector('.bubble').textContent = 'Errore di comunicazione con l\'assistente.';
            }
            box.scrollTop = box.scrollHeight;
        });
    })();
</script>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
