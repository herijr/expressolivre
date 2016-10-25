<?php
class ACL_Managers
{
	protected static $_instance = null;

	const ACL_VW_USERS                     = 'view_users';
	const ACL_ADD_USERS                    = 'add_users';
	const ACL_MOD_USERS                    = 'edit_users';
	const ACL_MOD_USERS_QUOTA              = 'change_users_quote';
	const ACL_MOD_USERS_PICTURE            = 'edit_users_picture';
	const ACL_MOD_USERS_PHONE_NUMBER       = 'edit_users_phonenumber';
	const ACL_MOD_USERS_CORPORATIVE        = 'manipulate_corporative_information';
	const ACL_MOD_USERS_PASSWORD           = 'change_users_password';
	const ACL_MOD_USERS_SAMBA_ATTRIBUTES   = 'edit_sambausers_attributes';
	const ACL_MOD_USERS_RADIUS             = 'edit_radius';
	const ACL_SET_USERS_DEFAULT_PASSWORD   = 'set_user_default_password';
	const ACL_SET_USERS_EMPTY_INBOX        = 'empty_user_inbox';
	const ACL_REN_USERS                    = 'rename_users';
	const ACL_DEL_USERS                    = 'delete_users';

	const ACL_ADD_INSTITUTIONAL_ACCOUNTS   = 'add_institutional_accounts';
	const ACL_MOD_INSTITUTIONAL_ACCOUNTS   = 'edit_institutional_accounts';
	const ACL_DEL_INSTITUTIONAL_ACCOUNTS   = 'remove_institutional_accounts';

	const ACL_ADD_GROUPS                   = 'add_groups';
	const ACL_MOD_GROUPS                   = 'edit_groups';
	const ACL_MOD_GROUPS_EMAIL             = 'edit_email_groups';
	const ACL_DEL_GROUPS                   = 'delete_groups';

	const ACL_ADD_EMAIL_LISTS              = 'add_maillists';
	const ACL_MOD_EMAIL_LISTS              = 'edit_maillists';
	const ACL_MOD_EMAIL_LISTS_SCL          = 'edit_scl_email_lists';
	const ACL_MOD_EMAIL_LISTS_ADD_EXTERNAL = 'add_externalEmail';
	const ACL_DEL_EMAIL_LISTS              = 'delete_maillists';

	const ACL_ADD_COMPUTERS                = 'create_computers';
	const ACL_MOD_COMPUTERS                = 'edit_computers';
	const ACL_DEL_COMPUTERS                = 'delete_computers';

	const ACL_MOD_SAMBA_DOMAINS            = 'edit_sambadomains';

	const ACL_ADD_SECTORS                  = 'create_sectors';
	const ACL_MOD_SECTORS                  = 'edit_sectors';
	const ACL_DEL_SECTORS                  = 'delete_sectors';

	const ACL_VW_GLOBAL_SESSIONS           = 'view_global_sessions';
	const ACL_VW_LOGS                      = 'view_logs';

	const GRP_VIEW_USERS                   = 'list_users';
	const GRP_VIEW_INSTITUTIONAL_ACCOUNTS  = 'list_institutional_accounts';
	const GRP_VIEW_GROUPS                  = 'list_groups';
	const GRP_VIEW_EMAIL_LISTS             = 'list_maillists';
	const GRP_VIEW_COMPUTERS               = 'list_computers';
	const GRP_VIEW_SECTORS                 = 'list_sectors';

	const GRP_DISPLAY_GROUPS               = 'display_groups';
	const GRP_DISPLAY_APPLICATIONS         = 'display_applications';
	const GRP_DISPLAY_EMAIL_LISTS          = 'display_emaillists';
	const GRP_DISPLAY_EMAIL_CONFIG         = 'display_emailconfig';

	const NOT_USED                         = NULL;

	private static $_bits_ord = array(
		self::ACL_ADD_USERS,
		self::ACL_MOD_USERS,
		self::ACL_DEL_USERS,
		self::NOT_USED,
		self::ACL_ADD_GROUPS,
		self::ACL_MOD_GROUPS,
		self::ACL_DEL_GROUPS,
		self::ACL_MOD_USERS_PASSWORD,
		self::ACL_ADD_EMAIL_LISTS,
		self::ACL_MOD_EMAIL_LISTS,
		self::ACL_DEL_EMAIL_LISTS,
		self::NOT_USED,
		self::ACL_ADD_SECTORS,
		self::ACL_MOD_SECTORS,
		self::ACL_DEL_SECTORS,
		self::ACL_MOD_USERS_SAMBA_ATTRIBUTES,
		self::ACL_VW_GLOBAL_SESSIONS,
		self::ACL_VW_LOGS,
		self::ACL_MOD_USERS_QUOTA,
		self::ACL_SET_USERS_DEFAULT_PASSWORD,
		self::ACL_ADD_COMPUTERS,
		self::ACL_MOD_COMPUTERS,
		self::ACL_DEL_COMPUTERS,
		self::ACL_REN_USERS,
		self::ACL_MOD_SAMBA_DOMAINS,
		self::ACL_VW_USERS,
		self::ACL_MOD_GROUPS_EMAIL,
		self::ACL_SET_USERS_EMPTY_INBOX,
		self::ACL_MOD_USERS_CORPORATIVE,
		self::ACL_MOD_USERS_PICTURE,
		self::ACL_MOD_EMAIL_LISTS_SCL,
		self::ACL_MOD_USERS_PHONE_NUMBER,
		self::ACL_ADD_INSTITUTIONAL_ACCOUNTS,
		self::ACL_MOD_INSTITUTIONAL_ACCOUNTS,
		self::ACL_DEL_INSTITUTIONAL_ACCOUNTS,
		self::ACL_MOD_EMAIL_LISTS_ADD_EXTERNAL,
		self::ACL_MOD_USERS_RADIUS,
	);

	private static $_bits_grp = array(
		self::GRP_VIEW_USERS => array(
			self::ACL_ADD_USERS,
			self::ACL_MOD_USERS,
			self::ACL_DEL_USERS,
			self::ACL_MOD_USERS_PASSWORD,
			self::ACL_MOD_USERS_QUOTA,
			self::ACL_MOD_USERS_SAMBA_ATTRIBUTES,
			self::ACL_VW_USERS,
			self::ACL_MOD_USERS_CORPORATIVE,
			self::ACL_MOD_USERS_PHONE_NUMBER,
		),
		self::GRP_VIEW_INSTITUTIONAL_ACCOUNTS => array(
			self::ACL_ADD_INSTITUTIONAL_ACCOUNTS,
			self::ACL_MOD_INSTITUTIONAL_ACCOUNTS,
			self::ACL_DEL_INSTITUTIONAL_ACCOUNTS,
		),
		self::GRP_VIEW_GROUPS => array(
			self::ACL_ADD_GROUPS,
			self::ACL_MOD_GROUPS,
			self::ACL_DEL_GROUPS,
		),
		self::GRP_VIEW_EMAIL_LISTS => array(
			self::ACL_ADD_EMAIL_LISTS,
			self::ACL_MOD_EMAIL_LISTS,
			self::ACL_DEL_EMAIL_LISTS,
		),
		self::GRP_VIEW_COMPUTERS => array(
			self::ACL_ADD_COMPUTERS,
			self::ACL_MOD_COMPUTERS,
			self::ACL_DEL_COMPUTERS,
		),
		self::GRP_VIEW_SECTORS => array(
			self::ACL_ADD_SECTORS,
			self::ACL_MOD_SECTORS,
			self::ACL_DEL_SECTORS,
		),
		self::GRP_DISPLAY_GROUPS => array(
			self::ACL_MOD_USERS,
			self::ACL_VW_USERS,
			self::ACL_MOD_USERS_SAMBA_ATTRIBUTES,
		),
		self::GRP_DISPLAY_APPLICATIONS => array(
			self::ACL_MOD_USERS,
			self::ACL_VW_USERS,
		),
		self::GRP_DISPLAY_EMAIL_LISTS => array(
			self::ACL_MOD_USERS,
			self::ACL_VW_USERS,
		),
		self::GRP_DISPLAY_EMAIL_CONFIG => array(
			self::ACL_MOD_USERS,
			self::ACL_VW_USERS,
		),
	);

	private static $_bits = NULL;

	protected function __construct() {}

	public static function getInstance()
	{
		if ( null === self::$_instance ) self::$_instance = new self();
		return self::$_instance;
	}

	private static function _getBits()
	{
		self::getInstance();
		if ( is_null( self::$_bits ) ) {
			foreach ( self::$_bits_ord as $value => $key )
				if ( !is_null( $key ) )
					self::$_bits[$key] = 0x1<<$value;
			foreach ( self::$_bits_grp as $key => $arr ) {
				foreach ( $arr as $value ) {
					if ( !isset( self::$_bits[$key] ) ) self::$_bits[$key] = 0;
					self::$_bits[$key] |= self::$_bits[$value];
				}
			}
		}
		return self::$_bits;
	}

	public static function getValue( $key )
	{
		self::_getBits();
		if ( !is_array( $key ) ) return isset( self::$_bits[$key] )? self::$_bits[$key] : false;
		$sum = 0;
		foreach ( $key as $k ) $sum += self::getValue( $k );
		return $sum;
	}

	public static function getPerms( $val )
	{
		$val = (int)$val;
		$key = 0;
		$perms = array();
		while ( $val > 0 ) {
			if ( $val&1 ) $perms[] = self::$_bits_ord[$key];
			$val >>= 1;
			$key++;
		}
		return $perms;
	}

	public static function isAllow( $acl, $perm )
	{
		self::_getBits();
		$params = func_get_args();
		array_shift( $params );
		foreach ( $params as $param ) {
			$param = ( (array)$param );
			$result = ( (bool)count( $param ) );
			foreach ( $param as $perm ) {
				$result = is_string( $perm ) && isset( self::$_bits[$perm] ) && ( $acl & self::$_bits[$perm] );
				if ( !$result ) break;
			}
			if ( $result ) return true;
		}
		return false;
	}

}
