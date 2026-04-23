<?php
class ModelExtensionPaymentCardlinkIris extends Model {

	public function install() {}

	public function uninstall() {}

	public function getSetting($code) {
		$query = $this->db->query("
			SELECT * 
			FROM " . DB_PREFIX . "setting 
			WHERE `code` = '" . $this->db->escape($code) . "'
		");

		$data = [];
		foreach ($query->rows as $result) {
			$data[$result['key']] = $result['value'];
		}

		return $data;
	}

	public function editSetting($code, $data) {
		$this->db->query("
			DELETE FROM " . DB_PREFIX . "setting 
			WHERE `code` = '" . $this->db->escape($code) . "'
		");

		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$value = json_encode($value);
			}

			$this->db->query("
				INSERT INTO " . DB_PREFIX . "setting 
				SET 
					`code` = '" . $this->db->escape($code) . "',
					`key` = '" . $this->db->escape($key) . "',
					`value` = '" . $this->db->escape($value) . "',
					`serialized` = '0'
			");
		}
	}
}
