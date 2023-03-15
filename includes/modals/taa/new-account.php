
    <div id="taa-modal-new-account" class="woocommerce ta-bg-modal ta-d-none">
        <div class="ta-bg-modal-child">
            <div class="ta-modal-header">
                <h2>Entre com sua conta</h2>
                <span id="taa-close-new-account" aria-hidden="true" onclick="taaCloseModal(this)" >&times;</span>
            </div>
            <form id="taa-form-new-account" method="post" >
                <p class="ta-text-left ta-mb-15" >
                    Informe o e-mail e a senha da sua conta:
                    <br>
                    <small>Caso não tenha uma, criaremos para você.</small>
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-mb-0">
                    <!-- <input type="hidden" name="form-new-account"> -->
                    <input id="taa-new-acc-email" type="email" name="taa-email" class="woocommerce-Input woocommerce-Input--text input-text ta-mb-15"
                           placeholder="E-mail" required />
                    <input id="taa-new-acc-pass1" type="password" name="taa-pass" class="woocommerce-Input woocommerce-Input--text input-text ta-mb-0"
                           placeholder="Senha" required />
                    <!-- pattern=".{8,}" title="A senha deve ter no mínimo 8 dígitos" -->
                    <!-- <input id="taa-new-acc-pass2" type="password" name="taa-pass-c" class="woocommerce-Input woocommerce-Input--text input-text ta-mb-0"
                           placeholder="Confirmação de senha" pattern=".{8,}" title="A senha deve ter no mínimo 8 dígitos" required /> -->

                    <!-- Error message -->
                    <p id="taa-error-new-acc" class="ta-bg-transp ta-text-center ta-text-danger ta-pt-8 ta-mb-0 ta-d-none" ></p>
                </p>
                <button id="taa-btn-form-new-account" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Entrar <div class="ta-d-none ta-spin-load"></div></button>
            </form>
        </div>
    </div>
