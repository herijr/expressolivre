<?php
namespace App\Services\Base\Modules\mail;

use App\Services\Base\Adapters\MailAdapter;
use App\Services\Base\Commons\Errors;

class RenameFolderResource extends MailAdapter
{

	public function setDocumentation()
	{
		$this->setResource("Mail", "Mail/RenameFolder", "Renomeia uma pasta, recebe como parametros o \"folderID\" da pasta a ser renomeada, e o nome da nova pasta \"folderName\".", array("POST"));
		$this->setIsMobile(true);
		$this->addResourceParam("auth", "string", true, "Chave de autenticação do usuário.", false);
		$this->addResourceParam("folderID", "string", true, "ID da pasta que será renomeada.", false, "");
		$this->addResourceParam("folderName", "string", true, "Nome da nova Pasta.");
	}

	public function post($request)
	{

		$this->setParams($request);

		$current = mb_convert_encoding($this->getParam('folderID'), "UTF-8", "ISO-8859-1");
		$current = mb_convert_encoding($current, "UTF-8", "ISO-8859-1");

		$newName = mb_convert_encoding($this->getParam('folderName'), "UTF-8", "ISO-8859-1");
		$newName = mb_convert_encoding($newName, "UTF-8", "ISO-8859-1");

		if (!$this->getImap()->folder_exists(mb_convert_encoding($current, "UTF7-IMAP", "UTF-8"))) {
			return Errors::runException("MAIL_INVALID_OLD_FOLDER");
		}

		$folders = array_keys($this->defaultFolders);
		if (in_array($current, $folders)) {
			return Errors::runException("MAIL_INVALID_OLD_FOLDER");
		}

		if (empty($newName) || preg_match('/[\/\\\!\@\#\$\%\&\*\(\)]/', $newName)) {
			return Errors::runException("MAIL_INVALID_NEW_FOLDER_NAME");
		}

		$params['current'] = $current;
		$params['rename'] = $newName;

		$result = $this->getImap()->ren_mailbox($params);

		$newFolderID = "";

		if ($result) {

			$allFolders = $this->getImap()->get_folders_list();

			if (count($allFolders) > 0) {

				$newName = mb_convert_encoding($newName, "ISO-8859-1", "UTF-8");

				$folder = current(array_filter($allFolders, function ($a) use ($newName) {

					$pattern = '/^(INBOX|user)?(\/|\.)' . $newName . '$/';

					return preg_match($pattern, $a['folder_id']);
				}));

				$newFolderID = (isset($folder['folder_id']) ? $folder['folder_id'] : '');
			}

		} else {
			return Errors::runException("MAIL_FOLDER_NOT_RENAMED");
		}

		$this->setResult(array('folderID' => $newFolderID));

		return $this->getResponse();
	}
}