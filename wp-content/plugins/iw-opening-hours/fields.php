<?php

/**
 * 1. Εγγραφή ACF fields (δυναμικά) για τα post type "building" ή "food-beverage".
 *    (Ίδιο με το προηγούμενο παράδειγμα.)
 */
add_action('acf/init', function () {

    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    // Ομάδα πεδίων: "Building Hours"
    acf_add_local_field_group(array(
        'key' => 'group_iw_building_hours',
        'title' => 'Opening Hours',
        'fields' => array(
            // —————— Δευτέρα
            array(
                'key' => 'field_mon_hours',
                'label' => 'Δευτέρα',
                'name' => 'mon_hours',
                'type' => 'group',
                'wrapper' => array("width" => (100 / 7) . "%"),
                'sub_fields' => array(
                    array(
                        'key' => 'field_mon_closed',
                        'label' => 'Κλειστά;',
                        'name' => 'closed',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_mon_open',
                        'label' => 'Άνοιγμα',
                        'name' => 'open_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 10:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_mon_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_mon_close',
                        'label' => 'Κλείσιμο',
                        'name' => 'close_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 18:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_mon_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // —————— Τρίτη
            array(
                'key' => 'field_tue_hours',
                'label' => 'Τρίτη',
                'name' => 'tue_hours',
                'type' => 'group',
                'wrapper' => array("width" => (100 / 7) . "%"),
                'sub_fields' => array(
                    array(
                        'key' => 'field_tue_closed',
                        'label' => 'Κλειστά;',
                        'name' => 'closed',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_tue_open',
                        'label' => 'Άνοιγμα',
                        'name' => 'open_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 10:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_tue_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_tue_close',
                        'label' => 'Κλείσιμο',
                        'name' => 'close_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 18:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_tue_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            // —————— κ.λπ. για Τετάρτη, Πέμπτη, Παρασκευή, Σάββατο, Κυριακή (πανομοιότυπα)
            array(
                'key' => 'field_wed_hours',
                'label' => 'Τετάρτη',
                'name' => 'wed_hours',
                'type' => 'group',
                'wrapper' => array("width" => (100 / 7) . "%"),
                'sub_fields' => array(
                    array(
                        'key' => 'field_wed_closed',
                        'label' => 'Κλειστά;',
                        'name' => 'closed',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_wed_open',
                        'label' => 'Άνοιγμα',
                        'name' => 'open_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 10:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_wed_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_wed_close',
                        'label' => 'Κλείσιμο',
                        'name' => 'close_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 18:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_wed_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_thu_hours',
                'label' => 'Πέμπτη',
                'name' => 'thu_hours',
                'type' => 'group',
                'wrapper' => array("width" => (100 / 7) . "%"),
                'sub_fields' => array(
                    array(
                        'key' => 'field_thu_closed',
                        'label' => 'Κλειστά;',
                        'name' => 'closed',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_thu_open',
                        'label' => 'Άνοιγμα',
                        'name' => 'open_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 10:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_thu_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_thu_close',
                        'label' => 'Κλείσιμο',
                        'name' => 'close_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 00:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_thu_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_fri_hours',
                'label' => 'Παρασκευή',
                'name' => 'fri_hours',
                'type' => 'group',
                'wrapper' => array("width" => (100 / 7) . "%"),
                'sub_fields' => array(
                    array(
                        'key' => 'field_fri_closed',
                        'label' => 'Κλειστά;',
                        'name' => 'closed',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_fri_open',
                        'label' => 'Άνοιγμα',
                        'name' => 'open_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 10:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_fri_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_fri_close',
                        'label' => 'Κλείσιμο',
                        'name' => 'close_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 18:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_fri_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_sat_hours',
                'label' => 'Σάββατο',
                'name' => 'sat_hours',
                'type' => 'group',
                'wrapper' => array("width" => (100 / 7) . "%"),
                'sub_fields' => array(
                    array(
                        'key' => 'field_sat_closed',
                        'label' => 'Κλειστά;',
                        'name' => 'closed',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_sat_open',
                        'label' => 'Άνοιγμα',
                        'name' => 'open_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 10:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_sat_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_sat_close',
                        'label' => 'Κλείσιμο',
                        'name' => 'close_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 18:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_sat_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_sun_hours',
                'label' => 'Κυριακή',
                'name' => 'sun_hours',
                'type' => 'group',
                'wrapper' => array("width" => (100 / 7) . "%"),
                'sub_fields' => array(
                    array(
                        'key' => 'field_sun_closed',
                        'label' => 'Κλειστά;',
                        'name' => 'closed',
                        'type' => 'true_false',
                        'ui' => 1,
                    ),
                    array(
                        'key' => 'field_sun_open',
                        'label' => 'Άνοιγμα',
                        'name' => 'open_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 10:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_sun_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_sun_close',
                        'label' => 'Κλείσιμο',
                        'name' => 'close_time',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 16:00',
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_sun_closed',
                                    'operator' => '==',
                                    'value' => '0',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_notes',
                'label' => 'Σημειώσεις',
                'name' => 'opening_hours_notes',
                'type' => 'wysiwyg',
                'toolbar' => 'simple',
            ),


            // Σταθερές αργίες (π.χ. 01-01, 03-25, 12-25 κ.λπ.)
            array(
                'key' => 'field_fixed_holidays',
                'label' => 'Σταθερές Αργίες',
                'name' => 'fixed_holidays',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Προσθήκη Αργίας',
                'sub_fields' => array(
                    array(
                        'key' => 'field_fixed_holiday_date',
                        'label' => 'Ημ/νία (ΜΜ-ΗΗ)',
                        'name' => 'holiday_date',
                        'type' => 'text',
                        'placeholder' => 'π.χ. 01-06 ή 12-25',
                    ),
                ),
            ),

            //  "Κλειστό τον Αύγουστο"
            array(
                'key' => 'field_closed_in_august',
                'label' => 'Κλειστό τον Αύγουστο;',
                'name' => 'closed_in_august',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
            ),

            //  "ΠΡΟΣΩΡΙΝΑ ΚΛΕΙΣΤΟ"
            array(
                'key' => 'field_closed_in_temporarily',
                'label' => 'Προσωρινά Κλειστό',
                'name' => 'temporarily_closed',
                'type' => 'true_false',
                'ui' => 1,
                'default_value' => 0,
            ),

        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'building',
                ),
            ),
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'food-beverage',
                ),
            ),
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'space',
                ),
            ),
        ),

    ));
});
