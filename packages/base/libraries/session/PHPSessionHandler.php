<?php
namespace packages\base\session;

use packages\base\Http;
use packages\base\Packages;
use packages\base\IO\Directory;

class PHPSessionHandler implements ISessionHandler {

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
	protected $options;

	/**
	 * Constructor of session handler with project options
	 */
	public function __construct(array $options) {
		$this->options = array_replace_recursive(array(
			'cookie' => array(
				'name' => 'PHPSESSID',
				'expire' => 0,
				'path' => '/',
				'domain' => '',
				'sslonly' => false,
				'httponly' => false,
			),
			'save_dir' => Packages::package('base')
				->getStorage('sessions')
				->getRoot(),
			'ip' => false,
		), $options);
	}

	/**
	 * Start session
	 * 
	 * @throws StartSessionException if cannot start session
	 * @return void
	 */
	public function start(): void {
		if (session_status() != PHP_SESSION_ACTIVE) {
			if (isset($this->options['save_dir']) and $this->options['save_dir']) {
				if (!is_string($this->options['save_dir']) and !$this->options['save_dir'] instanceof Directory\Local) {
					throw new \InvalidArgumentException(
						sprintf('sessions directory should be string or local directory, %s given', $this->options['save_dir'])
					);
				}
				$path = is_string($this->options['save_dir']) ? $this->options['save_dir'] : $this->options['save_dir']->getPath();
				session_save_path($path);
			}
			session_set_cookie_params($this->options['cookie']['expire'], $this->options['cookie']['path'], $this->options['cookie']['domain'], $this->options['cookie']['sslonly'], $this->options['cookie']['httponly']);
			session_name($this->options['cookie']['name']);
			if (!@session_start()) {
				throw new StartSessionException();
			}
			if ($this->options['ip']) {
				$this->checkIP();
			}
		}
		$this->id = session_id();
	}

	/**
	 * Getter for session's ID.
	 * 
	 * @return string|null NULL if session isn't started yet.
	 */
	public function getID(): ?string {
		return $this->id;
	}
	
	/**
	 * Get key's value from memory.
	 * It's retrive the data which cached since session started; that data may or may not changed by another process.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key) {
		return $_SESSION[$key] ?? null;
	}

	/**
	 * Set a key-value pair in memory.
	 * It's not necessarily commit the new data in time.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void 
	 */
	public function set(string $key, $value): void {
		$_SESSION[$key] = $value;
	}

	/**
	 * Determine if a key is declared and is different than NULL
	 * 
	 * @param string $key The key to be checked.
	 * @return bool Returns TRUE if key exists and has any value other than NULL. FALSE otherwise. 
	 */
	public function isset(string $key): bool {
		return isset($_SESSION[$key]);
	}

	/**
	 * Unset a given key
	 * 
	 * @param string $key The key to be unset. 
	 * @return void
	 */
	public function unset(string $key): void {
		unset($_SESSION[$key]);
	}

	/**
	 * Delete & destroy session from storage.
	 * 
	 * @return void
	 */
	public function destroy(): void {
		session_destroy();
		http::setcookie($this->options['cookie']['name'], '', 86400);
	}

	/**
	 * Check ip of session's starter must be equals to current user and if it's changed new session create for it.
	 * 
	 * @return void
	 */
	protected function checkIP(): void {
		$ip = $this->get('SESSION_IP') ?? Http::$client['ip'];
		if ($ip !== Http::$client['ip']) {
			session_unset();
		    session_write_close();
		    HTTP::setcookie($this->options['cookie']['name'], '', 0, $this->options['cookie']['path'], $this->options['cookie']['domain'], $this->options['cookie']['sslonly'], $this->options['cookie']['httponly']);
		    session_regenerate_id(false);
		}
	}
}
