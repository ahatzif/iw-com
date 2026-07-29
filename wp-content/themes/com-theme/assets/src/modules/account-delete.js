import axios from 'axios';
import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);

        this.abortController = new AbortController();
        this.modal = this.el.querySelector('[data-account-delete="modal"]');
        this.form = this.el.querySelector('[data-account-delete="form"]');
        this.message = this.el.querySelector('[data-account-delete="message"]');
        this.openButton = this.el.querySelector('[data-account-delete="open"]');
        this.submitButton = this.form?.querySelector('button[type="submit"]');
        this.onClick = this.onClick.bind(this);
        this.onSubmit = this.onSubmit.bind(this);
        this.onKeydown = this.onKeydown.bind(this);

        this.el.addEventListener('click', this.onClick, {
            signal: this.abortController.signal,
        });
        this.form?.addEventListener('submit', this.onSubmit, {
            signal: this.abortController.signal,
        });
        document.addEventListener('keydown', this.onKeydown, {
            signal: this.abortController.signal,
        });
    }

    destroy() {
        this.abortController.abort();
        document.body.classList.remove('overflow-hidden');
    }

    onClick(event) {
        const action = event.target.closest('[data-account-delete]');
        if (!action || !this.el.contains(action)) return;

        if (action.dataset.accountDelete === 'open') {
            this.open();
        }

        if (
            action.dataset.accountDelete === 'close'
            || action.dataset.accountDelete === 'backdrop'
        ) {
            this.close();
        }
    }

    onKeydown(event) {
        if (event.key === 'Escape' && this.modal?.classList.contains('is-open')) {
            this.close();
        }
    }

    open() {
        if (!this.modal) return;

        this.clearMessage();
        this.modal.classList.add('is-open');
        this.modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        this.modal.querySelector('[data-account-delete="close"]')?.focus({
            preventScroll: true,
        });
    }

    close() {
        if (!this.modal) return;

        this.modal.classList.remove('is-open');
        this.modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        this.openButton?.focus({ preventScroll: true });
    }

    async onSubmit(event) {
        event.preventDefault();
        if (!this.form || !this.submitButton) return;

        this.clearMessage();
        this.submitButton.disabled = true;

        try {
            const response = await axios.post(
                this.form.action,
                new FormData(this.form),
                { withCredentials: true }
            );

            if (!response.data?.success) {
                this.showMessage(
                    response.data?.data?.message
                    || this.el.dataset.deleteError
                );
                return;
            }

            if (response.data?.data?.redirect) {
                window.location.assign(response.data.data.redirect);
                return;
            }

            window.location.reload();
        } catch (error) {
            this.showMessage(
                error.response?.data?.data?.message
                || this.el.dataset.genericError
            );
        } finally {
            this.submitButton.disabled = false;
        }
    }

    showMessage(text) {
        if (!this.message) return;

        this.message.textContent = text;
        this.message.classList.remove('hidden');
    }

    clearMessage() {
        if (!this.message) return;

        this.message.textContent = '';
        this.message.classList.add('hidden');
    }
}
