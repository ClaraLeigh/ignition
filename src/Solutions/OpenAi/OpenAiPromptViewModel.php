<?php

namespace Spatie\Ignition\Solutions\OpenAi;

class OpenAiPromptViewModel
{
    public function __construct(
        protected string $file,
        protected array $trace,
        protected string $exceptionMessage,
        protected string $exceptionClass,
        protected string $snippet,
        protected string $line,
        protected string|null $applicationType = null,
    ) {
    }

    public function file(): string
    {
        return $this->file;
    }

    public function relatedFiles(): string
    {
        // Loop through all files in the trace, if they have the file array and then output them in a list
        $files = array_filter($this->trace, function ($frame) {
            return isset($frame['file']) && isset($frame['class']);
        });
        $details = array_map(function ($frame) {
            return [
                'class' => $frame['class'],
                'filename' => $frame['file'],
                'line' => $frame['line'],
                'args' => $frame['args'],
                'function' => $frame['function'],
            ];
        }, $files);
        if (false) { // TODO : allow config value to hide vendor files
            // Strip out any files with the word vendor in them
            $files = array_filter($files, function ($file) {
                return strpos($file, 'vendor') === false;
            });
        }
        // Only get the last 5 files
        $files = array_slice($files, -5);
        // Compile the contents of the files into a string, separated by newlines. Wrap each file in markdown code blocks.
        $files = implode("\n\n", array_map(function ($details) {
            $content = file_get_contents($details['file']);
            // Only get the contents of the function that is being called along with the line number, args, and function name
            $output = "Class: " . $details['class'] . "\n";
            $output .= "Function: " . $details['function'] . "\n";
            $output .= "Line: " . $details['line'] . "\n";
            // convert args array to string, being mindful of the fact that the args array can contain objects
            $output .= "Args: " . implode(', ', array_map(function ($arg) {
                    if (is_object($arg)) {
                        return get_class($arg);
                    }
                    if (is_array($arg)) {
                        return implode(', ', array_map(function ($arg) {
                            if (is_object($arg)) {
                                return get_class($arg);
                            }
                            if (is_array($arg)) {
                                return 'Array';
                            }
                            return $arg;
                        }, $arg));
                    }
                    return $arg;
                }, $details['args'])) . "\n";
            // Only get the contents of the function that is being called
            $function = $details['function'];
            $content = $this->extract_function($function, $content);
            // Strip out all comments
            $content = preg_replace('/\/\*.*?\*\//s', '', $content);
            // Strip out php open and close tage
            $content = preg_replace('/<\?(php)?/', '', $content);
                        $content = preg_replace('/\?>/', '', $content);
            // strip out all use statements
            $content = preg_replace('/use .*?;/', '', $content);
            $output .= "\nCode:\n```php\n" . $content . "\n```";
            return $output;
        }, $files));

        // When there is multiple empty lines, replace them with a single empty line
        $files = preg_replace('/\n{3,}/', "\n\n", $files);

        return $files;
    }

    protected function extract_function($function_name, $file_content) {
        $start = strpos($file_content, 'function '.$function_name);
        if ($start !== false) {
            $brace_count = 0;
            $end = $start;
            for ($i = $start, $iMax = strlen($file_content); $i < $iMax; $i++) {
                if ($file_content[$i] === '{') {
                    $brace_count++;
                } else if ($file_content[$i] === '}') {
                    $brace_count--;
                    if ($brace_count === 0) {
                        $end = $i;
                        break;
                    }
                }
            }

            return substr($file_content, $start, $end-$start+1);
        }

        return null;
    }

    public function line(): string
    {
        return $this->line;
    }

    public function snippet(): string
    {
        return $this->snippet;
    }

    public function exceptionMessage(): string
    {
        return $this->exceptionMessage;
    }

    public function exceptionClass(): string
    {
        return $this->exceptionClass;
    }

    public function applicationType(): string|null
    {
        return $this->applicationType;
    }
}
