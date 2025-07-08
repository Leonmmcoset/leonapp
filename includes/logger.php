<?php
/**
 * Logging utility for the application
 * Provides log_info() and log_error() functions
 */

if (!function_exists('log_info')) {
    /**
     * Log informational message
     * @param string $message The message to log
     * @param string $file Optional file name where the log was called
     * @param int $line Optional line number where the log was called
     */
    function log_info($message, $file = '', $line = 0) {
        $logFile = __DIR__ . '/../logs/app_' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        $prefix = '[' . date('Y-m-d H:i:s') . '] [INFO]';
        if (!empty($file) && $line > 0) {
            $prefix .= ' [' . basename($file) . ':' . $line . ']';
        }
        
        $logMessage = $prefix . ' ' . $message . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

if (!function_exists('log_error')) {
    /**
     * Log error message
     * @param string $message The error message to log
     * @param string $file Optional file name where the error occurred
     * @param int $line Optional line number where the error occurred
     */
    function log_error($message, $file = '', $line = 0) {
        $logFile = __DIR__ . '/../logs/error_' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        $prefix = '[' . date('Y-m-d H:i:s') . '] [ERROR]';
        if (!empty($file) && $line > 0) {
            $prefix .= ' [' . basename($file) . ':' . $line . ']';
        }
        
        $logMessage = $prefix . ' ' . $message . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
?>