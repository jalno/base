<?php

namespace packages\base;

use Illuminate\Http\Response as LaravelResponse;
use packages\base\Response\File;
use packages\base\Views\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated
 */
class Response extends LaravelResponse
{
    protected ?View $view = null;
    protected bool $isAjax = false;
    protected bool $isApi = false;
    protected bool $isJson = false;
    protected ?File $file = null;
    protected $raw;
    protected ?string $output = null;
    protected bool $isAttachment = false;

    public function __construct(protected ?bool $status = null, protected array $data = [])
    {
        $request = request();
        $this->setAjax(boolval($request->query->get("ajax")));
        $this->setAPI(boolval($request->query->get("api")));
        if ($this->isAjax or $this->isApi) {
            $isJson = $request->query->get("json");
            $this->setJSON($isJson === null or boolval($isJson));
        }
        parent::__construct();
    }

    /**
     * @return $this
     */
    public function prepare(Request $request): static
    {
        if ($this->file) {
            $this->headers->set("Content-Type", $this->file->getMimeType());
            $this->headers->set("Content-Length", $this->file->getSize());
            if ($name = $this->file->getName())
            {
                $position = $this->isAttachment ? 'attachment' : 'inline';
                $this->headers->set('Content-Disposition', [$position, "filename=\"{$name}\""]);
            }
        } elseif ($this->isJson) {
            $this->exportViewToData();
            $this->setContent($this->getJsonData());
        } elseif ($this->view) {
            $this->view->setData($this->getStatus(), 'status');
            $this->setContent($this->view->output());
        }

        return parent::prepare($request);
    }

    public function sendContent(): static
    {
        if (!$this->file) {
            return parent::sendContent();
        }
        $this->file->output();
        return $this;
    }

    public function is_ajax(): bool
    {
        return $this->isAjax;
    }

    public function is_api(): bool
    {
        return $this->isApi;
    }

    public function setView(?View $view): void
    {
        $this->view = $view;
    }

    

    public function getView(): ?View
    {
        return $this->view;
    }

    public function setFile(File $file): void
    {
        $this->file = $file;
    }

    public function setStatus(?bool $status): void
    {
        $log = Log::getInstance();
        $this->status = $status;
        $log->debug('status changed to', $status);
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }


    public function setData($data, $key = null)
    {
        if ($key) {
            $this->data[$key] = $data;
        } else {
            $this->data = $data;
        }
        if ($this->view) {
            $this->view->setData($data, $key);
        }
    }

    public function getData(?string $key = null): mixed
    {
        return $key ? ($this->data[$key] ?? null) : $this->data;
    }

    public function go(string $url): void
    {
        if ($this->isAjax) {
            $this->data['redirect'] = $url;
        } else {
            $this->setStatusCode(302);
            $this->headers->set("Location", $url);
        }
        $this->setContent(null);
    }

    public function rawOutput(&$output): void
    {
        $this->setContent($output);
        $this->isJson = false;
    }

    public function setHeader(string $key, string $value): void
    {
        $this->headers->set($key, $value);
    }

    public function setHttpCode(int $code): void
    {
        $this->setStatusCode($code);
    }

    public function setMimeType(string $type, ?string $charset = null)
    {
        $this->headers->set('content-type', $charset ? [$type, 'charset=' . $charset] : $type);
    }

    public function forceDownload(): void
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
        $this->isAjax = $status;
        $this->setJSON($status);
    }

    public function setAPI(bool $status = true): void
    {
        $this->isApi = $status;
        $this->setJSON($status);
    }

    public function setJSON(bool $status = true): void
    {
        $this->isJson = $status;
    }

    private function exportViewToData(): void
    {
        if (!$this->view) {
            return;
        }
        if (method_exists($this->view, 'export')) {
            $target = '';
            if ($this->isApi) {
                $target = 'api';
            } elseif ($this->isAjax) {
                $target = 'ajax';
            }
            $export = $this->view->export($target);
            if (isset($export['data'])) {
                foreach ($export['data'] as $key => $val) {
                    $this->data[$key] = $val;
                }
            }
        }
        if ($this->view instanceof Form) {
            $errors = $this->view->getFormErrors();
            if ($errors) {
                foreach ($errors as $e) {
                    $e->setTraceMode(View\Error::NO_TRACE);
                }
                $this->setData($errors, 'error');
            }
        }
        $errors = $this->view->getErrors();
        if ($errors) {
            foreach ($errors as $e) {
                $e->setTraceMode(View\Error::NO_TRACE);
            }
            $this->setData($errors, 'error');
        }
    }



    private function getJsonData(): array
    {
        return array_replace([
            'status' => $this->status,
        ], $this->data);
    }

}
