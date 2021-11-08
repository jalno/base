<?php
namespace packages\base\session;

interface ISessionHandler {

	/**
	 * Constructor of session handler with project options
	 */
	public function __construct(array $options);
	
	/**
	 * Start session
	 * 
	 * @throws StartSessionException if cannot start session
	 * @return void
	 */
	public function start(): void;

	/**
	 * Getter for session's ID.
	 * 
	 * @return string|null NULL if session isn't started yet.
	 */
	public function getID(): ?string;

	/**
	 * Set a key-value pair in memory.
	 * It's not necessarily commit the new data in time.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void 
	 */
	public function set(string $key, $value): void;

	/**
	 * Get key's value from memory.
	 * It's retrive the data which cached since session started; that data may or may not changed by another process.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key);

	/**
	 * Determine if a key is declared and is different than NULL
	 * 
	 * @param string $key The key to be checked.
	 * @return bool Returns TRUE if key exists and has any value other than NULL. FALSE otherwise. 
	 */
	public function isset(string $key): bool;

	/**
	 * Unset a given key
	 * 
	 * @param string $key The key to be unset. 
	 * @return void
	 */
	public function unset(string $key): void;

	/**
	 * Delete & destroy session from storage.
	 * 
	 * @return void
	 */
	public function destroy(): void;
}
