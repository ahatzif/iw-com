<?php
/**
 * WooCommerce email previews for IW Email Template.
 *
 * @package Iw_Email_Template
 */

if (! defined('ABSPATH')) {
    exit;
}

class IW_Email_Template_Email_Previews
{
    private const QUERY_VAR = 'iw_email_previews';
    private const ENDPOINT = 'email-previews';
    private const TICKET_PDF_EMAIL_ID = 'IW_Ticket_PDF_Email';
    private const SEND_ACTION = 'iw_send_preview_email';

    private array $order_email_ids = [
        'WC_Email_New_Order',
        'WC_Email_Cancelled_Order',
        'WC_Email_Customer_Cancelled_Order',
        'WC_Email_Failed_Order',
        'WC_Email_Customer_Failed_Order',
        'WC_Email_Customer_On_Hold_Order',
        'WC_Email_Customer_Processing_Order',
        'WC_Email_Customer_Completed_Order',
        'WC_Email_Customer_Refunded_Order',
        'WC_Email_Customer_Invoice',
        'WC_Email_Customer_Note',
        'WC_Email_Customer_POS_Completed_Order',
        'WC_Email_Customer_POS_Refunded_Order',
        'WCS_Email_New_Renewal_Order',
        'WCS_Email_New_Switch_Order',
        'WCS_Email_Processing_Renewal_Order',
        'WCS_Email_Completed_Renewal_Order',
        'WCS_Email_Customer_On_Hold_Renewal_Order',
        'WCS_Email_Completed_Switch_Order',
        'WCS_Email_Customer_Renewal_Invoice',
        'WC_Stripe_Email_Failed_Renewal_Authentication',
        'WC_Stripe_Email_Failed_Preorder_Authentication',
        'WC_Stripe_Email_Failed_Authentication_Retry',
        'WC_Stripe_Email_Admin_Failed_Refund',
        'WC_Stripe_Email_Customer_Failed_Refund',
    ];

    private array $subscription_email_ids = [
        'WCS_Email_Cancelled_Subscription',
        'WCS_Email_Expired_Subscription',
        'WCS_Email_On_Hold_Subscription',
        'WCS_Email_Customer_Notification_Manual_Trial_Expiration',
        'WCS_Email_Customer_Notification_Auto_Trial_Expiration',
        'WCS_Email_Customer_Notification_Manual_Renewal',
        'WCS_Email_Customer_Notification_Auto_Renewal',
        'WCS_Email_Customer_Notification_Subscription_Expiration',
    ];

    private array $gifting_email_ids = [
        'WCSG_Email_Customer_New_Account',
        'WCSG_Email_Recipient_New_Initial_Order',
        'WCSG_Email_Processing_Renewal_Order',
        'WCSG_Email_Completed_Renewal_Order',
    ];

    private array $custom_email_ids = [
        self::TICKET_PDF_EMAIL_ID => 'Tickets PDF email · IW Tickets',
    ];

    public function __construct()
    {
        add_action('init', [$this, 'register_rewrite']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'handle_endpoint']);
    }

    public static function activate(): void
    {
        add_rewrite_rule('^email-previews/?$', 'index.php?iw_email_previews=1', 'top');
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public function register_rewrite(): void
    {
        add_rewrite_rule('^' . self::ENDPOINT . '/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
    }

    public function register_query_vars(array $vars): array
    {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public function handle_endpoint(): void
    {
        if (! get_query_var(self::QUERY_VAR)) {
            return;
        }

        if (! is_user_logged_in()) {
            auth_redirect();
        }

        if (! function_exists('WC')) {
            wp_die('WooCommerce is not available.', 'Email previews', ['response' => 500]);
        }

        if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['iw_action'])) {
            $action = sanitize_text_field(wp_unslash($_POST['iw_action']));

            if (self::SEND_ACTION === $action) {
                $this->handle_send_preview_email();
                exit;
            }
        }

        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $subscription_id = isset($_GET['subscription_id']) ? absint($_GET['subscription_id']) : 0;
        $email_id = isset($_GET['email']) ? sanitize_text_field(wp_unslash($_GET['email'])) : '';

        if ($email_id) {
            $this->render_email_preview($order_id, $subscription_id, $email_id);
            exit;
        }

        $this->render_index($order_id, $subscription_id);
        exit;
    }

    private function render_index(int $order_id, int $subscription_id): void
    {
        $order = $order_id ? wc_get_order($order_id) : null;
        $subscription = $this->get_subscription($subscription_id);
        $current_user = wp_get_current_user();
        $default_send_to_email = $current_user instanceof WP_User ? $current_user->user_email : '';
        $default_send_from_email = $this->get_default_sender_email();
        $send_to_email = isset($_GET['send_to_email'])
            ? sanitize_email(wp_unslash($_GET['send_to_email']))
            : $default_send_to_email;
        $send_from_email = isset($_GET['send_from_email'])
            ? sanitize_email(wp_unslash($_GET['send_from_email']))
            : $default_send_from_email;
        $selected_email_id = isset($_GET['email']) ? sanitize_text_field(wp_unslash($_GET['email'])) : '';
        $send_status = isset($_GET['send_status']) ? sanitize_key(wp_unslash($_GET['send_status'])) : '';
        $send_message = isset($_GET['send_message']) ? sanitize_text_field(wp_unslash($_GET['send_message'])) : '';

        if (! $order && $subscription instanceof WC_Subscription && $subscription->get_parent_id()) {
            $order = wc_get_order($subscription->get_parent_id());
            $order_id = $order ? $order->get_id() : 0;
        }

        if (! $subscription && $order && function_exists('wcs_get_subscriptions_for_order')) {
            $subscriptions = wcs_get_subscriptions_for_order($order, ['order_type' => 'any']);
            $subscription = $subscriptions ? reset($subscriptions) : null;
            $subscription_id = $subscription instanceof WC_Subscription ? $subscription->get_id() : 0;
        }

        $emails = WC()->mailer()->get_emails();
        $groups = [
            'WooCommerce orders' => $this->order_email_ids,
            'WooCommerce Subscriptions' => $this->subscription_email_ids,
            'WooCommerce Subscriptions Gifting' => $this->gifting_email_ids,
            'IW Tickets' => array_keys($this->custom_email_ids),
        ];
        $available = [];
        $unavailable = [];

        foreach ($groups as $group => $email_ids) {
            foreach ($email_ids as $email_id) {
                if (isset($this->custom_email_ids[$email_id])) {
                    $available[$group][$email_id] = $this->custom_email_ids[$email_id];
                } elseif (isset($emails[$email_id])) {
                    $available[$group][$email_id] = $emails[$email_id];
                } else {
                    $unavailable[] = $email_id;
                }
            }
        }

        $first_email_id = '';
        foreach ($available as $items) {
            if ($items) {
                $first_email_id = array_key_first($items);
                break;
            }
        }

        if (! $selected_email_id || ! $this->is_previewable_email($selected_email_id)) {
            $selected_email_id = $first_email_id;
        }

        $recent_orders = function_exists('wc_get_orders') ? wc_get_orders([
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ]) : [];
        $recent_subscriptions = function_exists('wcs_get_subscriptions') ? wcs_get_subscriptions([
            'subscriptions_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ]) : [];

        if (! $order && ! $subscription && $recent_orders) {
            $recent_order = reset($recent_orders);

            if ($recent_order instanceof WC_Order) {
                $order = $recent_order;
                $order_id = $order->get_id();

                if (function_exists('wcs_get_subscriptions_for_order')) {
                    $subscriptions = wcs_get_subscriptions_for_order($order, ['order_type' => 'any']);
                    $subscription = $subscriptions ? reset($subscriptions) : null;
                    $subscription_id = $subscription instanceof WC_Subscription ? $subscription->get_id() : 0;
                }
            }
        }

        $base_url = home_url('/' . self::ENDPOINT . '/');

        nocache_headers();
        status_header(200);

        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Email previews</title>';
        echo '<style>
            *{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;color:#242424;background:#f6f6f2}
            .wrap{display:grid;grid-template-columns:360px 1fr;min-height:100vh}
            aside{padding:24px;background:#fff;border-right:1px solid #ddd;overflow:auto}
            main{padding:24px;min-width:0}
            h1{font-size:26px;margin:0 0 22px}h2{font-size:15px;margin:24px 0 10px}
            label{display:block;margin:0 0 14px;font-size:12px;font-weight:bold;color:#666;text-transform:uppercase;letter-spacing:.03em}
            select,input{display:block;width:100%;margin-top:7px;padding:11px 12px;border:1px solid #bbb;border-radius:6px;background:#fff;color:#222;font-size:14px}
            .manual{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:4px 0 18px}
            .hint{margin:-5px 0 18px;color:#666;font-size:12px;line-height:1.45}
            .meta{padding:12px 0;border-top:1px solid #eee;border-bottom:1px solid #eee;margin:18px 0;color:#666;font-size:13px;line-height:1.6}
            .muted{color:#666;font-size:13px;line-height:1.4}.error,.notice{padding:12px;border-radius:6px}
            .error{border:1px solid #b00020;background:#fff4f4;color:#7d0016}
            .notice{margin:0 0 18px;border:1px solid #1f7a1f;background:#f2fff2;color:#165516}
            .toolbar{display:flex;justify-content:flex-end;align-items:end;gap:12px;margin:0 0 16px}
            .toolbar-text{color:#666;font-size:13px;line-height:1.4}
            .send-form{display:grid;grid-template-columns:minmax(210px,1fr) minmax(210px,1fr) auto;align-items:end;gap:10px;width:100%;max-width:760px}
            .send-form label{margin:0;font-size:11px}
            .send-form input{margin-top:5px}
            .button{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;border:0;border-radius:999px;background:#111;color:#fff;font-size:14px;font-weight:700;cursor:pointer}
            .button[disabled]{opacity:.5;cursor:not-allowed}
            iframe{width:100%;height:calc(100vh - 48px);border:1px solid #ccc;background:#fff}
            @media(max-width:900px){.wrap{grid-template-columns:1fr}form{grid-template-columns:1fr}iframe{height:80vh}aside{border-right:0;border-bottom:1px solid #ddd}.toolbar{flex-direction:column;align-items:stretch}.send-form{max-width:none;grid-template-columns:1fr}.button{width:100%}}
        </style>';
        echo '</head><body><div class="wrap"><aside>';
        echo '<h1>Email previews</h1>';

        if ('success' === $send_status && $send_message) {
            echo '<p class="notice">' . esc_html($send_message) . '</p>';
        } elseif ('error' === $send_status && $send_message) {
            echo '<p class="error">' . esc_html($send_message) . '</p>';
        }

        echo '<label for="iw-preview-order">Order<select id="iw-preview-order">';
        echo '<option value="">No order</option>';
        foreach ($recent_orders as $recent_order) {
            if (! $recent_order instanceof WC_Order) {
                continue;
            }

            $selected = (int) $recent_order->get_id() === (int) $order_id ? ' selected' : '';
            $recent_order_subscription_id = 0;

            if (function_exists('wcs_get_subscriptions_for_order')) {
                $recent_order_subscriptions = wcs_get_subscriptions_for_order($recent_order, ['order_type' => 'any']);
                $recent_order_subscription = $recent_order_subscriptions ? reset($recent_order_subscriptions) : null;
                $recent_order_subscription_id = $recent_order_subscription instanceof WC_Subscription ? $recent_order_subscription->get_id() : 0;
            }

            echo '<option value="' . esc_attr((string) $recent_order->get_id()) . '" data-subscription-id="' . esc_attr((string) $recent_order_subscription_id) . '"' . $selected . '>#' . esc_html((string) $recent_order->get_id()) . ' · ' . esc_html(wc_get_order_status_name($recent_order->get_status())) . ' · ' . esc_html($recent_order->get_billing_email()) . '</option>';
        }
        echo '</select></label>';

        echo '<label for="iw-preview-subscription">Subscription<select id="iw-preview-subscription">';
        echo '<option value="">No subscription</option>';
        foreach ($recent_subscriptions as $recent_subscription) {
            if (! $recent_subscription instanceof WC_Subscription) {
                continue;
            }

            $selected = (int) $recent_subscription->get_id() === (int) $subscription_id ? ' selected' : '';
            echo '<option value="' . esc_attr((string) $recent_subscription->get_id()) . '"' . $selected . '>#' . esc_html((string) $recent_subscription->get_id()) . ' · ' . esc_html(wc_get_order_status_name($recent_subscription->get_status())) . ' · ' . esc_html($recent_subscription->get_billing_email()) . '</option>';
        }
        echo '</select></label>';

        echo '<label for="iw-preview-email">Email template<select id="iw-preview-email">';
        foreach ($available as $group => $items) {
            echo '<optgroup label="' . esc_attr($group) . '">';
            foreach ($items as $id => $email) {
                $selected = $id === $selected_email_id ? ' selected' : '';
                $email_title = is_object($email) && method_exists($email, 'get_title') ? $email->get_title() : (string) $email;
                echo '<option value="' . esc_attr($id) . '"' . $selected . '>' . esc_html($email_title) . ' · ' . esc_html($id) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select></label>';

        echo '<div class="manual">';
        echo '<input id="iw-preview-order-manual" inputmode="numeric" placeholder="Order ID" value="' . esc_attr((string) $order_id) . '">';
        echo '<input id="iw-preview-subscription-manual" inputmode="numeric" placeholder="Subscription ID" value="' . esc_attr((string) $subscription_id) . '">';
        echo '</div>';
        echo '<p class="hint">Τα selects φορτώνουν αυτόματα. Για παλιότερο order/subscription, βάλε ID χειροκίνητα.</p>';

        if (! $order_id && ! $subscription_id) {
            echo '<p class="muted">Choose an order or subscription to preview WooCommerce emails with the active email wrapper.</p>';
        } elseif ($order_id && ! $order) {
            echo '<p class="error">Order #' . esc_html((string) $order_id) . ' was not found.</p>';
        } else {
            echo '<div class="meta">';
            if ($order) {
                echo 'Order #' . esc_html((string) $order_id) . ' · ' . esc_html($order->get_billing_email()) . '<br>';
            }

            if ($subscription instanceof WC_Subscription) {
                $recipient_text = '';
                if (class_exists('WCS_Gifting')) {
                    $recipient_id = absint(WCS_Gifting::get_recipient_user($subscription));
                    $recipient_user = $recipient_id ? get_user_by('id', $recipient_id) : null;
                    $recipient_text = $recipient_user ? ' · recipient: ' . $recipient_user->user_email : '';
                }
                echo 'Subscription #' . esc_html((string) $subscription_id) . ' · ' . esc_html($subscription->get_billing_email() . $recipient_text);
            } elseif ($subscription_id) {
                echo '<p class="error">Subscription #' . esc_html((string) $subscription_id) . ' was not found.</p>';
            }
            echo '</div>';

            if ($unavailable) {
                echo '<h2>Unavailable</h2>';
                foreach ($unavailable as $id) {
                    echo '<p class="muted">' . esc_html($id) . '</p>';
                }
            }
        }

        echo '</aside><main>';
        $iframe_url = (($order || $subscription) && $selected_email_id)
            ? add_query_arg(['order_id' => $order_id, 'subscription_id' => $subscription_id, 'email' => $selected_email_id], $base_url)
            : 'about:blank';
        echo '<div class="toolbar">';
        echo '<form method="post" id="iw-preview-send-form" class="send-form">';
        wp_nonce_field(self::SEND_ACTION, 'iw_send_preview_nonce');
        echo '<input type="hidden" name="iw_action" value="' . esc_attr(self::SEND_ACTION) . '">';
        echo '<input type="hidden" name="order_id" id="iw-send-order-id" value="' . esc_attr((string) $order_id) . '">';
        echo '<input type="hidden" name="subscription_id" id="iw-send-subscription-id" value="' . esc_attr((string) $subscription_id) . '">';
        echo '<input type="hidden" name="email" id="iw-send-email-id" value="' . esc_attr($selected_email_id) . '">';
        echo '<label for="iw-send-from-email">From<input type="email" name="send_from_email" id="iw-send-from-email" value="' . esc_attr($send_from_email) . '" placeholder="' . esc_attr($default_send_from_email) . '"></label>';
        echo '<label for="iw-send-to-email">To<input type="email" name="send_to_email" id="iw-send-to-email" value="' . esc_attr($send_to_email) . '" placeholder="' . esc_attr($default_send_to_email ?: 'recipient@example.com') . '"></label>';
        echo '<button type="submit" class="button" id="iw-send-preview-button">Send preview</button>';
        echo '</form>';
        echo '</div>';
        echo '<iframe name="email-preview-frame" src="' . esc_url($iframe_url) . '"></iframe>';
        echo '</main></div>';

        echo '<script>
            const baseUrl = ' . wp_json_encode($base_url) . ';
            const orderSelect = document.getElementById("iw-preview-order");
            const subSelect = document.getElementById("iw-preview-subscription");
            const emailSelect = document.getElementById("iw-preview-email");
            const orderManual = document.getElementById("iw-preview-order-manual");
            const subManual = document.getElementById("iw-preview-subscription-manual");
            const frame = document.querySelector("iframe[name=email-preview-frame]");
            const sendOrderId = document.getElementById("iw-send-order-id");
            const sendSubscriptionId = document.getElementById("iw-send-subscription-id");
            const sendEmailId = document.getElementById("iw-send-email-id");
            const sendButton = document.getElementById("iw-send-preview-button");

            function currentOrderId() {
                return orderManual.value.trim() || orderSelect.value;
            }

            function currentSubscriptionId() {
                return subManual.value.trim() || subSelect.value;
            }

            function previewUrl(includeEmail = true) {
                const url = new URL(baseUrl);
                const orderId = currentOrderId();
                const subscriptionId = currentSubscriptionId();
                if (orderId) url.searchParams.set("order_id", orderId);
                if (subscriptionId) url.searchParams.set("subscription_id", subscriptionId);
                if (includeEmail && emailSelect.value) url.searchParams.set("email", emailSelect.value);
                return url.toString();
            }

            function syncSendForm() {
                if (sendOrderId) sendOrderId.value = currentOrderId();
                if (sendSubscriptionId) sendSubscriptionId.value = currentSubscriptionId();
                if (sendEmailId) sendEmailId.value = emailSelect.value;
                if (sendButton) sendButton.disabled = !emailSelect.value || (!currentOrderId() && !currentSubscriptionId());
            }

            function updatePreview() {
                syncSendForm();
                if (!frame || !emailSelect.value || (!currentOrderId() && !currentSubscriptionId())) return;
                frame.src = previewUrl(true);
                window.history.replaceState({}, "", previewUrl(false));
            }

            orderSelect.addEventListener("change", () => {
                const selectedOption = orderSelect.options[orderSelect.selectedIndex];
                const subscriptionId = selectedOption ? selectedOption.dataset.subscriptionId : "";
                orderManual.value = orderSelect.value;

                if (subscriptionId) {
                    subSelect.value = subscriptionId;
                    subManual.value = subscriptionId;
                }

                updatePreview();
            });
            subSelect.addEventListener("change", () => { subManual.value = subSelect.value; updatePreview(); });
            emailSelect.addEventListener("change", updatePreview);
            orderManual.addEventListener("change", updatePreview);
            subManual.addEventListener("change", updatePreview);
            orderManual.addEventListener("keydown", (event) => { if (event.key === "Enter") updatePreview(); });
            subManual.addEventListener("keydown", (event) => { if (event.key === "Enter") updatePreview(); });
            syncSendForm();
        </script>';
        echo '</body></html>';
    }

    private function handle_send_preview_email(): void
    {
        if (! isset($_POST['iw_send_preview_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['iw_send_preview_nonce'])), self::SEND_ACTION)) {
            wp_die('Invalid request.', 'Email previews', ['response' => 403]);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $subscription_id = isset($_POST['subscription_id']) ? absint($_POST['subscription_id']) : 0;
        $email_id = isset($_POST['email']) ? sanitize_text_field(wp_unslash($_POST['email'])) : '';
        $recipient = isset($_POST['send_to_email']) ? sanitize_email(wp_unslash($_POST['send_to_email'])) : '';
        $sender = isset($_POST['send_from_email']) ? sanitize_email(wp_unslash($_POST['send_from_email'])) : '';

        $redirect_url = add_query_arg(
            [
                'order_id' => $order_id,
                'subscription_id' => $subscription_id,
                'email' => $email_id,
                'send_to_email' => $recipient,
                'send_from_email' => $sender,
            ],
            home_url('/' . self::ENDPOINT . '/')
        );

        if (! $recipient || ! is_email($recipient)) {
            $this->redirect_with_notice($redirect_url, 'error', 'Please enter a valid recipient email address.');
        }

        if (! $sender || ! is_email($sender)) {
            $this->redirect_with_notice($redirect_url, 'error', 'Please enter a valid sender email address.');
        }

        try {
            $email_data = $this->build_email_data($order_id, $subscription_id, $email_id);
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $sender,
            ];
            $sent = wp_mail($recipient, $email_data['subject'], $email_data['body'], $headers);

            if (! $sent) {
                throw new RuntimeException('The preview email could not be sent.');
            }

            $this->redirect_with_notice($redirect_url, 'success', sprintf('Preview sent to %s.', $recipient));
        } catch (Throwable $exception) {
            $this->redirect_with_notice($redirect_url, 'error', $exception->getMessage());
        }
    }

    private function get_default_sender_email(): string
    {
        $sender = get_option('woocommerce_email_from_address');

        if (! $sender) {
            $sender = get_option('admin_email');
        }

        return is_email($sender) ? (string) $sender : '';
    }

    private function render_email_preview(int $order_id, int $subscription_id, string $email_id): void
    {
        try {
            $email_data = $this->build_email_data($order_id, $subscription_id, $email_id);
            $wrapped = $this->build_wrapped_html_message($email_data['preview_recipient'], $email_data['subject'], $email_data['body']);

            if (isset($wrapped['error'])) {
                throw new RuntimeException($wrapped['error']);
            }
        } catch (Throwable $exception) {
            wp_die(esc_html($exception->getMessage()), 'Email preview error', ['response' => 500]);
        }

        nocache_headers();
        status_header(200);
        header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        echo $wrapped['html'];
    }

    private function build_email_data(int $order_id, int $subscription_id, string $email_id): array
    {
        $order = $order_id ? wc_get_order($order_id) : null;
        $subscription = $this->get_subscription($subscription_id);

        if (! $order && $subscription instanceof WC_Subscription && $subscription->get_parent_id()) {
            $order = wc_get_order($subscription->get_parent_id());
        }

        if ($email_id === self::TICKET_PDF_EMAIL_ID) {
            return $this->get_ticket_pdf_email_data($order);
        }

        $emails = WC()->mailer()->get_emails();

        if (! isset($emails[$email_id]) || ! $this->is_previewable_email($email_id)) {
            throw new RuntimeException('Email is not available for previews.');
        }

        if (in_array($email_id, $this->order_email_ids, true) && ! $order) {
            throw new RuntimeException('This email requires an order.');
        }

        if ((in_array($email_id, $this->subscription_email_ids, true) || in_array($email_id, $this->gifting_email_ids, true)) && ! $subscription) {
            throw new RuntimeException('This email requires a subscription.');
        }

        $content = $this->build_email_content_data($emails[$email_id], $order, $subscription, $email_id);
        if (isset($content['error'])) {
            throw new RuntimeException((string) $content['error']);
        }

        $preview_recipient = method_exists($emails[$email_id], 'get_recipient') && $emails[$email_id]->get_recipient()
            ? $emails[$email_id]->get_recipient()
            : get_option('admin_email');

        return [
            'subject' => $content['subject'],
            'body' => $content['body'],
            'preview_recipient' => $preview_recipient,
        ];
    }

    private function build_email_content_data(object $email, ?WC_Order $order, ?WC_Subscription $subscription, string $email_id): array
    {
        $previous_display_errors = ini_get('display_errors');
        $base_ob_level = ob_get_level();

        ini_set('display_errors', '0');
        set_error_handler(static function () {
            return true;
        });

        try {
            $this->prepare_email_context($email, $order, $subscription, $email_id);

            ob_start();
            $body = $email->get_content_html();
            ob_end_clean();

            if (! is_string($body) || trim($body) === '') {
                throw new RuntimeException('The email body was empty.');
            }

            $subject = method_exists($email, 'get_subject') ? $email->get_subject() : $email->get_title();

            restore_error_handler();
            ini_set('display_errors', $previous_display_errors);

            return [
                'subject' => $subject ?: sprintf('Preview: %s', $email_id),
                'body' => $body,
            ];
        } catch (Throwable $exception) {
            while (ob_get_level() > $base_ob_level) {
                ob_end_clean();
            }

            restore_error_handler();
            ini_set('display_errors', $previous_display_errors);

            return ['error' => $exception->getMessage()];
        }
    }

    private function prepare_email_context(object $email, ?WC_Order $order, ?WC_Subscription $subscription, string $email_id): void
    {
        if (in_array($email_id, $this->subscription_email_ids, true)) {
            $email->object = $subscription;

            if (strpos($email_id, 'Customer_Notification') !== false) {
                $email->recipient = $subscription->get_billing_email();

                if (property_exists($email, 'placeholders')) {
                    $email->placeholders['{customers_first_name}'] = $subscription->get_billing_first_name();
                    if (method_exists($email, 'get_relevant_date_type') && method_exists($email, 'get_time_until_date')) {
                        $email->placeholders['{time_until_renewal}'] = $email->get_time_until_date($subscription, $email->get_relevant_date_type());
                    }
                }
            } elseif (empty($email->recipient)) {
                $email->recipient = get_option('admin_email');
            }

            return;
        }

        if (in_array($email_id, $this->gifting_email_ids, true)) {
            $recipient_id = class_exists('WCS_Gifting') && $subscription ? absint(WCS_Gifting::get_recipient_user($subscription)) : 0;
            $recipient_user = $recipient_id ? get_user_by('id', $recipient_id) : null;
            $owner_name = class_exists('WCS_Gifting') && $subscription ? WCS_Gifting::get_user_display_name($subscription->get_user_id()) : '';

            if ($email_id === 'WCSG_Email_Customer_New_Account') {
                $user = $recipient_user ?: ($subscription ? get_user_by('id', $subscription->get_user_id()) : null);
                if (! $user) {
                    throw new RuntimeException('No recipient user was found for this gifting email.');
                }

                $email->object = $user;
                $email->reset_key = 'preview-reset-key';
                $email->user_login = stripslashes($user->user_login);
                $email->user_email = stripslashes($user->user_email);
                $email->user_id = $user->ID;
                $email->recipient = $email->user_email;
                $email->subscription_owner = $owner_name;
                return;
            }

            if ($email_id === 'WCSG_Email_Recipient_New_Initial_Order') {
                if (! $recipient_user) {
                    throw new RuntimeException('No gift recipient user was found for this subscription.');
                }

                $email->object = $recipient_user;
                $email->recipient = stripslashes($recipient_user->user_email);
                $email->subscription_owner = $owner_name;
                $email->subscriptions = [$subscription->get_id()];
                $email->wcsg_sending_recipient_email = $recipient_user->ID;
                return;
            }

            if (in_array($email_id, ['WCSG_Email_Processing_Renewal_Order', 'WCSG_Email_Completed_Renewal_Order'], true) && $subscription instanceof WC_Subscription) {
                $renewal_order_ids = method_exists($subscription, 'get_related_orders') ? $subscription->get_related_orders('ids', 'renewal') : [];
                $renewal_order_id = $renewal_order_ids ? reset($renewal_order_ids) : 0;
                $renewal_order = $renewal_order_id ? wc_get_order($renewal_order_id) : null;
                $order = $renewal_order ?: $subscription;
            }

            if (! $order && $subscription && $subscription->get_parent_id()) {
                $order = wc_get_order($subscription->get_parent_id());
            }

            if (! $order) {
                throw new RuntimeException('No order was found for this gifting renewal email.');
            }

            $email->object = $order;
            $email->recipient = $recipient_user ? $recipient_user->user_email : $order->get_billing_email();
            if ($recipient_user) {
                $email->wcsg_sending_recipient_email = $recipient_user->ID;
            }
            return;
        }

        $email->object = $order;
        $email->recipient = $order ? $order->get_billing_email() : get_option('admin_email');

        if ($order && property_exists($email, 'placeholders')) {
            $email->placeholders['{order_date}'] = wc_format_datetime($order->get_date_created());
            $email->placeholders['{order_number}'] = $order->get_order_number();
            $email->placeholders['{customer_name}'] = $order->get_formatted_billing_full_name();
        }

        if (property_exists($email, 'customer_note')) {
            $email->customer_note = 'Αυτή είναι μια δοκιμαστική σημείωση για τον οπτικό έλεγχο του email.';
        }

        if (property_exists($email, 'refund')) {
            $email->refund = null;
        }

        if (property_exists($email, 'partial_refund')) {
            $email->partial_refund = false;
        }
    }

    private function get_ticket_pdf_email_data(?WC_Order $order): array
    {
        if (! $order) {
            throw new RuntimeException('This email requires an order.');
        }

        if (! class_exists('IW_Ticket_PDF_Service')) {
            $service_path = WP_PLUGIN_DIR . '/iw-tickets/includes/class-iw-ticket-pdf-service.php';
            if (file_exists($service_path)) {
                require_once $service_path;
            }
        }

        if (! class_exists('IW_Ticket_PDF_Service') || ! method_exists('IW_Ticket_PDF_Service', 'build_tickets_email_data_for_order')) {
            throw new RuntimeException('IW Tickets PDF email service is not available.');
        }

        $email_data = IW_Ticket_PDF_Service::build_tickets_email_data_for_order($order->get_id(), false);
        if (isset($email_data['error'])) {
            throw new RuntimeException((string) $email_data['error']);
        }

        return [
            'subject' => (string) $email_data['subject'],
            'body' => (string) $email_data['message'],
            'preview_recipient' => $order->get_billing_email() ?: get_option('admin_email'),
        ];
    }

    private function build_wrapped_html_message(string $recipient, string $subject, string $body): array
    {
        $captured = null;
        $capture_mail = static function ($return, $atts) use (&$captured) {
            $captured = $atts;
            return true;
        };

        add_filter('pre_wp_mail', $capture_mail, 10, 2);
        ob_start();
        wp_mail(
            $recipient ?: get_option('admin_email'),
            $subject,
            $body,
            ['Content-Type: text/html; charset=UTF-8']
        );
        ob_end_clean();
        remove_filter('pre_wp_mail', $capture_mail, 10);

        if (! isset($captured['message']) || trim((string) $captured['message']) === '') {
            return ['error' => 'The email wrapper did not produce a message.'];
        }

        return ['html' => $captured['message']];
    }

    private function get_subscription(int $subscription_id): ?WC_Subscription
    {
        if (! $subscription_id || ! function_exists('wcs_get_subscription')) {
            return null;
        }

        $subscription = wcs_get_subscription($subscription_id);
        return $subscription instanceof WC_Subscription ? $subscription : null;
    }

    private function is_previewable_email(string $email_id): bool
    {
        return in_array($email_id, $this->order_email_ids, true)
            || in_array($email_id, $this->subscription_email_ids, true)
            || in_array($email_id, $this->gifting_email_ids, true)
            || isset($this->custom_email_ids[$email_id]);
    }

    private function redirect_with_notice(string $redirect_url, string $status, string $message): void
    {
        wp_safe_redirect(add_query_arg([
            'send_status' => $status,
            'send_message' => $message,
        ], $redirect_url));
        exit;
    }
}

// Instantiated by iw-email-template.php.
