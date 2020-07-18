<?php
namespace packages\base;
use \packages\base;
use \packages\base\db\dbObject;
class process extends dbObject{
	const SIGHUP = 1;
	const SIGINT = 2;
	const SIGQUIT = 3;
	const SIGILL = 4;
	const SIGTRAP = 5;
	const SIGABRT = 6;
	const SIGBUS = 7;
	const SIGFPE = 8;
	const SIGKILL = 9;
	const SIGUSR1 = 10;
	const SIGSEGV = 11;
	const SIGUSR2 = 12;
	const SIGPIPE = 13;
	const SIGALRM = 14;
	const SIGTERM = 15;
	const SIGSTKFLT = 16;
	const SIGCHLD = 17;
	const SIGCONT = 18;
	const SIGSTOP = 19;
	const SIGTSTP = 20;
	const SIGTTIN = 21;
	const SIGTTOU = 22;
	const SIGURG = 23;
	const SIGXCPU = 24;
	const SIGXFSZ = 25;
	const SIGVTALRM = 26;
	const SIGPROF = 27;
	const SIGWINCH = 28;
	const SIGIO = 29;
	const SIGPWR = 30;
	const SIGSYS = 31;

	const stopped = 0;
	const running = 1;
	const error = 2;
	const OS_WINDOWS = 1;
	const OS_NIX = 2;
	const OS_OTHER = 3;

	protected $dbTable = "base_processes";
	protected $primaryKey = "id";
	protected $dbFields = array(
        'name' => array('type' => 'text', 'required' => true),
		'pid' => array('type' => 'int'),
        'start' => array('type' => 'int'),
        'end' => array('type' => 'int'),
		'parameters' => array('type' => 'text'),
        'response' => array('type' => 'text'),
		'progress' => array('type' => 'int'),
        'status' => array('type' => 'int')
    );
    protected $serializeFields = array('response', 'parameters');
	protected function preLoad($data){
		if($this->isNew){
			if(!isset($data['status'])){
				$data['status'] = self::stopped;
			}
		}
		return $data;
	}
	/**
     * Runs the command in a background process.
     *
     */
	public function background_run(){
		if(!$this->checkOS()){
			throw new notShellAccess();
		}
		if(!function_exists('shell_exec')){
			throw new notShellAccess();
		}
		if(!$this->id){
			throw new notSavedProcess();
		}
		$root_directory = options::get('root_directory');
		$php = options::get('packages.base.process.php-bin');
		if(!$php){
			$php = 'php';
		}
		$command = $php." ".realpath($root_directory.'/index.php').' --process='.$this->id;
		$pid = (int)shell_exec("$command > /dev/null  2>&1 & echo $!");
		if($pid > 0){
			$this->pid = $pid;
			$this->save();
			return true;
		}else{
			throw new cannotStartProcess();
		}
	}
	public function setPID(){
		switch(loader::sapi()){
			case(loader::cli):
				$this->pid = cli::$process['pid'];
				break;
			case(loader::cgi):
				$this->pid = http::pid();
				break;
		}
		$this->save();
	}
	public function runAndWaitFor(int $seconds = 0){
		if($this->background_run()){
			if($this->waitFor($seconds)){
				$this->byId($this->id);
				return $this->response;
			}else{
				return self::running;
			}
		}
	}

	/**
	 * Call a process method.
	 * All runtime throws of process will caught and save in process response.
	 * 
	 * @throws Exception if Could not find process.
	 * @throws Exception if the process is alread running
	 * @return null|Response if process successfully return a Response object, it will return. In case of exception null will return.
	 */
	public function run() {
		list($class,$method) = explode('@',$this->name,2);
		if (!class_exists($class) or !method_exists($class, $method)) {
			throw new Exception("Could not find process:" . $this->name);
		}
		if ($this->status == self::running) {
			throw new Exception("Process #{$this->id} already running");
		}
		$this->status = self::running;
		$this->start = Date::time();
		$this->end = null;
		$this->setPID();
		$this->save();
		$return = null;
		try {
			$obj = new $class();
			$return = $obj->$method($this->parameters);
			if ($return instanceof Response) {
				$this->status = $return->getStatus() ? self::stopped : self::error;
				$this->response = $return;
				if ($return->getStatus()) {
					$this->progress = 100;
				}
			} else {
				$this->status = self::stopped;
			}
		} catch(\Throwable $e) {
			$this->status = Process::error;
			if ($e instanceof Error) {
				$e->setTraceMode(Error::SHORT_TRACE);
			}
            $this->response = $e;
	    }
		$this->end = Date::time();
		$this->save();
		return $return;
	}

	/**
     * Returns if the process is currently running.
     *
     * @return bool TRUE if the process is running, FALSE if not.
     */
    public function isRunning(){
        if($this->checkOS()){
			if($this->pid){
	            return file_exists('/proc/'.$this->pid);
			}else{
				throw new notStartedProcess();
			}
		}
	}
	/**
	 * Returns if the process interrupted by anthor process.
	 * 
	 * @return bool
	 */
	protected function isInterrupted(): bool {
		if (!$this->pid) {
			throw new notStartedProcess();
		}
		return cache::get("packages.base.process.".$this->pid.".interrupt") == 1;
	}
	/**
	 * throws exceptions if the process interrupted by anthor process.
	 * 
	 * @throws packages\base\InterruptedException
	 * @return void
	 */
	protected function checkInterruption() {
		if ($this->isInterrupted()) {
			throw new InterruptedException();
		}
	}
	/**
	 * Interrupt anthor process to stop or pause it.
	 * 
	 * @return void
	 */
	public function interrupt() {
		if (!$this->pid) {
			throw new notStartedProcess();
		}
		cache::set("packages.base.process.".$this->pid.".interrupt", 1);
	}
	/**
     * Stops the process.
     *
     * @return bool `true` if the processes was stopped, `false` otherwise.
     */
    public function stop($signal = self::SIGTERM, $timeout = 10) {
		if(isset($this->pid)){
		    if ( $this->isRunning() ) {
		        shell_exec("kill -".$signal." ".$this->pid.' 2>&1');
		        $start = time();
				while((time() - $start < $timeout) and $this->isRunning());
				return !$this->isRunning();
		    }
        }else{
			throw new notStartedProcess();
		}
        return false;
    }
	protected function checkOS(){
		if(self::getOS() != self::OS_NIX){
			throw new OSSupport();
		}
		return true;
	}
	protected function progress($progress){
		$this->progress += $progress;
		$this->save();
	}
	static function getOS(){
		$os = strtoupper(PHP_OS);
        if (substr($os, 0, 3) === 'WIN') {
            return self::OS_WINDOWS;
        } else if ($os === 'LINUX' || $os === 'FREEBSD' || $os === 'DARWIN') {
            return self::OS_NIX;
        }
        return self::OS_OTHER;
	}
	public function waitFor(int $timeout = 0, bool $throwable = true): bool {
		$ftime = time();
		while($this->isRunning() and ($timeout == 0 or time() - $ftime < $timeout)){
			usleep(250000);
		}
		$this->where("id", $this->id);
		$this->getOne();
		if ($throwable and $this->response instanceof \Exception) {
	        throw $this->response;
	    }
		return !$this->isRunning();
	}
}
