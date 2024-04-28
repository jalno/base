<?php

namespace packages\base\Views;

use packages\base\DB\DBObject;
use packages\base\View;
use packages\base\Views\Traits\ListTrait;

class ListView extends View
{
    use ListTrait;

    public function export()
    {
        return [
            'data' => [
                'items' => DBObject::objectToArray($this->dataList),
                'items_per_page' => (int) $this->itemsPage,
                'current_page' => (int) $this->currentPage,
                'total_items' => (int) $this->totalItems,
            ],
        ];
    }
}
