
window.addEventListener( 'load', ( evt ) => {
    let submitWithSucess = false
    const formWraps = document.querySelectorAll( '.give-form-wrap' );

	Array.prototype.forEach.call( formWraps, function( formWrap ) {
        const form_element = formWrap.querySelector( '.give-form');
    
        const onsubmit = form_element.onsubmit;
        form_element.onsubmit = function( evt ) {
			const selectedGateway = form_element.querySelector( '.give-gateway:checked' ).value;

			if ( 'kkiapay' === selectedGateway && submitWithSucess == false ) {
                evt.stopImmediatePropagation()
                evt.preventDefault();
                let amount = document.querySelector( '.give-final-total-amount' ).getAttribute( 'data-total' );
                let firstname = document.getElementsByName('give_first')[0].value
                let lastname = document.getElementsByName('give_last')[0].value
                let { key,theme_kkiapay,url_kkiapay,website_url,sandbox,position } = give_kkiapay_vars
                openKkiapayWidget({
                    amount:parseInt(amount),
                    name:`${firstname} ${lastname}`,
                    key: key ,
                    sandbox,
                    theme: theme_kkiapay,
                    url: url_kkiapay,
                    position,
                    reason:'donation'
                })
			}else if(onsubmit){
                onsubmit.call(this,evt);
            }
        };
        
        var script = document.createElement('script');
        script.onload = function () {
            // console.log('le script de kkiapay vient de se chargé')
            addSuccessListener(response => {
                var input = document.createElement("input");
                input.setAttribute("type", "hidden");
                input.setAttribute("name", "give_kkiapay_transaction_id");
                input.setAttribute("value", response.transactionId);
                console.log(response.transactionId)
                form_element.appendChild(input)
                submitWithSucess = true
                form_element.submit();
            });

            window.addEventListener("message", function (e) {
                if (e.data === "close") {
                    const $donate_button_wrap = form_element.querySelector( '.give-submit-button-wrap' );
                    const $donate_button = $donate_button_wrap.querySelector( '#give-purchase-button' );
            

                    $donate_button_wrap.querySelector( '.give-loading-animation' ).style.display = 'none';
            

                    $donate_button.value = $donate_button.getAttribute( 'data-before-validation-label' );
                    $donate_button.removeAttribute( 'disabled' );
                }
            })
    
        };
    
        script.src = "https://cdn.kkiapay.me/k.js";
    
        document.head.appendChild(script);


    } );
} );
