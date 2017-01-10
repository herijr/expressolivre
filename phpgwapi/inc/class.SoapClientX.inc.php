<?php

class SoapClientX extends SoapClient
{
	private $_function_types = array();
	
	public function __construct( $wsdl, $options = array() )
	{
		$context = stream_context_create( array(
			'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
					)
				)
			);
	
		$params = array_merge( [ 'stream_context' => $context ] , $options );

		parent::__construct( $wsdl, $params );

		$types =  array();

		foreach( $this->__getTypes() as $type ) {
			preg_match( '/^([^ ]*) ([^ ]*) {([^}]*)}$/', $type, $matches );
			list( , , $name, $params ) = $matches;
			preg_match_all( '/ ([^;]*) ([^;]*);/', $params, $matches );
			$types[$name] = $matches[2];
		}

		foreach( $this->__getFunctions() as $prototype ) {
			preg_match( '/^(.*) (.*)\((.*) /', $prototype, $matches );
			$this->_function_types[$matches[2]] = $types[$matches[3]];
		}
	}
	
	public function __call( $method, $params )
	{
		return parent::__call(
			$method,
			array( 'parameters' => (object)array_combine( $this->_function_types[$method], $params ) )
		);
	}
}
