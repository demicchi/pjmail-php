<?php

namespace StudioDemmys\pjmail;

class ReadMailFromStdin
{
    protected string $data = "";
    protected int $seconds = 30;
    protected int $microseconds = 0;
    protected int $bytes_to_read = 0;
    
    public function __construct()
    {
        $this->data = "";
        stream_set_blocking(STDIN, false);
    }
    
    public function setTimeout(int $seconds = 30, int $microseconds = 0): void
    {
        $this->seconds = $seconds;
        $this->microseconds = $microseconds;
    }
    
    public function read(int $max_bytes_to_read): string
    {
        $this->bytes_to_read = $max_bytes_to_read;
        $read = [STDIN];
        $write = null;
        $except = null;
        Logging::debug("start reading a mail from stdin.");
        while (($this->bytes_to_read > 0) && stream_select($read, $write, $except, $this->seconds, $this->microseconds)) {
            $buffer = fread(STDIN, $this->bytes_to_read);
            $read_size = strlen($buffer);
            Logging::debug("  read: " . $buffer);
            Logging::debug("  length: " . strlen($buffer));
            if ($read_size == 0) {
                Logging::debug("reached the end.");
                $this->bytes_to_read = 0;
                break;
            }
            $this->bytes_to_read -= $read_size;
            $this->data .= $buffer;
            
            $endpos = strpos($this->data, "\r\n.\r\n");
            
            if ($endpos !== false) {
                Logging::debug("the mail data ended.");
                $this->bytes_to_read = 0;
                $this->data = substr($this->data, 0, $endpos + 6);
                break;
            }
        }
        return $this->data;
    }
}