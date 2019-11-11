/**************************************************************************\
 Inicio 
\**************************************************************************/
connector.loadScript('TreeS');

var ttreeBox = new function () {
	this.name_folder = '';

	this.name_func = '';

	this.update_folder = function (expand_local_root) {

		var handler_update_folders = function (data) {
			folders = data;

			// update element dftree_folders_tree;
			ttree.make_tree(data, 'div_tree', 'folders_tree', 'ttree.get_folder(\'root\')', ttreeBox.name_folder,
				ttreeBox.name_folder, false);

			// update element dftree_tree_folders;
			if (Element('content_folders')) Element('content_folders').innerHTML = '';
			draw_tree_folders(folders);

			// update element dftree_wfolders_tree;
			if (Element('dftree_wfolders_tree')) {
				Element('dftree_wfolders_tree').innerHTML = '';
				ttree.make_tree(data, 'wfolders_content_tree', 'wfolders_tree', '', '', '', false);
			}

			ttree.FOLDER = ttreeBox.name_folder;

			if (tree_folders.getNodeById(ttree.FOLDER)) tree_folders.getNodeById(ttree.FOLDER)._select();

			if (expand_local_root) tree_folders.getNodeById('local_root').changeState();

		}

		Ajax('$this.imap_functions.get_folders_list', {}, handler_update_folders);
	}

	this.verify_children = function (param) {
		var aux;
		var aux1;
		var cont = parseInt(0);

		for (var i = 0; i < folders.length; i++) {
			aux = folders[i].folder_id.split(cyrus_delimiter);
			aux.pop();

			for (var j = 0; j < aux.length; j++) {
				if (j == 0) aux1 = aux[j];
				else aux1 += cyrus_delimiter + aux[j];
			}

			if (aux1 == param) cont++;
		}

		if (cont == 0) ttreeBox.del_past(param);
		else alert(get_lang('Delete your sub-folders first'));
		cont = parseInt(0);
	}

	this.verify = function (exp) {
		// All chars printable except . and / less equal then \u00FF are valid
		return /^[\u0020-\u002d\u0030-\u007e\u00a1-\u00ac\u00ae-\u00ff]+$/.test(exp);
	}

	// Funcao para verificar o nome da caixa;
	this.verify_names = function (name_folder) {
		var arr_nm_folder = new Array('INBOX', trashfolder, draftsfolder, sentfolder, spamfolder);

		for (var i = 0; i < arr_nm_folder.length; i++) {
			if (name_folder == arr_nm_folder[i]) {
				ttree.FOLDER = '';
				return true;
			}
		}
		return false;
	}

	// Valida os nomes das pastas
	this.validate = function (func) {
		var aux = ttree.FOLDER.split(cyrus_delimiter);
		var aux2;
		if (ttree.FOLDER != "") {

			if (aux.length > 1) aux2 = aux[1];
			else aux2 = aux[0];

			if (func == 'rename' && this.verify_names(aux2)) {

				alert(get_lang('It\'s not possible rename the folder: ') + lang_folder(aux2) + '.');
				return false;

			} else {

				if (func == 'newpast') {
					var button = prompt(get_lang('Enter the name of the new folder:'), '');

					if (button.indexOf('local_') != -1 || button.toUpperCase() == 'INBOX') {
						alert(get_lang('cannot create folder. try other folder name'));
						//Nao posso criar pastas contendo a string local_
						return false;
					}

					if (button.match(/[\/\\\!\@\#\$\%\&\*\(\)]/gi)) {
						alert(get_lang('cannot create folder. try other folder name'));
						return false;
					}

					if (trim(button) == '' || trim(button) == null) return false;
					else ttreeBox.new_past(button);

				}

				if (func == 'rename') {

					if (ttree.FOLDER == 'root') {
						alert(get_lang('It\'s not possible rename this folder!'));
						return false;
					}

					if (ttree.FOLDER == get_current_folder()) {
						alert(get_lang(
							'It\'s not possible rename this folder, because it is being used in the moment!'
						));
						return false;
					}

					var button1 = prompt(get_lang('Enter a name for the box'), '');
					if (button1.indexOf('local_') != -1 || button1.toUpperCase() == 'INBOX') {
						alert(get_lang('cannot create folder. try other folder name'));
						//Nao posso criar pastas contendo a string local_
						return false;
					}

					if (button1.match(/[\/\\\!\@\#\$\%\&\*\(\)]/gi)) {
						alert(get_lang('It\'s not possible rename this folder. try other folder name'));
						return false;
					}

					if (trim(button1) == '' || trim(button1) == null) return false;
					else ttreeBox.rename(button1);
				}
			}
		} else {
			alert(get_lang('Select a folder!'));
			return false;
		}
	}

	// Para criar a nova pasta;
	this.new_past = function (newp) {
		var aux = ttree.FOLDER.split(cyrus_delimiter);
		var delimExp = new RegExp('\\' + cyrus_delimiter, 'g');
		newp = newp.replace(delimExp, '_');
		var newp2 = '';

		if (!this.verify(newp)) {
			alert(get_lang('Type without spaces, dots or special characters!'));
			newp = '';
			return false;
		} else {
			newp2 = newp;
			newp = ((aux[0] == 'root') ? 'INBOX' : ttree.FOLDER) + cyrus_delimiter + newp;
		}

		if ($.trim(newp) !== '') {
			Ajax('$this.imap_functions.create_mailbox', { 'newp': newp }, function(data){
				if (eval(data.status) == false && data.error) {
					if (data.error) {
						if ((new RegExp("Permission denied")).test($.trim(data.error))) {
							alert(get_lang("You don't have permission for this operation!"));
						} else {
							alert(get_lang(data.error));
						}
					}
				} else {
					ttreeBox.name_folder = 'root'; //or use var newpast
					this.name_func = 'newpast';
					localCache.remove('get_folders_list');
					ttreeBox.update_folder();
				}
			});
		}
	}

	// Funcao para renomear a pasta;
	this.rename = function (rename) {
		var old_box = ttree.FOLDER;
		var aux = old_box.split(cyrus_delimiter);
		var rename_new = '';

		if (old_box == 'root') {
			alert(get_lang('Select a folder!'));
			return false;
		}

		if (aux.length == 1) {
			alert(get_lang('It\'s not possible rename the folder: ') + aux[0] + '.');
			rename = '';
			return false;
		} else {
			if (this.verify_names(aux[1])) {
				alert(get_lang('It\'s not possible rename the folder: ') + aux[1] + '.');
				rename = '';
				return false;
			} else {
				if (!this.verify(rename)) {
					alert(get_lang('Type without spaces, dots or special characters!'));
					rename = '';
					return false;
				} else {
					aux.pop();
					aux.push(rename);
					for (var i = 0; i < aux.length; i++) {
						if (i == 0) rename_new = aux[i];
						else rename_new += cyrus_delimiter + aux[i];
					}
				}
			}
		}

		if ($.trim(rename) !== '') {

			Ajax('$this.imap_functions.ren_mailbox', { 'rename': rename_new, 'current': old_box }, function(data){
				if (data.status == false) {
					alert(get_lang(data.error));
				} else {
					ttreeBox.name_folder = 'root';
					localCache.remove('get_folders_list');
					ttreeBox.update_folder();
				}
			});
		}
	}

	this.export_all_msg = function () {
		if (ttree.FOLDER == 'root') return false;

		write_msg(get_lang('You must wait while the messages will be exported...'));

		var folders = {};

		folders[ttree.FOLDER] = '*';

		Download('$this.exporteml.exportMessages', { 'folders': folders });

		return true;
	}

	// Funcao para deletar a pasta;
	this.del = function () {
		var folder_name = ttree.FOLDER;
		var aux = ttree.FOLDER.split(cyrus_delimiter);

		if (aux[0] == 'root' || ttree.FOLDER == '') {
			alert(get_lang('Select a folder!'));
			return false;
		}

		if (aux.length == 1) {
			alert(get_lang('It\'s not possible delete the folder: ') + get_lang('Inbox') + '.');
			return false;
		} else {
			if (this.verify_names(aux[1]) && typeof (aux[2]) == 'undefined') {
				alert(get_lang('It\'s not possible delete the folder: ') + get_lang(special_folders[aux[1]]) + '.');
				return false;
			} else {
				this.verify_children(folder_name);
			}
		}
	}

	this.del_past = function (param) {
		var aux = param.split(cyrus_delimiter);
		var aux1 = aux.pop();

		if (ttree.FOLDER == get_current_folder()) {
			alert(get_lang('It\'s not possible delete this folder, because it is being used in the moment!'));
			return false;
		}

		if (confirm(get_lang('Do you wish to exclude the folder ') + aux1 + '?')) {

			Ajax('$this.imap_functions.delete_mailbox', { 'del_past': param }, function(data){
				if (eval(data.status) == false && data.error) {
					if (data.error) {
						if ((new RegExp("Permission denied")).test($.trim(data.error))) {
							alert(get_lang("You don't have permission for this operation!"));
						} else {
							alert(get_lang(data.error));
						}
					}
				} else {
					ttreeBox.name_folder = 'root';
					localCache.remove('get_folders_list');
					ttreeBox.update_folder();
					write_msg(get_lang('The folder %1 was successfully removed', aux1));
				}
			});
		}
	}
}
