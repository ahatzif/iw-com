<?php

if (defined('WP_CLI') && WP_CLI) {

    class IW_Wallet_CLI {

        public function push_all() {

            WP_CLI::log("Fetching users…");

            $users = get_users( [ 'meta_key' => 'apple_wallet_device_tokens', 'fields' => 'ID', 'number' => -1 ]);
            $total = count($users);



            if ($total === 0) {
                WP_CLI::warning("No users have wallet registrations.");
                return;
            }

            WP_CLI::log("Total users with registered devices: {$total}");
            WP_CLI::log("Sending pushes in batches of 100…");

            $batch_size = 100;
            $batches = array_chunk($users, $batch_size);
            $batch_number = 1;

            foreach ($batches as $batch) {
                WP_CLI::log("Batch {$batch_number} (" . count($batch) . " users)…");
                foreach ($batch as $user_id) {
                    if( class_exists( 'IW_Apple_Wallet_Service' ) ){
                        IW_Apple_Wallet_Service::push_update($user_id);
                    }
                    if( class_exists( 'IW_Google_Wallet_Service' ) ){
                        IW_Google_Wallet_Service::push_update($user_id);
                    }
                }
                usleep(300000); // 0.3 sec
                $batch_number++;
            }

            WP_CLI::success("Done! All push notifications have been sent.");
        }
    }

    WP_CLI::add_command('iw-wallet:push-all', [new IW_Wallet_CLI(), 'push_all']);
}
