<?php

namespace packages\base\Http;

interface Handler
{
    public function fire(Request $request, array $options): Response;
}
