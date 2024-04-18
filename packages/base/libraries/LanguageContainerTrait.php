<?php

namespace packages\base;

trait LanguageContainerTrait
{
    /** @var array */
    private $langs = [];

    /**
     * Register translator file in package settings.
     *
     * @param string $code should be a valid language code
     * @param string $file should be a file name inside the package home directory
     *
     * @throws packages\base\translator\LangAlreadyExists if this language already added
     * @throws packages\base\translator\InvalidLangCode   if code was invalid
     * @throws packages\base\IO\NotFoundException         if cannot find the provided file
     */
    public function addLang(string $code, string $file): void
    {
        if (isset($this->langs[$code])) {
            throw new Translator\LangAlreadyExists($code);
        }
        if (!Translator::is_validCode($code)) {
            throw new Translator\InvalidLangCode($code);
        }
        $file = $this->home->file($file);
        if (!$file->exists()) {
            throw new IO\NotFoundException($file);
        }
        $this->langs[$code] = $file;
    }

    /**
     * Get translator file of given language if exists.
     *
     * @return packages\base\IO\file|null
     */
    public function getLang(string $code): ?IO\file
    {
        return $this->langs[$code] ?? null;
    }

    /**
     * Add Package Supported langs.
     *
     * @return void
     */
    public function addLangs()
    {
        $activeLangs = Options::get('packages.base.translator.active.langs');
        foreach ($this->langs as $code => $file) {
            if (is_array($activeLangs) and !in_array($code, $activeLangs)) {
                continue;
            }
            Translator::addLang($code);
        }
    }

    /**
     * Register a translator file.
     */
    public function registerTranslates(string $code): void
    {
        if ($file = $this->getLang($code)) {
            $lang = new Translator\Language($code);
            if (!$lang->loadByFile($file->getPath())) {
                return;
            }
            Translator::import($lang);
        }
    }
}
