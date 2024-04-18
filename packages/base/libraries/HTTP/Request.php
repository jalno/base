<?php

namespace packages\base\HTTP;

use packages\base\IO\File;

class Request
{
    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var array<string,mixed>
     */
    protected $query = [];

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $scheme = 'http';

    /**
     * @var string|null
     */
    protected $ip;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int|null
     */
    protected $port;

    /**
     * @var array<string,string>
     */
    protected $headers = [];

    /**
     * @var string|array<string,mixed>|File|null
     */
    protected $body;

    /**
     * @var array{"type": "http"|"https"|"socks4"|"socks5","hostname": string,"port": int,"username"?:string,"password"?:string}|null
     */
    protected $proxy;

    /**
     * @var File|null
     */
    protected $outputFile;

    /**
     * @var string|null
     */
    protected $outgoingIP;

    public function __construct(string $host, string $uri)
    {
        $this->setHost($host);
        $this->setURI($uri);
    }

    public function setMethod(string $method): void
    {
        $this->method = strtoupper($method);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setURI(string $uri): void
    {
        $this->uri = ltrim($uri, '/');
    }

    public function getURI(): string
    {
        return $this->uri;
    }

    /**
     * @param array<string,mixed> $query
     */
    public function setQuery(array $query): void
    {
        $this->query = $query;
    }

    /**
     * @return array<string,mixed>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getURL(): string
    {
        $url = $this->scheme.'://'.$this->host;
        if ($this->port) {
            $url .= ':'.$this->port;
        }
        $url .= '/'.$this->uri;
        if ($this->query) {
            $url .= '?'.http_build_query($this->query);
        }

        return $url;
    }

    public function setScheme(string $scheme): void
    {
        $this->scheme = $scheme;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setIP(?string $ip): void
    {
        $this->ip = $ip;
    }

    public function getIP(): ?string
    {
        return $this->ip;
    }

    public function setReferer(?string $referer): void
    {
        $this->setHeader('Referer', $referer);
    }

    public function getReferer(): ?string
    {
        return $this->getHeader('Referer');
    }

    public function setHeader(string $name, ?string $value): void
    {
        if (null === $value) {
            unset($this->headers[$name]);

            return;
        }
        $this->headers[$name] = $value;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * @param array<string,string> $headers
     */
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string|array<string,mixed>|File|null $body
     */
    public function setBody($body): void
    {
        $this->body = $body;
    }

    /**
     * @return string|array<string,mixed>|File|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array{"type": "http"|"https"|"socks4"|"socks5","hostname": string,"port": int,"username"?:string,"password"?:string}|null $proxy
     */
    public function setProxy(?array $proxy): void
    {
        $this->proxy = $proxy;
    }

    /**
     * @return array{"type": "http"|"https"|"socks4"|"socks5","hostname": string,"port": int,"username"?:string,"password"?:string}|null
     */
    public function getProxy(): ?array
    {
        return $this->proxy;
    }

    public function saveAs(?File $file): void
    {
        $this->outputFile = $file;
    }

    public function getSaveAs(): ?File
    {
        return $this->outputFile;
    }

    public function setOutgoingIP(?string $outgoingIP): void
    {
        $this->outgoingIP = $outgoingIP;
    }

    public function getOutgoingIP(): ?string
    {
        return $this->outgoingIP;
    }
}
