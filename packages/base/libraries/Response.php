<?php

namespace packages\base;

use packages\base\Response\File;
use packages\base\Views\Form;

class Response
{
    protected $status;
    protected $data;
    protected $view;
    protected $ajax = false;
    protected $api = false;
    protected $json = false;
    protected $xml = false;
    protected $file;
    protected $raw;
    protected $output;
    protected $headers = [];
    protected $httpcode;
    protected $isAttachment = false;

    public function __construct($status = null, $data = [])
    {
        $this->status = $status;
        $this->data = $data;

        $this->setAjax(isset(HTTP::$request['get']['ajax']) and HTTP::$request['get']['ajax']);
        $this->setAPI(isset(HTTP::$request['get']['api']) and HTTP::$request['get']['api']);

        if ($this->is_ajax() or $this->is_api()) {
            $this->setJSON(!isset(HTTP::$request['get']['json']) or HTTP::$request['get']['json']);
            $this->setXML(isset(HTTP::$request['get']['xml']) and HTTP::$request['get']['xml']);
        }
    }

    public function is_ajax()
    {
        return $this->ajax;
    }

    public function is_api()
    {
        return $this->api;
    }

    public function setView(View $view)
    {
        $this->view = $view;
    }

    protected function prepareView()
    {
        $log = Log::getInstance();
        if ($this->view) {
            if (method_exists($this->view, 'export')) {
                $target = '';
                if ($this->api) {
                    $target = 'api';
                } elseif ($this->ajax) {
                    $target = 'ajax';
                }
                $log->debug('call export method for colleting data');
                $export = $this->view->export($target);
                $log->reply('Success');
                if (isset($export['data'])) {
                    foreach ($export['data'] as $key => $val) {
                        $this->data[$key] = $val;
                    }
                }
            }
            if ($this->view instanceof Form) {
                $log->debug('view is a form, colleting form errors');
                $errors = $this->view->getFormErrors();
                if ($errors) {
                    foreach ($errors as $e) {
                        $e->setTraceMode(View\Error::NO_TRACE);
                    }
                    $this->setData($errors, 'error');
                }
            }
            $log->debug('colleting errors');
            $errors = $this->view->getErrors();
            if ($errors) {
                foreach ($errors as $e) {
                    $e->setTraceMode(View\Error::NO_TRACE);
                }
                $this->setData($errors, 'error');
            }
        }
    }

    public function getView()
    {
        return $this->view;
    }

    public function setFile(File $file)
    {
        $this->file = $file;
    }

    public function setStatus($status)
    {
        $log = Log::getInstance();
        $this->status = $status;
        $log->debug('status changed to', $status);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setData($data, $key = null)
    {
        $log = Log::getInstance();
        if ($key) {
            $log->debug('data', $key, 'set to', $data);
            $this->data[$key] = $data;
        } else {
            $this->data = $data;
        }
        if ($this->view) {
            $log->debug('also passed to view');
            $this->view->setData($data, $key);
        }
    }

    public function getData($key = null)
    {
        if ($key) {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        } else {
            return $this->data;
        }
    }

    public function json()
    {
        $log = Log::getInstance();
        $log->debug('set http header to json');
        HTTP::tojson();
        $log->debug('encode json response');

        return json\encode(array_merge([
            'status' => $this->status,
        ], $this->data));
    }

    public function go($url)
    {
        if ($this->ajax) {
            $this->data['redirect'] = $url;
        } else {
            HTTP::redirect($url);
        }
    }

    public function rawOutput(&$output)
    {
        $this->raw = true;
        $this->output = $output;
        $this->file = null;
        $this->json = false;
    }

    public function setHeader($key, $value)
    {
        $log = Log::getInstance();
        $log->debug('set http header', $key, 'to', $value);
        $this->headers[$key] = $value;
    }

    public function setHttpCode($code)
    {
        $log = Log::getInstance();
        $log->debug('set http response code to', $code);
        $this->httpcode = $code;
    }

    public function setMimeType($type, $charset = null)
    {
        if ($charset) {
            $this->setHeader('content-type', $type.'; charset='.$charset);
        } else {
            $this->setHeader('content-type', $type);
        }
    }

    public function sendHeaders()
    {
        if ($this->httpcode) {
            HTTP::setHttpCode($this->httpcode);
        }
        foreach ($this->headers as $key => $val) {
            HTTP::setHeader($key, $val);
        }
    }

    public function send()
    {
        $log = Log::getInstance();
        $log->info('send response');
        $this->sendHeaders();
        if ($this->file) {
            HTTP::setMimeType($this->file->getMimeType());
            HTTP::setLength($this->file->getSize());
            if ($name = $this->file->getName()) {
                $position = $this->isAttachment ? 'attachment' : 'inline';
                HTTP::setHeader('content-disposition', "{$position}; filename=\"{$name}\"");
            }
            $this->file->output();
        } elseif ($this->json) {
            $this->prepareView();
            echo $this->json();
        } elseif ($this->raw) {
            echo $this->output;
        } elseif ($this->view) {
            $this->view->setData($this->getStatus(), 'status');
            $this->view->output();
        }
        $log->reply('Success');
    }

    public function forceDownload()
    {
        $this->isAttachment = true;
    }

    public function __serialize(): array
    {
        return [
            'status' => $this->getStatus(),
            'data' => $this->getData(),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->setStatus($data['status']);
        $this->setData($data['data']);
    }

    public function setAjax(bool $status = true): void
    {
        $this->ajax = $status;
        $this->setJSON($status);
    }

    public function setAPI(bool $status = true): void
    {
        $this->api = $status;
        $this->setJSON($status);
    }

    public function setJSON(bool $status = true): void
    {
        $this->json = $status;
    }

    public function setXML(bool $status = true): void
    {
        $this->xml = $status;
    }
}
