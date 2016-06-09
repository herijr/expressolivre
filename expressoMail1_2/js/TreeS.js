/**************************************************************************\
 Início 
\**************************************************************************/

	function treeS()
	{
		this.arrayW = new Array();
		this.el;
		this.FOLDER = "";
	}

	treeS.prototype.make_Window = function(){
		_this = this;
		connector.loadScript("TreeShow");
		var title = ':: '+get_lang("Manager your folders and export messages")+' ::';
		tree 				  = document.createElement("DIV");
		tree.style.visibility = "hidden";
		tree.style.position   = "absolute";
		tree.style.left 	  = "0px";
		tree.style.top 		  = "0px";
		tree.style.width 	  = "0px";
		tree.style.height 	  = "0px";
		tree.id				  = "window_tree";
		document.body.appendChild(tree);

		tree.innerHTML = "&nbsp;&nbsp;&nbsp;<b><font color='BLACK' nowrap>"+title+"</font></b>"+
		"&nbsp;&nbsp;<br><u></u>&nbsp;&nbsp;";

		var div_buttons = document.createElement("DIV");
		div_buttons.id = "div_buttons";
		div_buttons.style.position = "absolute";
		div_buttons.style.left = "440px";
		div_buttons.style.top = "20px"		
		div_buttons.style.width = "130px";
		div_buttons.style.height = "214px";
		div_buttons.innerHTML = "<table border='0' cellpading='0' cellspacing='0'>"+
							    "<tr>"+
							    "<td><input type='button' value='"+get_lang('New folder')+"' onclick='proxy_mensagens.proxy_create_folder()'></td>"+
							    "</tr>"+
							    "<tr>"+
							    "<td><input type='button' value='"+get_lang('Delete folder')+"' onclick='proxy_mensagens.proxy_remove_folder()'></td>"+
							    "</tr>"+
							    "<tr>"+
							    "<td><input type='button' value='"+get_lang('Rename folder')+"' onclick='proxy_mensagens.proxy_rename_folder()'></td>"+
							    "</tr>"+
							    "<tr>"+
                                                            //Por Bruno Costa(bruno.vieira-costa@serpro.gov.br - Chama o proxy_mensagens para que uma pasta local também possa ser exportada
                                                            "<td><input type='button' value='"+get_lang('Export messages')+"' onclick='proxy_mensagens.proxy_export_all_msg()'></td>"+
							    "</tr>"+
							    "<tr><td><br><br><br><br><br></td></tr>"+
							    "<tr><td><input type='button' value='"+get_lang('Close')+"' onclick='ttree.close_win()'></td></tr>"+
							    "</table>";
		tree.appendChild(div_buttons);		

		// Conf tree
		var jo = document.createElement("DIV");
		jo.id = "div_tree";
		jo.style.position = "absolute";
		jo.style.left = "10px";
		jo.style.top = "20px";
		jo.style.width = "420px";
		jo.style.height = "215px";
		jo.style.borderStyle = "outset";
		jo.style.borderColor = "black";
		jo.style.borderWidth = "2px";
		jo.style.background  = "#F7F7F7";
		jo.style.overflow    = "auto";
		jo.innerHTML = "";
		tree.appendChild(jo);

		if(!expresso_offline)
			ttree.make_tree(folders,"div_tree","folders_tree","ttree.get_folder(\"root\")","","root",false);
		else
			ttree.make_tree(folders,"div_tree","folders_tree","ttree.get_folder(\"local_root\")","","local_root",false);
		_this.showWindow(tree);

	}
	
	treeS.prototype.get_folder = function(param){
		this.FOLDER = param;
	}

	treeS.prototype.showWindow = function (div){
		if(! div) {
			return;
		}
		
		if(! this.arrayW[div.id]) {
			div.style.width  = "580px";
			div.style.height = "250px";
			div.style.zIndex = "10000";			
			var title = get_lang("Folder Management");
			var wHeight = div.offsetHeight + "px";
			var wWidth =  div.offsetWidth   + "px";
			div.style.width = div.offsetWidth - 5;

			win = new dJSWin({
				id: 'win_'+div.id,
				content_id: div.id,
				width: wWidth,
				height: wHeight,
				title_color: '#3978d6',
				bg_color: '#eee',
				title: title,
				title_text_color: 'white',
				button_x_img: '../phpgwapi/images/winclose.gif',
				border: true });
			
			this.arrayW[div.id] = win;
			win.draw();
		}
		else {
			win = this.arrayW[div.id];
		}
		win.open();
	}
	
	treeS.prototype.close_win = function(){
	
		this.FOLDER = "";
		this.arrayW['window_tree'].close();
	
	}

	treeS.prototype.make_tree = function (data,destination,name_tree, click_root, opentomb, selected,menu_folder){
		//Somente para teste
 		// alert("data: " + data.length + "\n" + "destination : " + destination + "\n" + "name_tree : " + name_tree + "\n" + "click_root : " + click_root + "\n" + "opentomb : " + opentomb + "\n" + "selected : " + selected + "\n" + "menu_folder : " + menu_folder); 
			
		if(Element('dftree_' + name_tree)){
			Element('dftree_' + name_tree).innerHTML = '';
		}
		
		folders_tree = new dFTree({name: name_tree});
		if (!expresso_offline) {		
			if(click_root != ""){
				var n_root = new dNode({id:'root', caption:get_lang("My Folders"), onClick:click_root});
			}else{
				var n_root = new dNode({id:'root', caption:get_lang("My Folders")});
			}
			folders_tree.add(n_root,'root'); //Places the root; second argument can be anything.
	
			if(data.length == 0){
				alert("sem dados");
				return false;
			}

			for (var i=0; i<data.length; i++){
				if(menu_folder){
					if (data[i].folder_unseen > 0)
						var nn = new dNode({id:data[i].folder_id, caption:lang_folder(data[i].folder_name) + '<font style=color:red>&nbsp(</font><span id="dftree_'+data[i].folder_id+'_unseen" style=color:red>'+data[i].folder_unseen+'</span><font style=color:red>)</font>', onClick:"change_folder('"+data[i].folder_id+"','"+data[i].folder_name+"')", plusSign:data[i].folder_hasChildren}); 
					else
						var nn = new dNode({id:data[i].folder_id, caption:lang_folder(data[i].folder_name), onClick:"change_folder('"+data[i].folder_id+"','"+data[i].folder_name+"')", plusSign:data[i].folder_hasChildren}); 
				}else{
					var nn = new dNode({id:data[i].folder_id, caption:lang_folder(data[i].folder_name), plusSign:data[i].folder_hasChildren});
				}
	
				if (data[i].folder_parent == '')
					data[i].folder_parent = 'root';
				else if (data[i].folder_parent == 'user'){
					if (tree_folders.getNodeById('user')){
						var n_root_shared_folders = new dNode({id:'user', caption:get_lang("Shared Folders"), plusSign:true}); 
						folders_tree.add(n_root_shared_folders,'root');
					}
				}
				
				// Foi preciso fazer esse tratamento porque a api (imap_getmailboxes) do PHP retorna uma informação incorreta em algumas ocasiões
				// a função imap_getmailboxes não retorna o atributo 32 (has_children) quando tem pastas seguindo o seguinte padrão:
				// prefixo
				//    subPasta1
				//    subPasta2
				// prefixo-pasta2  (tudo devido a este underscore aqui)
				// Caso seja criada pastas dessa forma, a pasta "prefixo", fica com a flag folder_hasChildren como false
				// sendo assim não aparece o sinal de "mais" na árvore de pastas para poder expandir
				// Olhar a chamada para a função imap_getmailboxes() dentro do método expressoMail1_2.imap_functions.get_folders_list()
				// Ticket #1548
				if(data[i].folder_parent != 'root') {
					var node_parent = folders_tree.getNodeById(data[i].folder_parent);
					node_parent.plusSign = true;
					folders_tree.alter(node_parent);
				}
				
				folders_tree.add(nn,data[i].folder_parent);
			}
		}
		//Pastas locais
		if (preferences.use_local_messages == 1) {
			var n_root_local = new dNode({
				id: 'local_root',
				caption: get_lang("local messages"),
				plusSign: true,
                                onClick:"ttree.get_folder('local_root')"
			});
			folders_tree.add(n_root_local, 'root');
			
			var local_folders = expresso_local_messages.list_local_folders();
			for (var i in local_folders) { //Coloca as pastas locais.
			
				var node_parent = "local_root";
				var new_caption = local_folders[i][0];
				if(local_folders[i][0].indexOf("/")!="-1") {
					final_pos = local_folders[i][0].lastIndexOf("/");
					node_parent = "local_"+local_folders[i][0].substr(0,final_pos);
					new_caption = local_folders[i][0].substr(final_pos+1);
				}
				
				if (local_folders[i][1] > 0) 
					var nodeLocal = new dNode({
						id: "local_" + local_folders[i][0],
						caption: lang_folder(new_caption) + '<font style=color:red>&nbsp(</font><span id="local_unseen" style=color:red>' + local_folders[i][1] + '</span><font style=color:red>)</font>',
						plusSign: local_folders[i][2]
					});
				else 
					var nodeLocal = new dNode({
						id: "local_" + local_folders[i][0],
						caption: lang_folder(new_caption),
						plusSign: local_folders[i][2]
					});
				folders_tree.add(nodeLocal, node_parent);
			}
		}
		folders_tree.draw(Element(destination));
		if(!expresso_offline)
			n_root.changeState();
		else
			n_root_local.changeState();
		if(opentomb != ""){folders_tree.openTo(opentomb);}
		if(selected != "" && folders_tree.getNodeById(selected)){folders_tree.getNodeById(selected)._select();}

	}

/* Build the Object */
	var ttree;
	ttree = new treeS();
