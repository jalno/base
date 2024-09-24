<?php

namespace packages\base;

use Illuminate\Support\Facades\Vite;
use packages\base\Frontend\Events\ThrowDynamicData;
use packages\base\frontend\Source;
use packages\base\Frontend\Theme;
use packages\base\View\Events\AfterLoad;
use packages\base\View\Events\AfterOutput;
use packages\base\View\Events\BeforeLoad;
use Throwable;

class View
{
    /**
     * Find a view by it's name or parents name.
     *
     * @param string $viewName [parent] Full Qualified Class Name
     *
     * @throws NoViewException if cannot find any view by this name or parent name
     */
    public static function byName(string $viewName): View
    {
        $location = Theme::locate($viewName);
        $view = new $location['name']();
        $view->setSource($location['source']);

        return $view;
    }

    /** @var string[] */
    protected $title = [];

    /** @var string */
    protected $description = '';

    /** @var string|IO\file|null */
    protected $file;

    protected ?Source $source = null;

    /** @var array */
    protected $css = [];

    /** @var array */
    protected $js = [];

    protected $data = [];

    /** @var view\error[] */
    protected $errors = [];

    /** @var ThrowDynamicData */
    protected $dynamicData;

    /**
     * Construct the view and assets by Its frontend source.
     */
    public function __construct()
    {
        $this->dynamicData = new ThrowDynamicData();
        $this->dynamicData->setView($this);
    }

    /**
     * Setter for view title.
     *
     * @param string|string[] $title
     */
    public function setTitle($title): void
    {
        if (!is_array($title)) {
            $title = [$title];
        }
        $this->title = $title;
    }

    /**
     * Getter title.
     *
     * @return string|array if glue wasn't empty it will keep titles togheter and return string, in otherwise return array of title parts
     */
    public function getTitle(?string $glue = ' | ')
    {
        return $glue ? implode($glue, $this->title) : $this->title;
    }

    /**
     * Setter for description of view.
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Getter for description of view.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCSSAssets(): array
    {
        return $this->css;
    }

    public function getJSAssets(): array
    {
        return $this->js;
    }

    /**
     * Add an inline of css block.
     */
    public function addCSS(string $code, string $name = ''): void
    {
        $this->css[] = [
            'name' => $name,
            'type' => 'inline',
            'code' => $code,
        ];
    }

    /**
     * Add a link to css file.
     *
     * @param string $file    should be public URL
     * @param string $name    it will set to file URL if it was empty (or not set)
     * @param bool   $preload default: false
     */
    public function addCSSFile(string $file, string $name = '', $preload = false): void
    {
        if ('' == $name) {
            $name = $file;
        }
        $this->css[] = [
            'name' => $name,
            'type' => 'file',
            'file' => $file,
            'preload' => $preload,
        ];
    }

    /**
     * Find first css asset by given name.
     */
    public function removeCSS(string $name): void
    {
        foreach ($this->css as $key => $css) {
            if ($css['name'] == $name) {
                unset($this->css[$key]);

                return;
            }
        }
    }

    /**
     * Add an inline of Javascript block.
     */
    public function addJS(string $code, string $name = ''): void
    {
        $this->js[] = [
            'name' => $name,
            'type' => 'inline',
            'code' => $code,
        ];
    }

    /**
     * Add a link to Javascript file.
     *
     * @param string $file should be public URL
     * @param string $name it will set to file URL if it was empty (or not set)
     */
    public function addJSFile(string $file, string $name = ''): void
    {
        if ('' == $name) {
            $name = $file;
        }
        $this->js[] = [
            'name' => $name,
            'type' => 'file',
            'file' => $file,
        ];
    }

    /**
     * Find first Javascript asset by given name.
     */
    public function removeJS(string $name): void
    {
        foreach ($this->js as $key => $js) {
            if ($js['name'] == $name) {
                unset($this->js[$key]);

                return;
            }
        }
    }

    /**
     * Clear all CSS and Javascript assets from view.
     */
    public function clearAssets(): void
    {
        $this->js = [];
        $this->css = [];
    }

    /**
     * Clear all Javascript assets from view.
     */
    public function clearJSAssets(): void
    {
        $this->js = [];
    }

    /**
     * Clear all CSS assets from view.
     */
    public function clearCSSAssets(): void
    {
        $this->css = [];
    }

    /**
     * Getter for dynamic data.
     */
    public function dynamicData(): ThrowDynamicData
    {
        if (!$this->dynamicData) {
            $this->dynamicData = new ThrowDynamicData();
            $this->dynamicData->setView($this);
        }

        return $this->dynamicData;
    }

    /**
     * Set html file.
     */
    public function setFile(IO\File $file): void
    {
        $this->file = $file;
    }

    /**
     * Get html file.
     */
    public function getFile(): ?IO\File
    {
        if (is_string($this->file)) {
            $this->file = $this->source->getHome()->file($this->file);
        }

        return $this->file;
    }

    /**
     * Attach a data to view.
     */
    public function setData($data, ?string $key = null): void
    {
        if ($key) {
            $this->data[$key] = $data;
        } else {
            $this->data = $data;
        }
    }

    /**
     * Get data attached to data.
     *
     * @return mixed|false return null if cannot found the key
     */
    public function getData(?string $key = null)
    {
        if ($key) {
            return $this->data[$key] ??  null;
        } else {
            return $this->data;
        }
    }

    /**
     * Add a error to view.
     */
    public function addError(View\Error $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Return the first error.
     *
     * @return view\Error|null
     */
    public function getError()
    {
        return $this->errors ? $this->errors[0] : null;
    }

    /**
     * Getter for all errors.
     *
     * @return view\Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Getter for source of view.
     *
     * @return frontend\Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Setter for source.
     */
    public function setSource(Frontend\Source $source): void
    {
        $this->source = $source;
        Theme::setPrimarySource($source);
        $sources = Theme::byName($source->getName());
        foreach ($sources as $source) {
            foreach ($source->getAssets(['css', 'js']) as $asset) {
                if ('css' == $asset['type']) {
                    if (isset($asset['file'])) {
                        $this->addCSSFile(Theme::url($asset['file']), $asset['name'] ?? '', $asset['preload'] ?? false);
                    } elseif (isset($asset['inline'])) {
                        $this->addCSS($asset['inline'], $asset['name'] ?? '');
                    }
                } elseif ('js' == $asset['type']) {
                    if (isset($asset['file'])) {
                        $this->addJSFile(Theme::url($asset['file']), $asset['name'] ?? '');
                    } elseif (isset($asset['inline'])) {
                        $this->addJS($asset['inline'], $asset['name'] ?? '');
                    }
                }
            }
        }
    }

    /**
     * Ouput the html file.
     */
    public function output(): string
    {
        $this->loadHTMLFile();
        if (!$this->file) {
            return "";
        }
        (new BeforeLoad($this))->trigger();
        $this->dynamicData()->trigger();
        if (method_exists($this, '__beforeLoad')) {
            $this->__beforeLoad();
        }
        (new AfterLoad($this))->trigger();
        $this->dynamicData()->addAssets();


        $obLevel = ob_get_level();
        ob_start();
        try {
            require $this->file->getPath();
        } catch (Throwable $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

           throw $e;
        }

        $result = ltrim(ob_get_clean());

        (new AfterOutput($this))->trigger();

        return $result;
    }

    /**
     * Genrate html tags to load CSS assets by browser.
     */
    protected function loadCSS(): void
    {
        foreach ($this->css as $css) {
            if ('inline' == $css['type']) {
                echo "<style>\n{$css['code']}\n</style>\n";
            }
        }
        echo Vite::__invoke("resources/css/{$this->source->getName()}.css")->toHtml();
    }

    /**
     * Genrate html tags to load Javascript assets by browser.
     */
    protected function loadJS(): void
    {
        foreach ($this->js as $js) {
            if ('inline' == $js['type']) {
                echo "<script>\n{$js['code']}\n</script>\n";
            }
        }
        echo Vite::__invoke("resources/js/{$this->source->getName()}.js")->toHtml();
    }

    /**
     * Locate html file and put in $file property based on this priority:
     *  1. $file property of extended class.
     *  2. theme.json file.
     *  3. same path of view file in frontend source in it's html directory.
     *
     * @throws IO\NotFoundException if located file doesn't exist
     */
    protected function loadHTMLFile(): void
    {
        if (!$this->source) {
            return;
        }
        if ($this->file) {
            if (!is_string($this->file)) {
                return;
            }
            $this->file = $this->source->getHome()->file($this->file);
        } else {
            $reflection = new \ReflectionClass(get_class($this));
            $filename = $reflection->getFileName();
            $needle = DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR;

            if (($pos = stripos($filename, $needle)) !== false) {
                $this->file = $this->source->getHome()->file("html" . DIRECTORY_SEPARATOR . substr($filename, $pos + strlen($needle)));
            }
        }
        if ($this->file and !$this->file->exists()) {
            throw new IO\NotFoundException($this->file, "Notfound html file for " . get_class($this) . " in " . $this->file->getPath());
        }
    }
}
