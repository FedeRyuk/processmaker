@extends('layouts.app')
@section('title', 'Flusso · ' . $project->name)
@section('ai_page', 'flow')

@push('head')
<style>
    #cond-panel { display:none; }
    .rule-row { display:grid; grid-template-columns: 1fr 110px 1fr 32px; gap:.4rem; margin-bottom:.4rem; }
    .edge-bend { fill:#fff; stroke:#4f46e5; stroke-width:2; cursor:move; pointer-events:all; }
    .edge-insert { fill:#4f46e5; opacity:.18; cursor:copy; pointer-events:all; }
    .edge-insert:hover { opacity:.9; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-diagram-3"></i> Progettazione del flusso</h4>
    <div class="d-flex gap-2">
        <button id="add-task" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Aggiungi task</button>
        <span class="text-muted small align-self-center">Trascina i task · collega le frecce dai pallini · clicca una freccia per le condizioni · usa i punti sulle frecce per spezzarle</span>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div id="canvas" class="flow-canvas">
            <svg class="flow-edges" id="edges"></svg>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card" id="cond-panel">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-arrow-right"></i> Condizioni della freccia</span>
                <button class="btn-close" id="cond-close"></button>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label small">Etichetta</label>
                    <input id="cond-label" class="form-control form-control-sm">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Logica</label>
                    <select id="cond-logic" class="form-select form-select-sm">
                        <option value="AND">Tutte vere (AND)</option>
                        <option value="OR">Almeno una vera (OR)</option>
                    </select>
                </div>
                <label class="form-label small">Regole sui valori dei campi</label>
                <div id="cond-rules"></div>
                <button id="add-rule" class="btn btn-outline-secondary btn-sm mt-1"><i class="bi bi-plus"></i> Regola</button>
                <hr>
                <div class="d-flex gap-2">
                    <button id="cond-save" class="btn btn-primary btn-sm">Salva condizioni</button>
                    <button id="cond-delete" class="btn btn-outline-danger btn-sm">Elimina freccia</button>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-info-circle"></i> Legenda</div>
            <div class="card-body small text-muted">
                <p class="mb-1"><span class="badge" style="background:#16a34a">verde</span> = task iniziale / porta di ingresso</p>
                <p class="mb-1"><span class="badge" style="background:#4f46e5">viola</span> = porta di uscita</p>
                <p class="mb-1">Clicca il punto chiaro a meta freccia per aggiungere una piega.</p>
                <p class="mb-0">Trascina i punti pieni per modificare la forma; doppio click su un punto per eliminarlo.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const PROJECT = {{ $project->id }};
const ROUTES = {
    storeTask: "{{ route('flow.tasks.store', $project) }}",
    positions: "{{ route('flow.positions', $project) }}",
    destroyTaskBase: "{{ url('projects/'.$project->id.'/flow/tasks') }}",
    storeTransition: "{{ route('flow.transitions.store', $project) }}",
    transitionBase: "{{ url('projects/'.$project->id.'/flow/transitions') }}",
    designerBase: "{{ url('projects/'.$project->id.'/tasks') }}",
};
const CSRF = document.querySelector('meta[name=csrf-token]').content;
let tasks = {!! $tasksJson !!};
let transitions = {!! $transitionsJson !!};
let fieldsByTask = {!! $fieldsByTaskJson !!};

const canvas = document.getElementById('canvas');
const svg = document.getElementById('edges');
let linking = null; // source task id when drawing an arrow
let selectedTransition = null;

async function api(url, method, body) {
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: body ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) throw new Error('Request failed');
    return res.status === 204 ? null : res.json();
}

function render() {
    canvas.querySelectorAll('.flow-node').forEach(n => n.remove());
    tasks.forEach(renderNode);
    renderEdges();
}

function renderNode(t) {
    const el = document.createElement('div');
    el.className = 'flow-node' + (t.is_initial ? ' initial' : '');
    el.style.left = t.pos_x + 'px';
    el.style.top = t.pos_y + 'px';
    el.dataset.id = t.id;
    el.innerHTML = `
        <div class="port in" title="ingresso"></div>
        <div class="node-title">${escapeHtml(t.name)}</div>
        <div class="node-actions">
            <button class="btn btn-sm btn-light border p-0 px-1 act-del" title="Elimina">&times;</button>
        </div>
        <div class="port out" title="uscita: trascina verso un altro task"></div>`;
    canvas.appendChild(el);

    makeDraggable(el, t);

    el.querySelector('.act-del').addEventListener('click', async (e) => {
        e.stopPropagation();
        if (!confirm('Eliminare il task e le sue frecce?')) return;
        await api(ROUTES.destroyTaskBase + '/' + t.id, 'DELETE');
        tasks = tasks.filter(x => x.id !== t.id);
        transitions = transitions.filter(x => x.from !== t.id && x.to !== t.id);
        render();
    });

    el.addEventListener('dblclick', () => {
        window.location = ROUTES.designerBase + '/' + t.id + '/designer';
    });

    // start linking from out port
    el.querySelector('.port.out').addEventListener('mousedown', (e) => {
        e.stopPropagation();
        linking = t.id;
    });
    // finish linking on in port
    el.querySelector('.port.in').addEventListener('mouseup', async (e) => {
        e.stopPropagation();
        if (linking && linking !== t.id) {
            try {
                const tr = await api(ROUTES.storeTransition, 'POST', { from_task_id: linking, to_task_id: t.id });
                if (!transitions.find(x => x.id === tr.id)) {
                    transitions.push({ id: tr.id, from: tr.from_task_id, to: tr.to_task_id, label: tr.label, conditions: tr.conditions, bend_points: tr.bend_points || [] });
                }
                renderEdges();
            } catch (err) { alert('Impossibile creare la freccia.'); }
        }
        linking = null;
    });
}

function makeDraggable(el, t) {
    let ox, oy, dragging = false;
    el.addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('port') || e.target.classList.contains('act-del')) return;
        dragging = true;
        ox = e.clientX - el.offsetLeft;
        oy = e.clientY - el.offsetTop;
        el.style.cursor = 'grabbing';
    });
    document.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        t.pos_x = Math.max(0, e.clientX - ox);
        t.pos_y = Math.max(0, e.clientY - oy);
        el.style.left = t.pos_x + 'px';
        el.style.top = t.pos_y + 'px';
        renderEdges();
    });
    document.addEventListener('mouseup', async () => {
        if (!dragging) return;
        dragging = false;
        el.style.cursor = 'grab';
        await api(ROUTES.positions, 'POST', { positions: [{ id: t.id, x: Math.round(t.pos_x), y: Math.round(t.pos_y) }] });
    });
}

function nodeCenter(id, which) {
    const t = tasks.find(x => x.id === id);
    if (!t) return null;
    const w = 160, h = 56;
    return which === 'out'
        ? { x: t.pos_x + w / 2, y: t.pos_y + h }
        : { x: t.pos_x + w / 2, y: t.pos_y };
}

function renderEdges() {
    svg.innerHTML = `<defs><marker id="arrow" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto">
        <path d="M0,0 L8,3 L0,6 Z" fill="#6b7280"/></marker></defs>`;
    transitions.forEach(tr => {
        const a = nodeCenter(tr.from, 'out');
        const b = nodeCenter(tr.to, 'in');
        if (!a || !b) return;
        tr.bend_points = Array.isArray(tr.bend_points) ? tr.bend_points : [];
        const points = [a, ...tr.bend_points, b];
        const d = points.map((point, index) => `${index === 0 ? 'M' : 'L'}${point.x},${point.y}`).join(' ');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', d);
        path.setAttribute('marker-end', 'url(#arrow)');
        path.addEventListener('click', () => openConditions(tr));
        svg.appendChild(path);
        renderEdgeHandles(tr, points);
        if (tr.label || (tr.conditions && tr.conditions.rules && tr.conditions.rules.length)) {
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            const labelPoint = points[Math.floor(points.length / 2)];
            text.setAttribute('x', labelPoint.x + 6);
            text.setAttribute('y', labelPoint.y);
            text.setAttribute('font-size', '11');
            text.setAttribute('fill', '#4f46e5');
            text.textContent = tr.label || 'condizione';
            svg.appendChild(text);
        }
    });
}

function renderEdgeHandles(tr, points) {
    points.slice(0, -1).forEach((point, index) => {
        const next = points[index + 1];
        const insert = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        insert.setAttribute('class', 'edge-insert');
        insert.setAttribute('cx', (point.x + next.x) / 2);
        insert.setAttribute('cy', (point.y + next.y) / 2);
        insert.setAttribute('r', 7);
        insert.addEventListener('click', async (e) => {
            e.stopPropagation();
            tr.bend_points.splice(index, 0, { x: Number(insert.getAttribute('cx')), y: Number(insert.getAttribute('cy')) });
            renderEdges();
            await saveTransitionShape(tr);
        });
        svg.appendChild(insert);
    });

    tr.bend_points.forEach((point, index) => {
        const bend = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        bend.setAttribute('class', 'edge-bend');
        bend.setAttribute('cx', point.x);
        bend.setAttribute('cy', point.y);
        bend.setAttribute('r', 6);
        bend.addEventListener('mousedown', (e) => startBendDrag(e, tr, index));
        bend.addEventListener('dblclick', async (e) => {
            e.stopPropagation();
            tr.bend_points.splice(index, 1);
            renderEdges();
            await saveTransitionShape(tr);
        });
        svg.appendChild(bend);
    });
}

function startBendDrag(e, tr, index) {
    e.preventDefault();
    e.stopPropagation();
    let moved = false;
    const onMove = (event) => {
        moved = true;
        const point = canvasPoint(event);
        tr.bend_points[index] = point;
        renderEdges();
    };
    const onUp = async () => {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        if (moved) await saveTransitionShape(tr);
    };
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
}

function canvasPoint(event) {
    const rect = canvas.getBoundingClientRect();
    return {
        x: Math.max(0, Math.min(canvas.clientWidth, event.clientX - rect.left)),
        y: Math.max(0, Math.min(canvas.clientHeight, event.clientY - rect.top)),
    };
}

async function saveTransitionShape(tr) {
    const saved = await api(ROUTES.transitionBase + '/' + tr.id, 'PUT', { bend_points: tr.bend_points });
    tr.bend_points = saved.bend_points || [];
}

// ---- Conditions panel ----
const panel = document.getElementById('cond-panel');
const rulesBox = document.getElementById('cond-rules');

function allFieldsFor(fromId) {
    return (fieldsByTask[fromId] || []);
}

function openConditions(tr) {
    selectedTransition = tr;
    panel.style.display = 'block';
    document.getElementById('cond-label').value = tr.label || '';
    document.getElementById('cond-logic').value = (tr.conditions && tr.conditions.logic) || 'AND';
    rulesBox.innerHTML = '';
    const rules = (tr.conditions && tr.conditions.rules) || [];
    if (rules.length === 0) addRule();
    else rules.forEach(addRule);
}

function addRule(rule) {
    rule = rule || { field: '', operator: '==', value: '' };
    const fields = allFieldsFor(selectedTransition.from);
    const row = document.createElement('div');
    row.className = 'rule-row';
    const opts = fields.map(f => `<option value="${escapeHtml(f.name)}" ${f.name===rule.field?'selected':''}>${escapeHtml(f.label)}</option>`).join('');
    row.innerHTML = `
        <select class="form-select form-select-sm r-field"><option value="">— campo —</option>${opts}</select>
        <select class="form-select form-select-sm r-op">
            ${['==','!=','>','>=','<','<=','contains','empty','not_empty'].map(o=>`<option ${o===rule.operator?'selected':''}>${o}</option>`).join('')}
        </select>
        <input class="form-control form-control-sm r-val" value="${escapeHtml(rule.value ?? '')}">
        <button class="btn btn-sm btn-outline-danger r-del">&times;</button>`;
    row.querySelector('.r-del').addEventListener('click', () => row.remove());
    rulesBox.appendChild(row);
}

document.getElementById('add-rule').addEventListener('click', () => addRule());
document.getElementById('cond-close').addEventListener('click', () => panel.style.display = 'none');

document.getElementById('cond-save').addEventListener('click', async () => {
    const rules = [...rulesBox.querySelectorAll('.rule-row')].map(r => ({
        field: r.querySelector('.r-field').value,
        operator: r.querySelector('.r-op').value,
        value: r.querySelector('.r-val').value,
    })).filter(r => r.field);
    const payload = {
        label: document.getElementById('cond-label').value,
        conditions: { logic: document.getElementById('cond-logic').value, rules },
    };
    const tr = await api(ROUTES.transitionBase + '/' + selectedTransition.id, 'PUT', payload);
    selectedTransition.label = tr.label;
    selectedTransition.conditions = tr.conditions;
    renderEdges();
    panel.style.display = 'none';
});

document.getElementById('cond-delete').addEventListener('click', async () => {
    if (!confirm('Eliminare questa freccia?')) return;
    await api(ROUTES.transitionBase + '/' + selectedTransition.id, 'DELETE');
    transitions = transitions.filter(x => x.id !== selectedTransition.id);
    renderEdges();
    panel.style.display = 'none';
});

document.getElementById('add-task').addEventListener('click', async () => {
    const name = prompt('Nome del nuovo task:');
    if (!name) return;
    const isInitial = tasks.length === 0;
    const t = await api(ROUTES.storeTask, 'POST', { name, pos_x: 60 + (tasks.length*30)%300, pos_y: 60 + (tasks.length*40)%300, is_initial: isInitial });
    tasks.push({ id: t.id, name: t.name, is_initial: t.is_initial, pos_x: t.pos_x, pos_y: t.pos_y });
    fieldsByTask[t.id] = [];
    render();
});

function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

canvas.addEventListener('mouseup', () => linking = null);
render();
</script>
@endpush
