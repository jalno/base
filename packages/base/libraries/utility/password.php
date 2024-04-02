<?php
namespace packages\base\utility;
class password{
	public static function hash($string, $algo = PASSWORD_DEFAULT): string {
        return password_hash($string, $algo);
	}
	static function verify($string, $hash){
		if(function_exists('password_verify')){
			return password_verify($string, $hash);
		}else if(function_exists('hash_equals')){
			return hash_equals($hash, crypt($string, $hash));
		}else{
			return self::hash_equals($hash, crypt($string, $hash));
		}
	}
	/**
     * Timing attack safe string comparison
     *
     * Compares two strings using the same time whether they're equal or not.
     * This function should be used to mitigate timing attacks; for instance, when testing crypt() password hashes.
     * @link http://php.net/manual/en/function.hash-equals.php#115664
     * @param string $known_string The string of known length to compare against
     * @param string $user_string The user-supplied string
     * @return boolean Returns TRUE when the two strings are equal, FALSE otherwise.
     */
    static function hash_equals($known_string, $user_string)
    {
        if (func_num_args() !== 2) {
            // handle wrong parameter count as the native implentation
            trigger_error('hash_equals() expects exactly 2 parameters, ' . func_num_args() . ' given', E_USER_WARNING);
            return null;
        }
        if (is_string($known_string) !== true) {
            trigger_error('hash_equals(): Expected known_string to be a string, ' . gettype($known_string) . ' given', E_USER_WARNING);
            return false;
        }
        $known_string_len = strlen($known_string);
        $user_string_type_error = 'hash_equals(): Expected user_string to be a string, ' . gettype($user_string) . ' given'; // prepare wrong type error message now to reduce the impact of string concatenation and the gettype call
        if (is_string($user_string) !== true) {
            trigger_error($user_string_type_error, E_USER_WARNING);
            // prevention of timing attacks might be still possible if we handle $user_string as a string of diffent length (the trigger_error() call increases the execution time a bit)
            $user_string_len = strlen($user_string);
            $user_string_len = $known_string_len + 1;
        } else {
            $user_string_len = $known_string_len + 1;
            $user_string_len = strlen($user_string);
        }
        if ($known_string_len !== $user_string_len) {
            $res = $known_string ^ $known_string; // use $known_string instead of $user_string to handle strings of diffrent length.
            $ret = 1; // set $ret to 1 to make sure false is returned
        } else {
            $res = $known_string ^ $user_string;
            $ret = 0;
        }
        for ($i = strlen($res) - 1; $i >= 0; $i--) {
            $ret |= ord($res[$i]);
        }
        return $ret === 0;
    }
    static function generate(int $length = 10, bool $number = true, bool $az = true, bool $AZ = true, bool $special = false): string {
        $azChar = "abcdefghijklmnopqrstuvwxyz";
        $AZChar = strtoupper($azChar);
        $numberChar = "0123456789";
        $specialChar = ".-+=_,!@$#*%<>[]{}";
        $uses = array(
            $numberChar => $number,
            $azChar => $az,
            $AZChar => $AZ,
            $specialChar => $special,
        );
        $password = "";
        $parts = 0;
        if($number) $parts++;
        if($az) $parts++;
        if($AZ) $parts++;
        if($special) $parts++;
        for ($i = 0; $i != ceil($length / $parts); $i++) {
            foreach ($uses as $chars => $flag) {
                if (strlen($password) == $length) {
                    break;
                }
                if ($flag) {
                    $password .= substr($chars, rand(0, strlen($chars) -1), 1);
                }
            }
        }
        return $password;
    }
}
?>
