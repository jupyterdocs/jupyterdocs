// Save / like / dislike / report on documents.
// Every control is a real <form>, so it still works without JS; this just
// submits it in the background and updates the page in place.

const loginUrl = () => document.querySelector('meta[name="login-url"]')?.content || '/login';

function flash(message) {
    let el = document.getElementById('jd-toast');
    if (!el) {
        el = document.createElement('div');
        el.id = 'jd-toast';
        el.setAttribute('role', 'status');
        el.className = 'fixed bottom-5 left-1/2 -translate-x-1/2 z-50 rounded-lg bg-pine text-mint dark:bg-mint dark:text-pine px-4 py-2 text-sm font-display shadow-lg transition-opacity';
        document.body.appendChild(el);
    }
    el.textContent = message;
    el.style.opacity = '1';
    clearTimeout(el._timer);
    el._timer = setTimeout(() => (el.style.opacity = '0'), 2200);
}

async function send(form, extra = {}) {
    const data = Object.fromEntries(new FormData(form));
    try {
        const { data: body } = await window.axios.post(form.action, { ...data, ...extra });
        return body;
    } catch (error) {
        const status = error.response?.status;
        if (status === 401 || status === 419) {
            window.location.href = loginUrl();
        } else if (status === 422) {
            throw error.response.data;
        } else {
            flash('Something went wrong. Please try again.');
        }
        return null;
    }
}

function setSaved(resourceId, saved) {
    document.querySelectorAll(`[data-save-form][data-resource="${resourceId}"]`).forEach((form) => {
        const btn = form.querySelector('button');
        btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
        btn.title = saved ? 'Remove from saved' : 'Save for later';
        form.querySelectorAll('[data-save-label]').forEach((l) => (l.textContent = saved ? 'Saved' : 'Save'));
    });
}

function renderVotes(group, body) {
    group.querySelectorAll('[data-vote-form]').forEach((form) => {
        const kind = form.dataset.voteForm;
        const btn = form.querySelector('button');
        const percent = body[`${kind}_percent`];
        btn.setAttribute('aria-pressed', body.vote === kind ? 'true' : 'false');
        form.querySelector('[data-vote-label]').textContent =
            percent === null ? (kind === 'like' ? 'Like' : 'Dislike') : `${percent}%`;
        btn.title = `${body[kind === 'like' ? 'likes' : 'dislikes']} ${kind === 'like' ? 'like(s)' : 'dislike(s)'}`;
    });
}

document.addEventListener('submit', async (event) => {
    const form = event.target;

    if (form.matches('[data-save-form]')) {
        event.preventDefault();
        const body = await send(form);
        if (body) {
            setSaved(form.dataset.resource, body.saved);
            flash(body.message);
        }
        return;
    }

    if (form.matches('[data-vote-form]')) {
        event.preventDefault();
        const body = await send(form);
        if (body) {
            renderVotes(form.closest('[data-vote-group]'), body);
        }
        return;
    }

    if (form.matches('[data-report-form]')) {
        event.preventDefault();
        const errorEl = form.querySelector('[data-report-error]');
        errorEl.textContent = '';
        try {
            const body = await send(form);
            if (body) {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'report-resource' }));
                document.querySelectorAll('[data-report-label]').forEach((l) => (l.textContent = 'Reported'));
                form.reset();
                flash(body.message);
            }
        } catch (validation) {
            errorEl.textContent = Object.values(validation.errors || {}).flat()[0] || validation.message;
        }
    }
});
