<?php

class Bf_Profile extends Bf_MutableEntity {
	public function create() {
		trigger_error('Create support is denied for this entity; '
		 .'at the time of writing, no API endpoint exists to support it.',
		 E_USER_ERROR);
	}
	
	protected static $_resourcePath;

	public static function initStatics() {
		self::$_resourcePath = new Bf_ResourcePath('profiles', 'profile');
	}


	protected function doUnserialize(array $json) {
		// consult parent for further unserialization
		parent::doUnserialize($json);

		$this->unserializeArrayEntities('addresses', Bf_Address::getClassName(), $json);
	}


	/**
	 * Gets Bf_Addresses for this Bf_Profile.
	 * @return Bf_Profile
	 */
	public function getAddresses() {
		return $this->addresses;
	}

}
Bf_Profile::initStatics();