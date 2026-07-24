const STRIPE_SCRIPT_SELECTOR = 'script[src*="js.stripe.com"]';
const STRIPE_SCRIPT_SRC = 'https://js.stripe.com/clover/stripe.js';

export function loadStripeJs() {
    if ( window.Stripe ) return Promise.resolve(window.Stripe);
    if ( window.__iwStripeLoaderPromise ) return window.__iwStripeLoaderPromise;

    window.__iwStripeLoaderPromise = new Promise((resolve, reject) => {
        const resolveStripe = () => window.Stripe
            ? resolve(window.Stripe)
            : reject(new Error('Stripe loaded but not available'));

        const existingScript = document.querySelector(STRIPE_SCRIPT_SELECTOR);
        if ( existingScript ) {
            existingScript.addEventListener('load', resolveStripe, { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Failed to load Stripe.js')), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = STRIPE_SCRIPT_SRC;
        script.async = true;
        script.onload = resolveStripe;
        script.onerror = () => reject(new Error('Failed to load Stripe.js'));
        document.head.appendChild(script);
    });

    return window.__iwStripeLoaderPromise;
}
