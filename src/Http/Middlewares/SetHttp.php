<?php
namespace packages\base\Http\Middlewares;

use Illuminate\Http\Request;
use packages\base\Http;
use Symfony\Component\HttpFoundation\Response;

class SetHttp {
	public function handle(Request $request, \Closure $next): Response
	{
		Http::set($request);

		return $next($request);
	}
}