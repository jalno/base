<?php

namespace packages\base\Frontend;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use packages\base\IO;
use packages\base\IO\Directory\Local as LocalDirectory;
use packages\base\IO\File\Local as LocalFile;
use packages\base\Json;
use packages\base\LanguageContainerTrait;
use packages\base\ListenerContainerTrait;
use packages\base\Package;

class Source extends ServiceProvider
{
    use LanguageContainerTrait;
    use ListenerContainerTrait;

    /**
     * construct a theme from its jalno.json.
     *
     * @throws IO\NotFoundException     if cannot find theme.json in the home directory
     * @throws IO\SourceConfigException if source doesn't have name
     * @throws IO\SourceConfigException if event listener was invalid
     */
    public static function fromDirectory(Application $app, Package $package, LocalDirectory $home): Source
    {
        $config = $home->file('jalno.json');
        if (!$config->exists()) {
            throw new IO\NotFoundException($config);
        }
        $config = json\decode($config->read());
        if (!isset($config['name'])) {
            throw new SourceConfigException("source doesn't have name", $home);
        }
        $source = new Source($app, $package, $home, $config['name']);
        if (isset($config['assets'])) {
            foreach ($config['assets'] as $asset) {
                $source->addAsset($asset);
            }
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

        return $source;
    }

    /** @var array */
    private array $assets = [];

    public function getHome(): LocalDirectory
    {
        return $this->home;
    }

    /**
     * Get home directory path.
     * @deprecated
     */
    public function getPath(): string
    {
        return $this->home->getPath();
    }

    /**
     * @deprecated
     */
    public function getFile(string $path): LocalFile
    {
        return $this->home->file($path);
    }

    /**
     * Get jalno.json file.
     */
    public function getConfigFile(): LocalFile
    {
        return $this->home->file('jalno.json');
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
     * @throws SourceAssetException if type was invalid
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
                throw new SourceAssetException('Unkown asset type', $this->home->getPath());
        }
    }

    /**
     * Add code asset to source.
     *
     * @param array $asset should contain "type" index within these values: "js", "css", "less", "scss", "sass", "ts", "package"
     *                     also every asset could have a "name" and "file" and "inline" block code
     *
     * @throws SourceAssetFileException if file does not exist
     * @throws SourceAssetFileException if there no file and no Code for asset
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
            $file = $this->home->file($asset['file']);
            if ('node_modules/' != substr($asset['file'], 0, 13) and !$file->exists()) {
                throw new SourceAssetFileException($asset['file'], $this->home->getPath());
            }

            $assetData['file'] = $asset['file'];
        } elseif (isset($asset['inline'])) {
            $assetData['inline'] = $asset['inline'];
        } else {
            throw new SourceAssetException('No file and no Code for asset', $this->home->getPath());
        }
        $this->assets[] = $assetData;
    }

    /**
     * Add npm package to source.
     *
     * @param array $asset should contain "name" index, and could have a "version" index
     *
     * @throws SourceAssetException if asset doesn't have "name" index
     * @throws SourceAssetException if "version" was invalid
     */
    private function addNodePackageAsset(array $asset): void
    {
        if (!isset($asset['name'])) {
            throw new SourceAssetException('No node package name', $this->home->getPath());
        }
        if (isset($asset['version'])) {
            if (!preg_match("/^[\^\>\=\~\<\*]*[\\d\\w\\.\\-]+$/", $asset['version'])) {
                throw new SourceAssetException('invalid node package version', $this->home->getPath());
            }
        }
        $this->assets[] = $asset;
    }

    /**
     * Get list of assets.
     *
     * @param string|string[]|null $type filter type
     */
    public function getAssets(string|array|null $type = null): array
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
            $url .= Request::getSchemeAndHttpHost();
        }

        return $url . '/packages/' . $this->package->getName() . "/" . $this->home->getRelativePath($this->package->getHome()) . "/" . $file;
    }

    public function register(): void
    {
        $this->addLinkForAssets();
    }

    protected function addLinkForAssets(): void
    {
        $links = config('filesystems.links', [public_path('storage') => storage_path('app/public')]);
        $symlink = public_path('packages/' . $this->package->getName() . "/" . $this->home->getRelativePath($this->package->getHome()));
        if (!is_dir(dirname($symlink))) {
            mkdir(dirname($symlink), 0755, true);
        }
        $links[$symlink] = $this->home->getPath();
        config()->set("filesystems.links", $links);
    }


    private function __construct(Application $app, private Package $package, private LocalDirectory $home , private string $name)
    {
        parent::__construct($app);
    }
}
