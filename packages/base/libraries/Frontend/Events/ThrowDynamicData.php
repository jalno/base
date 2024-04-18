<?php

namespace packages\base\Frontend\Events;

use packages\base\Event;
use packages\base\Json;
use packages\base\Options;
use packages\base\Translator;
use packages\base\View;

class ThrowDynamicData extends Event
{
    private $view;
    private $data = [
        'options' => [],
    ];

    public function setView(View $view)
    {
        $this->view = $view;
    }

    public function getView()
    {
        return $this->view;
    }

    public function setOption(string $name)
    {
        $this->data['options'][$name] = Options::get($name);
    }

    public function deleteOption(string $name)
    {
        unset($this->data['options'][$name]);
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->data['options']);
    }

    public function setData(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    public function deleteData(string $name)
    {
        unset($this->data[$name]);
    }

    public function hasData($name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function getData($name = null)
    {
        if ($name) {
            return array_key_exists($name, $this->data) ? $this->data[$name] : null;
        }

        return $this->data;
    }

    private function setDefaultDynamicData()
    {
        $this->setOption('packages.base.translator.defaultlang');
        $this->setOption('packages.base.translator.changelang');
        $this->setOption('packages.base.translator.changelang.type');
        $this->setOption('packages.base.routing.www');
        $this->setData('translator', [
            'lang' => Translator::getCodeLang(),
        ]);
    }

    public function trigger()
    {
        $this->setDefaultDynamicData();
        parent::trigger();
    }

    public function addAssets()
    {
        $js = '';
        foreach ($this->data as $key => $value) {
            $js .= "var {$key} = ".Json\encode($value).';';
        }
        if ($js) {
            $this->view->addJS($js, 'dynamicData');
        }
    }
}
