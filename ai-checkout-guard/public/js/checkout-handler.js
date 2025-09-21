/**
 * checkout-handler.js
 * Integrates AI Checkout Guard into WooCommerce Checkout Block.
 */

( function( wp, $ ) {
    const { hooks, element, data } = wp;
    const { useEffect, useState } = element;
    const { useSelect, useDispatch } = data;

    /**
     * React component wrapping Payment Methods to inject risk logic.
     */
    const AICheckoutGuard = () => {
        const [ decision, setDecision ] = useState( null );
        const { getPaymentMethodRegistry, getSelectedPaymentMethod } = useSelect(
            ( select ) => ({
                getPaymentMethodRegistry: select( 'wc-checkout' ).getPaymentMethodRegistry,
                getSelectedPaymentMethod: select( 'wc-checkout' ).getSelectedPaymentMethod,
            })
        );
        const { setPaymentMethodRegistry } = useDispatch( 'wc-checkout' );

        // Fetch risk tier on mount or when address changes.
        useEffect( () => {
            const fetchRisk = async () => {
                const response = await fetch( AI_Checkout_Guard_Settings.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': AI_Checkout_Guard_Settings.nonce,
                    },
                    body: JSON.stringify( {
                        name: $( '#billing_first_name' ).val() + ' ' + $( '#billing_last_name' ).val(),
                        email: $( '#billing_email' ).val(),
                        phone: $( '#billing_phone' ).val(),
                        address: $( '#billing_address_1' ).val(),
                        pincode: $( '#billing_postcode' ).val(),
                        order_total: wc_checkout_params.totals.total,
                        items: wc_checkout_params.line_items.map( ( item ) => ( {
                            sku: item.sku,
                            qty: item.quantity,
                            price: item.subtotal / item.quantity,
                        } ) ),
                    } ),
                } );

                const result = await response.json();
                setDecision( result );
            };

            fetchRisk();
        }, [] );

        // When decision changes, filter methods.
        useEffect( () => {
            if ( ! decision ) {
                return;
            }

            const registry = getPaymentMethodRegistry() || {};
            const tier = decision.tier;
            const action = AI_Checkout_Guard_Settings.cod_action;

            // Clone registry to avoid mutating original.
            const updated = { ...registry };

            if ( updated.cod ) {
                if ( tier === 'low' ) {
                    // leave COD
                } else if ( tier === 'medium' ) {
                    // leave COD and show nudge
                    updated.cod.description = updated.cod.description + 
                        ' <div class="ai-guard-prepaid-incentive">💰 Save ₹20 by prepaying!</div>';
                } else if ( tier === 'high' ) {
                    if ( action === 'hide' ) {
                        delete updated.cod;
                    } else if ( action === 'verify' ) {
                        // Add warning badge
                        updated.cod.description = '<div class="ai-guard-cod-warning">Verification required for COD</div>' + updated.cod.description;
                    }
                }
            }

            setPaymentMethodRegistry( updated );
        }, [ decision ] );

        return null;
    };

    // Inject our component into Checkout Block before payment methods.
    hooks.addAction(
        'woocommerce_checkout_before_payment',
        'ai-checkout-guard/inject-guard',
        () => {
            const root = document.querySelector( '.wc-block-checkout-payment-methods' );
            if ( root ) {
                wp.element.render( wp.element.createElement( AICheckoutGuard ), root );
            }
        }
    );

} )( window.wp, jQuery );
