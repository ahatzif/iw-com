<?php


class IW_Wallet_Common
{

    public static function get_active_or_last_subscription($user_id){
        $subs = wcs_get_users_subscriptions($user_id);
        if (empty($subs)) return false;
        $subs = array_filter($subs, function ($subscription) {
            return !self::is_donation_subscription($subscription);
        });
        if (empty($subs)) return false;
        foreach ($subs as $s) { if ($s->has_status(['active', 'trial'])) return $s; }
        usort($subs, function ($a, $b) { return strtotime($b->get_date_created()) - strtotime($a->get_date_created()); });
        return $subs[0];
    }

    public static function get_subscription_type($subscription){
        if (!$subscription) return '';
        $item = self::get_subscription_membership_item($subscription);
        if (!$item) return '';
        return trim(explode('-', $item->get_name() )[0]);
    }

    public static function get_subscription_product_id($subscription){
        if (!$subscription) return null;
        $item = self::get_subscription_membership_item($subscription);
        return $item ? $item->get_product_id() : null;
    }

    public static function get_subscription_membership_item($subscription) {
        if (!$subscription) return null;
        $items = $subscription->get_items();
        if (empty($items)) return null;

        foreach ($items as $item) {
            if (!self::is_donation_item($item)) {
                return $item;
            }
        }

        return null;
    }

    public static function is_donation_item($item): bool {
        if (!$item) return false;

        if (method_exists($item, 'get_meta')) {
            $item_type = $item->get_meta('iw_item_type', true) ?: $item->get_meta('_iw_item_type', true);
            if ($item_type === 'donation') {
                return true;
            }
        }

        $product_ids = [];
        if (method_exists($item, 'get_product_id')) {
            $product_ids[] = (int) $item->get_product_id();
        }
        if (method_exists($item, 'get_variation_id')) {
            $product_ids[] = (int) $item->get_variation_id();
        }

        foreach (array_filter(array_unique($product_ids)) as $product_id) {
            if (class_exists('IW_Donations') && method_exists('IW_Donations', 'is_donation_product') && IW_Donations::is_donation_product($product_id)) {
                return true;
            }

        }

        return false;
    }

    public static function is_donation_subscription($subscription): bool {
        if (!$subscription) return false;

        foreach ($subscription->get_items() as $item) {
            if (self::is_donation_item($item)) {
                return true;
            }
        }

        return false;
    }

    public static function get_subscription_features_string($product_id) {
        $features = get_field('features', $product_id) ?: [];
        $out = "\n";
        foreach ($features as $feature) { $out .= "• " . $feature['feature'] . "\n\n";}
        return $out;
    }

    public static function normalize_next_payment_date($subscription) {
        if (!$subscription) return null;
        $raw = $subscription->get_date('next_payment');
        if (!$raw) return null;

        return ($raw instanceof DateTime) ? $raw : new DateTime($raw);
    }

    public static function get_member_name($user_id) {
        return get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true);
    }

    public static function prepare_pass_images($image_arr, $tmp_dir) {
        return IW_Google_Wallet_Card::prepare_pass_images($image_arr, $tmp_dir);
    }

    public static function resize_image($src, $dest, $scale){
        return IW_Google_Wallet_Card::resize_image($src, $dest, $scale);
    }

    public static function get_enabled_locations() {



        $query = new WP_Query([ 'post_type' => [ 'building'], 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => [ [ 'key' => 'enable_location_for_wallet_cards', 'value'  => '1', 'compare' => '=', ] ] ]);
        $locations = [];
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {

                $enabled = get_field( 'enable_location_for_wallet_cards', $post->ID );
                if ( ! $enabled ) continue;
                $lat  = get_field( 'lat',  $post->ID );
                $long = get_field( 'long', $post->ID );

                if ( is_numeric( $lat ) && is_numeric( $long ) ) {
                    $relevant_text = get_field( 'relevant_text', $post->ID );
                    $locations[] = [
                        'latitude'  => (float) $lat,
                        'longitude' => (float) $long,
                        'relevantText' => ! empty( $relevant_text) ?  $relevant_text : strip_tags( apply_filters( 'the_title', $post->post_title, $post->ID ) ),
                    ];
                }
            }
        }

        return $locations;
    }

}
