/**
 * @author diogenes
 */

function local_messages() {
    this.dbGears = null;
    this.localServer = null;
    this.store = null;
    this.filterSerch = "";
}

function charOrdA (a, b){
    a = a[0].toLowerCase();
    b = b[0].toLowerCase();
    if (a>b) return 1;
    if (a <b) return -1;
    return 0;
}
function charOrdD(a, b){
    a = a[0].toLowerCase();
    b = b[0].toLowerCase();
    if (a<b) return 1;
    if (a>b) return -1;
    return 0;
}


function HeaderFlags()
{
    this.Answered = 0;
    //this.Draft = 0;
    this.Flagged = 0;
    this.Recent = 0;
}

//ADD forwarded files
function addForwardedFile(id_border,file_name,link,divFiles){
	if(!divFiles)
		divFiles = document.getElementById("divFiles_"+id_border);

	if (! divFiles)
		return false;

	if (divFiles.lastChild)
		var countDivFiles = parseInt(divFiles.lastChild.id.split('_')[2]) + 1;

	if (! countDivFiles)
		var countDivFiles = 1;

	divFile = document.createElement('DIV');


	if (!expresso_offline) {
		if (!is_ie) {
			var inputFile = document.createElement("INPUT");

			var tmp_id_border = document.createAttribute('id_border');
			tmp_id_border.value = id_border;

			inputFile.setAttributeNode(tmp_id_border);
			inputFile.id = "inputFile_" + id_border + "_" + countDivFiles;
			inputFile.type = 'file';
			inputFile.size = 50;
			inputFile.maxLength = 255;
			inputFile.name = 'file_' + countDivFiles;
			inputFile.style.display = "none";
		}
		else {
			var inputFile = document.createElement("link");

			var tmp_id_border = document.createAttribute('id_border');
			tmp_id_border.value = id_border;

			inputFile.setAttributeNode(tmp_id_border);
			inputFile.id = "inputFile_" + id_border + "_" + countDivFiles;
			inputFile.name = 'file_' + countDivFiles;


		}

	}
	else {
		var inputFile = document.createElement("input");
		inputFile.type = 'hidden';
		inputFile.name = 'offline_forward_' + countDivFiles;
	}
	divFile.appendChild(inputFile);

	var a_tmp = new Array();
	a_tmp[0] = "local_";
	a_tmp[1] = 'file_' + countDivFiles;
	a_tmp[2] = file_name;
	s_tmp = escape(connector.serialize(a_tmp));
	var checkbox = document.createElement("INPUT");
	checkbox.type = "checkbox";
	checkbox.id = "checkbox_"+id_border+"_"+countDivFiles;
	checkbox.name = "local_attachments[]";

	checkbox.value = s_tmp;
	divFile.appendChild(checkbox);

	var link_attachment = document.createElement("A");
	link_attachment.setAttribute("href", link);

	link_attachment.innerHTML = file_name;
	divFile.appendChild(link_attachment);

	countDivFiles++;
	divFile.id = "divFile_"+id_border+"_"+countDivFiles;
	divFiles.appendChild(divFile);

	checkbox.checked = true; //Incrivel, mas no Internet Explorer o checked so funciona efetivamente se o elemente ja estiver renderizado... por isso so aqui eu seleciono ele...
	return inputFile;
}

HeaderFlags.prototype.getAnswered = function()
{
    return this.Answered;
}

HeaderFlags.prototype.setAnswered = function(answered)
{
    this.Answered = answered;
}

//HeaderFlags.prototype.getDraft = function()
//{
//    return this.Draft;
//}

HeaderFlags.prototype.setDraft = function(draft)
{
    this.Draft = draft;
}

HeaderFlags.prototype.getFlagged = function()
{
    return this.Flagged;
}

HeaderFlags.prototype.setFlagged = function(flagged)
{
    this.Flagged = flagged;
}

HeaderFlags.prototype.getRecent = function()
{
    return this.Recent;
}

HeaderFlags.prototype.setRecent = function(recent)
{
    this.Recent = recent;
}

function FlagsParser(headerObj)
{
    this.Header = headerObj;
}

FlagsParser.prototype.parse = function()
{
    var tmp = null;
    if (typeof this.Header == 'string')
    {
	tmp = connector.unserialize(this.Header);
    }
    else
    {
	tmp = this.Header;
    }

    flags = new HeaderFlags();

    if (tmp.Answered && tmp.Answered.match(/^A$/))
    {
	flags.setAnswered(1);
    //if (tmp.Draft && tmp.Draft.match(/^X$/))
    //{
    //    flags.setDraft(1);
    //}
    }

    if (tmp.Flagged && tmp.Flagged.match(/^F$/)){
	flags.setFlagged(1);
    }

    if (tmp.Forwarded && tmp.Forwarded.match(/^F$/)){
	flags.setAnswered(1);
    //flags.setDraft(1);
    }

    if (tmp.Recent && tmp.Recent.match(/^R$/)){
	flags.setRecent(1);
    }

    return flags;

}
        
local_messages.prototype.installGears = function (){
    temp = confirm(get_lang("To use local messages you have to install google gears. Would you like to install it now?"));
    if (temp && typeof(preferences.googlegears_url) != 'undefined'){
	if (is_ie)
	    location.href = preferences.googlegears_url + "/gears.exe";
	else
	    location.href = preferences.googlegears_url + "/gears.xpi";
	return false;
    }
    if (temp) {
	location.href = "http://gears.google.com/?action=install&message="+
	get_lang("To use local messages, install Google Gears")+"&return=" + document.location.href;
    }
    else return false;
}

local_messages.prototype.create_objects = function() {
    if(window.google){
	if (this.dbGears == null)
	    this.dbGears = google.gears.factory.create('beta.database');
	if(this.localServer == null)
	    this.localServer = google.gears.factory.create('beta.localserver');
	if(this.store == null)
	    this.store = this.localServer.createStore('test-store');
    }
}

local_messages.prototype.init_local_messages = function(){ //starts only database operations
		
    if(this.dbGears==null || this.localServer==null || this.store == null)
	this.create_objects();
		
    var db_in_other_use = true;
    var start_trying = new Date().getTime();
    while (db_in_other_use) {
	try {
	    this.dbGears.open('database-test');
	    db_in_other_use = false;
	}
	catch (ex) {
				if(new Date().getTime()-start_trying>10000) { //too much time trying, throw an exception
					throw ex;
	}
    }
		}
			
    //		this.dbGears.open('database-test');
    this.dbGears.execute('create table if not exists folder (folder text,uid_usuario int,unique(folder,uid_usuario))');
    this.dbGears.execute('create table if not exists mail' +
	' (mail blob,original_id int,original_folder text,header blob,timestamp int,uid_usuario int,unseen int,id_folder int,' +
	' ffrom text, subject text, fto text, cc text, body text, size int,unique (original_id,original_folder,uid_usuario,id_folder))');
    this.dbGears.execute('create table if not exists anexo' +
	' (id_mail int,nome_anexo text,url text,pid int)');
    this.dbGears.execute('create table if not exists folders_sync' +
	' (id_folder text,folder_name text,uid_usuario int)');
    this.dbGears.execute('create table if not exists msgs_to_remove (id_msg int,folder text,uid_usuario int)');
    this.dbGears.execute('create index if not exists idx_user3 on mail (id_folder,uid_usuario,timestamp)');
    this.dbGears.execute('create INDEX if not exists idx_folder ON folder(uid_usuario,folder)');

    //some people that used old version of local messages could not have the size column. If it's the first version
    //with local messages you're using in expresso, this part of code can be removed
    try {
	this.dbGears.execute('alter table mail add column size int');
    }catch(Exception) {
			
    }
    var rs = this.dbGears.execute('select rowid,header from mail where size is null');
    while(rs.isValidRow()) {
	var temp = connector.unserialize(rs.field(1));
			
	this.dbGears.execute('update mail set size='+temp.Size+' where rowid='+rs.field(0));
	rs.next();
    }
    //end of temporary code

    try {
	this.dbGears.execute('begin transaction');
	this.dbGears.execute('alter table mail add column answered int');
	//this.dbGears.execute('alter table mail add column draft int');
	this.dbGears.execute('alter table mail add column flagged int');
	this.dbGears.execute('alter table mail add column recent int');
	//this.dbGears.execute('commit transaction');
	//transaction_ended = true;
	//if (transaction_ended){
	rs = null;
	rs = this.dbGears.execute('select rowid,header from mail');

	// Popular os valores das novas colunas.
	var tmp = null;
	//this.dbGears.execute('begin transaction');
	while(rs.isValidRow()) {
	    //tmp = connector.unserialize(rs.field(1));
	    parser = new FlagsParser(rs.field(1));
	    flags = parser.parse();

	    this.dbGears.execute('update mail set answered='+flags.getAnswered()+
		',flagged='+flags.getFlagged()+',recent='+flags.getRecent()+
		//',draft='+flags.getDraft()+' where rowid='+rs.field(0));
		' where rowid='+rs.field(0));

	    rs.next();
	}
	this.dbGears.execute('commit transaction');

    //tmp = null;

    }catch(Exception) {
	this.dbGears.execute('rollback transaction');
    }

}
	
local_messages.prototype.drop_tables = function() {
    this.init_local_messages();
    var rs = this.dbGears.execute('select url from anexo');
    while(rs.isValidRow()) {
	this.store.remove(rs.field(0));
	rs.next();
    }
    this.dbGears.execute('drop table folder');
    this.dbGears.execute('drop table mail');
    this.dbGears.execute('drop table anexo');
    this.finalize();
}
	
local_messages.prototype.insert_mail = function(msg_info,msg_header,anexos,folder) {
    try {
	this.init_local_messages();
	var unseen = 0;
	var login = msg_info.login;
	var original_id = msg_info.msg_number;
	var original_folder = msg_info.msg_folder;
			
	//This fields needs to be separeted to search.
	var from = connector.serialize(msg_info.from);
	var subject = msg_info.subject;
	var body = msg_info.body;
	var to = connector.serialize(msg_info.toaddress2);
	var cc = connector.serialize(msg_info.cc);
	var size = msg_header.Size;
	
	//do not duplicate this information
	msg_info.from = null;
	msg_info.subject = null;
	msg_info.body = null;
	msg_info.to = null;
	msg_info.cc = null;
	msg_header.Size=null;
	//If the mail was archieved in the same date the user received it, the date cames with the time.
	//here I solved it
	if(msg_header.udate.indexOf(":")!=-1) {
	    msg_header.udate = msg_header.aux_date;
	}
			
	/**
			 * The importance attribute can be empty, and javascript consider as null causing nullpointer.
			 */
	if((msg_header.Importance == null) ||  (msg_header.Importance == ""))
	    msg_header.Importance = "Normal";
			
	msg_header.aux_date = null;
			
	var mail = connector.serialize(msg_info);
	var header = connector.serialize(msg_header);
	
	var timestamp = msg_info.timestamp;
	var id_folder;
	
	if((folder==null) || (folder=="local_root"))
	    folder = "Inbox";
	else
	    folder = folder.substr(6);//take off the word "local_"
			
	var rs = this.dbGears.execute("select rowid from folder where folder=? and uid_usuario=?",[folder,account_id]);
	if(rs.isValidRow())
	    id_folder=rs.field(0);
	else {
	    this.dbGears.execute("insert into folder (folder,uid_usuario) values (?,?)",["Inbox",account_id]);
	    id_folder = this.dbGears.lastInsertRowId;
	}
			
	if(msg_info.Unseen=="U")
	    unseen = 1;

	//parse header
	parser = new FlagsParser(msg_header);
	flags = parser.parse();

	//insere o e-mail
	//this.dbGears.execute("insert into mail (mail,original_id,original_folder,header,timestamp,uid_usuario,unseen,id_folder,ffrom,subject,fto,cc,body,size) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[mail,original_id,original_folder,header,timestamp,login,unseen,id_folder,from,subject,to,cc,body,size]);
	this.dbGears.execute("insert into mail (mail,original_id,original_folder,header,timestamp,uid_usuario,unseen,id_folder,ffrom,subject,fto,cc,body,size,answered,flagged,recent) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[mail,original_id,original_folder,header,timestamp,login,unseen,id_folder,from,subject,to,cc,body,size,flags.getAnswered(),flags.getFlagged(),flags.getRecent()]);
	var call_back = function() {
	}
	this.store.capture(msg_info.url_export_file,call_back);
	var id_mail = this.dbGears.lastInsertRowId;
	
	this.insert_attachments(id_mail,anexos);
	this.finalize();
	return true;
    } catch (error) {
	this.finalize();
	return false;
    }


}
	
/**
	 * check if ID is no from main tab, if it's from main returns false, else 
	 * returns an array with all string in position 0, the mail id in position 1 
	 * and the part of string relative to tab in position 2
	 * @param {Object} id_mail
	 */
local_messages.prototype.parse_id_mail = function(id_mail) { 
    if (this.isInt(id_mail))
	return false;
		
    var matches = id_mail.match(/(.+)(_[a-zA-Z0-9]+)/);
    return matches;
}
	
local_messages.prototype.isInt = function(x) {
    var y=parseInt(x);
    if (isNaN(y)) return false;
    return x==y && x.toString()==y.toString();
} 
	
local_messages.prototype.get_local_mail = function(id_mail) {
    this.init_local_messages();

    var plus_id = '';
    var matches = '';
    if(matches = this.parse_id_mail(id_mail)) { //Mails coming from other tab.
	id_mail = matches[1];
	plus_id = matches[2];
    }
		
    var rs = this.dbGears.execute("select mail.rowid,mail.mail,mail.ffrom,mail.subject,mail.body,mail.fto,mail.cc,folder.folder,mail.original_id from mail inner join folder on mail.id_folder=folder.rowid  where mail.rowid="+id_mail);
    var retorno = null;
    if(rs.isValidRow()) {
	retorno = rs.field(1);
    }
    retorno = connector.unserialize(retorno);

    //alert('tipo retorno.source: ' + typeof(retorno.source));

    if (typeof(retorno.source) == 'string')
    {
	retorno.msg_number=rs.field(0)+plus_id;
	retorno.original_ID=rs.field(8);
	retorno.msg_folder=rs.field(7);

    //alert('tipo retorno: '+typeof(retorno))
    //show_msg(retorno);
    }
    else
    {
	retorno['from'] = connector.unserialize(rs.field(2));
	retorno['subject'] = rs.field(3);
	retorno['body'] = rs.field(4);
	//Codigo que as imagens embutidas em emails (com multipart/related ou multipart/mixed) sejam corretamente mostradas em emails arquivados. Os links do
	//tipo "./inc/show_img.php?msg_folder=[folder]&msg_num=[msg_num]&msg_part=[part]"
	//sao substituidos pelos links dos anexos capturados pelo gears.

	var thumbs= retorno.thumbs;
	var anexos= retorno.array_attach;
	for (i in anexos)
	{
	    if(anexos[i]['url'] && anexos[i]['url'].match(/((jpg)|(jpeg)|(png)|(gif)|(bmp))/gi))
	    {
		var er_imagens = new RegExp("\\.\\/inc\\/show_img.php\\?msg_folder=[\\w/]+\\&msg_num=[0-9]+\\&msg_part="+anexos[i]['pid']);
		var Result_imagens = er_imagens.exec(retorno['body']);
		retorno['body'] = retorno['body'].replace(Result_imagens,anexos[i]['url']);
		if(thumbs && thumbs[i]){
		    er_imagens = new RegExp("\\.\\/inc\\/show_img.php\\?msg_num=[0-9]+\\&msg_folder=[\\w/%]+\\&msg_part="+anexos[i]['pid']+"\\&thumb=true\\&file_type=image\\/[\\w]+");
		    Result_imagens = er_imagens.exec(thumbs[i]);
		    thumbs[i] = thumbs[i].replace(Result_imagens,"'"+anexos[i]['url']+"'");
		    er_imagens = new RegExp("\\.\\/inc\\/show_img.php\\?msg_num=[0-9]+\\&msg_folder=[\\w/%]+\\&msg_part="+anexos[i]['pid']);
		    Result_imagens = er_imagens.exec(thumbs[i]);
		    thumbs[i] = thumbs[i].replace(Result_imagens,anexos[i]['url']);
		    thumbs[i] = thumbs[i].replace(/<IMG/i,'<img width="120"');
		}
	    }
	}

	retorno['to'] = connector.unserialize(rs.field(5));
	retorno['cc'] = connector.unserialize(rs.field(6));

	retorno['local_message'] = true;
	retorno['msg_folder'] = "local_"+rs.field(7); //Now it's a local folder
	retorno['msg_number'] = rs.field(0)+plus_id; //the message number is the rowid

    }

    rs.close();
    this.finalize();
    return retorno;
}

local_messages.prototype.insert_attachments = function(id_msg,anexos) { 
    //insert_mail already close and open gears.
    for (var i = 0; i < anexos.length; i++) {
	this.dbGears.execute("insert into anexo (id_mail,nome_anexo,url,pid) values (?,?,?,?)", [id_msg, anexos[i]['name'],anexos[i]['url'],anexos[i]['pid']]);
	this.capt_url(anexos[i]['url']);
    }
}

local_messages.prototype.capt_url = function (url) {
    //insert_mail already close and open gears.
    var call_back = function(url,success,captureId) {
    //alert("Capturado: " + url);
    }
    //alert(url);
    this.store.capture(url,call_back);
}

local_messages.prototype.strip_tags = function (str) {
    return str.replace(/<\/?[^>]+>/gi, '');
}

local_messages.prototype.get_local_range_msgs = function(folder,msg_range_begin,emails_per_page,sort,sort_reverse,search,preview_msg_subject,preview_msg_tip) {

    this.init_local_messages();
    var retorno = new Array();
    msg_range_begin--;
		
    mail_filter = " ";
    if(search=="FLAGGED") {
	mail_filter = "and (header like '%\"Flagged\";s:1:\"F%' or header like '%\"Importance\";s:5:\"High%') ";
    }
    if(search=="UNSEEN") {
	mail_filter = "and unseen = 1 ";
    }
    if(search=="SEEN") {
	mail_filter = "and unseen = 0 ";
    }
    if (search=="ANSWERED") {
	mail_filter = "and header like '%\"Answered\";s:1:\"A%' ";
    }
		
	sql = 'select mail.rowid as rowid,mail.header as header,mail.size as size,' +
    'mail.timestamp as timestamp,mail.unseen as unseen,mail.body as body, mail.mail as mail, ' +
    'case when lower(mail.ffrom) like ? then ' +
    'case when ltrim(ltrim(substr(UPPER(fto),7,length(fto)),\':\'),\'"\') like \'5:%\' then ' +
    'substr(ltrim(ltrim(substr(UPPER(fto),7,length(fto)),\':\'),\'"\'),17) ' +
    'else ' +
    'ltrim(ltrim(substr(UPPER(fto),7,length(fto)),\':\'),\'"\') ' +
    'end ' +
    'else ' +
    'case when ltrim(ltrim(substr(UPPER(ffrom),21,length(ffrom)),\':\'),\'"\')  like \'5:%\' then ' +
    'substr(ltrim(ltrim(substr(UPPER(ffrom),21,length(ffrom)),\':\'),\'"\'),17) ' +
    'else ' +
    'ltrim(ltrim(substr(UPPER(ffrom),21,length(ffrom)),\':\'),\'"\') ' +
    'end ' +
    'end as order_from,mail.subject from mail inner join folder on mail.id_folder=folder.rowid where mail.uid_usuario=? and folder.folder=? ' +
    mail_filter + ' order by ';
		
    if(sort == 'SORTFROM') {
	sql += 'order_from ';
    }
    if(sort == 'SORTARRIVAL') {
	sql += 'timestamp ';
    }
    if(sort == 'SORTSIZE') {
	sql += 'size ';
    }
    if(sort == 'SORTSUBJECT') {
	sql += 'UPPER(subject) ';
    }


    sql+= sort_reverse==0?"ASC ":"DESC ";
    sql +='limit ?,? ';


    var rs = this.dbGears.execute(sql,['%'+Element("user_email").value+'%',account_id,folder,msg_range_begin,emails_per_page]);
    var cont = 0;
		
    var rs3 = this.dbGears.execute('select count(*) from mail inner join folder on mail.id_folder=folder.rowid where mail.uid_usuario=? and folder.folder=?'+mail_filter,[account_id,folder]);
				
    while (rs.isValidRow()) {
	//var email = rs.field(1);
	var head = rs.field(1);
	var codigoMail = rs.field(0);
        var mail = rs.field(6);

	var msg_body = rs.field(5);//recebe o conteudo da coluna "body" do banco de dados;

	var rs2 = this.dbGears.execute('select count(*) from anexo where id_mail = '+codigoMail);
	var head_unserialized = connector.unserialize(head);
        var mail_unserialized = connector.unserialize(mail);

      head_unserialized.Size = rs.field(2);
	if(rs.field(4)==1)
	    head_unserialized.Unseen = 'U';
			

	head_unserialized.subject=(head_unserialized.subject==null)?"":unescape(head_unserialized.subject);

	//var email_unserialized = connector.unserialize(email);
	retorno[cont] = head_unserialized;
	retorno[cont]['msg_number'] = codigoMail;
        retorno[cont]['msg_day'] = mail_unserialized.msg_day;
        retorno[cont]['msg_hour'] = mail_unserialized.msg_hour;
	//declaracao do array() para receber o body de cada mensagem encontrada na busca sql realizada;

	retorno[cont]['msg_sample'] = new Array();

	if( (preview_msg_subject == 0) && (preview_msg_tip == 0) )
	{
	    retorno[cont]['msg_sample']['body'] = "";
	}
	else
	{
	    msg_body += ">";
	    msg_body=this.strip_tags(msg_body);
	    msg_body=msg_body.replace(/\&nbsp;/ig," ");
	    retorno[cont]['msg_sample']['body'] = " - " + msg_body.substr(2,msg_body.length-1);
	}

	cont++;
	rs.next();
    }
    retorno['num_msgs'] = rs3.field(0);
    rs3.close();
    rs.close();
    if(cont>0)
	rs2.close();
    this.finalize();
    return retorno;
}
	
local_messages.prototype.get_url_anexo = function(msg_number,pid) {
    this.init_local_messages();
    var matches = '';
    if(matches = this.parse_id_mail(msg_number)) {
	msg_number = matches[1];
    }
		
    var retorno;
    var rs = this.dbGears.execute("select url from anexo where id_mail="+msg_number+" and pid = '"+pid+"'");
    retorno = rs.field(0)
    this.finalize();
		
    return retorno;
}

local_messages.prototype.getInputFileFromAnexo = function (element,url) { 
    this.init_local_messages();
    fileSubmitter = this.store.createFileSubmitter();
    fileSubmitter.setFileInputElement(element,url);
    this.finalize();
}

local_messages.prototype.finalize = function() {
    this.dbGears.close();
    this.dbGears = null;
}

local_messages.prototype.delete_msgs = function(msgs_number,border_ID) {
    this.init_local_messages();
    var rs = this.dbGears.execute("select url from anexo where id_mail in ("+msgs_number+")");
    while(rs.isValidRow()) {
	this.store.remove(rs.field(0));
	rs.next();
    }
    this.dbGears.execute("delete from anexo where id_mail in ("+msgs_number+")");
    this.dbGears.execute("delete from mail where rowid in ("+msgs_number+")");
    this.finalize();
    if (msgs_number.length == 1)
	write_msg(get_lang("The message was deleted."));
    else
	write_msg(get_lang("The messages were deleted."));
		
    mail_msg = Element("tbody_box");

    try {
	msgs_exploded = msgs_number.split(",");
    }catch(error) {
	msgs_exploded = new Array();
	msgs_exploded[0] = msgs_number;
    }
    var msg_to_delete;
    for (var i=0; i<msgs_exploded.length; i++){
	msg_to_delete = Element(msgs_exploded[i]);
	if (msg_to_delete){
	    if ( (msg_to_delete.style.backgroundColor != '') && (preferences.use_shortcuts == '1') )
		shortcutExpresso.selectMsg( false , 'down' );
	    mail_msg.removeChild(msg_to_delete);
	    decrement_folder_unseen();
	}
    }
    Element('chk_box_select_all_messages').checked = false;
    if (border_ID != 'null')
	delete_border(border_ID,'false');
		
}
	
local_messages.prototype.get_source_msg = function(id_msg) {
    this.init_local_messages();
    var rs = this.dbGears.execute("select mail from mail where rowid="+id_msg);


    mail = connector.unserialize(rs.field(0));
    download_local_attachment(mail.url_export_file)

    this.finalize();
}
	
	
	
local_messages.prototype.set_messages_flag = function(msgs_number, flag) {
    this.init_local_messages();
	var no_errors = true;
    var msgs_to_set;
	var one_message = false;
    if (msgs_number == 'get_selected_messages') {
		var msgs_to_set = get_selected_messages();
		msgs_to_set= msgs_to_set.split(",");
    }
    else { //Just one message
	    one_message = true;
		msgs_to_set = new Array();
		msgs_to_set[0] = msgs_number;
    }
    for (var i in msgs_to_set) {
			
	var matches = '';//Messages comming from other tabs.
	if(matches = this.parse_id_mail(msgs_to_set[i])) {
	    msgs_to_set[i] = matches[1];
	}
			
	var rs = this.dbGears.execute("select header,unseen,mail from mail where rowid=" + msgs_to_set[i]);
	header = connector.unserialize(rs.field(0));
	var mail = connector.unserialize(rs.field(2));
	unseen = rs.field(1);
	switch(flag) {
	    case "unseen":
			set_msg_as_unread(msgs_to_set[i]);
			header["Unseen"] = "U";
			mail["Unseen"] = "U";
			mail["header"]["Unseen"] = "U";
			unseen = 1;
			break;
	    case "flagged":
			set_msg_as_flagged(msgs_to_set[i]);
			header["Flagged"] = "F";
			mail["Flagged"] = "F";
			mail["header"]["Flagged"] = "F";
			break;
	    case "unflagged":
			if (header["Importance"].indexOf("High") != -1) 
				no_errors = false;
			else {
			    set_msg_as_unflagged(msgs_to_set[i]);
			    header["Flagged"] = "N";
				mail["Flagged"] = "N";
				mail["header"]["Flagged"] = "N";
			}
		break;
	    case "seen":
			header["Unseen"] = "N";
			mail["Unseen"] = "N";
			mail["header"]["Unseen"] = "N";
			set_msg_as_read(msgs_to_set[i],true);
			unseen = 0;
			break;
	    case "answered":
			header["Draft"]="";
			mail["Draft"] = "";
			mail["header"]["Draft"] = "";
			header["Answered"]="A";
			mail["Answered"] = "A";
			mail["header"]["Answered"] = "A";
			Element("td_message_answered_"+msgs_to_set[i]).innerHTML = '<img src=templates/default/images/answered.gif title=Respondida>';
		break;
	    case "forwarded":
			header["Draft"]="X";
			mail["Draft"] = "X";
			mail["header"]["Draft"] = "X";
			header["Answered"]="A";
			mail["Answered"] = "A";
			mail["header"]["Answered"] = "A";
			Element("td_message_answered_"+msgs_to_set[i]).innerHTML = '<img src=templates/default/images/forwarded.gif title=Encaminhada>';
			break;
	}
		
	rs.close();
			
	if(Element("check_box_message_" + msgs_to_set[i]))
	    Element("check_box_message_" + msgs_to_set[i]).checked = false;
	

	this.dbGears.execute("update mail set mail=?,header=?,unseen=? where rowid=?",[connector.serialize(mail),connector.serialize(header),unseen,msgs_to_set[i]]);

    }
    if(Element('chk_box_select_all_messages'))
	Element('chk_box_select_all_messages').checked = false;
    this.finalize();
	if (!no_errors) {
		if(one_message)
			write_msg(get_lang("this message cant be marked as normal"));
		else
			write_msg(get_lang("At least one of selected message cant be marked as normal"));
		return false;
	}
	return true;

}
	
local_messages.prototype.set_message_flag = function(msg_number,flag,func_after_flag_change) {
    no_errors = this.set_messages_flag(msg_number,flag);
	if(no_errors && func_after_flag_change)
		func_after_flag_change(true);
}
	
local_messages.prototype.get_unseen_msgs_number = function() {
    this.init_local_messages();
    var rs = this.dbGears.execute("select count(*) from mail where unseen=1");
    var retorno = rs.field(0);
    rs.close();
    this.finalize();
    return retorno;
}

local_messages.prototype.create_folder = function(folder) {

    if (folder.indexOf("local_") != -1)
	return false; //can't create folder with string local_

    this.init_local_messages();
    try {
	this.dbGears.execute("insert into folder (folder,uid_usuario) values (?,?)",[folder,account_id]);
    } catch (error) {
	this.finalize();
	return false;
    }
    this.finalize();
    return true;
}

local_messages.prototype.list_local_folders = function(folder) {
    this.init_local_messages();
    var retorno = new Array();
    var retorno_defaults = new Array();
    rs = this.dbGears.execute("select folder,rowid from folder where uid_usuario=?",[account_id]);
//    rs = this.dbGears.execute("select folder.folder,sum(mail.unseen) from folder left join mail on "+
//	"folder.rowid=mail.id_folder where folder.uid_usuario=? group by folder.folder",[account_id]);
    var achouInbox = false,achouSent = false ,achouSentConf = false,achouTrash = false,achouDrafts = false;
    while(rs.isValidRow()) {
	var temp = new Array();
	temp[0] = rs.field(0);
	var rs2 = this.dbGears.execute("select count(*) from mail where id_folder=? and unseen=1",[rs.field(1)]);
	 rs2.field(0)? temp[1] = rs2.field(0):temp[1]=0;

	var rs3 = this.dbGears.execute("select * from folder where folder like ? limit 1",[temp[0]+"/%"]);
	if(rs3.isValidRow())
	    temp[2] = 1;
	else
	    temp[2] = 0;

	if(sentfolder ==  preferences.save_in_folder.replace("INBOX/","") || preferences.save_in_folder.replace("INBOX/","") == trashfolder || preferences.save_in_folder.replace("INBOX/","") == draftsfolder)
	    achouSentConf= true;

	switch (temp[0]) {
	    case 'Inbox':
		retorno_defaults[0] = temp;
		achouInbox = true;
		break;
	    case sentfolder :
		retorno_defaults[1] = temp;
		achouSent = true;
		break;
	    case trashfolder:
		retorno_defaults[3] = temp;
		achouTrash = true;
		break;
	    case draftsfolder:
		retorno_defaults[4] = temp;
		achouDrafts = true;
		break;
	    case preferences.save_in_folder.replace("INBOX/",""):
		retorno_defaults[2] = temp;
		achouSentConf = true;
		break;
	    default:
		retorno.push(temp);
	}

	rs.next();
    }

    rs.close();
    this.finalize();

    if(preferences.auto_create_local=='0' || (achouInbox && achouSent && achouSentConf && achouTrash && achouDrafts)){
	var retorno_final = retorno_defaults.concat(retorno.sort(charOrdA));
	return retorno_final;
    }else{
	if(!achouInbox)
	    this.create_folder('Inbox');
	if(!achouSent)
	    this.create_folder(sentfolder);
	if(!achouTrash)
	    this.create_folder(trashfolder);
	if(!achouDrafts)
	    this.create_folder(draftsfolder);
	if(!achouSentConf)
	    this.create_folder(preferences.save_in_folder.replace("INBOX/",""));
	return this.list_local_folders();
    }

}
local_messages.prototype.rename_folder = function(folder,old_folder) {
    if (folder.indexOf("local_") != -1)
	return false; //can't create folder with string local_
    this.init_local_messages();
    if (old_folder.indexOf("/") != "-1") {
	final_pos = old_folder.lastIndexOf("/");
	folder = old_folder.substr(0, final_pos) + "/" + folder;
    }
    try {
	this.dbGears.execute("update folder set folder=? where folder=? and uid_usuario=?",[folder,old_folder,account_id]);
    } catch (error) {
	this.finalize();
	return false;
    }
    rs = this.dbGears.execute("select folder from folder where folder like ? and uid_usuario=?",[old_folder+'/%',account_id]);
    while(rs.isValidRow()) {
	folder_tmp = rs.field(0);
	folder_new = folder_tmp.replace(old_folder,folder);
	this.dbGears.execute("update folder set folder=? where folder=?",[folder_new,folder_tmp]);
	rs.next();
    }


    this.finalize();
    return true;
}
	
local_messages.prototype.remove_folder = function(folder) {
    this.init_local_messages();
    var rs = this.dbGears.execute("select count(rowid) from folder where folder like ? and uid_usuario=?",[folder+"/%",account_id]);
    var sons = rs.field(0);
    rs.close();

    if(sons == 0){
		var rs = this.dbGears.execute("select rowid from folder where folder=? and uid_usuario=?",[folder,account_id]);
		var folder_id = rs.field(0);
		rs.close();
		this.dbGears.execute("delete from folder where rowid=?",[folder_id]);
		rs = this.dbGears.execute("select rowid,mail from mail where id_folder=?",[folder_id]);
		while(rs.isValidRow()) {
		    var rs2 = this.dbGears.execute("select url from anexo where id_mail=?",[rs.field(0)]);
		    while(rs2.isValidRow()) {
			this.store.remove(rs2.field(0));
			rs2.next();
		    }
		    rs2.close();
		    this.dbGears.execute("delete from anexo where id_mail=?",[rs.field(0)]);
		    var mail = connector.unserialize(rs.field(1));
		    this.store.remove(mail.url_export_file);
		    rs.next();
		}
		rs.close();
		this.dbGears.execute("delete from mail where id_folder=?",[folder_id]);
		this.finalize();
		return true;
    }else  {
		this.finalize();
		return false;
    }

}

local_messages.prototype.move_messages = function(new_folder,msgs_number) {
    this.init_local_messages();
    var rs = this.dbGears.execute("select rowid from folder where folder=? and uid_usuario=?",[new_folder,account_id]);
    var id_folder = rs.field(0);
    rs.close();
    this.dbGears.execute("update mail set id_folder="+id_folder+" where rowid in ("+msgs_number.toString()+")"); //usando statement nao tava funcionando quando tinha mais de um email...
    this.finalize();
}

local_messages.prototype.setFilter = function(sFilter)
{
	this.filterSerch = sFilter;
}

local_messages.prototype.getFilter = function()
{
	return this.filterSerch;
}

local_messages.prototype.search = function(folders,sFilter) {
    this.init_local_messages();
    this.setFilter(sFilter);
    var filters = sFilter.replace(/^##|##$/g,"").split('##');
    var friendly_filters = new Array();

    if (sFilter.indexOf('ALL') != -1) { //all filters...
	filters[0] = sFilter.replace(/##/g,"");
	tmp = filters[0].split("<=>");

	searchKey = new Array();
	searchKey.push("SUBJECT");
	searchKey.push(tmp[1]);
	friendly_filters.push(searchKey);

	searchKey = new Array();
	searchKey.push("BODY");
	searchKey.push(tmp[1]);
	friendly_filters.push(searchKey);

	searchKey = new Array();
	searchKey.push("FROM");
	searchKey.push(tmp[1]);
	friendly_filters.push(searchKey);

	searchKey = new Array();
	searchKey.push("TO");
	searchKey.push(tmp[1]);
	friendly_filters.push(searchKey);

	searchKey = new Array();
	searchKey.push("CC");
	searchKey.push(tmp[1]);
	friendly_filters.push(searchKey);
    }
    else {
	for (var i=0; i<filters.length; i++)
	{
	    if (filters[i] != ""){
		//tmp[0] = tmp[0].replace(/^\s+|\s+$/g,"");
		//tmp[1] = tmp[1].replace(/^\s+|\s+$/g,"");
		friendly_filters.push(filters[i].split("<=>"));
	    }
	}
    }
    var sql = "select mail.header,folder.folder,mail.rowid,size from mail inner join folder on mail.id_folder=folder.rowid where mail.uid_usuario="+account_id + " and folder.folder in (";
    for(var fnum in folders) {
	sql+="'"+folders[fnum]+"'";
	if(fnum<folders.length-1)
	    sql+=",";
    }
    sql += ") and (";
    for (var z=0;z<friendly_filters.length;z++) {
	if (z != 0) {
	    if (sFilter.indexOf('ALL') != -1)
		sql += " or";
	    else
		sql += " and";
	}
	var cond = friendly_filters[z][0].replace(/^\s+|\s+$/g,"");
	if (cond == "SINCE" || cond == "BEFORE" | cond == "ON"){

	    tmpDate = friendly_filters[z][1].replace(/\%2F/g,"/").split('/');

	    // Date = url_decode(friendly_filters[z][1]);
	    sql+=" mail.timestamp " + this.aux_convert_filter_field(friendly_filters[z][0], tmpDate);
	}
	else if (!friendly_filters[z][1])
	{
	    sql+=" mail."+this.aux_convert_filter_field(friendly_filters[z][0]);
	}
	else
	{
	    sql+=" mail."+this.aux_convert_filter_field(friendly_filters[z][0])+" like '%"+url_decode(friendly_filters[z][1])+"%'";
	}
    }
    sql += ")";
    var rs = this.dbGears.execute(sql);
    var retorno = [];
    var numRec = 0;
    
    while( rs.isValidRow() )
    {
		var header 			= connector.unserialize( rs.field(0) );
		var date_formated	= ( header["udate"] ).toString();
		/* 
		 * Antigamente o udate vinha com o formato de data e foi alterado para vir o timestamp. 
		 * Nesse caso, e-mails arquivados anteriormente usamos o udate, caso contrario, usamos o smalldate,
		 * pois o udate agora vem como timestamp
		 * verifica tambem no caso de mensagens antigas que nao exista o smalldate
		 * definir 
		*/
		if( !date_formated.match(/\d{2}\/\d{2}\/\d{4}/) ) 
		{
			if ( (typeof(header["smalldate"]) != "undefined") && (!header["smalldate"].match(/\d{2}\:\d{2}/) ) ){
				date_formated = header["smalldate"];
			}else{
				var day = new Date();
				day.setTime(header["udate"] * 1000);
				aux_dia = day.getDate() < 10 ? '0' + day.getDate() : day.getDate();
				aux_mes = day.getMonth()+1; // array start with '0..11'
				aux_mes = aux_mes < 10 ? '0' + aux_mes : aux_mes;
				date_formated = aux_dia + '/' +  aux_mes + '/' + day.getFullYear();
			}
		}

		retorno[ numRec++ ] = 
		{ 
			'from'		: header["from"]["name"],
			'subject'	: unescape(header["subject"]),
			'udate'		: date_formated,
			'size'		: rs.field(3),
			'flag'		: header["Unseen"]+header["Recent"]+header["Flagged"]+header["Draft"],
			'boxname'	: "local_"+rs.field(1),
			'uid'		: rs.field(2)
		}

		rs.next();
    }

    this.finalize();

    return ( retorno.length > 0 ) ? retorno : false ;
}
	
local_messages.prototype.aux_convert_size = function(size) {
    var tmp = Math.floor(size/1024);
    if(tmp >= 1){
	return tmp + " kb";
    }else{
	return size + " b";
    }
		
}
	
local_messages.prototype.aux_convert_filter_field = function(filter,date) 
{
	var dateObj;
    
	if (typeof date != 'undefined')
    {
    	dateObj = new Date(date[2],date[1]-1,date[0]);
    }

    if((filter=="SUBJECT ") || (filter=="SUBJECT"))
	return "subject";
    else if((filter=="BODY ") || (filter=="BODY"))
	return "body";
    else if((filter=="FROM ") || (filter=="FROM"))
	return "ffrom";
    else if((filter=="TO ") || (filter=="TO"))
	return "fto";
    else if((filter=="CC ") || (filter=="CC"))
	return "cc";
    else if (filter.replace(/^\s+|\s+$/g,"") == "SINCE"){
	dateObj.setHours(0, 0, 0, 0);
	return ">= " + dateObj.getTime().toString(10).substr(0, 10);
    }
    else if (filter.replace(/^\s+|\s+$/g,"") == "BEFORE"){
	dateObj.setHours(23, 59, 59, 999);
	return "<= " + dateObj.getTime().toString(10).substr(0, 10);
    }
    else if (filter.replace(/^\s+|\s+$/g,"") == "ON"){
	dateObj.setHours(0, 0, 0, 0);
	var ts1 = dateObj.getTime().toString(10).substr(0, 10);
	dateObj.setHours(23, 59, 59, 999);
	var ts2 = dateObj.getTime().toString(10).substr(0, 10);
	return ">= " + ts1 + ") and (timestamp <= " + ts2;
    }
    else if (filter.replace(/^\s+|\s+$/g,"") == "FLAGGED")
	return "flagged = 1";
    else if (filter.replace(/^\s+|\s+$/g,"") == "UNFLAGGED")
	return "flagged = 0";
    else if (filter.replace(/^\s+|\s+$/g,"") == "UNSEEN")
	return "unseen = 1";
    else if (filter.replace(/^\s+|\s+$/g,"") == "SEEN")
	return "unseen = 0";
    else if (filter.replace(/^\s+|\s+$/g,"") == "ANSWERED")
	return "answered = 1";
    else if (filter.replace(/^\s+|\s+$/g,"") == "UNANSWERED")
	return "answered = 0";
    else if (filter.replace(/^\s+|\s+$/g,"") == "RECENT")
	return "recent = 1";
    else if (filter.replace(/^\s+|\s+$/g,"") == "OLD")
	return "recent = 0";

}
	
local_messages.prototype.has_local_mails = function() {
    this.init_local_messages();
    var rs = this.dbGears.execute("select rowid from folder limit 0,1");
    var retorno;
    if(rs.isValidRow())
	retorno = true;
    else
	retorno = false;
    this.finalize();
    return retorno;
}

//Por Bruno Costa(bruno.vieira-costa@serpro.gov.br - Simple AJAX function used to get the RFC822 email source.
local_messages.prototype.get_src = function(url){
    AJAX = false;
    if (window.XMLHttpRequest) { // Mozilla, Safari,...
	AJAX = new XMLHttpRequest();
	if (AJAX.overrideMimeType) {
	    AJAX.overrideMimeType('text/xml');
	}
    } else if (window.ActiveXObject) { // IE
	try {
	    AJAX = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
	    try {
		AJAX = new ActiveXObject("Microsoft.XMLHTTP");
	    } catch (e) {}
	}
    }

    if (!AJAX) {
	alert('ERRO :(Seu navegador nao suporta a aplicacao usada neste site');
	return false;
    }

    AJAX.onreadystatechange = function() {
	if (AJAX.readyState == 4) {
	    AJAX.src=AJAX.responseText;
	    if (AJAX.status == 200) {
		return AJAX.responseText;
	    } else {
		return false;
	    }
	}
    }

    AJAX.open('get', url, false);
    AJAX.send(null);
    return AJAX.responseText;
};

local_messages.prototype.get_msg_date = function (original_id, is_local){

    this.init_local_messages();

    if (typeof(is_local) == 'undefined')
    {
	is_local = false;
    }

    var rs;

    if (is_local)
    {
	rs = this.dbGears.execute("select mail from mail where rowid="+original_id);
    }
    else
    {
	rs = this.dbGears.execute("select mail from mail where original_id="+original_id);
    }
    var tmp = connector.unserialize(rs.field(0));
    var ret = new Array();
    ret.fulldate = tmp.fulldate.substr(0,16);
    ret.smalldate = tmp.msg_day;
    ret.msg_day = tmp.msg_day;
    ret.msg_hour = tmp.msg_day;

    rs.close();
    this.finalize();
    return ret;
}

/*************************************************************************/

	
/******************************************************************
	 				Offline Part 
 ******************************************************************/

local_messages.prototype.is_offline_installed = function() {
    this.init_local_messages();
    var check = this.localServer.openManagedStore('expresso-offline');
    this.finalize();
    if(check==null)
	return false;
    else
	return true;
		
}
local_messages.prototype.update_offline = function(redirect) {
    this.init_local_messages();
    var managedStore = this.localServer.openManagedStore('expresso-offline');

    if(managedStore!=null){
			
	managedStore.oncomplete = function(details){
	    if(redirect!=null)
		location.href=redirect;
	}
			
	managedStore.checkForUpdate();
    } else if(redirect!=null) {
	location.href=redirect;
    }
    this.finalize();
}
	
local_messages.prototype.uninstall_offline = function() {
    if (!window.google || !google.gears) {
	temp = confirm(document.getElementById('lang_gears_redirect').value);
	if (temp) {
	    expresso_local_messages.installGears();
	}
	return;

    }
    this.init_local_messages();
    this.localServer.removeManagedStore('expresso-offline');
    alert(document.getElementById('lang_offline_uninstalled').value);
    //this.dbGears.execute('drop table user');
    //this.dbGears.execute('drop table queue');
    //this.dbGears.execute('drop table attachments_queue');
    this.finalize();
}
	
local_messages.prototype.get_folders_to_sync = function() {//Precisa ter visibilidade ao array de linguagens.
    this.init_local_messages();
    var rs = this.dbGears.execute("select id_folder,folder_name from folders_sync where uid_usuario="+account_id);
    var retorno = new Array();
    while(rs.isValidRow()) {
	temp = new Array();
	temp[0] = rs.field(0);
	if(temp[0]=='INBOX/Drafts' ||temp[0]=='INBOX/Trash' || temp[0]=='INBOX/Sent') {
	    temp[1] = array_lang[rs.field(1).toLowerCase()];
	}
	else {
	    temp[1] = rs.field(1);
	}
			
	retorno.push(temp);
	rs.next();
    }
    this.finalize();
    return retorno;
}
	
local_messages.prototype.install_offline = function(urlOffline,urlIcone,uid_usuario,login,pass,redirect) {
    if (!window.google || !google.gears) {
	temp = confirm(document.getElementById('lang_gears_redirect').value);
	if (temp) {
	    expresso_local_messages.installGears();
	}
	return;

    }
		
    if(pass.length>0) {
	only_spaces = true;
	for(cont=0;cont<pass.length;cont++) {
	    if(pass.charAt(cont)!=" ")
		only_spaces = false;
	}
	if(only_spaces) {
	    alert(document.getElementById('lang_only_spaces_not_allowed').value);
	    return false;
	}
    }

    modal('loading');
    var desktop = google.gears.factory.create('beta.desktop');
    desktop.createShortcut('ExpressoMail Offline',
	urlOffline,
	        {'32x32': urlIcone},
	'ExpressoMail Offline');


    this.init_local_messages();

    //user with offline needs to have at least the folder Inbox already created.
    tmp_rs = this.dbGears.execute("select rowid from folder where folder='Inbox' and uid_usuario=?",[uid_usuario]);
    if(!tmp_rs.isValidRow())
	this.dbGears.execute("insert into folder (folder,uid_usuario) values (?,?)",['Inbox',uid_usuario]);

    this.localServer.removeManagedStore('expresso-offline');
	
    var managedStore = this.localServer.createManagedStore('expresso-offline');
    managedStore.manifestUrl = 'js/manifest';

    managedStore.onerror = function (error) {
	alert(error);
    }
		
    managedStore.oncomplete = function(details) {
	if (close_lightbox_div) {
	    close_lightbox();
	    close_lightbox_div = false;
	    alert(document.getElementById('lang_offline_installed').value);
	    location.href=redirect;
	}
    }

    //create structure to user in db
    this.dbGears.execute('create table if not exists user (uid_usuario int,login text,pass text, logged int,unique(uid_usuario))');
    this.dbGears.execute('create table if not exists queue (ffrom text, fto text, cc text, cco text,'+
	'subject text, conf_receipt int, important int,body text,sent int,user int)');
    this.dbGears.execute('create table if not exists attachments_queue ('+
	'id_queue int, url_attach text)');
    this.dbGears.execute('create table if not exists sent_problems (' +
	'id_queue int,message text)');
		
    //d = new Date();
		
    try {
	var rs = this.dbGears.execute("select uid_usuario from user where uid_usuario=?",[uid_usuario]);
	if(!rs.isValidRow())
	    this.dbGears.execute("insert into user (uid_usuario,login,pass) values (?,?,?)",[uid_usuario,login,pass]);
	else
	    this.dbGears.execute("update user set pass=? where uid_usuario=?",[pass,uid_usuario]);
    } catch (error) {
	this.finalize();
	alert(error);
	return false;
    }
    managedStore.checkForUpdate();
    this.capt_url('controller.php?action=$this.db_functions.get_dropdown_contacts_to_cache');
	setTimeout(function(){
        managedStore.complete();
    }, 60000);
    this.finalize();
}
	
/**
	 * Return all users in an array following the structure below.
	 * 
	 * key: uid
	 * value: user login
	 */
local_messages.prototype.get_all_users = function() {
    this.init_local_messages();
    var users = new Array();
    var rs = this.dbGears.execute("select uid_usuario,login from user");
    while(rs.isValidRow()) {
	users[rs.field(0)] = rs.field(1);
	rs.next();
    }
    this.finalize();
    return users;
}
	
local_messages.prototype.set_as_logged = function(uid_usuario,pass,bypass) {
    this.init_local_messages();
    if (!bypass) {
	var rs = this.dbGears.execute("select pass from user where uid_usuario=?", [uid_usuario]);
	if (!rs.isValidRow() || (pass != rs.field(0) && pass != MD5(rs.field(0)))) {
	    this.finalize();
	    return false;
	}
    }
    d = new Date();

    this.dbGears.execute("update user set logged=null"); //Logoff in everybody
    this.dbGears.execute("update user set logged=? where uid_usuario=?",[d.getTime(),uid_usuario]); //Login just in one...
    this.finalize();
    return true;
}
	
local_messages.prototype.unset_as_logged = function() {
    this.init_local_messages();
    this.dbGears.execute("update user set logged=null"); //Logoff in everybody
    this.finalize();
}
	
local_messages.prototype.user_logged = function() {
    this.init_local_messages();
    var user_logged = new Array();
    var rs = this.dbGears.execute("select uid_usuario,logged from user where logged is not null");
    if(!rs.isValidRow()) {
	this.finalize();
	return null;
    }
    user_logged[0] = rs.field(0);
    user_logged[1] = rs.field(1);
    this.finalize();
    return user_logged;
}
	
local_messages.prototype.send_to_queue = function (form) {
    this.init_local_messages();
    var mail_values = new Array();
		
    for (var i=0;i<form.length;i++) {
	if (form.elements[i].name != '') { //I.E made me to do that...
	    if(form.elements[i].name=='folder' || form.elements[i].name=='msg_id' || form.elements[i].name=='' || form.elements[i].name==null)
		continue;
	    else if (form.elements[i].name == 'input_return_receipt' )
		mail_values['conf_receipt'] = form.elements[i].checked ? 1 : 0;
	    else if(form.elements[i].name == 'input_important_message')
		mail_values['important'] = form.elements[i].checked ? 1 : 0;
	    else
	    if (form.elements[i].name == 'body')
		mail_values['body'] = form.elements[i].value;
	    else
	    if (form.elements[i].name == 'input_from')
		mail_values['ffrom'] = form.elements[i].value;
	    else
	    if (form.elements[i].name == 'input_to')
		mail_values['fto'] = form.elements[i].value;
	    else
	    if (form.elements[i].name == 'input_cc')
		mail_values['cc'] = form.elements[i].value;
	    else
	    if (form.elements[i].name == 'input_cco')
		mail_values['cco'] = form.elements[i].value;
	    else
	    if (form.elements[i].name == 'input_subject')
		mail_values['subject'] = form.elements[i].value;
	}
    }
    //mail_values['fto'] = input_to;
    //mail_values['cc'] = input_cc;
    //mail_values['cco'] = input_cco;
    //mail_values['subject'] = input_subject;
    //mail_values['conf_receipt'] = input_return_receipt;
    //mail_values['important'] = input_important_message;
		
    try {
	this.dbGears.execute("insert into queue (ffrom,fto,cc,cco,subject,conf_receipt,important,body,sent,user) values (?,?,?,?,?,?,?,?,0,?)", [mail_values['ffrom'], mail_values['fto'], mail_values['cc'], mail_values['cco'], mail_values['subject'], mail_values['conf_receipt'], mail_values['important'], mail_values['body'], account_id]);
	this.send_attach_to_queue(this.dbGears.lastInsertRowId,form);
    }catch(error) {
	alert(error);
	return get_lang('Error sending a mail to queue. Verify if you have installed ExpressoMail Offline');
    }
    this.finalize();
    return true;
}
	
local_messages.prototype.send_attach_to_queue = function(id_queue,form) {
		
    for(i=0;i<form.elements.length;i++) {
			
	if(form.elements[i].name.indexOf("file_")!=-1) {
	    var tmp_input = form.elements[i];
	    var d = new Date();
	    var url_local = 'local_attachs/'+d.getTime();
	    this.store.captureFile(tmp_input, url_local);
	    this.dbGears.execute("insert into attachments_queue (id_queue,url_attach) values (?,?)",[id_queue,url_local]);
	}
	else if(form.elements[i].name.indexOf("offline_forward_")!=-1){
	    //alert();
	    this.dbGears.execute("insert into attachments_queue (id_queue,url_attach) values (?,?)",[id_queue,form.elements[i].value]);
	}
    }
}

	
local_messages.prototype.get_num_msgs_to_send = function() {
    this.init_local_messages();

    var rs = this.dbGears.execute("select count(*) from queue where user=? and sent=0",[account_id]);
    var to_return = rs.field(0);

    this.finalize();
    return to_return;
}
	
local_messages.prototype.set_problem_on_sent = function(rowid_message,msg) {
    this.init_local_messages();
    this.dbGears.execute("update queue set sent = 2 where rowid=?",[rowid_message]);
    this.dbGears.execute("insert into sent_problems (id_queue,message) values (?,?)"[rowid_message,msg]);
    this.finalize();
}
	
local_messages.prototype.set_as_sent = function(rowid_message) {
    this.init_local_messages();
    this.dbGears.execute("update queue set sent = 1 where rowid=?",[rowid_message]);
    this.finalize();
}
	
local_messages.prototype.get_form_msg_to_send = function() {
    this.init_local_messages();
    var rs = this.dbGears.execute('select ffrom,fto,cc,cco,subject,conf_receipt,important,body,rowid from queue where sent=0 and user = ? limit 0,1',[account_id]);
    if(!rs.isValidRow())
	return false;
		
    var form = document.createElement('form');
    form.method = 'POST';
    form.name = 'form_queue_'+rs.field(8);
    form.style.display = 'none';
		form.onsubmit = function(){return false;}
    if(!is_ie)
	form.enctype="multipart/form-data";
    else
	form.encoding="multipart/form-data";
		
    var ffrom = document.createElement('TEXTAREA');
    ffrom.name = "input_from";
    ffrom.value = rs.field(0);
    form.appendChild(ffrom);
		
    var fto = document.createElement('TEXTAREA');
    fto.name = "input_to";
    fto.value = rs.field(1);
    form.appendChild(fto);
		
    var cc = document.createElement('TEXTAREA');
    cc.name = "input_cc";
    cc.value = rs.field(2);
    form.appendChild(cc);

    var cco = document.createElement('TEXTAREA');
    cco.name = "input_cco";
    cco.value = rs.field(3);
    form.appendChild(cco);
		
    var subject = document.createElement('TEXTAREA');
    subject.name = "input_subject";
    subject.value = rs.field(4);
    form.appendChild(subject);
		
    var folder = document.createElement('input');
    folder.name='folder';
    folder.value=preferences.save_in_folder;
    form.appendChild(folder);
		
    if (rs.field(5) == 1) {
	var conf_receipt = document.createElement('input');
	conf_receipt.type='text';
	conf_receipt.name = "input_return_receipt";
	conf_receipt.value = 'on';
	form.appendChild(conf_receipt);
    }
		
    if (rs.field(6) == 1) {
	var important = document.createElement('input');
	important.type='text';
	important.name = "input_important_message";
	important.value = 'on';
	form.appendChild(important);
    }
		
    var body = document.createElement('TEXTAREA');
    body.name = "body";
    body.value = rs.field(7);
    form.appendChild(body);
		
    var rowid = document.createElement('input');
    rowid.type = 'hidden';
    rowid.name = 'rowid';
    rowid.value = rs.field(8);
    form.appendChild(rowid);
		
    //Mounting the attachs
    var divFiles = document.createElement("div");
    divFiles.id = 'divFiles_queue_'+rs.field(8);
		
    form.appendChild(divFiles);
		
    document.getElementById('forms_queue').appendChild(form);

    var is_local_forward = false;
    try {
			
	var rs_attach = this.dbGears.execute('select url_attach from attachments_queue where id_queue=?', [rs.field(8)]);
	while (rs_attach.isValidRow()) {
	    if(rs_attach.field(0).indexOf('../tmpLclAtt/')==-1) {
		tmp_field = addForwardedFile('queue_' + rs.field(8), this.store.getCapturedFileName(rs_attach.field(0)), 'nothing');
	    }
	    else {
		var tempNomeArquivo = rs_attach.field(0).split("/");
		var nomeArquivo = tempNomeArquivo[tempNomeArquivo.length-1];
		nomeArquivo = nomeArquivo.substring(0,nomeArquivo.length - 4); //Anexos no gears sao todos com extensao .php. tenho que tirar a extensao para ficar o nome real do arquivo.
		is_local_forward = true;
		tmp_field = addForwardedFile('queue_' + rs.field(8), nomeArquivo, 'nothing');
	    }
	    fileSubmitter = this.store.createFileSubmitter();
	    fileSubmitter.setFileInputElement(tmp_field,rs_attach.field(0));
	    //		alert(form.innerHTML);
	    //	div.appendChild(tmp_field);
	    rs_attach.next();
	}
			
	if(is_local_forward) {
	    var is_lcl_fw = document.createElement('input');
	    is_lcl_fw.type = 'hidden';
	    is_lcl_fw.name = 'is_local_forward';
	    is_lcl_fw.value = "1";
	    form.appendChild(is_lcl_fw);
	}
				
    }
    catch(exception) {
	alert(exception);
    }
		
    this.finalize();
    return form;
}

var expresso_local_messages;
expresso_local_messages = new local_messages();
//expresso_local_messages.create_objects();
