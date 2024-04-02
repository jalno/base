<?php

namespace packages\base\views;

use packages\base\db\dbObject;
use packages\base\view;
use packages\base\views\traits\listTrait;

class listview extends view
{
    use listTrait;

    public function export()
    {
        return [
            'data' => [
                'items' => dbObject::objectToArray($this->dataList),
                'items_per_page' => (int) $this->itemsPage,
                'current_page' => (int) $this->currentPage,
                'total_items' => (int) $this->totalItems,
            ],
        ];
    }
}
