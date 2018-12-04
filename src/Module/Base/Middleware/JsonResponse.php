<?php
namespace Module\Base\Middleware;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class JsonResponse
{

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$response = $next($request);

		// 忽略对重定向的处理。
		if ($response instanceof RedirectResponse) {
			return $response;
		}

		// 忽略对二进制响应的处理。
		if ($response instanceof BinaryFileResponse) {
			return $response;
		}

		// 忽略纯文本响应。
		if ($response instanceof Response) {
			if (str_contains($response->headers->get('Content-Type'), 'text/plain')) {
				return $response;
			}
		}

		// 忽略对已经是JSON的200响应处理。
		if ($response instanceof HttpJsonResponse && $response->getStatusCode() === 200) {
			return $response;
		}

		// 对数NULL类型进行处理。
		$recstr = null;
		$recstr = function ($data) use(&$recstr) {
			if ($data instanceof Arrayable) {
				$data = $data->toArray();
			}
			if (is_array($data)) {
				return array_map($recstr, $data);
			} elseif (is_null($data)) {
				return '';
			}
			return $data;
		};

		// JSON封装。
		$data = [
			'status' => 200,
			'message' => '',
			'data' => ''
		];
		if ($response instanceof Response || $response instanceof HttpJsonResponse) {
			$data['status'] = $response->getStatusCode();
			if ($data['status'] === 200) {
				$data['data'] = $response->getContent();
				if ($response->headers->get('Content-Type') === 'application/json') {
					$data['data'] = json_decode($data['data']);
				}
			} else {
				$data['message'] = $response->getContent();
				$data['data'] = '';

				if ($data['status'] === 422) {
					$message = @json_decode($data['message']);
					if ($message) {
						$data['message'] = head(head($message));
					}
				}
			}
		} else {
			$data['data'] = $response;
		}
		return response()->json($recstr($data));
	}
}
