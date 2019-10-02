<?php

namespace App\Modules\Core;

use App\Adapters\ExpressoAdapter;

class ExpressoBannerResource extends ExpressoAdapter
{
	public function req()
	{
		$base_dir  		= '../../phpgwapi/templates/news/';
		$img_dir   		= 'src_images/';

		$uri = explode('/', $_SERVER['REQUEST_URI']); // URI Format: /seed/ExpressoBanner
		$client = $uri[1];

		$urlClient = $this->getProfileServer($client);

		$url_expresso 	= $urlClient . '/phpgwapi/templates/news/src_images/';

		$conf_name 		= 'config_images.ini';
		$files     		= array();
		$finfo     		= finfo_open(FILEINFO_MIME_TYPE);

		if ($handle = opendir($base_dir . $img_dir)) {
			while (false !== ($filename = readdir($handle))) {
				if (is_file($base_dir . $img_dir . $filename) && preg_match('/^image/', finfo_file($finfo, $base_dir . $img_dir . $filename))) {
					$files[] = array(
						'name' 			=> basename($filename),
						'link_image' 	=> $url_expresso . $filename
					);
				}
			}

			finfo_close($finfo);

			closedir($handle);
		}

		if (count($files) > 0) {
			shuffle($files);

			$files = array_combine(array_map(function ($arr) {
				return basename($arr['name']);
			}, $files), $files);

			if (is_file($base_dir . $conf_name)) {
				foreach (parse_ini_file($base_dir . $conf_name, true) as $ini) {
					if (isset($files[$ini['name']])) {
						foreach (array('text', 'title', 'link') as $field) {
							$files[$ini['name']][$field]  = ($ini[$field]) ? utf8_encode($ini[$field]) : '';
						}
					}
				}
			}
		}

		$result = (count($files) > 0 ? $files : "null");

		return $result;
	}


	private function getProfileServer($client)
	{
		$serverClient = false;

		if (file_exists(dirname(__FILE__) . '/../../Config/profileHomeServer.ini')) {

			$profileServer	= parse_ini_file(dirname(__FILE__) . '/../../Config/profileHomeServer.ini', true);

			if (isset($profileServer['client.server'])) {
				$serverClient = isset($profileServer['client.server'][strtoupper($client)]) ?
					$profileServer['client.server'][strtoupper($client)] : $profileServer['client.server']['DEFAULT'];
			}
		}

		return $serverClient;
	}
}
