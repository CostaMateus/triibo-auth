( function ( $ ) {

    "use strict";

    let taa_mna = document.getElementById( "taa-modal-new-account"         );
    // let taa_mnp = document.getElementById( "taa-modal-new-password"        );
    let taa_mva = document.getElementById( "taa-modal-validate-account"    );
    let taa_mvc = document.getElementById( "taa-modal-validate-code"       );

    let taa_bna = document.getElementById( "taa-btn-form-new-account"      );
    let taa_bnp = document.getElementById( "taa-btn-form-new-password"     );
    let taa_bva = document.getElementById( "taa-btn-form-validate-account" );
    let taa_bvc = document.getElementById( "taa-btn-form-validate-code"    );

    let taa_ena = document.getElementById( "taa-error-new-acc"             );
    // let taa_enp = document.getElementById( "taa-error-new-pass"            );
    let taa_eva = document.getElementById( "taa-error-validate-acc"        );
    let taa_evc = document.getElementById( "taa-error-validate-code"       );

    let taa_cd         = 0;
    let taa_intervalId = null;
    let taa_ajaxurl    = taa_script_obj.ajaxurl;

    $( document ).ready( function() {

        const queryString = window.location.search;
        const urlParams   = new URLSearchParams( queryString );
        const code        = urlParams.get( "taac"  );
        const email       = urlParams.get( "email" );

        if ( code !== null )
        {
            switch ( code )
            {
                case "acc":
                    $( taa_mva ).removeClass( "ta-d-none" );
                    $( "#taa-validate-acc-email" ).val( email );
                break;

                case "code":
                    taa_cd         = 40;
                    taa_intervalId = setInterval( taaCountdown, 1000 );
                    $( "#taa-resend-count" ).removeClass( "ta-d-none" );
                    $( taa_mvc ).removeClass( "ta-d-none" );
                    $( "#taa-email" ).val( email );
                break;

                case "newacc":
                    $( taa_mna ).removeClass( "ta-d-none" );
                    if ( email ) $( "#taa-new-acc-email" ).val( email );
                break;
            }
        }
    } );

    window.addEventListener( "click", function ( e ) {

        switch ( e.target.id )
        {
            case "taa-btn-form-new-account":
                e.preventDefault();
                taaSubmitNewAccount();
            break;

            case "taa-btn-form-validate-account":
                e.preventDefault();
                taaSubmitValAccount();
            break;

            case "taa-btn-form-validate-code":
                e.preventDefault();
                taaSubmitValCode();
            break;
        }

    }, false );

    window.taaCountdown        = function()
    {
        if ( taa_cd == 0 )
        {
            clearInterval( taa_intervalId );
            $( "#taa-resend-count" ).addClass( "ta-d-none" ).html( "Reenviar código em 40" );
            $( "#taa-resend-code"  ).removeClass( "ta-d-none" );
        }
        else
        {
            $( "#taa-resend-count" ).html( `Reenviar código em ${taa_cd}` );
            taa_cd -= 1;
        }
    }

    window.taaSubmitNewAccount = function ()
    {
        taaDisableBtns();

        $( taa_ena ).addClass( "ta-d-none" );
        $( "#taa-new-acc-email" ).removeClass( "ta-error-border" );
        $( "#taa-new-acc-pass1" ).removeClass( "ta-error-border" );

        const email = $( "#taa-new-acc-email" ).val();
        const pass1 = $( "#taa-new-acc-pass1" ).val();

        if ( email == "" )
        {
            taaEnableBtns();
            $( taa_ena ).removeClass( "ta-d-none" ).html( "Informe um e-mail válido" );
            $( "#taa-new-acc-email" ).addClass( "ta-error-border" );
        }
        else if ( pass1 == "" )
        {
            taaEnableBtns();
            $( taa_ena ).removeClass( "ta-d-none" ).html( "Informe uma senha" );
            $( "#taa-new-acc-pass1" ).addClass( "ta-error-border" );
        }
        else
        {
            $.ajax( {
                url : taa_script_obj.ajaxurl,
                type: "POST",
                data: {
                    action : "taa_new_account",
                    email  : email,
                    pass   : pass1
                },
                success: function( data ) {

                    let result = JSON.parse( data );

                    if ( result.error )
                    {
                        taaEnableBtns();

                        $( taa_ena ).removeClass( "ta-d-none" ).html( result.message );
                        $( "#taa-code" ).addClass( "ta-error-border" );
                        $( "#taa-new-acc-email" ).addClass( "ta-error-border" );
                        $( "#taa-new-acc-pass1" ).addClass( "ta-error-border" );
                    }
                    else
                    {
                        taaRefresh();
                    }
                },
                error: function( err ) {
                    taaAjaxError( err );
                }
            } );
        }
    }

    window.taaSubmitValAccount = function ()
    {
        taaDisableBtns();

        $( taa_eva ).addClass( "ta-d-none" );
        $( "#taa-validate-acc-email" ).removeClass( "ta-error-border" );
        $( "#taa-validate-acc-pass"  ).removeClass( "ta-error-border" );

        const email = $( "#taa-validate-acc-email" ).val();
        const pass  = $( "#taa-validate-acc-pass"  ).val();

        if ( email == "" )
        {
            taaEnableBtns();
            $( taa_eva ).removeClass( "ta-d-none" ).html( "Informe um e-mail válido" );
            $( "#taa-validate-acc-email" ).addClass( "ta-error-border" );
        }
        else if ( pass == "" )
        {
            taaEnableBtns();
            $( taa_eva ).removeClass( "ta-d-none" ).html( "Informe uma senha" );
            $( "#taa-validate-acc-pass" ).addClass( "ta-error-border" );
        }
        else
        {
            $.ajax( {
                url : taa_script_obj.ajaxurl,
                type: "POST",
                data: {
                    action : "taa_validate_account",
                    email  : email,
                    pass   : pass
                },
                success: function( data ) {

                    let result = JSON.parse( data );

                    if ( result.error )
                    {
                        taaEnableBtns();

                        $( taa_eva ).removeClass( "ta-d-none" ).html( "E-mail e/ou senha incorreta" );
                        $( "#taa-validate-acc-email" ).addClass( "ta-error-border" );
                        $( "#taa-validate-acc-pass"  ).addClass( "ta-error-border" );
                    }
                    else
                    {
                        taaRefresh();
                    }
                },
                error: function( err ) {
                    taaAjaxError( err );
                }
            } );
        }
    }

    window.taaSubmitValCode    = function ()
    {
        taaDisableBtns();

        $( taa_evc ).addClass( "ta-d-none" );
        $( "#taa-code" ).removeClass( "ta-error-border" );

        const code  = $( "#taa-code" ).val();
        const email = $( "#taa-email" ).val();

        if ( code != "" && code.length == 6 )
        {
            $.ajax( {
                url : taa_script_obj.ajaxurl,
                type: "POST",
                data: {
                    action : "taa_validate_code",
                    code   : code,
                    email  : email
                },
                success: function( data ) {

                    let result = JSON.parse( data );

                    if ( result.error )
                    {
                        taaEnableBtns();

                        $( taa_evc ).removeClass( "ta-d-none" ).html( result.message );
                        $( "#taa-code" ).addClass( "ta-error-border" );
                    }
                    else
                    {
                        switch ( result.code )
                        {
                            case 2000:
                                taaRefresh();
                            break;

                            case 2001:
                                $( taa_mvc ).addClass( "ta-d-none" );
                                taaEnableBtns();
                                $( taa_mna ).removeClass( "ta-d-none" );
                                $( "#taa-new-acc-email" ).val( email );
                            break;
                        }
                    }
                },
                error: function( err ) {
                    taaAjaxError( err );
                }
            } );
        }
        else
        {
            taaEnableBtns();

            let errorMsg = ( code == "" ) ? "Insira o código recebido" : "O código tem 6 dígitos" ;
            $( taa_evc ).removeClass( "ta-d-none" ).html( errorMsg );
            $( "#taa-code" ).addClass( "ta-error-border" );
        }
    }

    window.taaResendCodeEmail  = function ()
    {
        taaDisableBtns();

        $( taa_evc            ).addClass( "ta-d-none" );
        $( "#taa-resend-code" ).addClass( "ta-d-none" );
        $( "#taa-code"        ).removeClass( "ta-error-border" );

        let taa_email = $( "#taa-email" ).val();

        $.ajax( {
            url  : taa_ajaxurl,
            type : "POST",
            data : {
                action : "taa_resend_email_code",
                email  : taa_email
            },
            success: function( data ) {
                let result = JSON.parse( data );

                taaEnableBtns();

                if ( !result.success )
                {
                    $( taa_evc ).removeClass( "ta-d-none" ).html( result.error.message );
                }
                else
                {
                    $( "#taa-code" ).focus();
                    $( "#taa-resent-code"  ).removeClass( "ta-d-none" );

                    taa_cd         = 40;
                    taa_intervalId = setInterval( taaCountdown, 1000 );
                    $( "#taa-resend-count" ).removeClass( "ta-d-none" );

                    setTimeout( function() {
                        $( "#taa-resent-code" ).addClass( "ta-d-none" );
                    }, 3000 );
                }
            },
            error: function( err ) {
                taaAjaxError( err );
            }
        } );
    }

    window.taaCloseModal       = function ( e )
    {
        const id = e.id.replace( "close", "modal" );
        $( `#${id}` ).addClass( "ta-d-none" );
    }

    window.taaDisableBtns      = function ()
    {
        [ taa_bna, taa_bnp, taa_bva, taa_bvc ].forEach( ( e ) => {
            $( e ).prop( "disabled", "disabled" ).find( "div" ).removeClass( "ta-d-none" );
        } );
    }

    window.taaEnableBtns       = function ()
    {
        [ taa_bna, taa_bnp, taa_bva, taa_bvc ].forEach( ( e ) => {
            $( e ).prop( "disabled", null ).find( "div" ).addClass( "ta-d-none" );
        } );
    }

    window.taaAjaxError        = function ( err )
    {
        console.log( err );
        taaRefresh();
    }

    window.taaRefresh          = function ()
    {
        let url = new URL( window.location.href );
        url.searchParams.delete( "taac"  );
        url.searchParams.delete( "email" );
        window.location.href = url.toString();
    }

} )( jQuery );
