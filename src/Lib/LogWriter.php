<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/4
 * Time: 11:46
 */

namespace PaymentIntegration\Lib;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Psr\Log\InvalidArgumentException;
use Monolog\Logger as MonologLogger;

class LogWriter
{
    protected $monolog;
    protected $levels = [
        'debug'     => MonologLogger::DEBUG,
        'info'      => MonologLogger::INFO,
        'notice'    => MonologLogger::NOTICE,
        'warning'   => MonologLogger::WARNING,
        'error'     => MonologLogger::ERROR,
        'critical'  => MonologLogger::CRITICAL,
        'alert'     => MonologLogger::ALERT,
        'emergency' => MonologLogger::EMERGENCY,
    ];

    protected $switch = false;
    /**
     * Writer constructor.
     */
    public function __construct()
    {
        $this->monolog = new MonologLogger('payment-integration');
    }

    /**
     * 关闭日志
     */
    public function openLogSwitch()
    {
        $this->switch = true;
    }

    /**
     * @param $level
     * @return mixed
     */
    protected function parseLevel($level)
    {
        if (isset($this->levels[$level])) {
            return $this->levels[$level];
        }

        throw new InvalidArgumentException('Invalid log level.');
    }

    /**
     *
     */
    protected function getDefaultFormatter()
    {
        $formatter = new LineFormatter(null, null, true, true);
        return $formatter;
    }


    /**
     * Register a daily file log handler.
     *
     * @param  string  $path
     * @param  int     $days
     * @param  string  $level
     * @return void
     */
    public function useDailyFiles($path, $days = 0, $level = 'info')
    {
        $this->monolog->pushHandler(
            $handler = new RotatingFileHandler($path, $days, $this->parseLevel($level))
        );
        $handler->setFormatter($this->getDefaultFormatter());
    }

    /**
     * @param $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = [])
    {
        if(!$this->switch) return true;
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * @param $level
     * @param $message
     * @param $context
     */
    protected function writeLog($level, $message, $context)
    {
        $message = $this->formatMessage($message);
        $this->monolog->{$level}($message, $context);
    }

    /**
     * @param $message
     * @return mixed
     */
    protected function formatMessage($message)
    {
        if (is_array($message)) {
            return var_export($message, true);
        }
        return $message;
    }
}