<?php


class IW_Form_Validator{

    public static function  rules ( ){
        return [
            'required' => function ( $value ){ return ! empty( $value ); },
            'email' => function ( $value ) { return is_email( $value ); },
            'min' => function ( $value, $length ) { return strlen( $value ) >= $length; },
            'uppercase' => function ( $value ){ return preg_match('/[A-Z]/',$value); },
            'lowercase' => function ( $value ){ return preg_match('/[a-z]/',$value); },
            'number' => function ( $value ){ return preg_match('/[0-9]/',$value); },
            'special' => function ( $value ){ return preg_match('/[!@#$%^&*]/',$value); },
            'url' => function ( $value ){ return empty($value) || filter_var($value, FILTER_VALIDATE_URL) !== FALSE ; },
            'in_array' => function( $value, $intersect  ) { return is_string( $value ) && ( empty( $value ) || in_array( $value, $intersect ) ); },
            'in_array|required' => function( $value, $intersect  ) { return is_string( $value ) && in_array( $value, $intersect ); },
            'multiple' => function( $value, $intersect ){
                $value = ( array ) $value;
                if( ! in_array( '', $intersect ) ) $intersect[] = '';
                return empty( $value ) || count(array_intersect($value, $intersect)) == count($value);
            },
            'multiple|required' => function( $value, $intersect ){
                $value = ( array ) $value;
                return empty( $value ) || count(array_intersect($value, $intersect)) == count($value);
            }
        ];
    }

    public static function validateField( $fieldName, $fieldValue, $fieldRules ){
        $availableRules = IW_Form_Validator::rules();
        if( is_array( $fieldRules ) &&  count( $fieldRules ) === 2 ) {
            if( isset( $availableRules[ $fieldRules[0] ] ) && ! $availableRules[ $fieldRules[0] ]( $fieldValue, $fieldRules[1] ) ){
                wp_send_json( [ 'success' => false, 'message' => 'Field ' . $fieldName . " is not valid (" . $fieldRules[0] . ')' ] );
            }
            return;
        }
        $fieldRules = explode( '|', $fieldRules );
        foreach ( $fieldRules as $fieldRule ){
            $fieldRuleParts = explode( ':', $fieldRule );
            $fieldRule = $fieldRuleParts[0];
            $fieldRuleArgs = count( $fieldRuleParts ) > 1 ? $fieldRuleParts[1] : false;
            if( isset( $availableRules[ $fieldRule ] ) && ! $availableRules[ $fieldRule ]( $fieldValue, $fieldRuleArgs ) ){
                wp_send_json( [ 'success' => false, 'message' => 'Field ' . $fieldName . " is not valid (" . $fieldRule . ')' ] );
            }
        }
    }

    public static function validate( $rules, $input, $force = false ){
        if( ! $force ){
            $fillableKeys = array_intersect( array_keys( $rules ), array_keys( $input ) );
        } else {
            $fillableKeys = array_keys( $rules );
        }

        foreach ( $fillableKeys as $key ){
            IW_Form_Validator::validateField( $key, $input[ $key ],  $rules[ $key ]  );
        }

        $output = [];
        foreach ( $fillableKeys as $key ){
            $output[ $key ] = $input[ $key ];
        }

        return $output;
    }

    public static function maybe_update_user_card( $userId ){
        if( ! isset( $_REQUEST[ 'member-card' ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Bad request', 'iw-theme' )] );
        }
        $card_id = absint( $_REQUEST[ 'member-card' ] );
        $card = get_post( $card_id );
        if ( ! $card || 'member-card' !== $card->post_type ) {
            wp_send_json_error( [ 'message' => __( 'Card not found.', 'iw-theme' ) ] );
        }
        update_field( 'member_card', $card_id ,'user_' . $userId );

        IW_Wallet_Versioning::check_member_card_change( $card_id ,'user_' . $userId, [ 'name' => 'member_card' ]);
    }
}
