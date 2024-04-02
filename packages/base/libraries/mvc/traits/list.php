<?php

namespace packages\base\views\traits;

trait listTrait
{
    protected $dataList = [];
    protected $currentPage;
    protected $totalPages;
    protected $itemsPage;
    protected $totalItems;

    public function setDataList($data)
    {
        $this->dataList = $data;
        if (!is_array($this->dataList)) {
            $this->dataList = [];
        }
    }

    public function getDataList()
    {
        return $this->dataList;
    }

    public function setPaginate($currentPage, $totalItems, $itemsPage)
    {
        $this->currentPage = $currentPage;
        $this->totalItems = $totalItems;
        $this->itemsPage = $itemsPage;
        $this->totalPages = ceil($totalItems / $itemsPage);
    }
}
