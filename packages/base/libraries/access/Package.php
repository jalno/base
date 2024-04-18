<?php

namespace packages\base\access\Package;

function controller(&$package, $controller)
{
    return $package->checkPermission($controller);
}
