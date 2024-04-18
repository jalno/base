<?php

namespace packages\base\Frontend;

use packages\base\AutoloadContainerTrait;
use packages\base\IO;
use packages\base\Json;
use packages\base\LanguageContainerTrait;
use packages\base\ListenerContainerTrait;
use packages\base\Router;

class Source
{
    use AutoloadContainerTrait;
    use LanguageContainerTrait;
    use ListenerContainerTrait;

    /**
     * construct a theme from its package.json.
     *
     * @return packages\base\frontend\Source
     *
     * @throws packages\base\IO\NotFoundException     if cannot find theme.json in the home directory
     * @throws packages\base\IO\SourceConfigException if source doesn't have name
     * @throws packages\base\IO\SourceConfigException if event listener was invalid
     */
    public static function fromDirectory(IO\Directory $home): Source
    {
        $config = $home->file('theme.json');
        if (!$config->exists()) {
            throw new IO\NotFoundException($config);
        }
        $config = json\decode($config->read());
        if (!isset($config['name'])) {
            throw new SourceConfigException("source doesn't have name", $home);
        }
        $source = new Source($home, $config['name']);
        if (isset($config['parent'])) {
            $source->setParent($config['parent']);
        }
        if (isset($config['assets'])) {
            foreach ($config['assets'] as $asset) {
                $source->addAsset($asset);
            }
        }
        if (isset($config['autoload'])) {
            $source->setAutoload($config['autoload']);
        }
        if (isset($config['bootstrap'])) {
            $source->setBootstrap($config['bootstrap']);
        }
        if (isset($config['languages'])) {
            foreach ($config['languages'] as $lang => $file) {
                $source->addLang($lang, $file);
            }
        }
        if (isset($config['events'])) {
            foreach ($config['events'] as $event) {
                if (!isset($event['name'], $event['listener'])) {
                    throw new SourceConfigException('invalid event', $home);
                }
                $source->addEvent($event['name'], $event['listener']);
            }
        }
        if (isset($config['views'])) {
            foreach ($config['views'] as $view) {
                if (!isset($view['name'], $view['file'])) {
                    throw new SourceConfigException('invalid view: '.print_r($view, true), $home);
                }
                $source->addHTMLFile($view['name'], $view['file']);
            }
        }

        return $source;
    }

    /** @var IO\Directory */
    private $home;

    /** @var string */
    private $name;

    /** @var string|null */
    private $parent;

    /** @var packages\base\IO\file|null */
    private $bootstrap;

    /** @var array */
    private $views = [];

    /** @var array */
    private $assets = [];

    /** @var array */
    private $htmlFiles = [];

    /**
     * Get home directory of source.
     */
    public function getHome(): IO\Directory
    {
        return $this->home;
    }

    /**
     * Get home directory path.
     */
    public function getPath(): string
    {
        return $this->home->getPath();
    }

    /**
     * Get file.
     */
    public function getFile(string $path): IO\File
    {
        return $this->home->file($path);
    }

    /**
     * Get theme.json file.
     */
    public function getConfigFile(): IO\File
    {
        return $this->getFile('theme.json');
    }

    /**
     * Set parent frontend.
     */
    public function setParent(?string $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get parent frontend.
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * get source frontend.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add new asset.
     *
     * @param array $asset should contain "type" index within these values: "js", "css", "less", "scss", "sass", "ts", "package"
     *
     * @throws packages\base\frontend\SourceAssetException if type was invalid
     */
    public function addAsset(array $asset): void
    {
        switch ($asset['type']) {
            case 'js':
            case 'css':
            case 'less':
            case 'scss':
            case 'sass':
            case 'ts':
            case 'tsx':
                $this->addCodeAsset($asset);
                break;
            case 'package':
                $this->addNodePackageAsset($asset);
                break;
            default:
                throw new SourceAssetException('Unkown asset type', $this->getPath());
        }
    }

    /**
     * Add code asset to source.
     *
     * @param array $asset should contain "type" index within these values: "js", "css", "less", "scss", "sass", "ts", "package"
     *                     also every asset could have a "name" and "file" and "inline" block code
     *
     * @throws packages\base\frontend\SourceAssetFileException if file does not exist
     * @throws packages\base\frontend\SourceAssetFileException if there no file and no Code for asset
     */
    private function addCodeAsset(array $asset): void
    {
        $assetData = [
            'type' => $asset['type'],
        ];
        if (isset($asset['name'])) {
            $assetData['name'] = $asset['name'];
        }
        if (isset($asset['file'])) {
            $file = $this->getFile($asset['file']);
            if ('node_modules/' != substr($asset['file'], 0, 13) and !$file->exists()) {
                throw new SourceAssetFileException($asset['file'], $this->home->getPath());
            }

            $assetData['file'] = $asset['file'];
        } elseif (isset($asset['inline'])) {
            $assetData['inline'] = $asset['inline'];
        } else {
            throw new SourceAssetException('No file and no Code for asset', $this->getPath());
        }
        $this->assets[] = $assetData;
    }

    /**
     * Add npm package to source.
     *
     * @param array $asset should contain "name" index, and could have a "version" index
     *
     * @throws packages\base\frontend\SourceAssetException if asset doesn't have "name" index
     * @throws packages\base\frontend\SourceAssetException if "version" was invalid
     */
    private function addNodePackageAsset(array $asset): void
    {
        if (!isset($asset['name'])) {
            throw new SourceAssetException('No node package name', $this->getPath());
        }
        if (isset($asset['version'])) {
            if (!preg_match("/^[\^\>\=\~\<\*]*[\\d\\w\\.\\-]+$/", $asset['version'])) {
                throw new SourceAssetException('invalid node package version', $this->getPath());
            }
        }
        $this->assets[] = $asset;
    }

    /**
     * Get list of assets.
     *
     * @param string|string[]|null $type filter type
     */
    public function getAssets($type = null): array
    {
        if (is_string($type)) {
            $type = [$type];
        }
        $assets = [];
        foreach ($this->assets as $asset) {
            if (null === $type or in_array($asset['type'], $type)) {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    /**
     * Check existance of a file asset.
     *
     * @param string $file path with in source
     */
    public function hasFileAsset(string $file): bool
    {
        if ($this->home->file($file)->exists()) {
            return true;
        }
        foreach ($this->assets as $asset) {
            if (isset($asset['file']) and $asset['file'] == $file) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get URL of  a file for direct access by browser.
     */
    public function url(string $file, bool $absolute = false): string
    {
        $url = '';
        if ($absolute) {
            $hostname = Router::gethostname();
            if (!$hostname and $defaultHostnames = Router::getDefaultDomains()) {
                $hostname = $defaultHostnames[0];
            }
            $url .= Router::getscheme().'://'.$hostname;
        }

        return $url.'/'.$this->home->file($file)->getPath();
    }

    /**3 */
    public function addView($view)
    {
        if (isset($view['name'])) {
            if (!isset($view['file']) or is_file("{$this->getPath()}/{$view['file']}")) {
                if ('\\' == substr($view['name'], 0, 1)) {
                    $view['name'] = substr($view['name'], 1);
                }
                $newview = [
                    'name' => $view['name'],
                ];
                if (isset($view['parent'])) {
                    if ('\\' == substr($view['parent'], 0, 1)) {
                        $view['parent'] = substr($view['parent'], 1);
                    }
                    $newview['parent'] = $view['parent'];
                }
                if (isset($view['file'])) {
                    $newview['file'] = $view['file'];
                }
                $this->views[] = $newview;
            } else {
                throw new SourceViewFileException($view['file'], $this->getPath());
            }
        } else {
            throw new SourceViewException('View name is not set', $this->getPath());
        }
    }

    /**
     * Rememmber a html file for a view.
     *
     * @throws packages\base\IO\NotFoundException if cannot find file in the home directory
     */
    public function addHTMLFile(string $view, string $path): void
    {
        $view = $this->prependNamespaceIfNeeded($view);
        $file = $this->getFile($path);
        if (!$file->exists()) {
            throw new IO\NotFoundException($file);
        }
        $this->htmlFiles[$view] = $file;
    }

    /**
     * Get html file using view class name.
     *
     * @param string $view Full Qualified Class Name
     *
     * @return packages\base\IO\file|null
     */
    public function getHTMLFile(string $view)
    {
        return $this->htmlFiles[strtolower($view)] ?? null;
    }

    /**
     * Prepend theme namespace to given namespace if hasn't any other theme namespace.
     *
     * @return string
     */
    public function prependNamespaceIfNeeded(string $namespace)
    {
        $namespace = ltrim(str_replace('/', '\\', $namespace), '\\');
        if (!preg_match('/^(themes|packages)(?:\\\\[a-zA-Z0-9-\\_]+)+/', $namespace)) {
            $namespace = "themes\\{$this->name}\\".$namespace;
        }

        return strtolower($namespace);
    }

    /**
     * @param packages\base\IO\directory $home
     */
    private function __construct(IO\directory $home, string $name)
    {
        $this->home = $home;
        $this->name = $name;
    }
}
