<!-- BEGIN page_block -->
<!-- vcardin form -->
{vcal_header}
		{error_box}
    <form ENCTYPE="multipart/form-data" method="POST" action="{action_url}">
      <table border=0>
      <tr>
       <td>{ical_lang}: <input type="file" name="uploadedfile"></td>
       <td><input type="submit" name="action" value="{load_vcal}"></td>
      </tr>
      </table>
     </form>
<!-- END page_block -->
<!-- BEGIN error_block -->
<b><center>{error_message}</b></center><br><br>
<!-- END error_block -->
