<?php
namespace Module\Base;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Curl\Curl;

abstract class Http
{

	/**
	 * 接口基地址
	 */
	protected $base_url;

	/**
	 * 最后一次操作的错误信息
	 */
	protected $error_message = '';

	/**
	 * 超时时间
	 */
	protected $timeout = 300;

	/**
	 * 调试模式
	 */
	protected $debug = true;

	/**
	 * 取得最后一次操作的错误信息
	 *
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->error_message;
	}

	/**
	 * 设置接口基地址
	 */
	public function setBaseUrl($value)
	{
		$this->base_url = $value;
	}

	/**
	 * 设置超时时间
	 */
	public function setTimeout($value)
	{
		$this->timeout = $value;
	}

    /**
     * GET请求
     *
     * @param $url
     * @param array $data
     * @return null
     */
	protected function httpGet($url, $data = [])
	{
		return $this->http('get', $url, $data);
	}

    /**
     * POST请求
     *
     * @param $url
     * @param array $data
     * @return null
     */
	protected function httpPost($url, $data = [])
	{
		return $this->http('post', $url, $data);
	}

    /**
     * 封装一层http响应的处理
     *
     * @param $type
     * @param $url
     * @param array $data
     * @return null
     */
	protected function http($type, $url, $data = [])
	{
		// 暂时只支持GET与POST请求。
		$type = strtolower((string) $type);
		if (! in_array($type, [
			'get',
			'post'
		])) {
			throw new InvalidArgumentException('Type supports only GET and POST method.');
		}

		// 自动补全URL。
		if (! preg_match('#^https?://#i', $url)) {

			// 若不是绝对URI调用，需要接口基地址配置支持。
			if (! preg_match('#^https?://#i', $this->base_url)) {
				throw new InvalidArgumentException('Base url must be provided.');
			}

			if ($url{0} === '/') {
				$url = substr($url, 1);
			}
			$url = str_finish($this->base_url, '/') . $url;
		}

		// 创建CURL对象。
		$curl = new Curl();
		$curl->setUserAgent(sprintf('Curl %s (+%s)', class_basename(get_class($this)), config('app.url')));
		$curl->setHeader('Accept', 'application/json');
		$curl->setOpt(CURLOPT_TIMEOUT, $this->timeout);

		// 数据兼容。
		$data = $this->compatibleCollection($data);

		$this->pretreatment($data, $curl);

		$curl->$type($url, $data);
		$response = $curl->error ? false : $curl->response;
		if ($this->debug && function_exists('logger')) {
			logger('Curl ' . get_class($this), [
				'method' => $type,
				'url' => $url,
				'data' => $data,
				'error_code' => $curl->error_code,
				'response' => $response
			]);
		}
		$result = $this->analyzeResponse($response, $curl->error_code, $curl->error_message);
		$curl->close();

		return $result;
	}

	/**
	 * 兼容集合数据类型
	 *
	 * @param array|Collection $data
	 * @return array
	 */
	private function compatibleCollection($data)
	{
		if ($data instanceof Collection) {
			$data = $data->all();
		}
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->compatibleCollection($value);
			}
		}
		return $data;
	}

	/**
	 * 预处理请求
	 */
	protected function pretreatment(& $data, & $curl)
	{
		// noop.
	}

	/**
	 * 解析HTTP返回结果
	 */
	protected function analyzeResponse($response, $error_code, $error_message)
	{
		if ($error_code === 401) {
			$this->error_message = $error_code . ': 访问被拒绝。';
			return null;
		}

		$ret = $response ? @json_decode($response) : null;
		if (is_null($ret)) {
			$this->error_message = $error_code . ': ' . ($error_message ?: '未知错误。');
			return null;
		}
		logger('analyzeResponse ' . get_class($this), [
            'data' => $ret
        ]); 
		if (@$ret->status !== 200) {
			$this->error_message = (@$ret->status ?: $error_code) . ': ' . (@$ret->message ?: '未知错误。');
			return null;
		}
		$this->error_message = '';
		return @$ret->data;
	}
}