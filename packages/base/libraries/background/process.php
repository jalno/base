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
		if($this->checkOS()){
			if(function_exists('shell_exec')){
				if($this->id){
					$command = "php ".realpath(options::get('root_directory').'/index.php').' --process='.$this->id;
					$pid = (int)shell_exec("$command > /dev/null  2>&1 & echo $!");
					if($pid > 0){
						$this->pid = $pid;
						$this->save();
						return true;
					}else{
						throw new cannotStartProcess();
					}
				}else{
					throw new notSavedProcess();
				}
			}else{
				throw new notShellAccess();
			}
		}
	}
	public function setPID(){
		$this->pid = cli::$process['pid'];
		$this->save();
	}
	public function runAndWaitFor($seconds){
		if($this->background_run()){
			$startime = time();
			while((time() - $startime < $seconds) and $this->isRunning());
			if($this->isRunning()){
				return self::running;
			}else{
				$this->byId($this->id);
				return $this->response;
			}
		}
	}
	public function run(){
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
}
