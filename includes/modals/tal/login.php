    <hr>
    <p class="ta-title-optin" >
        Entrar com Triibo
    </p>

    <form id="tal-form-auth" method="post" >
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
            <input type="hidden" name="form-auth" >
            <input id="tal-cellphone" type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="cellphone" placeholder="+55 (XX) XXXXX-XXXX" />
            <!-- Error message -->
            <p id= "tal-error-auth" class="ta-bg-transp ta-text-center ta-text-danger ta-pt-8 ta-mb-0 ta-d-none" ></p>
        </p>

        <p class="form-row" >
            <button id="tal-btn-form-auth" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >
                Solicitar código SMS <div class="ta-d-none ta-spin-load" ></div>
            </button>
        </p>
    </form>
    <hr>
    <p class="ta-title-optin" >
        Entrar por rede social
    </p>

    <script>
        ( function ( $ ) {
            "use strict";
            var maskBehavior = function ( val ) {
                return val.replace( /\D/g, "" ).length === 13 ? "+55 (00) 00000-0000" : "+55 (00) 0000-00009";
            },
            options = { onKeyPress: function( val, e, field, options ) {
                field.mask( maskBehavior.apply( {}, arguments ), options );
            } };
            $( "#tal-cellphone" ).mask( maskBehavior, options );
        } )( jQuery );
    </script>
