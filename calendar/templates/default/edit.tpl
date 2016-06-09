<!-- BEGIN edit_entry -->
<link rel="stylesheet" type="text/css" href="./prototype/plugins/jquery/css/redmond/jquery-ui-latest.min.css" />
<link rel="stylesheet" type="text/css" href="./prototype/plugins/zebradialog/css/zebra_dialog.css" />
<style type="text/css">
    div#tipDiv
    {
      position:absolute; 
      visibility:hidden; 
      left:0; top:0; z-index:10000;
      background-color:#EFEFEF; 
      border:1px solid #337;
      width:220px; 
      padding:3px;
      color:#000; 
      font-size:11px; 
      line-height:1.2;
      cursor: default;
    }
</style>
<center>
  <font color="#000000" face="{font}">
    <form action="{action_url}" method="post" name="app_form">
      {common_hidden}
      <table id="editFormTable" border="0" width="90%"   class="prefTable">
        <tr>
          <td colspan="2">
            <center><font size="+1"><b id="formStatus">{errormsg}</b></font></center>
          </td>
        </tr>
        {row}
        <tr>
          <td>
            <table>
              <tr valign="top">
                <td>
                  <div style="padding-top:15px; padding-right: 2px">
                    <script language="JavaScript">var alert_field = '{alert_msg}';</script>
                    <input id="submit_button" style="font-size:10px" type="submit" value="{submit_button}" onClick="return submitValues(alert_field);">
                  </div>
                </td>
                <td>{cancel_button}</td>
              </tr>
            </table>
          </td>
          <td align="right">{delete_button}</td>
        </tr>
      </table>
    </form>
  </font>
</center>
<div id="availability" style="display:none"></div>
<div id="participantsExterns" style="display:none"></div>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-migrate.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/jquery/jquery-ui-latest.min.js"></script>
<script type="text/javascript" src="./prototype/plugins/ejs/ejs.js"></script>
<script type="text/javascript" src="./prototype/plugins/ejs/ejs_production.js"></script>
<script type="text/javascript" src="./prototype/plugins/ejs/view.js"></script>
<script type="text/javascript" src="./prototype/plugins/zebradialog/zebra_dialog.min.js"></script>
<script type="text/javascript">
    
    self.name = "first_Window";

    function accounts_popup()
    {
      Window1 = window.open('{accounts_link}',"Search","width=800,height=600,toolbar=no,scrollbars=yes,resizable=yes");
    }

    var begin_hour  = [];
    var end_hour    = [];

    function set_time( hour , minute )   
    {
        if( begin_hour.length == 0 )
        {
            begin_hour[0] = hour;
            begin_hour[1] = minute;

            end_hour[0] = hour;
            end_hour[1] = minute;

            //Hour Start
            $("#start_hour").val( begin_hour[0] );
            $("#start_minute").val( begin_hour[1] );
        }
        else
        {
            end_hour[0] = hour;
            end_hour[1] = minute;
        }

        //Hour Start
        $("#end_hour").val( end_hour[0] );
        $("#end_minute").val( end_hour[1] );

        paintColumns( hour, minute);
    }

    function clear_time()
    {
      var _table = $("#availability").find("table");

      _table.find("td[name]").each(function()
      {
           if( $(this).attr("bgcolor").toUpperCase() == "#009ACD" )
           {
              $(this).attr("bgcolor", "#FFFFFF");
              
              begin_hour.splice(0,2);

              end_hour.splice(0,2);

              $("#start_hour").val("00");
              $("#start_minute").val("00");
              $("#end_hour").val("00");
              $("#end_minute").val("00");
          }
      });
    }

    function paintColumns( hour, minute )
    {
       var _table = $("#availability").find("table");

       _table.find("td[name]").each(function()
       {
          if( $(this).attr("bgcolor").toUpperCase() != "#DA3A3A" && $(this).attr("bgcolor").toUpperCase() != "#009ACD")
          {
            if( $(this).attr("name") == (hour+""+minute) )
            {
                $(this).attr("bgcolor", "#009ACD");
            }
          }
       });
    }

</script>

<!-- END edit_entry -->

<!-- BEGIN list -->
<tr bgcolor="{tr_color}">
  <td valign="top" width="25%">&nbsp;<b>{field}:</b></td>
  <td valign="top" width="75%">{data}</td>
</tr>
<!-- END list -->

<!-- BEGIN hr -->
<tr bgcolor="{tr_color}">
  <td colspan="2">
    {hr_text}
  </td>
</tr>
<!-- END hr -->