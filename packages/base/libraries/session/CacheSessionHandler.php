<?php
namespace packages\base\session;

use packages\base\{HTTP, db as DatabaseManager, db\Mysqlidb, json, Date, Cache};

class CacheSessionHandler implements ISessionHandler {

	public static function setInCache(string $id, string $ip, int $createAt, array $data, int $ttl) {
		Cache::set("session-" . $id, array(
			'ip' => $ip,
			'create_at' => $createAt,
			'data' => $data,
		), $ttl);
	}

	/**
	 * @var bool
	 */
	protected $loaded = false;

	/**
	 * @var string|null
	 */
	protected $id;

	/**
	 * @var string|null
	 */
	protected $ip;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var int|null
	 */
	protected $createAt;

	/**
	 * @var bool
	 */
	protected $changed = false;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor of session handler with project options
	 */
	public function __construct(array $options) {
		$this->options = array_replace_recursive(array(
			'cookie' => array(
				'name' => 'PHPSESSID',
				'expire' => 3600,
				'path' => '/',
				'domain' => '',
				'sslonly' => false,
				'httponly' => false,
			),
			'connection' => 'default',
			'ip' => false,
			'gc' => array(
				'ttl' => 3600,
			),
		), $options);
	}

	public function __destruct() {
		$this->save();
	}

	/**
	 * @throws StartSessionException if cannot find database connection.
	 * @throws StartSessionException see db::register() method
	 */
	public function start(): void {
		if ($this->loaded or $this->data) {
			// Session already started
			return;
		}

		if (isset(http::$request['cookies'][$this->options['cookie']['name']])) {
			// Session cookie received and we should load it's data
			$this->id = http::$request['cookies'][$this->options['cookie']['name']];
			$this->load();
		} 
		if (!$this->loaded) {
			// Session cookie doesn't exists or it's invalid.
			$this->data = [];
		}
	}


	/**
	 * Get a key's value.
	 * 
	 * @param string $key The key to be extracted.
	 * @return mixed
	 */
	public function get(string $key) {
		return $this->data[$key] ?? null;
	}

	/**
	 * Set a value of a key to new value.
	 * 
	 * @param string $key The key to be modified.
	 * @param mixed $value new value.
	 * @return void
	 */
	public function set(string $key, $value): void {
		if (!$this->changed and (!isset($this->data[$key]) or $this->data[$key] !== $value)) {
			$this->changed = true;
		}
		$this->data[$key] = $value;

		if (!$this->loaded and !$this->id) {
			// Very first data for new session that hasn't a cookie, yet.
			// We must save it immediately and send the cookie before some other code cause sending body of http response.
			$this->save();
		}
	}

	/**
	 * Determine if a key is declared and is different than NULL
	 * 
	 * @param string $key The key to be checked.
	 * @return bool Returns TRUE if key exists and has any value other than NULL. FALSE otherwise. 
	 */
	public function isset(string $key): bool {
		return isset($this->data[$key]);
	}

	/**
	 * Unset a given key
	 * 
	 * @param string $key The key to be unset. 
	 * @return void
	 */
	public function unset(string $key): void {
		if (!$this->changed and isset($this->data[$key])) {
			$this->changed = true;
		}
		unset($this->data[$key]);
		if (!$this->loaded and !$this->id) {
			$this->save();
		}
	}

	/**
	 * Save new data of session on database.
	 * 
	 * @param bool $force It force method to update session regardless of modification status of data. default: false
	 * @return void
	 */
	public function save(bool $force = false): void {
		if (!$force and !$this->changed) {
			return;
		}
		if (!$this->data) {
			if ($this->loaded) {
				// Exist session is empty now so We remove it from storage but cookie still active.
				$this->destroy();
			}
			return;
		}
		if (!$this->loaded) {

			// If session's cookie is valid but it's data removed, id is not empty and we will try to create new session on current cookie.
			if (!$this->id) {
				$this->id = $this->generateID();
			}

			$success = false;
			while (!$success) {
				$success = !Cache::has("session-" . $this->id);
				if (!$success) {
					$this->id = $this->generateID();
				}
			}

			// in anycase we renew cookie expiration date.
			$this->setCookie();
			$this->loaded = true;
		}

		Cache::set("session-" . $this->id, array(
			'ip' => $this->ip ?? Http::$client['ip'],
			'create_at' => $this->createAt,
			'data' => $this->data,
		), $this->options['gc']['ttl']);

		$this->changed = false;
	}

	/**
	 * Delete session record from database but data still be available in data property.
	 * 
	 * @return void
	 */
	public function destroy(): void {
		if (!$this->id) {
			return;
		}
		Cache::delete("session-" . $this->id);
		$this->loaded = false;
		$this->changed = false;
	}

	/**
	 * Getter for session id.
	 * 
	 * @return string
	 */
	public function getID(): ?string {
		return $this->id;
	}

	/**
	 * Setter for session id.
	 * 
	 * @param string|null $id
	 * @return void
	 */
	public function setID(?string $id): void {
		$this->id = $id;
	}

	/**
	 * Getter for creator IP of session
	 * 
	 * @return string|null
	 */
	public function getIP(): ?string {
		return $this->ip;
	}

	/**
	 * Setter for IP
	 * 
	 * @param string|null
	 * @return void
	 */
	public function setIP(?string $ip): void {
		$this->ip = $ip;
	}

	/**
	 * Getter for create date of session
	 * 
	 * @return int|null
	 */
	public function getCreateAt(): ?int {
		return $this->createAt;
	}

	/**
	 * Setter for create date of session
	 * 
	 * @param int|null
	 * @return void
	 */
	public function setCreateAt(?int $createAt): void {
		$this->createAt = $createAt;
	}

	/**
	 * Get whole session data
	 * 
	 * @return array|null
	 */
	public function getData(): ?array {
		return $this->data;
	}

	/**
	 * Set new data for session
	 * 
	 * @param array $data
	 * @return void
	 */
	public function setData(array $data): void {
		$this->data = $data;
		$this->changed = true;
	}

	/**
	 * Check status of existance of session.
	 * 
	 * @return bool
	 */
	public function isLoaded(): bool {
		return $this->loaded;
	}

	/**
	 * Set status of existance of session.
	 * 
	 * @param bool $loaded
	 * @return void
	 */
	public function setLoaded(bool $loaded): void {
		$this->loaded = $loaded;
	}

	/**
	 * Touch the cache item which holding session's data to make it live longer.
	 * 
	 * @return void
	 */
	public function touch(): void {
		if (!$this->loaded) {
			return;
		}
		Cache::touch("session-" . $this->id, $this->options['gc']['ttl']);
	}

	/**
	 * Loads an session using its ID into data property.
	 * We assumed id isn't null.
	 * 
	 * @return void
	 */
	public function load(): void {
		$this->loaded = false;
		$row = Cache::get("session-" . $this->id);
		if (!$row) {
			return;
		}
		if ($this->options['ip'] and $row['ip'] and $row['ip'] != Http::$client['ip']) {
			return;
		}


		$this->loaded = true;
		$this->ip = $row['ip'];
		$this->createAt = $row['create_at'];
		$this->data = $row['data'];
		$this->touch();
	}

	/**
	 * Generates random ID using 16 cryptographic random bytes.
	 * 
	 * @return string
	 */
	protected function generateID(): string {
		return bin2hex(random_bytes(16));
	}

	/**
	 * Save session ID on client browser using cookies.
	 * 
	 * @throws StartSessionException if cannot set cookies
	 */
	protected function setCookie(): void {
		if (!HTTP::setcookie($this->options['cookie']['name'], $this->id, $this->options['cookie']['expire'] > 0 ? Date::time() + $this->options['cookie']['expire'] : 0, $this->options['cookie']['path'], $this->options['cookie']['domain'], $this->options['cookie']['sslonly'], $this->options['cookie']['httponly'])) {
			throw new StartSessionException("Cannot set cookie");
		}
	}
}
