import { module } from 'modujs';
import Emitter from 'tiny-emitter/instance';
import axios from 'axios';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { resend: 'resendCode' } };
        this.detailsForm = this.$('details-form')[0];
        this.verifyForm = this.$('verify-form')[0];
        this.phoneLabel = this.$('phone')[0];
        this.resendButton = this.$('resend')[0];
        this.resendMessage = this.$('resend-message')[0];
        this.resendTimer = null;
        this.resendDelay = this.verifyForm ? parseInt(this.verifyForm.dataset.resendDelay || '60', 10) : 60;

        Emitter.on('form-success', this.onFormSuccess.bind(this));
    }

    onFormSuccess(args) {
        if (args.el !== this.detailsForm) return;

        let data = args.response && args.response.data && args.response.data.data ? args.response.data.data : {};
        if (!data.phone_verification_required) return;

        if (this.phoneLabel) {
            this.phoneLabel.textContent = data.pending_phone || '';
        }

        if (this.detailsForm) {
            this.detailsForm.classList.add('hidden');
        }

        if (this.verifyForm) {
            this.verifyForm.classList.remove('hidden');
        }

        this.startResendCountdown(data.retry_after || this.resendDelay);
    }

    resendCode(e) {
        e.preventDefault();
        if (!this.verifyForm || !this.resendButton || this.resendButton.disabled) return;

        let formData = new FormData(this.verifyForm);
        formData.set('action', 'iw-auth-resend-account-phone');
        formData.delete('activation_code');

        this.resendButton.disabled = true;
        this.setResendMessage('');

        axios.post(this.verifyForm.getAttribute('action'), formData, { withCredentials: true })
            .then((response) => {
                let data = response.data && response.data.data ? response.data.data : {};
                this.setResendMessage(data.message || '');
                this.startResendCountdown(data.retry_after || this.resendDelay);
            })
            .catch((error) => {
                let data = error.response && error.response.data && error.response.data.data ? error.response.data.data : {};
                this.setResendMessage(data.message || '');
                if (data.retry_after) {
                    this.startResendCountdown(data.retry_after);
                } else {
                    this.enableResendButton();
                }
            });
    }

    startResendCountdown(seconds) {
        if (!this.resendButton) return;

        this.clearResendCountdown(false);
        let remaining = parseInt(seconds || this.resendDelay, 10);
        if (!remaining || remaining < 1) {
            this.enableResendButton();
            return;
        }

        let label = this.resendButton.dataset.label || this.resendButton.textContent.trim();
        let countdownLabel = this.resendButton.dataset.countdownLabel || label + ' ({seconds}s)';

        let update = () => {
            if (remaining <= 0) {
                this.enableResendButton();
                return;
            }

            this.resendButton.disabled = true;
            this.resendButton.textContent = countdownLabel.replace('{seconds}', remaining);
            remaining -= 1;
        };

        update();
        this.resendTimer = window.setInterval(update, 1000);
    }

    clearResendCountdown(enableButton = true) {
        if (this.resendTimer) {
            window.clearInterval(this.resendTimer);
            this.resendTimer = null;
        }

        if (enableButton) {
            this.enableResendButton();
        }
    }

    enableResendButton() {
        if (!this.resendButton) return;

        if (this.resendTimer) {
            window.clearInterval(this.resendTimer);
            this.resendTimer = null;
        }

        this.resendButton.disabled = false;
        this.resendButton.textContent = this.resendButton.dataset.label || this.resendButton.textContent;
    }

    setResendMessage(message) {
        if (this.resendMessage) {
            this.resendMessage.textContent = message;
        }
    }
}
