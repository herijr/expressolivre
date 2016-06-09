<!-- BEGIN header -->
<script type="text/javascript"> 
function valida(pForm) 
{ 
        if((!document.getElementsByName('newsettings[expressoMail_Max_attachment_size]').item(0).value) || (!pForm.php_upload_limit.value)) 
                return ExpressoLivre.form(pForm); 
 
        if (parseInt(document.getElementsByName('newsettings[expressoMail_Max_attachment_size]').item(0).value) > parseInt(pForm.php_upload_limit.value)) 
        { 
                alert(pForm.valida_alert.value); 
                return false; 
        } 
        else 
                return ExpressoLivre.form(pForm); 
} 

function formatar_campo(mascara, elemento, evento, chr_masc)
{
  if (!evento)
    var evento = window.event;

  // para IE
  if(evento.keyCode)
    tecla = evento.keyCode;
  // para Navegadores
  else if(evento.which)
    tecla = evento.which;

  // se pressionado backspace, ou delete, ou tab, ou enter, não aplica máscara
  if(tecla == 8 || tecla == 46 || tecla == 9 || tecla == 13)
    return true;

  // tamanho da máscara
  var masc_t = mascara.length;

  // tamanho do campo digitado
  var elem_t = elemento.value.length;

  // se existe a propriedade maxLength e ela é igual ao número de caracteres digitados, não aceita mais nada
  if((masc_t == elem_t) || (elemento.maxLength && elem_t == elemento.maxLength))
    return false;

  // máscara sem o último caracter
  var texto  = mascara.substring(0, masc_t - 1);

  // índices para percorrer as strings da máscara e do campo digitado
  var i      = texto.length;
  var j      = elem_t;

  // caracter da máscara a ser substituído pelos dígitos
  var saida  = '';

  var campo  = '';

  // se não foi passado o parâmetro, recupera o primeiro caracter da máscara
  if(chr_masc == null)
    saida = mascara.substring(0,1);
  // senão, utiliza o caracter recebido (útil quando a máscara não começa com o caracter a ser substituido)
  else
    saida = chr_masc;

  // percorre as strings da máscara e do campo e substitui o caracter da variável saída pelo dígito
  while(i > 0 && j > 0){
    // percorre a máscara
    while(i > 0 && texto.substring(i-1, i) != saida){
      campo = texto.substring(i-1, i) + campo;
      i--;
    }

    // percorre o campo
    while(j > 0 && (isNaN(elemento.value.substring(j-1, j)) || elemento.value.substring(j-1, j) == " ")){
      j--;
    }

    if(j > 0)
      campo = elemento.value.substring(j-1, j) + campo;

    i--;
    j--;

    i_cp     = i;
    campo_cp = campo;

    // percorre a máscara novamente
    while(i > 0 && j >= 0 && texto.substring(i-1, i) != saida){
      campo = texto.substring(i-1, i) + campo;
      i--;
    }

    if(i > 0){
      i     = i_cp;
      campo = campo_cp;
    }
  }
  elemento.value = campo;
  return true;
}

</script> 
<form method="POST" action="{action_url}" onsubmit="return valida( this );"> 
<input type="hidden" name="php_upload_limit" value="{php_upload_limit}" /> 
<table border="0" align="center">
   <tr bgcolor="{th_bg}">
    <td colspan="2"><font color="{th_text}">&nbsp;<b>{title}</b></font></td>
   </tr>
<!-- END header -->
<!-- BEGIN body -->
   <tr bgcolor="{row_on}">
    <td colspan="2">&nbsp;</td>
   </tr>
   <tr bgcolor="{row_off}">
    <td colspan="2"><b>{lang_ExpressoMail_settings}</b></td>
   </tr>
   <tr class="{row_on}">
    <td>{lang_Would_you_like_to_use_expresso_offline?}:</td>
    <td>
     <select name="newsettings[enable_expresso_offline]">
      <option value="">{lang_No}</option>
      <option value="True"{selected_enable_expresso_offline_True}>{lang_Yes}</option>
     </select>
    </td>
   </tr>
   <tr bgcolor="{row_off}">
    <td>{lang_Do_you_want_to_enable_expressoMail_log?}</td>
    <td>
     <select name="newsettings[expressoMail_enable_log_messages]">
      <option value=""{selected_expressoMail_enable_log_messages_False}>{lang_No}</option>
      <option value="True"{selected_expressoMail_enable_log_messages_True}>{lang_Yes}</option>
     </select>&nbsp;&nbsp;&nbsp;path: /home/expressolivre/
    </td>
   </tr>
   <tr bgcolor="{row_on}">
   <td>{lang_Do_you_want_to_cache_php_requests_in_javascript?}</td>
   <td>
   <select name="newsettings[expressoMail_enable_cache]">
   <option value=""{selected_expressoMail_enable_cache_False}>{lang_No}</option>
   <option value="True"{selected_expressoMail_enable_cache_True}>{lang_Yes}</option>
   </select>
   </td>
   </tr>
   <tr bgcolor="{row_off}">
   <td>{lang_Do_you_want_to_use_the_spam_filter?}</td>
   <td>
   <select name="newsettings[expressoMail_use_spam_filter]">
   <option value=""{selected_expressoMail_use_spam_filter_False}>{lang_No}</option>
   <option value="True"{selected_expressoMail_use_spam_filter_True}>{lang_Yes}</option>
   </select>
    </td>
   </tr>
   <tr bgcolor="{row_on}">
    <td>{lang_URL_for_spam}</td>
    <td>
    <input type="text" name="newsettings[expressoMail_spam_url]" value="{value_expressoMail_spam_url}" size="60" />
    </td>
    <tr bgcolor="{row_off}">
    <td>{lang_spam_headers_fields}</td>
    <td>
    <input size="60" name="newsettings[expressoMail_spam_fields]" value="{value_expressoMail_spam_fields}">
    </td>
    </tr>
    <tr bgcolor="{row_on}">
    <td>{lang_reliable_domains}</td>
    <td>
    <input size="60" name="newsettings[expressoMail_notification_domains]" value="{value_expressoMail_notification_domains}">
    </td>
    </tr>
    <tr bgcolor="{row_off}">
    <td>{lang_Allowed_domains_for_sieve_forwarding} ({lang_Comma_separated})</td>
    <td>
    <input size="60" name="newsettings[expressoMail_sieve_forward_domains]" value="{value_expressoMail_sieve_forward_domains}">
    </td>
    </tr>
    <tr bgcolor="{row_on}">
    <td>{lang_Number_of_dynamic_contacts}</td>
    <td>
    <input size="1" name="newsettings[expressoMail_Number_of_dynamic_contacts]" value="{value_expressoMail_Number_of_dynamic_contacts}"> 
    </td>
    </tr>
    <tr bgcolor="{row_off}">
    <td>{lang_imap_max_folders}:</td>
    <td>
    <input size="2" name="newsettings[expressoMail_imap_max_folders]" value="{value_expressoMail_imap_max_folders}">
    </td>
    </tr>
    <tr bgcolor="{row_on}">
    <td>{lang_Max_attachment_size}</td>
    <td>
    <input size="1" name="newsettings[expressoMail_Max_attachment_size]" value="{value_expressoMail_Max_attachment_size}">&nbsp;Mb<span style='position:relative; left:20px;'>Max: {php_upload_limit}Mb.</span> 
    <input type="hidden" name="valida_alert" value="{lang_Value_exceeds_the_PHP_upload_limit_for_this_server}" /> 
    </td>
    </tr>
    <tr bgcolor="{row_off}">
    <td>{lang_googlegears_url}</td>
    <td>
    <input size="80" name="newsettings[expressoMail_googlegears_url]" value="{value_expressoMail_googlegears_url}"> 
    </td>
    </tr>
   <tr bgcolor="{row_on}">
   <td>{lang_Do_you_want_to_use_x_origin_in_source_menssage?}</td>
   <td>
   <select name="newsettings[expressoMail_use_x_origin]">
   <option value=""{selected_expressoMail_use_x_origin_False}>{lang_No}</option>
   <option value="True"{selected_expressoMail_use_x_origin_True}>{lang_Yes}</option>
   </select>
    </td>
   </tr> 
    <tr bgcolor="{th_bg}">
        <td colspan="2">
            &nbsp;
        </td>
    </tr>
    <tr bgcolor="{row_on}">
        <td colspan="2">
            <label style="font-weight:bold;">{lang_Share_folders}</label>
        </td>
    </tr>
   <tr bgcolor="{row_off}">
     <td>{lang_Do_you_wish_enable_autosearch?}</td>
     <td>
       <select id="usersAutoSearch" name="newsettings[expressoMail_users_auto_search]">
            <option value="true" {selected_expressoMail_users_auto_search_true}>{lang_Yes}</option>
            <option value="false" {selected_expressoMail_users_auto_search_false}>{lang_No}</option>
       </select>
     </td>
   </tr>  
   <tr bgcolor="{row_on}">
       <td>{lang_Minimum_number_of_characters_to_start_the_search_for_participants}</td>
       <td>
          <input type="text" id="minNum" value="{value_expressoMail_min_num_characters}" name="newsettings[expressoMail_min_num_characters]" size=2 maxlength=2 />
       </td>
   </tr> 
   <tr bgcolor="{th_bg}">
    <td colspan="2">
        &nbsp;
    </td>
   </tr>
   <tr>
    <td colspan="2">
      <!-- Mensagem do dia -->
      <label style="font-weight:bold;">{lang_Message_of_the_day}</label>
    </td>
   </tr>
   <tr>
      <td>{lang_Enable_messages}</td>
      <td>
        <select name="newsettings[expressoMail_motd_enabled]">
          <option value="true" {selected_expressoMail_motd_enabled_true}>{lang_Yes}</option>
          <option value="false" {selected_expressoMail_motd_enabled_false}>{lang_No}</option>
        </select>
        <span style="color:red;">&nbsp;&nbsp;&nbsp;{lang_CREATE_or_EXCLUDE_THE_COOKIE_CREATED_AS_EXHIBITS_TO_CONTROL_THE_MESSAGE}.</span>
      </td>
   </tr>
   <tr>
      <!-- Quantidade de Exibições -->
      <td>{lang_Number_of_views}</td>
      <td><input type="text" size="2" maxlength="2" name="newsettings[expressoMail_motd_number_views]" value="{value_expressoMail_motd_number_views}">&nbsp;&nbsp;&nbsp;{lang_times_within_the_time} ( 08:00 as 18:00hrs )</td>
   </tr>
   <tr>
      <!-- Intervalo das mensagens em minutos -->
      <td>{lang_Range_of_messages_in_minutes}</td>
      <td>
        <select name="newsettings[expressoMail_motd_range_of_messages_in_minutes]">
          <option value="20" {selected_expressoMail_motd_range_of_messages_in_minutes_20}>20 {lang_minutes}</option>
          <option value="30" {selected_expressoMail_motd_range_of_messages_in_minutes_30}>30 {lang_minutes}</option>
          <option value="40" {selected_expressoMail_motd_range_of_messages_in_minutes_40}>40 {lang_minutes}</option>
          <option value="50" {selected_expressoMail_motd_range_of_messages_in_minutes_50}>50 {lang_minutes}</option>
          <option value="60" {selected_expressoMail_motd_range_of_messages_in_minutes_60}>60 {lang_minutes}</option>
        </select>
      </td>
   </tr>
   <tr>
      <td>Tipo da Mensagem</td>
      <td>
        <select name="newsettings[expressoMail_motd_type_message]">
          <option value="information" {selected_expressoMail_motd_type_message_information}>{lang_Information}</option>
          <option value="warning" {selected_expressoMail_motd_type_message_warning}>{lang_Warning}</option>
        </select>        
      </td>  
   </tr>
   <tr>
      <!-- Título da Mensagem -->
      <td>{lang_Message_title}</td>
      <td><input type="text" size="45" maxlength="45" name="newsettings[expressoMail_motd_msg_title]" value="{value_expressoMail_motd_msg_title}"></td>
   </tr>
   <tr>
      <!-- Mensagem -->
      <td>{lang_Message}</td>
      <td>
    <textarea cols="60" rows="4" name="newsettings[expressoMail_motd_msg_body]">{value_expressoMail_motd_msg_body}</textarea>       
      </td>
   </tr>
   <tr bgcolor="{th_bg}">
    <td colspan="2">
        &nbsp;
    </td>
   </tr>
   <tr>
    <td colspan="2">
      <!-- Mensagem do dia -->
      <label style="font-weight:bold;">{lang_Block_Send_email}</label>
    </td>
   </tr>
   <tr>
      <td>{lang_Enable_block_send_email}</td>
      <td>
        <select name="newsettings[expressoMail_block_send_email_enabled]">
          <option value="true" {selected_expressoMail_block_send_email_enabled_true}>{lang_Yes}</option>
          <option value="false" {selected_expressoMail_block_send_email_enabled_false}>{lang_No}</option>
        </select>
      </td>
   </tr>
   <tr>
      <!-- Grupo do LDAP que não poderá enviar emails -->
      <td>{lang_Block_Send_Email_Group}</td>
      <td>
        <textarea cols="60" rows="4" name="newsettings[expressoMail_block_send_email_group]">{value_expressoMail_block_send_email_group}</textarea>
        <br>
        {lang_Fill_with_group_names_separated_by_commas}
      </td>
   </tr>
   <tr>
      <!-- Mensagem -->
      <td>{lang_Block_Email_Message}</td>
      <td>
        <textarea cols="60" rows="4" name="newsettings[expressoMail_block_send_email_error_message]">{value_expressoMail_block_send_email_error_message}</textarea>       
      </td>
   </tr>
<!-- END body -->
<!-- BEGIN footer -->
  <tr bgcolor="{th_bg}">
    <td colspan="2">
        &nbsp;
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" name="submit" value="{lang_submit}">
      <input type="submit" name="cancel" value="{lang_cancel}">
    </td>
  </tr>
</table>
</form>
<!-- END footer -->