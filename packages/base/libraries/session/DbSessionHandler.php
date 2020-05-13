<?php
namespace packages\base\session;

use packages\base\{HTTP, db as DatabaseManager, db\Mysqlidb, json, Date, Cache};

class DbSessionHandler implements ISessionHandler {

	/**
	 * @var MysqliDb|null
	 */
	protected $connection;

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
	 * @var array|null
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
	 * @var CacheSessionHandler|null
	 */
	protected $cache;

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
			'cache' => array(
				'enable' => true,
				'ttl' => 3600,
			),
			'gc' => array(
				'ttl' => 3600,
				'period' => 120,
				'trigger' => 'http',
			),
			'phpfallback' => false,
		), $options);

		$this->connection = DatabaseManager::connection($this->options['connection']);
		if (!$this->connection) {
			throw new StartSessionException("Cannot find database connection: " . $this->options['connection']);
		}

		if ($this->options['cache']['enable']) {
			$this->cache = new CacheSessionHandler(array(
				'cookie' => $this->options['cookie'],
				'ip' => $this->options['ip'],
				'gc' => array(
					'ttl' => $this->options['cache']['ttl'],
				),
			));
		}
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

		if ($this->options['gc']['trigger'] == 'http') {
			// Run garbage collector by receiving http requests
			$this->gc();
		}

		if (isset(Http::$request['cookies'][$this->options['cookie']['name']])) {
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

		if (!$this->loaded) {
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
		if ($this->loaded) {
			$this->connection
				->where("id", $this->id)
				->update("base_sessions", array(
					'lastuse_at' => Date::time(),
					'data' => json\encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				));
		} else {

			// If session's cookie is valid but it's data removed, id is not empty and we will try to create new session on current cookie.
			if (!$this->id) {
				$this->id = $this->generateID();
			}

			$success = false;
			while (!$success) {
				$success = $this->connection->insert("base_sessions", array(
					'id' => $this->id,
					'ip' => http::$client['ip'],
					'create_at' => Date::time(),
					'lastuse_at' => Date::time(),
					'data' => json\encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				));
				if (!$success) {
					$this->id = $this->generateID();
				}
			}
			$this->loaded = true;

			// in anycase we renew cookie expiration date.
			$this->setCookie();
		}
		$this->saveToCache();
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

		$this->connection
			->where("id", $this->id)
			->delete("base_sessions");

		if ($this->cache) {
			$this->cache->setID($this->id);
			$this->cache->setLoaded(true);
			$this->cache->destroy();
		}
		$this->loaded = false;
		$this->changed = false;
	}

	/**
	 * Remove old sessions.
	 * 
	 * @return void
	 */
	public function gc(bool $force = false): void {
		if (!$force) {
			$lastClear = Cache::get("packages.base.session.db.gc-last-run");
			if (Date::time() - $lastClear < $this->options['gc']['period']) {
				return;
			}
		}
		Cache::set("packages.base.session.db.gc-last-run", Date::time(), $this->options['gc']['period']);
		if ($this->cache) {
			$rows = $this->connection
				->where("lastuse_at", Date::time() - $this->options['gc']['ttl'], '<')
				->get("base_sessions", null, ['id']);
			foreach ($rows as $row) {
				$this->cache->setID($this->id);
				$this->cache->setLoaded(true);
				$this->cache->destroy();
			}
		}
		$this->connection
			->where("lastuse_at", Date::time() - $this->options['gc']['ttl'], '<')
			->delete("base_sessions");
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
	 * Update lastuse time to session live longer.
	 * We do not touch cache bacuase it will touch every time we load it.
	 * 
	 * @return void
	 */
	public function touch(): void {
		$this->connection
			->where("id", $this->id)
			->update("base_sessions", array(
				'lastuse_at' => Date::time()
			));
	}

	/**
	 * Loads an session using its ID into data property.
	 * We assumed id isn't null.
	 * 
	 * @return void
	 */
	protected function load(): void {
		$this->loaded = false;

		// At first we try Cache
		if ($this->cache) {
			$this->cache->setID($this->id);
			$this->cache->load();
			if ($this->cache->isLoaded()) {
				$this->loaded = true;
				$this->ip = $this->cache->getIP();
				$this->createAt = $this->cache->getCreateAt();
				$this->data = $this->cache->getData();
				$this->touch();
				return;
			}
		}

		// If cannot use cache, We will query the database.
		if (!$this->loaded) {
			$query = $this->connection->where("id", $this->id);
			if ($this->options['ip']) {
				$query->where("ip", Http::$client['ip']);
			}
			$row = $query->getOne("base_sessions");
			if ($row) {
				$this->loaded = true;
				$this->ip = $row['ip'];
				$this->createAt = $row['create_at'];
				$this->data = json\decode($row['data']);
				$this->touch();
				$this->saveToCache();
				return;
			}
		}

		// And finally if session is not present we will fallback to php native session.
		if (!$this->loaded and $this->options['phpfallback'] and session_status() != PHP_SESSION_ACTIVE) {
			session_name($this->options['cookie']['name']);
			$start = @session_start();
			if ($start) {
				session_write_close();
				if (!empty($_SESSION)) {
					$this->loaded = false;
					$this->ip = Http::$client['ip'];
					$this->createAt = Date::time();
					$this->data = json\decode(json\encode($_SESSION));
					$this->save(true);
					return;
				}
			}
		}

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
		if (!HTTP::setcookie($this->options['cookie']['name'], $this->id, $this->options['cookie']['expire'] > 0 ? time() + $this->options['cookie']['expire'] : 0, $this->options['cookie']['path'], $this->options['cookie']['domain'], $this->options['cookie']['sslonly'], $this->options['cookie']['httponly'])) {
			throw new StartSessionException("Cannot set cookie");
		}
	}

	/**
	 * Save session data to cache.
	 * 
	 * @return void
	 */
	protected function saveToCache(): void {
		if (!$this->cache) {
			return;
		}
		$this->cache->setID($this->id);
		$this->cache->setIP($this->ip);
		$this->cache->setCreateAt($this->createAt);
		$this->cache->setData($this->data);
		$this->cache->setLoaded(true);
		$this->cache->save();
	}
}
