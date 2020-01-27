<?php
/**
 * rainloop-iredmail
 * A plugin for RainLoop to allow your iRedMail users to change their passwords.
 */

class iRedMailPlugin extends \RainLoop\Plugins\AbstractPlugin
{	
	/**
	 * iRedMail database stuff, modify if you need to.
	 */
	const IRM_HOST = '127.0.0.1';
	const IRM_PORT_MYSQL = 3306;
	const IRM_PORT_PGSQL = 5432;
	const IRM_VMAIL_NAME = 'vmail';
	const IRM_VMAIL_USER = 'vmailadmin';
	
	/**
	 * iRedMail install types
	 */
	const IRM_EDITION_MYSQL_LABEL = 'iRedMail MySQL/MariaDB';
	const IRM_EDITION_PGSQL_LABEL = 'iRedMail PostgreSQL';
	const IRM_EDITION_MYSQL_KEY = 'mysql';
	const IRM_EDITION_PGSQL_KEY = 'pgsql';
	
	/**
	 * Should be equal to iRedAdmin setting, default is 8.
	 */
	const MIN_PASS_LENGTH = 8;
	
	/**
	 * RainLoop hooks
	 * @return void
	 */
	public function Init()
	{
		$this->addHook('main.fabrica', 'MainFabrica');
	}

	/**
	 * Plugin is supported?
	 * @return string	Null if supported, otherwise error string.
	 */
	public function Supported()
	{
		if ( shell_exec('which doveadm') === '' ) {
			return '[iRedMail Plugin] Doveadm command must be available for password crypts.';
		}
		
		return null;
	}

	/**
	 * RainLoop's documentation sucks so I'm not really sure if I'm doing
	 * this correctly but it seems to get the job done.
	 * @param string $sName
	 * @param mixed $oProvider
	 */
	public function MainFabrica($sName, &$oProvider)
	{
		// This variable prefixing is dumb, but iRedMail does it by convention, so...
		if ( $sName === 'change-password' ) {
			// Grab driver
			require __DIR__ . '/iRedMailChangePasswordDriver.php';

			// This plugin works with either mysql or pgsql editions of iRedMail, you selected one in RainLoop admin.
			if ( $this->Config()->Get('plugin', 'edition', static::IRM_EDITION_MYSQL_LABEL) === static::IRM_EDITION_MYSQL_LABEL ) {
				$sDriver = static::IRM_EDITION_MYSQL_KEY;
			} else {
				$sDriver = static::IRM_EDITION_PGSQL_KEY;
			}
			
			// The vmail password you configured.
			$sVmailPassword = $this->Config()->Get('plugin', 'vmail_password', '');
			
			// The appropriate database port.
			$iPort = $sDriver === static::IRM_EDITION_MYSQL_KEY ? static::IRM_PORT_MYSQL : static::IRM_PORT_PGSQL;

			// Pass all this info to the iRedMail password driver.
			$oProvider = new iRedMailChangePasswordDriver;
			$oProvider
				->SetDriver($sDriver)
				->SetHost(static::IRM_HOST)
				->SetPort($iPort)
				->SetDatabase(static::IRM_VMAIL_NAME)
				->SetUser(static::IRM_VMAIL_USER)
				->SetPassword($sVmailPassword)
				->SetMinPasswordLength(static::MIN_PASS_LENGTH)
				->SetLogger($this->Manager()->Actions()->Logger());
		}
	}

	/**
	 * Build configs
	 * @return array
	 */
	public function configMapping()
	{	
		return [
			\RainLoop\Plugins\Property::NewInstance('edition')->SetLabel('Installation Type')->SetType(\RainLoop\Enumerations\PluginPropertyType::SELECTION)
				->SetDefaultValue(array(static::IRM_EDITION_MYSQL_LABEL, static::IRM_EDITION_PGSQL_LABEL))->SetDescription('OpenLDAP edition is not supported.'),
			\RainLoop\Plugins\Property::NewInstance('vmail_password')->SetLabel('DB "vmail" Password')->SetType(\RainLoop\Enumerations\PluginPropertyType::PASSWORD)
				->SetDefaultValue(''),
		];
	}
}
