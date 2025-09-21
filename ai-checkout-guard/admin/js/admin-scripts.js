/**
 * admin-scripts.js
 * JavaScript enhancements for AI Checkout Guard admin settings page.
 */

jQuery( document ).ready( function( $ ) {

    // Highlight required fields if left blank on form submit.
    $( 'form[action="options.php"]' ).on( 'submit', function( e ) {
        var valid = true;

        // Check API URL.
        var apiUrl = $( '#api_url' ).val().trim();
        if ( apiUrl === '' ) {
            valid = false;
            $( '#api_url' ).css( 'border-color', '#dc3232' );
        } else {
            $( '#api_url' ).css( 'border-color', '' );
        }

        // Check API Key.
        var apiKey = $( '#api_key' ).val().trim();
        if ( apiKey === '' ) {
            valid = false;
            $( '#api_key' ).css( 'border-color', '#dc3232' );
        } else {
            $( '#api_key' ).css( 'border-color', '' );
        }

        if ( ! valid ) {
            e.preventDefault();
            $( '<div class="notice notice-error is-dismissible ai-checkout-guard-notice"><p>Please fill in both API URL and API Key before saving.</p></div>' )
                .prependTo( '.wrap' )
                .delay( 5000 )
                .fadeOut( 400, function() {
                    $( this ).remove();
                } );
            return false;
        }
    } );

    // Dismiss custom notices manually.
    $( document ).on( 'click', '.notice.is-dismissible .notice-dismiss', function() {
        $( this ).closest( '.notice' ).fadeOut( 300 );
    } );

} );
