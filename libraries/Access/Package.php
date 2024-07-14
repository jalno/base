<?php

namespace packages\base\Access\Package;

function controller(&$package, $controller)
{
    return $package->checkPermission($controller);
}
