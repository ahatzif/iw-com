import { module } from 'modujs';

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export default class extends module {
    constructor(m) {
        super(m);

        this.abortController = new AbortController();
        this.panel = this.el.querySelector('[data-auth-modal-panel]');
        this.views = [...this.el.querySelectorAll('[data-auth-view]')];
        this.triggers = [...document.querySelectorAll('[data-auth-modal-trigger]')];
        this.currentView = 'choice';
        this.lastTrigger = null;

        this.onTrigger = this.onTrigger.bind(this);
        this.onClick = this.onClick.bind(this);
        this.onSubmit = this.onSubmit.bind(this);
        this.onKeydown = this.onKeydown.bind(this);

        this.triggers.forEach(trigger => {
            trigger.addEventListener('click', this.onTrigger, {
                signal: this.abortController.signal,
            });
        });
        this.el.addEventListener('click', this.onClick, {
            signal: this.abortController.signal,
        });
        this.el.addEventListener('submit', this.onSubmit, {
            signal: this.abortController.signal,
        });
        document.addEventListener('keydown', this.onKeydown, {
            signal: this.abortController.signal,
        });

        this.showView('choice', false);
    }

    destroy() {
        this.abortController.abort();
    }

    isLoggedIn() {
        return document.body.classList.contains('logged-in');
    }

    onTrigger(event) {
        if (this.isLoggedIn()) return;

        event.preventDefault();
        this.lastTrigger = event.currentTarget;
        this.open('choice');
    }

    onClick(event) {
        const action = event.target.closest('[data-auth-modal-action]');
        if (!action) return;

        const actionName = action.dataset.authModalAction;

        if (actionName === 'close') {
            this.close();
            return;
        }

        if (actionName === 'show') {
            this.showView(action.dataset.authViewTarget);
            return;
        }

        if (actionName === 'password') {
            this.togglePassword(action);
            return;
        }

        if (actionName === 'google') {
            this.completeAuthentication();
        }
    }

    onSubmit(event) {
        const form = event.target.closest('[data-auth-form]');
        if (!form) return;

        event.preventDefault();

        const formName = form.dataset.authForm;

        if (formName === 'signup') {
            const password = form.querySelector('[name="signup_password"]');
            const confirmation = form.querySelector('[name="signup_password_confirmation"]');
            const passwordsMatch = password?.value === confirmation?.value;
            confirmation?.setCustomValidity(passwordsMatch ? '' : 'Οι κωδικοί δεν ταιριάζουν.');
        }

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (formName === 'forgot') {
            const email = form.querySelector('input[type="email"]')?.value || '';
            const resetEmail = this.el.querySelector('[data-auth-reset-email]');
            if (resetEmail) resetEmail.textContent = email;
            this.showView('forgot-success');
            return;
        }

        this.completeAuthentication();
    }

    onKeydown(event) {
        if (!this.el.classList.contains('active')) return;

        if (event.key === 'Escape') {
            this.close();
            return;
        }

        if (event.key !== 'Tab') return;

        const focusable = [...this.el.querySelectorAll(FOCUSABLE_SELECTOR)]
            .filter(element => !element.closest('[data-auth-view].hidden'));
        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    open(view = 'choice') {
        this.showView(view, false);
        this.el.classList.add('active');
        this.el.setAttribute('aria-hidden', 'false');

        try {
            this.call('stop', false, 'Scroll');
        } catch (error) {
            // Pages without the smooth-scroll module can still use the modal.
        }

        requestAnimationFrame(() => {
            this.getVisibleView()?.querySelector(FOCUSABLE_SELECTOR)?.focus({ preventScroll: true });
        });
    }

    close() {
        this.el.classList.remove('active');
        this.el.setAttribute('aria-hidden', 'true');

        try {
            this.call('start', false, 'Scroll');
        } catch (error) {
            // Pages without the smooth-scroll module can still use the modal.
        }

        this.lastTrigger?.focus({ preventScroll: true });
        window.setTimeout(() => this.showView('choice', false), 300);
    }

    showView(view, shouldFocus = true) {
        if (!view || !this.views.some(item => item.dataset.authView === view)) return;

        this.currentView = view;
        this.views.forEach(item => {
            const isCurrent = item.dataset.authView === view;
            item.classList.toggle('hidden', !isCurrent);
            item.setAttribute('aria-hidden', isCurrent ? 'false' : 'true');
        });

        this.panel?.classList.toggle('max-w-[112rem]', view === 'signup');
        this.panel?.classList.toggle('max-w-[48rem]', view !== 'signup');
        this.el.scrollTop = 0;

        if (shouldFocus) {
            requestAnimationFrame(() => {
                this.getVisibleView()?.querySelector(FOCUSABLE_SELECTOR)?.focus({ preventScroll: true });
            });
        }
    }

    getVisibleView() {
        return this.views.find(item => item.dataset.authView === this.currentView);
    }

    togglePassword(button) {
        const field = button.closest('[data-auth-password-field]');
        const input = field?.querySelector('input');
        const use = button.querySelector('use');
        if (!input) return;

        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
        button.setAttribute('aria-label', isVisible ? 'Εμφάνιση κωδικού' : 'Απόκρυψη κωδικού');
        use?.setAttributeNS(
            'http://www.w3.org/1999/xlink',
            'xlink:href',
            `#icon-${isVisible ? 'show' : 'hide'}-password`
        );
    }

    completeAuthentication() {
        document.body.classList.add('logged-in');
        window.location.assign(this.el.dataset.accountUrl || 'my-account.php');
    }
}
