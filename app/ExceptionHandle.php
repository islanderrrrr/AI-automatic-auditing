<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 如果是 AJAX 请求或期望 JSON 响应，返回 JSON 格式错误
        if ($this->isJsonRequest($request)) {
            $code = 500;
            if ($e instanceof HttpException) {
                $code = $e->getStatusCode();
            }
            
            return json([
                'code' => $code,
                'msg' => $e->getMessage(),
                'trace' => config('app.app_debug') ? $e->getTraceAsString() : null
            ], $code);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
    
    /**
     * 检查是否为 JSON 请求
     *
     * @param \think\Request $request
     * @return bool
     */
    private function isJsonRequest($request): bool
    {
        // AJAX 请求
        if ($request->isAjax()) {
            return true;
        }
        
        // 期望 JSON 响应（使用 acceptJson 方法，如果存在）
        if (method_exists($request, 'acceptJson') && $request->acceptJson()) {
            return true;
        }
        
        // 检查 Accept 头
        if (strpos($request->header('Accept', ''), 'application/json') !== false) {
            return true;
        }
        
        // 检查 Content-Type 头（某些客户端可能设置此头）
        if (strpos($request->header('Content-Type', ''), 'application/json') !== false) {
            return true;
        }
        
        return false;
    }
}
