
    <div id="tal-modal-form-register" class="woocommerce ta-bg-modal-login ta-d-none" >
        <div class="ta-bg-modal-login-child" >
            <div class="ta-modal-header" >
                <h2 id="tal-register-title" ></h2>

                <?php
                    if ( class_exists( "Triibo_Auth_Checkout" ) && !Triibo_Auth_Checkout::has_query_param() )
                        echo "<span id=\"tal-close-form-register\" aria-hidden=\"true\" >&times;</span>";
                ?>
            </div>
            <form id="tal-form-register" method="post" >
                <p id="tal-register-p1" class="ta-text-left ta-mb-5"  ></p>
                <p id="tal-register-p2" class="ta-text-left ta-mb-5"  ></p>
                <p id="tal-register-p3" class="ta-text-left ta-mb-15" ></p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <input type="hidden" name="form-register" >
                    <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" requered placeholder="Endereço de e-mail"
                        name="email" id="tal-form-register-email" autocomplete="email" >
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" requered placeholder="Senha"
                        name="password" id="tal-form-register-password" autocomplete="password" >
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide mb-0" >
                    <!-- Error message -->
                    <p id="tal-error-register" class="ta-bg-transp ta-text-center ta-text-danger ta-pt-20 ta-mb-0 ta-d-none" ></p>
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide mb-0" >
                    <a id="tal-lost-pass"   class="ta-bg-transp ta-float-right ta-pointer ta-pb-20 ta-mb-0" onclick="closeModal()"
                        href="<?= esc_url( home_url( "/minha-conta/lost-password/" ) ); ?>" >Esqueceu a senha?</a>
                </p>

                <button id="tal-btn-form-register" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Entrar <div class="ta-d-none ta-spin-load" ></div></button>
            </form>
        </div>
    </div>
