<?php
/**
 * SessionModel Class
 * 
 * 数据库驱动的 Session 模型，用于将 Session 保存在数据库中
 * 
 * @package		AtomCode
 * @subpackage	model
 * @category	model
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class SessionModel extends Model {
	private $exists = FALSE;
	/**
	 * @return SessionModel
	 */
	public static function &instance() {
		return parent::getInstance(__CLASS__);
	}
	
	/**
	 * 读出Session数据
	 * 
	 * @param string $sess_id
	 */
	public function read($sess_id) {
		$this->where('sessionid', $sess_id);
		
		if (get_config('sess_match_ip')) {
			$this->where('ip', get_ip(TRUE));
		}
		
		$result = $this->get();
		$return = null;
		
		if (count($result)) {
			$this->exists = TRUE;
			$return = $result[0];
		}
		
		return $return;
	}
	
	/**
	 * 写入 Session 编码后的值
	 * 
	 * @param string $sess_id
	 * @param string $sess_data
	 */
	public function write($sess_id, $sess_data) {
		$data['data'] = $sess_data;
		$data['lastactivity'] = TIMESTAMP;
		
		if ($this->exists) {
			$this->where('sessionid', $sess_id);
			return $this->update($data);
		} else {
			$data['sessionid'] = $sess_id;
			$data['ip'] = get_ip(TRUE);
			$data['starttime'] = TIMESTAMP;
			return $this->insert($data);
		}
	}
	
	/**
	 * 将当前的 Session 删除
	 * 
	 * @param string $sess_id
	 */
	public function destroy($sess_id) {
		$this->where('sessionid', $sess_id);
		return $this->delete();
	}
	
	/**
	 * 清除过期的 Session 
	 * 
	 * @param integer $time 超时时间
	 */
	public function gc($time) {
		if (get_config('sess_expiration')) {
			$time = get_config('sess_expiration');
		}
		$this->where('lastactivity <', TIMESTAMP - $time);
		return $this->delete();
	}
}