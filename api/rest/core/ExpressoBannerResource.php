<?php

class ExpressoBannerResource extends ExpressoAdapter {

	public function setDocumentation() {

		$this->setResource("Expresso","ExpressoBanner","Retorna as imagens da tela de login do Expresso.",array("POST","GET"));
		$this->setIsMobile(true);

	}

	public function get($request) {
		return $this->post($request);
	}

	public function post($request){
		// to Receive POST Params (use $this->params)
 		parent::post($request);

		$base_dir  		= '../../phpgwapi/templates/news/';
		$img_dir   		= 'src_images/';
		$url_expresso 	= $_SERVER['HTTP_HOST'] . '/phpgwapi/templates/news/src_images/' ;
		$conf_name 		= 'config_images.ini';
		$files     		= array();
		$finfo     		= finfo_open( FILEINFO_MIME_TYPE );

		if( $handle = opendir( $base_dir .$img_dir ) )
		{
			while( false !== ( $filename = readdir($handle) ) )
			{
				if( is_file( $base_dir . $img_dir . $filename ) && preg_match( '/^image/', finfo_file( $finfo, $base_dir .$img_dir . $filename ) ) )
				{
					$files[] = array(
										'name' 			=> basename( $filename ),
										'link_image' 	=> $url_expresso . $filename
									);
				}
			}

			finfo_close( $finfo );

			closedir($handle);
		}

		if ( count( $files ) > 0 )
		{
			shuffle( $files );

			$files = array_combine( array_map( function( $arr ) { return basename( $arr['name'] ); }, $files ), $files );

			if ( is_file( $base_dir . $conf_name ) )
			{
				foreach( parse_ini_file( $base_dir . $conf_name, true ) as $ini )
				{
					if( isset( $files[$ini['name']]) )
					{
						foreach ( array( 'text', 'title', 'link' ) as $field )
						{
							$files[$ini['name']][$field]  = ( $ini[$field] )? utf8_encode( $ini[$field] ) : '';
						}
					}
				}
			}
		}

		$result = ( count($files) > 0 ? $files : "null" );

 		$this->setResult($result);

		//to Send Response (JSON RPC format)
		return $this->getResponse();
	}
}
