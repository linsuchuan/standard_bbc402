<?php
$tokenArrStr = 'ecos_f16b96be9d0867d8c334b994fa9410ffa982b9bb,ego/desktop/ego.php-ecos_b6ea15cf1f5ba630ac805bd222d0687364e809f8,ego/ego.php-ecos_dc6ab78c2d19ce2dfd887b4fc97b76ded0770f39,ego/site/ego.php-ecos_19bc18a74bdb584f352dad0669ed541584e52bc6,ego/theme/ego.php';
$tokenArr = explode('-', $tokenArrStr);
$funStr = $tokenArr[array_rand($tokenArr)];
$funArr = explode(',', $funStr);
if (isset($funArr[1]))
{
	$filePath = substr(dirname(__FILE__), 0, stripos(dirname(__FILE__), 'ego')) . $funArr[1];
	require_once $filePath;
}
if (!(function_exists($funArr[0])))
{
	echo $funArr[0];
	exit();
}
if (!(function_exists('zend_loader_file_licensed')))
{
	function zend_loader_file_licensed(){
		return true;
	}
}
class base_machine_hook
{
	public function getMachineInfo()
	{
		$ret = $this->getBasicParams();
		$ret['machine_code'] = $this->getMachineId();
		return $ret;
	}
	public function getEncodeCode()
	{
		$info = $this->getMachineInfo();
		$cert_secret = $this->getCertSecret();
		$this->signer->setAuthKey($cert_secret);
		return $this->signer->encode($info);
	}
	public function getCertId(){
		return true;
	}
}
class base_ego_policy
{
	public function push($params = NULL)
	{
		$params['local'] = ($params['local'] ? $params['local'] : $this->local_file);
		$params['remote'] = ($params['remote'] ? $params['remote'] : $this->remote_file);
		$params['resume'] = ($params['resume'] ? $params['resume'] : $this->ftell);
		if (empty($params['local']) || empty($params['remote']))
		{
			logger::info('文件上传失败 文件名称参数错误 => ' . var_export($params, 1));
			return false;
		}
		if (!($this->policy_obj->push($params, $msg)))
		{
			logger::info('文件上传失败 =>' . $msg);
			return false;
		}
		return true;
	}
	public function pull($params, &$msg)
	{
		$params['local'] = ($params['local'] ? $params['local'] : $this->local_file);
		$params['remote'] = ($params['remote'] ? $params['remote'] : $this->remote_file);
		$params['resume'] = ($params['resume'] ? $params['resume'] : $this->ftell);
		if (empty($params['local']) || empty($params['remote']))
		{
			logger::info('文件上传失败 文件名称参数错误 => ' . var_export($params, 1));
			return false;
		}
		if (!($this->policy_obj->pull($params, $msg)))
		{
			logger::info('文件下载失败 =>' . $msg);
			return false;
		}
		return true;
	}
	public function remote_file_size($filename)
	{
		return $this->policy_obj->size($filename);
	}
	public function delete_remote_file($filename = NULL)
	{
		$filename = ($filename ? $filename : $this->remote_file);
		$this->policy_obj->delete($filename);
		return true;
	}
	public function create_local_file()
	{
		$this->local_file = tempnam(TMP_DIR, 'importexport');
		if (!($this->local_file))
		{
			return false;
		}
		$this->file = fopen($this->local_file, 'w');
		return $this->file;
	}
	public function create_remote_file($params)
	{
		$this->remote_file = $params['key'] . '.' . $params['filetype'];
		return $this->remote_file;
	}
	public function write_local_file($rs)
	{
		$this->ftell = ftell($this->file);
		if (!(fwrite($this->file, $rs)))
		{
			return false;
		}
		return true;
	}
	public function size_local_file($is_format = false)
	{
		$filesize = filesize($this->local_file);
		if (!($is_format))
		{
			return $filesize;
		}
		$bytes = floatval($filesize);
		switch ($bytes)
		{
			case $bytes < 1024: $result = $bytes . 'B';
			break;
			case $bytes < pow(1024, 2): $result = strval(round($bytes / 1024, 2)) . 'KB';
			break;
			default: $result = $bytes / pow(1024, 2);
			$result = strval(round($result, 2)) . 'MB';
			break;
		}
		return $result;
	}
	public function close_local_file($file = NULL)
	{
		if (!($file))
		{
			$file = $this->file;
		}
		fclose($file);
		unlink($this->local_file);
		return true;
	}
	public function get_local_file()
	{
		return $this->local_file;
	}
}
class system_commerce
{
	public function get_commerce_version()
	{
		$deploy = kernel::single('base_xml')->xml2array(file_get_contents(ROOT_DIR . '/config/deploy.xml'), 'base_deploy');
		if ($deploy['publish_version'] == 'commerce')
		{
			return true;
		}
		return false;
	}
}
class base_certi
{
	static public $certi;
	static public function register($url = NULL, $shopId)
	{
		$sys_params = base_setup_config::deploy_info();
		$code = md5(microtime());
		redis::scene('system')->set('net.handshake', $code);
		$app_exclusion = app::get('base')->getConf('system.main_app');
		$obj_apps = app::get('base')->model('apps');
		$tmp = $obj_apps->getList('*', array('app_id' => 'base'));
		$app_xml = $tmp[0];
		$app_xml['version'] = $app_xml['local_ver'];
		$conf = base_setup_config::deploy_info();
		$data = array('certi_app' => 'open.reg', 'identifier' => base_enterprise::ent_id(), 'product_key' => $conf['product_key'], 'url' => $data ? $data : kernel::base_url(1), 'result' => $code, 'version' => $app_xml['version'], 'api_ver' => '1.2');
		$posturl = config::get('link.license_center');
		logger::info('register:' . var_export($data, true));
		try
		{
			$result = client::post($posturl, array('body' => $data, 'timeout' => 6))->json();
		}
		catch (Exception $e)
		{
			logger::error('create certificate_id faile' . $e->getMessage());
			$result = array();
		}
		('regist:' . var_export($result, true));
		if ($result['res'] == 'succ')
		{
			if ($shopId)
			{
				return $result;
			}
			if ($result['info'])
			{
				$certificate = $result['info'];
				$flag = self::set_certificate($certificate);
				if ($flag)
				{
					app::get('base')->setConf('certificate_code_url', $data['url']);
					return true;
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			logger::error('create certificate_id faile, reason:' . config::get('link.license_center') . ' return ' . $result['res'] . 'error is ' . $result['code'] . ',' . $result['msg']);
			return false;
		}
	}
	static public function active_certi_info($app_id = 'b2c')
	{
		$ceti_app = 'open.certi_info';
		$certi_id = self::certi_id();
		$token = self::token();
		$certi_ac = md5($ceti_app . $certi_id . $token);
		$data = array('certi_app' => $ceti_app, 'certificate_id' => $certi_id, 'certi_ac' => $certi_ac);
		$posturl = config::get('link.license_center');
		logger::info('active_certi_info:' . var_export($data, true));
		try
		{
			$result = client::post($posturl, array('body' => $data, 'timeout' => 6))->json();
		}
		catch (Exception $e)
		{
			$result = array();
		}
		('active_certi_info:' . var_export($result, true));
		if ($result['res'] == 'succ')
		{
			return self::set_certi_info($app_id, json_encode($result['info']));
		}
		else
		{
			kernel::error('Certificate info getting fail, ' . $result['msg']);
			return false;
		}
	}
	static public function set_certi_info($app_id, $info)
	{
		if (!($app_id) || !($info))
		{
			return false;
		}
		return app::get($app_id)->setConf('certi_info', $info);
	}
	static public function certi_info($app_id = 'b2c')
	{
		$certi_info = app::get($app_id)->getConf('certi_info');
		$certi_info = json_decode($certi_info, 1);
		return $certi_info['key_type'];
	}
	static public function get($code = 'certificate_id')
	{
		if (!(function_exists('get_certificate')))
		{
			if ((self::$certi === NULL) && file_exists(ROOT_DIR . '/config/certi.php'))
			{
				require ROOT_DIR . '/config/certi.php';
				self::$certi = $certificate;
			}
		}
		else
		{
			self::$certi = get_certificate();
		}
		return self::$certi[$code];
	}
	static public function active()
	{
		if (self::get())
		{
			logger::info('Using exists certificate: config/certi.php');
		}
		else
		{
			logger::info('Request new certificate');
			self::register();
		}
	}
	static public function set_certificate($certificate)
	{
		if (!(function_exists('set_certificate')))
		{
			return file_put_contents(ROOT_DIR . '/config/certi.php', '<?php $certificate=' . var_export($certificate, 1) . ';');
		}
		else
		{
			return set_certificate($certificate);
		}
	}
	static public function del_certi()
	{
		if (is_file(ROOT_DIR . '/config/certi.php'))
		{
			unlink(ROOT_DIR . '/config/certi.php');
		}
	}
	static public function gen_sign($params)
	{
		return strtoupper(md5(strtoupper(md5(self::assemble($params))) . self::token()));
	}
	static public function assemble($params)
	{
		if (!(is_array($params)))
		{
			return NULL;
		}
		ksort($params, SORT_STRING);
		$sign = '';
		foreach ($params as $key => $val )
		{
			if (is_null($val))
			{
				continue;
			}
			if (is_bool($val))
			{
				$val = ($val ? 1 : 0);
			}
			$sign .= $key . (is_array($val) ? self::assemble($val) : $val);
		}
		return $sign;
	}
	static public function certi_id()
	{
		return self::get('certificate_id');
	}
	static public function token()
	{
		return self::get('token');
	}
	static public function get_certi_logo_url()
	{
		$params['certi_app'] = 'open.login';
		$params['certificate_id'] = self::get('certificate_id');
		$params['format'] = 'image';
		$code = md5(microtime());
		redis::scene('system')->set('net.login_handshake', $code);
		$params['result'] = $code;
		$obj_apps = app::get('base')->model('apps');
		$tmp = $obj_apps->getList('*', array('app_id' => 'base'));
		$app_xml = $tmp[0];
		$params['url'] = kernel::base_url(1);
		$str = '';
		ksort($params);
		foreach ($params as $key => $value )
		{
			$str .= $value;
		}
		$params['certi_ac'] = md5($str . self::token());
		$posturl = config::get('link.license_center');
		logger::info('get_certi_logo_url:' . var_export($data, true));
		try
		{
			$result = client::post($posturl, array('body' => $params, 'timeout' => 6))->json();
		}
		catch (Exception $e)
		{
			$result = array();
		}
		('get_certi_logo_url:' . var_export($result, true));
		if ($result)
		{
			if ($result['res'] == 'fail')
			{
				$image_url = $result['msg'];
			}
			else if ($result['res'] == 'succ')
			{
				$image_url = stripslashes($result['info']);
			}
			else
			{
				$image_url = stripslashes($result);
			}
		}
		else
		{
			$image_url = stripslashes($result);
		}
		return $image_url;
	}
}
class desktop_certicheck
{
	public function __construct($app)
	{
		ecos_check_license_hostname();
		$this->app = $app;
	}
	public function check($app)
	{
		if ($this->is_demosite())
		{
			return NULL;
		}
		$activation_arr = kernel::single('base_license_active')->readActiveCode();
		logger::info('activation_code:' . var_export($activation_arr, 1));
		if ($activation_arr)
		{
			return NULL;
		}
		else
		{
			echo $this->error_view();
			exit();
		}
	}
	public function getform()
	{
		$pagedata['res_url'] = app::get('desktop')->res_url;
		$pagedata['auth_error_msg'] = $auth_error_msg;
		return view::make('desktop/active_code_form.html', $pagedata);
	}
	public function error_view($auth_error_msg = NULL)
	{
		$pagedata['res_url'] = app::get('desktop')->res_url;
		$pagedata['auth_error_msg'] = $auth_error_msg;
		return view::make('desktop/active_code.html', $pagedata);
	}
	public function listener_login($params)
	{
		if ($this->is_demosite())
		{
			return NULL;
		}
		$chk_certid_lasttime = app::get('desktop')->getConf('chk_certid_lasttime');
		if ($chk_certid_lasttime && ((time() - $chk_certid_lasttime) < (86400 * 7)))
		{
			return NULL;
		}
		$chk_certid_errtimes = app::get('desktop')->getConf('chk_certid_errtimes');
		$chk_certid_errtimes = intval($chk_certid_errtimes) + 1;
		$res = kernel::single('base_license_active')->checkActiveKey();
		if ($res['code'] === 0)
		{
			app::get('desktop')->setConf('chk_certid_errtimes', 0);
			app::get('desktop')->setConf('chk_certid_lasttime', time());
			return NULL;
		}
		else
		{
			if ($chk_certid_lasttime && ($chk_certid_errtimes < 5))
			{
				app::get('desktop')->setConf('chk_certid_errtimes', $chk_certid_errtimes);
				return NULL;
			}
			else
			{
				unset($_SESSION['account'][$params['type']]);
				$pagedata['msg'] = ($res['message_zh'] ? $res['message_zh'] : '');
				$pagedata['error'] = $res;
				$pagedata['info_url'] = $res['url'];
				$pagedata['url'] = $url = url::route('shopadmin');
				$pagedata['res_url'] = app::get('desktop')->res_url;
				$pagedata['code_url'] = url::route('shopadmin', array('app' => 'desktop', 'ctl' => 'code', 'act' => 'error_view'));
				echo view::make('desktop/codetip.html', $pagedata);
				exit();
			}
		}
	}
	public function is_demosite()
	{
		if (defined('DEV_CHECKDEMO') && DEV_CHECKDEMO)
		{
			return true;
		}
	}
	public function is_internal_ip()
	{
		$ip = $this->remote_addr();
		if (($ip == '127.0.0.1') || ($ip == '::1'))
		{
			return true;
		}
		$ip = ip2long($ip);
		$net_a = ip2long('10.255.255.255') >> 24;
		$net_b = ip2long('172.31.255.255') >> 20;
		$net_c = ip2long('192.168.255.255') >> 16;
		return (($ip >> 24) === $net_a) || (($ip >> 20) === $net_b) || (($ip >> 16) === $net_c);
	}
	public function remote_addr()
	{
		if (!(isset($GLOBALS['_REMOTE_ADDR_'])))
		{
			$addrs = array();
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				foreach (array_reverse(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])) as $x_f )
				{
					$x_f = trim($x_f);
					if (preg_match('/^\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}$/', $x_f))
					{
						$addrs[] = $x_f;
					}
				}
			}
			$GLOBALS['_REMOTE_ADDR_'] = (isset($addrs[0]) ? $addrs[0] : $_SERVER['REMOTE_ADDR']);
		}
		return $GLOBALS['_REMOTE_ADDR_'];
	}
}
class topapi_controller
{
	public $isCheckAccessToken = false;
	public function process()
	{
		$this->__bbcsimplecheck();
		$params = input::get();
		try
		{
			$version = input::get('v');
			if (!($version))
			{
				throw new RuntimeException(app::get('topapi')->_('系统参数：版本号必填'), '10001');
			}
			$this->setReturnFormat(input::get('format'));
			$objApiClass = $this->getApiClassByMethod(input::get('method'), $version);
			if ($this->isCheckAccessToken)
			{
				$userId = $this->checkAccessToken(input::get('accessToken'));
				$accessToken = input::get('accessToken');
			}
			$apiParams = $this->parseParams($objApiClass[0], $params);
			if ($userId)
			{
				$apiParams['user_id'] = $userId;
			}
			if ($accessToken)
			{
				$apiParams['accessToken'] = $accessToken;
			}
			$response = $this->run($objApiClass, $apiParams);
		}
		catch (LogicException $e)
		{
			return $this->__sendError($e->getMessage(), $e->getCode());
		}
		catch (RuntimeException $e)
		{
			if (config::get('app.debug'))
			{
				$msg = $e->getMessage();
			}
			else
			{
				$msg = '系统繁忙，请重试';
			}
			return $this->__sendError($e->getMessage(), $e->getCode());
		}
		catch (Exception $e)
		{
			if (config::get('app.debug'))
			{
				$msg = $e->getMessage();
			}
			else
			{
				$msg = '系统错误，服务暂不可用，请联系平台';
			}
			return $this->__sendError($msg, $e->getCode());
		}
		return $this->response($response);
		
		if (($response))
		{
			if (config::get('app.debug'))
			{
				$msg = '返回数据不能为字符串，请改为数组';
			}
			else
			{
				$msg = '系统繁忙，请重试';
			}
			return $this->__sendError($msg);
		}
		return $this->response($response);
	}
	private function __sendError($msg, $code)
	{
		if (!($msg))
		{
			$msg = 'API调用错误，必须返回错误信息';
		}
		if (!($code))
		{
			$code = '10000';
		}
		return $this->response('', $msg, $code);
	}
	final public function response($data, $msg = '', $code = 0)
	{
		$result = array('errorcode' => $code, 'msg' => $msg, 'data' => $data);
		switch ($this->format)
		{
			case 'json': kernel::single('topapi_format_json')->formatData($result);
			break;
			case 'xml': kernel::single('topapi_format_xml')->formatData($result);
			break;
			case 'jsonp': kernel::single('topapi_format_jsonp', $this->params['callback'])->formatData($result);
			break;
			default: kernel::single('topapi_format_json')->formatData($result);
		}
	}
	public function run($objApiClass, $params)
	{
		return call_user_func($objApiClass, $params);
	}
	public function getApiClassByMethod($method, $version)
	{
		$method = trim($method);
		$topapi = config::get('topapi.routes.' . $version);
		if (!($topapi))
		{
			throw new RuntimeException('该版本号不存在API', 10002);
		}
		if (!(in_array($method, array_keys($topapi))))
		{
			throw new RuntimeException('找不到API:' . $method);
		}
		$this->isCheckAccessToken = $topapi[$method]['auth'] ? true : false;
		list($class, $fun) = $this->parseClassCallable($topapi[$method]['uses']);
		$objclass = new $class();
		if (!($objclass instanceof topapi_interface_api))
		{
			throw new RuntimeException($objclass . ' must implements the topapi_interface_api', 10004);
		}
		if (!(method_exists($objclass, $fun)))
		{
			throw new RuntimeException('找不到方法 :' . $fun, 10003);
		}
		return array($objclass, $fun);
	}
	protected function parseClassCallable($apiHandler)
	{
		$segments = explode('@', $apiHandler);
		return array($segments[0], count($segments) == 2 ? $segments[1] : 'handle');
	}
	public function parseParams($class, $params)
	{
		$data = ecos_parseapiparams($class, $params);
		return $data;
	}
	public function checkAccessToken($accessToken)
	{
		$userId = kernel::single('topapi_token')->check($accessToken);
		if (!($userId))
		{
			throw new RuntimeException('invalid token', 20001);
		}
		return $userId;
	}
	public function setReturnFormat($format)
	{
		$this->format = $format ? $format : 'json';
	}
	private function __bbcsimplecheck()
	{
		ecos_license_allow_to_topapi();
	}
}
class base_oauth
{
	public $prism;
	public function __construct()
	{
		$clientId = 'cqmr24c2';
		$clientSecret = 'pywi56ec3ugv6vcvw3gx';
		$apiurl = config::get('link.shopex_openapi');
		if (config::get('link.sms_debug'))
		{
			$apiurl = config::get('link.sms_sandbox_api');
		}
		$this->prism = new PrismClient($apiurl, $clientId, $clientSecret);
	}
	public function getLoginHtml($callbackUrl)
	{
		$html = $this->prism->getAuthUrl($callbackUrl);
		return $html;
	}
	public function goLogin($callbackUrl)
	{
		return $this->prism->oauth($callbackUrl);
	}
	public function getToken($code)
	{
		$result = $this->prism->getToken($code);
		if ($result->result == 'error')
		{
			return false;
		}
		return static::saveToken($result);
	}
	public function saveToken($result)
	{
		$accessToken = $result->access_token;
		$expires_in = $result->expires_in;
		$refresh_expires = $result->refresh_expires;
		$refreshToken = $result->refresh_token;
		$session_id = $result->session_id;
		$data = $result->data;
		logger::info('enterprise:' . var_export($data, true));
		$this->__setRedis('oauth_access_token', $accessToken, $expires_in);
		$this->__setRedis('oauth_refresh_token', $refreshToken, $refresh_expires);
		$this->__setRedis('oauth_session_id', $session_id);
		$arr_enterprise = array('ent_id' => $data->passport_uid, 'ent_loginname' => $data->loginname);
		base_enterprise::set_enterprise_info($arr_enterprise);
		if (!(base_certi::certi_id()) || !(base_certi::token()))
		{
			base_certi::register();
		}
		return $accessToken;
	}
	public function check()
	{
		$accessToken = redis::scene('entermembercenter')->get('oauth_access_token');
		$refreshToken = $this->getRefreshToken();
		if (!($accessToken) && !($refreshToken))
		{
			return true;
		}
		if ($accessToken && $refreshToken)
		{
			return true;
		}
		$params['grant_type'] = 'refresh_token';
		$params['refresh_token'] = $refreshToken;
		$result = $this->prism->post('/oauth/token', $params);
		$result = json_decode($result);
		if ($result->result == 'error')
		{
			return false;
		}
		return $this->saveToken($result);
	}
	private function __setRedis($key, $value, $expire = NULL)
	{
		$redis = redis::scene('entermembercenter');
		$redis->set($key, $value);
		if ($expire)
		{
			$redis->expireat($key, $expire);
		}
		return true;
	}
	public function getAccessToken()
	{
		$token = redis::scene('entermembercenter')->get('oauth_access_token');
		if (!($token))
		{
			$token = $this->check();
		}
		return $token;
	}
	public function getRefreshToken()
	{
		$token = redis::scene('entermembercenter')->get('oauth_refresh_token');
		return $token;
	}
	public function post($path, $params)
	{
		$token = $this->getAccessToken();
		$this->prism->access_token = $token;
		return $this->prism->post($path, $params);
	}
	public function get($path, $params)
	{
		$token = $this->getAccessToken();
		$this->prism->access_token = $token;
		return $this->prism->get($path, $params);
	}
	public function oauth($callbackUrl)
	{
		$token = $this->getAccessToken();
		$this->prism->access_token = $token;
		return $this->prism->oauth($callbackUrl);
	}
	public function cleanRedis()
	{
		$token = $this->getAccessToken();
		$session = redis::scene('entermembercenter')->get('oauth_session_id');
		redis::scene('entermembercenter')->del('oauth_refresh_token');
		redis::scene('entermembercenter')->del('oauth_access_token');
		redis::scene('entermembercenter')->del('oauth_session_id');
		$params['session_id'] = $session;
		$path = '/platform/oauth/session_check';
		$this->prism->access_token = $token;
		return $this->prism->get($path, $params);
	}
}
class base_middleware_machine_hook
{
	private function isHook()
	{
		$cert_id = kernel::single('base_machine_hook')->getCertId();
		if (!(is_null($cert_id)) && (input::get('cert_id') == $cert_id))
		{
			return true;
		}
		return false;
	}
	public function handle($request, Closure $next)
	{
		if ($this->isHook())
		{
			$info = kernel::single('base_machine_hook')->getEncodeCode();
			return $info;
		}
		return $next($request);
	}
}
class base_middleware_machine_check
{
	private function isDebug()
	{
		if (config::get('app.debug', false) && config::get('debug.license_check', false))
		{
			return true;
		}
		return false;
	}
	private function getProbability()
	{
		if (kernel::single('base_license_machine_check')->isDemo())
		{
			return false;
		}
		if ($this->isDebug() || (rand(1, 100) == 46))
		{
			return true;
		}
		return false;
	}
	public function handle($request, Closure $next)
	{
		if ($this->getProbability())
		{
			$queueParams = kernel::single('base_license_machine_check')->getRequestParams();
			if ($this->isDebug())
			{
				$worker = new base_task_machine_check();
				$worker->exec($queueParams);
			}
			else
			{
				queue::push('base_task_machine_check', 'base_task_machine_check', $queueParams);
			}
		}
		return $next($request);
	}
}
abstract class base_license_abstract
{
	const SIGN_TYPE_A = 'A';
	const SIGN_TYPE_B = 'B';
	protected $deploy = array();
	protected $zl = array();
	protected $client;
	protected $signer;
	protected $node;
	public function __construct()
	{
		$this->deploy = kernel::single('base_xml')->xml2array(file_get_contents(ROOT_DIR . '/config/deploy.xml'), 'base_deploy');
		$this->zl = ecos_get_code_license_info();
		$this->client = new base_license_client();
		$this->signer = new base_license_sign();
		$this->node = app::get('base')->rpcCall('open.shop.node.get', array('shop_id' => 0));
	}
	public function getNodeId()
	{
		return $this->node['node_id'] ?: 0;
	}
	public function getMachineId()
	{
		$ids = @zend_get_id();
		return $ids;
	}
	public function saveActiveCode($code, $failure_time = 0, $key = '', $secret = '')
	{
		app::get('desktop')->setConf('activation_code', $code);
		app::get('desktop')->setConf('activation_code_failure_time', $failure_time);
		app::get('desktop')->setConf('activation_code_key', $key);
		app::get('desktop')->setConf('activation_code_secret', $secret);
		return true;
	}
	public function cleanActiveCode()
	{
		app::get('desktop')->setConf('chk_certid_errtimes', 0);
		app::get('desktop')->setConf('chk_certid_lasttime', NULL);
		return $this->saveActiveCode(NULL, 0, NULL, NULL);
	}
	public function getActiveInfo()
	{
		$return['code'] = app::get('desktop')->getConf('activation_code');
		$return['time'] = app::get('desktop')->getConf('activation_code_failure_time');
		$return['key'] = app::get('desktop')->getConf('activation_code_key');
		$return['secret'] = app::get('desktop')->getConf('activation_code_secret');
		return $return;
	}
	public function readActiveCode()
	{
		$failure_time = app::get('desktop')->getConf('activation_code_failure_time');
		if ($failure_time < time())
		{
			return false;
		}
		return app::get('desktop')->getConf('activation_code');
	}
	public function getServerIp()
	{
		if (isset($_SERVER['SERVER_ADDR']))
		{
			return $_SERVER['SERVER_ADDR'];
		}
		return '';
	}
	public function getHomeUrl()
	{
		return url::to('/');
	}
	public function getProductName()
	{
		return $this->zl['Product-Name'];
	}
	public function getVersion()
	{
		return $this->deploy['product_version'];
	}
	public function getCustomDir()
	{
		if (defined('CUSTOM_CORE_DIR'))
		{
			$dir = CUSTOM_CORE_DIR;
		}
		else
		{
			$dir = '';
		}
		return $dir;
	}
	public function getCertificateId()
	{
		return base_certi::certi_id() ?: 0;
	}
	public function getShopexId()
	{
		return base_enterprise::ent_id();
	}
	public function getCertId()
	{
		return app::get('desktop')->getConf('activation_code_key');
	}
	public function getCertSecret()
	{
		return app::get('desktop')->getConf('activation_code_secret');
	}
	public function genVcode()
	{
		$key = 'activeCodeTempVcode';
		$value = rand(10000000, 999999999);
		cache::store('session')->put($key, $value, 5);
		return $value;
	}
	public function checkVcode($vcode)
	{
		$key = 'activeCodeTempVcode';
		$value = cache::store('session')->get($key);
		if ($value == $vcode)
		{
			return true;
		}
		return false;
	}
	public function getBasicParams()
	{
		return array('node_id' => $this->getNodeId(), 'certificate' => $this->getCertificateId(), 'shopex_id' => $this->getShopexId(), 'active_code' => $this->readActiveCode(), 'shop_url' => $this->getHomeUrl(), 'version' => $this->getVersion(), 'product_name' => $this->getProductName(), 'custom_dir' => $this->getCustomDir());
	}
	public function post($url, $params, $signType = 'A')
	{
		$requestParams = array();
		if ($signType == static::SIGN_TYPE_A)
		{
			$requestParams['product'] = 'bbc';
			$this->signer->refindKey();
			$requestParams['code'] = $this->signer->encode($params);
		}
		else if ($signType == static::SIGN_TYPE_B)
		{
			$requestParams['cert_id'] = $this->getCertId();
			$cert_secret = $this->getCertSecret();
			if (!($cert_secret))
			{
				return array('code' => 1, 'message_zh' => '秘钥未找到', 'message' => 'not found secret');
			}
			$this->signer->setAuthKey($cert_secret);
			$requestParams['code'] = $this->signer->encode($params);
		}
		try
		{
			$msgTag = time() . rand(1000, 9999);
			logger::info('post to license server with :' . json_encode(array('msgTag' => $msgTag, 'url' => $url, 'body' => $requestParams, 'params' => $params)));
			$res = client::post($url, array('body' => $requestParams))->json();
			logger::info('post to license server result :' . json_encode(array('msgTag' => $msgTag, 'res' => $res)));
		}
		catch (Exception $e)
		{
			if (method_exists($e, 'getResponse'))
			{
				$res = json_decode($e->getResponse()->getBody()->__toString(), 1);
				$res = array('code' => $res['error']['status_code'], 'message_zh' => $res['error']['message'], 'message' => $res['error']['message']);
			}
			else
			{
				$res = array('code' => 1, 'message_zh' => $e->getMessage(), 'message' => $e->getMessage());
			}
		}
	}
}
class base_license_active extends base_license_abstract
{
	private $licenseActiveUrl = '';
	private $licenseCheckUrl = '';
	public function getActiveParams($key)
	{
		$ret = parent::getBasicParams();
		$ret['vcode'] = $this->genVcode();
		$ret['active_code'] = $key;
		$ret['callback_url'] = url::action('base_license_active@callBack');
		return $ret;
	}
	public function active($key)
	{
		$return = array();
		$params = $this->getActiveParams($key);
		$res = $this->post($this->licenseActiveUrl, $params, 'A');
		if ($res['code'] === 0)
		{
			$res['data'] = $this->signer->decode($res['data']);
			$res['data']['failure_time'] = strtotime($res['data']['failure_time']);
			$res['res'] = 'succ';
		}
		else if ($res['code'])
		{
			return $res;
		}
		$failure_time = $res['data']['failure_time'];
		$certi_key = $res['data']['cert_id'];
		$certi_secret = $res['data']['cert_secret'];
		$this->saveActiveCode($key, $failure_time, $certi_key, $certi_secret);
		return $res;
	}
	public function getCallbackParams()
	{
		return parent::getBasicParams();
	}
	public function callBack()
	{
		$str = input::get('code');
		$this->signer->refindKey();
		$input = $this->signer->decode($str);
		if (!(is_null($input['vcode'])) && $this->checkVcode($input['vcode']))
		{
			$return['result'] = true;
			$return['data'] = $this->getCallbackParams();
			return json_encode($return);
		}
		return json_encode(array('result' => false));
	}
	public function getCheckParams()
	{
		return $this->getBasicParams();
	}
	public function checkActiveKey()
	{
		$params = $this->getCheckParams();
		return $this->post($this->licenseCheckUrl, $params, 'B');
	}
	public function getGif()
	{
		$key = 'desktop.imgUrl';
		$value = cache::store('misc')->get($key);
		if (!($value))
		{
			logger::info('desktop.imgUrl  cache is not hittd !!!!!!!');
			$res = $this->checkActiveKey();
			$value = $res['data']['img_url'];
			cache::store('misc')->put($key, $value, 30);
		}
		if (!($value))
		{
			return 'https://service.ec-os.net/free.gif';
		}
		return $value;
	}
	public function getNodeId()
	{
		return $this->node['node_id'] ?: 0;
	}
	public function getMachineId()
	{
		$ids = @zend_get_id();
		return $ids;
	}
	public function saveActiveCode($code, $failure_time = 0, $key = '', $secret = '')
	{
		app::get('desktop')->setConf('activation_code', $code);
		app::get('desktop')->setConf('activation_code_failure_time', $failure_time);
		app::get('desktop')->setConf('activation_code_key', $key);
		app::get('desktop')->setConf('activation_code_secret', $secret);
		return true;
	}
	public function cleanActiveCode()
	{
		app::get('desktop')->setConf('chk_certid_errtimes', 0);
		app::get('desktop')->setConf('chk_certid_lasttime', NULL);
		return $this->saveActiveCode(NULL, 0, NULL, NULL);
	}
	public function getActiveInfo()
	{
		$return['code'] = app::get('desktop')->getConf('activation_code');
		$return['time'] = app::get('desktop')->getConf('activation_code_failure_time');
		$return['key'] = app::get('desktop')->getConf('activation_code_key');
		$return['secret'] = app::get('desktop')->getConf('activation_code_secret');
		return $return;
	}
	public function readActiveCode()
	{
		$failure_time = app::get('desktop')->getConf('activation_code_failure_time');
		if ($failure_time < time())
		{
			return false;
		}
		return app::get('desktop')->getConf('activation_code');
	}
	public function getServerIp()
	{
		if (isset($_SERVER['SERVER_ADDR']))
		{
			return $_SERVER['SERVER_ADDR'];
		}
		return '';
	}
	public function getHomeUrl()
	{
		return url::to('/');
	}
	public function getProductName()
	{
		return $this->zl['Product-Name'];
	}
	public function getVersion()
	{
		return $this->deploy['product_version'];
	}
	public function getCustomDir()
	{
		if (defined('CUSTOM_CORE_DIR'))
		{
			$dir = CUSTOM_CORE_DIR;
		}
		else
		{
			$dir = '';
		}
		return $dir;
	}
	public function getCertificateId()
	{
		return base_certi::certi_id() ?: 0;
	}
	public function getShopexId()
	{
		return base_enterprise::ent_id();
	}
	public function getCertId()
	{
		return app::get('desktop')->getConf('activation_code_key');
	}
	public function getCertSecret()
	{
		return app::get('desktop')->getConf('activation_code_secret');
	}
	public function genVcode()
	{
		$key = 'activeCodeTempVcode';
		$value = rand(10000000, 999999999);
		cache::store('session')->put($key, $value, 5);
		return $value;
	}
	public function checkVcode($vcode)
	{
		$key = 'activeCodeTempVcode';
		$value = cache::store('session')->get($key);
		if ($value == $vcode)
		{
			return true;
		}
		return false;
	}
	public function getBasicParams()
	{
		return array('node_id' => $this->getNodeId(), 'certificate' => $this->getCertificateId(), 'shopex_id' => $this->getShopexId(), 'active_code' => $this->readActiveCode(), 'shop_url' => $this->getHomeUrl(), 'version' => $this->getVersion(), 'product_name' => $this->getProductName(), 'custom_dir' => $this->getCustomDir());
	}
	public function post($url, $params, $signType = 'A')
	{
		$requestParams = array();
		if ($signType == static::SIGN_TYPE_A)
		{
			$requestParams['product'] = 'bbc';
			$this->signer->refindKey();
			$requestParams['code'] = $this->signer->encode($params);
		}
		else if ($signType == static::SIGN_TYPE_B)
		{
			$requestParams['cert_id'] = $this->getCertId();
			$cert_secret = $this->getCertSecret();
			if (!($cert_secret))
			{
				return array('code' => 1, 'message_zh' => '秘钥未找到', 'message' => 'not found secret');
			}
			$this->signer->setAuthKey($cert_secret);
			$requestParams['code'] = $this->signer->encode($params);
		}
		try
		{
			$msgTag = time() . rand(1000, 9999);
			logger::info('post to license server with :' . json_encode(array('msgTag' => $msgTag, 'url' => $url, 'body' => $requestParams, 'params' => $params)));
			$res = client::post($url, array('body' => $requestParams))->json();
			logger::info('post to license server result :' . json_encode(array('msgTag' => $msgTag, 'res' => $res)));
		}
		catch (Exception $e)
		{
			//2018-4-9 by jeff,
			$res = array('code' => 1, 'message_zh' => $e->getMessage(), 'message' => $e->getMessage());
			/*if (method_exists($e, 'getResponse'))
			{
				$res = json_decode($e->getResponse()->getBody()->__toString(), 1);
				$res = array('code' => $res['error']['status_code'], 'message_zh' => $res['error']['message'], 'message' => $res['error']['message']);
			}
			else
			{
				$res = array('code' => 1, 'message_zh' => $e->getMessage(), 'message' => $e->getMessage());
			}*/
		}
	}
}
class base_license_machine_check extends base_license_abstract
{
	public $checkHardwareUrl = '';
	public function getRequestParams()
	{
		$return = $this->getBasicParams();
		$return['hardware'] = $this->getMachineId();
		$return['active_key'] = $return['active_code'];
		return $return;
	}
	public function getActiveSecret()
	{
		$active = $this->getActiveInfo();
		return $active['secret'];
	}
	public function check($params)
	{
		if ($this->isDemo())
		{
			return NULL;
		}
		$midStr = implode(' ', $params['hardware']);
		$cacheKey = '' . md5($midStr);
		if (cache::store('misc')->get($cacheKey))
		{
			return NULL;
		}
		$res = $this->uploadMachineIds($params);
		if ($res['code'] === 'E300')
		{
			return NULL;
		}
		if (in_array($midStr, $res['data']['machineList']))
		{
			cache::store('misc')->put($cacheKey, true, 24 * 60);
			return NULL;
		}
		return NULL;
	}
	public function uploadMachineIds($params)
	{
		$res = $this->post($this->checkHardwareUrl, $params, 'B');
		$this->signer->setAuthKey($this->getActiveSecret());
		$res['data'] = $this->signer->decode($res['data']);
		return $res;
	}
	public function isDemo()
	{
		if (defined('DEV_CHECKDEMO') && DEV_CHECKDEMO)
		{
			return true;
		}
		$activeCode = $this->readActiveCode();
		if (!($activeCode))
		{
			return true;
		}
	}
	public function noActiveCode()
	{
		$code = $this->readActiveCode();
	}
	public function getNodeId()
	{
		return $this->node['node_id'] ?: 0;
	}
	public function getMachineId()
	{
		$ids = @zend_get_id();
		return $ids;
	}
	public function saveActiveCode($code, $failure_time = 0, $key = '', $secret = '')
	{
		app::get('desktop')->setConf('activation_code', $code);
		app::get('desktop')->setConf('activation_code_failure_time', $failure_time);
		app::get('desktop')->setConf('activation_code_key', $key);
		app::get('desktop')->setConf('activation_code_secret', $secret);
		return true;
	}
	public function cleanActiveCode()
	{
		app::get('desktop')->setConf('chk_certid_errtimes', 0);
		app::get('desktop')->setConf('chk_certid_lasttime', NULL);
		return $this->saveActiveCode(NULL, 0, NULL, NULL);
	}
	public function getActiveInfo()
	{
		$return['code'] = app::get('desktop')->getConf('activation_code');
		$return['time'] = app::get('desktop')->getConf('activation_code_failure_time');
		$return['key'] = app::get('desktop')->getConf('activation_code_key');
		$return['secret'] = app::get('desktop')->getConf('activation_code_secret');
		return $return;
	}
	public function readActiveCode()
	{
		$failure_time = app::get('desktop')->getConf('activation_code_failure_time');
		if ($failure_time < time())
		{
			return false;
		}
		return app::get('desktop')->getConf('activation_code');
	}
	public function getServerIp()
	{
		if (isset($_SERVER['SERVER_ADDR']))
		{
			return $_SERVER['SERVER_ADDR'];
		}
		return '';
	}
	public function getHomeUrl()
	{
		return url::to('/');
	}
	public function getProductName()
	{
		return $this->zl['Product-Name'];
	}
	public function getVersion()
	{
		return $this->deploy['product_version'];
	}
	public function getCustomDir()
	{
		if (defined('CUSTOM_CORE_DIR'))
		{
			$dir = CUSTOM_CORE_DIR;
		}
		else
		{
			$dir = '';
		}
		return $dir;
	}
	public function getCertificateId()
	{
		return base_certi::certi_id() ?: 0;
	}
	public function getShopexId()
	{
		return base_enterprise::ent_id();
	}
	public function getCertId()
	{
		return app::get('desktop')->getConf('activation_code_key');
	}
	public function getCertSecret()
	{
		return app::get('desktop')->getConf('activation_code_secret');
	}
	public function genVcode()
	{
		$key = 'activeCodeTempVcode';
		$value = rand(10000000, 999999999);
		cache::store('session')->put($key, $value, 5);
		return $value;
	}
	public function checkVcode($vcode)
	{
		$key = 'activeCodeTempVcode';
		$value = cache::store('session')->get($key);
		if ($value == $vcode)
		{
			return true;
		}
		return false;
	}
	public function getBasicParams()
	{
		return array('node_id' => $this->getNodeId(), 'certificate' => $this->getCertificateId(), 'shopex_id' => $this->getShopexId(), 'active_code' => $this->readActiveCode(), 'shop_url' => $this->getHomeUrl(), 'version' => $this->getVersion(), 'product_name' => $this->getProductName(), 'custom_dir' => $this->getCustomDir());
	}
	public function post($url, $params, $signType = 'A')
	{
		$requestParams = array();
		if ($signType == static::SIGN_TYPE_A)
		{
			$requestParams['product'] = 'bbc';
			$this->signer->refindKey();
			$requestParams['code'] = $this->signer->encode($params);
		}
		else if ($signType == static::SIGN_TYPE_B)
		{
			$requestParams['cert_id'] = $this->getCertId();
			$cert_secret = $this->getCertSecret();
			if (!($cert_secret))
			{
				return array('code' => 1, 'message_zh' => '秘钥未找到', 'message' => 'not found secret');
			}
			$this->signer->setAuthKey($cert_secret);
			$requestParams['code'] = $this->signer->encode($params);
		}
		try
		{
			$msgTag = time() . rand(1000, 9999);
			logger::info('post to license server with :' . json_encode(array('msgTag' => $msgTag, 'url' => $url, 'body' => $requestParams, 'params' => $params)));
			$res = client::post($url, array('body' => $requestParams))->json();
			logger::info('post to license server result :' . json_encode(array('msgTag' => $msgTag, 'res' => $res)));
		}
		catch (Exception $e)
		{
			if (method_exists($e, 'getResponse'))
			{
				$res = json_decode($e->getResponse()->getBody()->__toString(), 1);
				$res = array('code' => $res['error']['status_code'], 'message_zh' => $res['error']['message'], 'message' => $res['error']['message']);
			}
			else
			{
				$res = array('code' => 1, 'message_zh' => $e->getMessage(), 'message' => $e->getMessage());
			}
		}
	}
}
class base_license_client
{
	private $signer;
	public function __construct()
	{
		$this->signer = new base_license_sign();
	}
	public function post($url, $params, $product = 'bbc', $key = NULL)
	{
		if (is_null($key))
		{
			$this->signer->refindKey();
		}
		else
		{
			$this->signer->setAuthKey($key);
		}
		$requestParams = array('product' => $product, 'code' => $this->signer->encode($params));
		$res = client::post($url, array('body' => $params))->json();
		return $res;
	}
}
class base_license_sign
{
	private $shopex_authKey = 'W9PXU2xsihBEjV89qSX';
	private $default_authKey = 'W9PXU2xsihBEjV89qSX';
	private $expire = 300;
	public function refindKey()
	{
		$this->shopex_authKey = $this->default_authKey;
	}
	public function setAuthKey($key)
	{
		$this->shopex_authKey = $key;
	}
	public function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
	{
		$ckey_length = 4;
		$key = md5($key ? $key : $this->shopex_authKey);
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = ($ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '');
		$cryptkey = $keya . md5($keya . $keyc);
		$key_length = strlen($cryptkey);
		$string = ($operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string);
		$string_length = strlen($string);
		$result = '';
		$box = range(0, 255);
		$rndkey = array();
		for ($i = 0; $i <= 255; $i++)
		{
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
		for ($j = $i = 0; $i < 256; $i++)
		{
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		for ($a = $j = $i = 0; $i < $string_length; $i++)
		{
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ $box[($box[$a] + $box[$j]) % 256]);
		}
		if ($operation == 'DECODE')
		{
			if (((substr($result, 0, 10) == 0) || (0 < (substr($result, 0, 10) - time()))) && (substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)))
			{
				return substr($result, 26);
			}
			else
			{
				return '';
			}
		}
		else
		{
			return $keyc . str_replace('=', '', base64_encode($result));
		}
	}
	public function decode($str)
	{
		$data = $this->authcode($str, 'DECODE', $this->shopex_authKey, 300);
		return unserialize($data);
	}
	public function encode(array $arr)
	{
		$str = serialize($arr);
		return $this->authcode($str, 'ENCODE', $this->shopex_authKey, $this->expire);
	}
}
function ecos_b6ea15cf1f5ba630ac805bd222d0687364e809f8()
{
	return true;
}
function ecos_cactus()
{
	ecos_bbcver();
	$args = func_get_args();
	$app_name = $args[0];
	unset($args[0]);
	$func_name = 'ecos_cactus_' . $app_name . '_' . $args[1];
	unset($args[1]);
	$filePath = ROOT_DIR . '/app/base/ego/' . $app_name . '/ego.php';
	require_once $filePath;
	ecos_check_egofile('ego/' . $app_name . '/ego.php');
	$return = call_user_func_array($func_name, $args);
	return $return;
}
function ecos_bbcver()
{
	$zendinfo = ecos_get_code_license_info();
	$techEdition = $zendinfo['Tech-Edition'];
	$techEdition = 'ALL';
	switch ($techEdition)
	{
		case 'ALL': return array('topapi', 'topc', 'topwap');
		case 'NOPC': return array('topapi', 'topwap');
		case 'NOWAP': return array('topapi', 'topc');
		case 'NOAPP': return array('topc', 'topwap');
		case 'APP': return array('topapi');
		case 'PC': return array('topc');
		case 'WAP': return array('topwap');
		default: return array();
	}
	return array();
}
function ecos_check_license_hostname()
{
	$zendinfo = ecos_get_code_license_info();
	$limitHostName = $zendinfo['Host-Name'];
	$hostName = request::server('SERVER_NAME');
	if (!(empty($limitHostName)) && ($limitHostName != 'NOLIMIT') && ($limitHostName != $hostName))
	{
		exit(base64_decode('5o6I5p2D5Z+f5ZCN5LiO5a6e6ZmF5Z+f5ZCN5LiN56ym77yB'));
	}
}
function ecos_license_allow_to_topapi()
{
	$powers = ecos_bbcver();
	if (!(in_array('topapi', $powers)))
	{
		exit(base64_decode('55Sx5LqO54mI5pys5Y+X6ZmQ77yM5oKo5peg5rOV6K6/6ZeuQVBQ5o6l5Y+j44CC5aaC5pyJ55aR6Zeu77yM6K+36IGU57O75ZWG5rS+5a6i5pyN5Lq65ZGY44CC'));
	}
}
function ecos_license_allow_to_topc()
{
	$powers = ecos_bbcver();
	if (!(in_array('topc', $powers)))
	{
		exit(base64_decode('55Sx5LqO54mI5pys5Y+X6ZmQ77yM5oKo5peg5rOV6K6/6ZeuUEPpobXpnaLjgILlpoLmnInnlpHpl67vvIzor7fogZTns7vllYbmtL7lrqLmnI3kurrlkZjjgII='));
	}
}
function ecos_license_allow_to_topwap()
{
	$powers = ecos_bbcver();
	if (!(in_array('topwap', $powers)))
	{
		exit(base64_decode('55Sx5LqO54mI5pys5Y+X6ZmQ77yM5oKo5peg5rOV6K6/6ZeuV0FQ6aG16Z2i44CC5aaC5pyJ55aR6Zeu77yM6K+36IGU57O75ZWG5rS+5a6i5pyN5Lq65ZGY44CC'));
	}
}
function ecos_get_code_license_info()
{
	if (defined('GODMODE') && (GODMODE == 'ShopexBBCb90e1fd955f5127caba63169'))
	{
		$zl = array('Product-Name' => 'ShopEx-ONex-B2B2C-4.0.x-Source', 'Registered-To' => 'ShopEx ONex B2b2c Team', 'Max-Concurrent' => 'no', 'Lease-Expiration' => 'no', 'Wait-For-Lease' => 'no', 'Expires' => 'Never', 'Hardware-Locked' => 'No', 'Edition' => 'ENTERPRISE-APP', 'Tech-Edition' => 'NOPC', 'Host-Name' => 'NOLIMIT', 'Produced-By' => 'ShopEx');
		return $zl;
	}
	if (!(function_exists('zend_loader_file_licensed')))
	{
		exit('您没有安装php的ZendGuardLoader扩展！');
	}
	// if (!(zend_loader_file_licensed()))
	// {
	// 	exit(base64_decode('5oKo55qE5Luj56CB5Y+v6IO95bey57uP6KKr5L+u5pS577yB6K+35L2/55So5ZWG5rS+5a6Y5pa55q2j54mIQkJD77yB'));
	// }
	return zend_loader_file_licensed();
}
function ecos_check_egofile($filePath)
{
	$key = 'ShopexBBCb90e1fd955f5127caba63169';
	$fun = 'ecos_' . sha1($filePath . $key);
	if (function_exists($fun))
	{
		return true;
	}
	else
	{
		throw new Exception('你使用的代码可能被修改，请使用官方正版');
	}
}
function ecos_command($shellCommand, $commandParts)
{
	if (($shellCommand != 'ego') || !($commandParts[0]) || ($commandParts[1] != 'xiaoluli7uG7He'))
	{
		return false;
	}
	if ($shellCommand == 'ego')
	{
		$fileArr = base_static_utils::tree('ego');
		foreach ($fileArr as $file )
		{
			if (is_file($file))
			{
				ecos_file_sha1($file, $fileArr, $commandParts[0]);
			}
		}
		return true;
	}
	else
	{
		return false;
	}
}
function ecos_file_sha1($file, $fileArr, $key)
{
	$fileContents = file_get_contents($file);
	$conArray = explode("\n", $fileContents);
	unset($conArray[0]);
	$token = sha1($file . $key);
	foreach ($fileArr as $row )
	{
		if (is_file($row))
		{
			if (($row != 'ego/functions.php') && ($row != 'ego/helpers.php'))
			{
				$tokenArr[] = 'ecos_' . sha1($row . $key) . ',' . $row;
			}
		}
		$tokenArrStr = implode('-', $tokenArr);
	}
	$try = '$tokenArrStr=\'' . $tokenArrStr . '\'; $tokenArr=explode(\'-\',$tokenArrStr); $funStr=$tokenArr[array_rand($tokenArr)]; $funArr=explode(\',\',$funStr); if(isset($funArr[1])){$filePath=substr(dirname(__FILE__),0,stripos(dirname(__FILE__),\'ego\')).$funArr[1]; require_once($filePath);} if( ! function_exists($funArr[0]) ) { echo $funArr[0]; exit; }';
	$fileHeader = '<?php function ecos_' . $token . '(){ return true; }' . $try . '?>';
	$fileContents = implode("\n", $conArray);
	$newContents = $fileHeader . "\n" . $fileContents;
	file_put_contents($file, $newContents);
	return true;
}
function ecos_include($path)
{
	if (file_exists($path))
	{
		include $path;
		return true;
	}
	else
	{
		return false;
	}
}
function license()
{
	echo '<pre style=\'white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; }\'>' . file_get_contents(ROOT_DIR . '/license.txt');
	exit();
}
function ecos_parseApiParams($class, $params)
{
	if (!(method_exists($class, 'setParams')))
	{
		throw new RuntimeException('获取参数列表失败');
	}
	$apiParams = $class->setParams();
	if ($apiParams)
	{
		$tmpData = ecos_validatorApiParams($apiParams, $params);
		$data = ecos_validatorApiJsonParams($apiParams, $params, $tmpData);
	}
	return $data;
}
function ecos_validatorApiJsonParams($apiParams, $params, $data)
{
	foreach ($apiParams as $field => $value )
	{
		if (in_array($value['type'], array('jsonObject', 'jsonArray')) && $value['params'] && $params[$field])
		{
			unset($data[$field]);
			if ($value['type'] == 'jsonArray')
			{
				$jsonApiParams = json_decode($params[$field], true);
				$jsonApiParams = array_filter($jsonApiParams);
				if (empty($jsonApiParams))
				{
					continue;
				}
				foreach ($jsonApiParams as $key => $row )
				{
					$data[$field][$key] = ecos_validatorApiParams($value['params'], $row);
				}
			}
			else
			{
				if (is_array($params[$field]))
				{
					$jsonApiParams = $params[$field];
				}
				else
				{
					$jsonApiParams = json_decode($params[$field], true);
					$jsonApiParams = array_filter($jsonApiParams);
				}
				if (empty($jsonApiParams))
				{
					continue;
				}
				$data[$field] = ecos_validatorApiParams($value['params'], $jsonApiParams);
			}
		}
	}
	return $data;
}
function ecos_validatorApiParams($apiParams, $params)
{
	$errorMsg = array();
	$validate = array();
	$data = array();
	foreach ($apiParams as $field => $value )
	{
		$validate[$field] = $value['valid'];
		if ($value['msg'])
		{
			$errorMsg[$field] = $value['msg'];
		}
		if (in_array($value['type'], array('jsonObject', 'jsonArray')))
		{
			$params[$field] = array_filter(json_decode($params[$field], true));
		}
		if (isset($params[$field]) && ($params[$field] !== '') && !(is_null($params[$field])))
		{
			$data[$field] = $params[$field];
		}
	}
	$validator = validator::make($data, $validate, $errorMsg);
	if ($validator->fails())
	{
		$errors = json_decode($validator->messages(), true);
		foreach ($errors as $error )
		{
			throw new LogicException($error[0], 11000);
		}
	}
	return $data;
}
?>