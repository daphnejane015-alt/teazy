<!-- Tea Uses Modal (shared by recommendations and recommendation-result) -->
<div id="teaUsesModal" class="fixed inset-0 z-50 hidden" aria-labelledby="tea-uses-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeTeaUses()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all max-w-2xl w-full max-h-[90vh] flex flex-col">
                <div class="p-6 text-white flex items-center justify-between" style="background: var(--accent-green);">
                    <h3 id="modalTeaUsesTitle" class="text-xl font-bold">What can this tea do?</h3>
                    <button onclick="closeTeaUses()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div id="modalTeaUsesBody" class="p-6 overflow-y-auto" style="background: var(--cream-green, #f3f4f6);">
                    <div class="text-center py-8 text-gray-500">Click a tea to see fun and practical ideas.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openTeaUses(teaId, teaName) {
    document.getElementById('modalTeaUsesTitle').textContent = 'What can ' + teaName + ' do?';
    const body = document.getElementById('modalTeaUsesBody');
    body.innerHTML = '<div class="text-center py-8 text-gray-500">Loading ideas…</div>';
    document.getElementById('teaUsesModal').classList.remove('hidden');

    fetch(`/teas/${teaId}/uses`, { headers: { 'Accept': 'application/json' } })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) throw new Error(data.message || 'Could not load ideas right now.');
            return data;
        })
        .then(d => {
            if (!d.available || !d.uses) {
                throw new Error('No ideas available right now.');
            }
            body.innerHTML = renderTeaUses(d.uses);
        })
        .catch(e => {
            body.innerHTML = '<div class="text-center py-8 text-red-500">' + escapeHtml(e.message) + '</div>';
        });
}

function closeTeaUses() {
    document.getElementById('teaUsesModal').classList.add('hidden');
}

function normalizeList(value) {
    if (Array.isArray(value)) return value.filter(v => v && typeof v === 'string');
    if (typeof value === 'string' && value.trim()) return value.split(/,|\n/).map(s => s.trim()).filter(s => s);
    return [];
}

function renderSimpleCard(item) {
    let html = '<div class="p-4 rounded-xl bg-white border shadow-sm" style="border-color: var(--border-color);">';
    html += '<h5 class="font-semibold" style="color: var(--text-dark);">' + escapeHtml(item.title || '') + '</h5>';
    html += '<p class="text-sm mt-1" style="color: var(--text-light);">' + escapeHtml(item.description || '') + '</p>';
    html += '</div>';
    return html;
}

function renderDrinkVariation(item) {
    const ingredients = normalizeList(item.ingredients);
    const steps = normalizeList(item.steps);
    const hasRecipe = ingredients.length > 0 || steps.length > 0;
    let html = '<div class="p-4 rounded-xl bg-white border shadow-sm" style="border-color: var(--border-color);">';
    html += '<h5 class="font-semibold" style="color: var(--text-dark);">' + escapeHtml(item.title || '') + '</h5>';
    html += '<p class="text-sm mt-1" style="color: var(--text-light);">' + escapeHtml(item.description || '') + '</p>';
    if (hasRecipe) {
        html += '<button type="button" onclick="toggleTeaUsesDetails(this)" class="mt-3 inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold text-white transition-colors" style="background: var(--accent-green);">';
        html += '<span class="mr-1">Show recipe</span>';
        html += '<svg class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';
        html += '</button>';
        html += '<div class="hidden mt-4 pt-4 border-t text-sm space-y-4" style="border-color: var(--border-color); color: var(--text-medium);">';
        if (ingredients.length > 0) {
            html += '<div><h6 class="font-semibold mb-2" style="color: var(--text-dark);">Ingredients</h6><ul class="list-disc list-inside space-y-1">' + ingredients.map(i => '<li>' + escapeHtml(i) + '</li>').join('') + '</ul></div>';
        }
        if (steps.length > 0) {
            html += '<div><h6 class="font-semibold mb-2" style="color: var(--text-dark);">Steps</h6><ol class="list-decimal list-inside space-y-1">' + steps.map(s => '<li>' + escapeHtml(s) + '</li>').join('') + '</ol></div>';
        }
        html += '</div>';
    }
    html += '</div>';
    return html;
}

function renderTeaUses(uses) {
    const labels = {
        drink_variations: '🥤 Drink Variations',
        mixer_ideas: '🍹 Mixer Ideas',
        food_pairings: '🍽️ Food Pairings',
        wellness_rituals: '🧘 Wellness Rituals',
        diy_uses: '🛠️ DIY Uses',
    };
    const order = ['drink_variations', 'mixer_ideas', 'food_pairings', 'wellness_rituals', 'diy_uses'];
    let html = '';

    order.forEach(key => {
        const items = uses[key];
        if (!Array.isArray(items) || items.length === 0) return;

        html += '<div class="mb-6">';
        html += '<h4 class="text-lg font-bold mb-3" style="color: var(--primary-green);">' + escapeHtml(labels[key]) + '</h4>';
        html += '<div class="space-y-3">';
        if (key === 'drink_variations') {
            items.forEach(item => { html += renderDrinkVariation(item); });
        } else {
            items.forEach(item => { html += renderSimpleCard(item); });
        }
        html += '</div></div>';
    });

    return html || '<div class="text-center text-gray-500">No ideas found.</div>';
}

function toggleTeaUsesDetails(btn) {
    const details = btn.nextElementSibling;
    if (!details) return;
    const isHidden = details.classList.contains('hidden');
    const label = btn.querySelector('span');
    const icon = btn.querySelector('svg');
    if (isHidden) {
        details.classList.remove('hidden');
        if (label) label.textContent = 'Hide recipe';
        if (icon) icon.classList.add('rotate-180');
    } else {
        details.classList.add('hidden');
        if (label) label.textContent = 'Show recipe';
        if (icon) icon.classList.remove('rotate-180');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeTeaUses();
});
</script>
