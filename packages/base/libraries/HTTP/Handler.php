<?php

namespace packages\base\HTTP;

interface Handler
{
    public function fire(Request $request, array $options): Response;
}
