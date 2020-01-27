<?php
/**
 * rainloop-iredmail
 * A plugin for RainLoop to allow your iRedMail users to change their passwords.
 */

class iRedMailChangePasswordDriver implements \RainLoop\Providers\ChangePassword\ChangePasswordInterface
{
	/**
	 * @var string
	 */
	private $sHost = '';

	/**
	 * @var int
	 */
	private $iPort = 0;

	/**
	 * @var string
	 */
	private $sDatabase = '';

	/**
	 * @var string
	 */
	private $sUser = '';

	/**
	 * @var string
	 */
	private $sPassword = '';
	
	/**
	 * @var string 'mysql' or 'pgsql'
	 */
	private $sDriver = '';

	/**
	 * @var \MailSo\Log\Logger
	 */
	private $oLogger = null;
	
	/**
	 * @var integer
	 */
	private $iMinPassLength = 0;

	/**
	 * @param string $sHost
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetHost($sHost)
	{
		$this->sHost = $sHost;
		return $this;
	}

	/**
	 * @param int $iPort
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetPort($iPort)
	{
		$this->iPort = (int) $iPort;
		return $this;
	}
	
	/**
	 * @param string $driver
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetDriver($sDriver)
	{
		if ( !in_array($sDriver, ['mysql', 'pgsql']) ) {
			if ( $this->oLogger ) {
				$this->oLogger->Write('Invalid database driver supplied. Must be one of: mysql, pgsql');
			}
		}
		
		$this->sDriver = $sDriver;
		return $this;
	}

	/**
	 * @param string $sDatabase
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetDatabase($sDatabase)
	{
		$this->sDatabase = $sDatabase;
		return $this;
	}

	/**
	 * @param string $sUser
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetUser($sUser)
	{
		$this->sUser = $sUser;
		return $this;
	}

	/**
	 * @param string $sPassword
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetPassword($sPassword)
	{
		$this->sPassword = $sPassword;
		return $this;
	}
	
	/**
	 * @param integer $iMinPassLength
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetMinPasswordLength($iMinPassLength)
	{
		$this->iMinPassLength = $iMinPassLength;
		return $this;
	}

	/**
	 * @param \MailSo\Log\Logger $oLogger
	 * @return \iRedMailChangePasswordDriver
	 */
	public function SetLogger(\MailSo\Log\Logger $oLogger)
	{
		$this->oLogger = $oLogger;
		return $this;
	}

	/**
	 * @param \RainLoop\Model\Account $oAccount
	 * @return bool
	 */
	public function PasswordChangePossibility($oAccount)
	{
		return $oAccount && $oAccount->Email();
	}

	/**
	 * @param \RainLoop\Model\Account $oAccount
	 * @param string $sPrevPassword
	 * @param string $sNewPassword
	 * @return bool
	 */
	public function ChangePassword(\RainLoop\Account $oAccount, $sPrevPassword, $sNewPassword)
	{
		if ( $this->oLogger ) {
			$this->oLogger->Write('Changing password for '.$oAccount->Email());
		}

		if ( strlen($sNewPassword) >= $this->iMinPassLength ) {
			try {
				switch ( $this->sDriver ) {
					case 'mysql':
						$sDsn = 'mysql:host='.$this->sHost.';port='.$this->iPort.';dbname='.$this->sDatabase;
						$oPdo = new \PDO($sDsn, $this->sUser, $this->sPassword);
						break;
					case 'pgsql':
						$sDsn = 'pgsql:host='.$this->sHost.';port='.$this->iPort.';dbname='.$this->sDatabase.';user='.$this->sUser.';password='.$this->sPassword;
						$oPdo = new \PDO($sDsn);
						break;
					default:
						if ( $this->oLogger ) {
							$this->oLogger->Write('Invalid PDO driver: ' . $this->sDriver);
						}
						break;
				}

				$oPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				
				$sHashedPassword = $this->crypt($sNewPassword);
				$oStmt = $oPdo->prepare('UPDATE mailbox SET password=?, passwordlastchange=NOW() WHERE username=?');
				$bResult = (bool) $oStmt->execute([$sHashedPassword, $oAccount->Email()]);

				$oPdo = null;
				return $bResult;
			} catch (\Exception $oException) {
				if ( $this->oLogger ) {
					$this->oLogger->WriteException($oException);
				}
			}
		}
	}
	
	/**
	 * Crypt password with SSHA512
	 * Requires doveadm
	 *
	 * @param Plain password
	 * @return Crypted password
	 */
	protected function crypt($sPassword)
	{
		$sEscapedPassword = escapeshellcmd($sPassword);
		return shell_exec("doveadm pw -s 'ssha512' -p '{$sEscapedPassword}'");
	}
}
