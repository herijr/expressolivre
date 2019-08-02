<!-- BEGIN form -->
<div style="margin:0 auto; width:90%; padding:10px;">
    <div style="display: inline-block; padding:2px; width:400px; vertical-align:top;">
        <div style="color:red;font-size:9pt;">{messages}</div>
        <form method="POST" action="{form_action}">
            <table style='height:200px;width:300px;'>
                <tr>
                    <td>{lang_enter_actual_password}</td>
                    <td><input type="password" name="a_passwd" style="overflow:auto !important"></td>
                </tr>
                <tr>
                    <td>{lang_enter_password}</td>
                    <td><input type="password" name="n_passwd" style="overflow:auto !important"></td>
                </tr>
                <tr>
                    <td>{lang_reenter_password}</td>
                    <td><input type="password" name="n_passwd_2" style="overflow:auto !important"></td>
                </tr>
                <tr>
                    <td><input type="submit" name="cancel" value="{lang_cancel}"></td>
                    <td><input type="submit" name="change" value="{lang_change}"></td>
                </tr>
            </table>
        </form>
    </div>

    <div style=" display:inline-block;font-size:10pt; background-color:#fff; border:1px solid #000; padding:5px;">
        <h4>Orientações: </h4>
        <p>- Tamanho m&iacute;nimo da senha: <span style="color:red;font-weight:bold;">{num_letters_userpass}</span> caracteres; </p>
        <p>- Ao menos <span style="color:red;font-weight:bold;">{num_special_letters_userpass}</span> caracteres especiais ou n&uacute;meros; </p> 
        <p>- Ao menos <span style="color:red;font-weight:bold;">{num_uppercase_letters}</span> letra mai&uacute;scula;</p>
        <p>- Sua nova senha n&atilde;o pode ser igual &agrave; senha anterior;</p>
    </div>
</div>
<!-- END form -->