<?php
namespace packages\base\http;
interface handler{
	public function fire(request $request, array $options):response;
}