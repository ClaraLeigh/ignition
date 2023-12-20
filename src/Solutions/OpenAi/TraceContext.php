<?php

namespace Spatie\Ignition\Solutions\OpenAi;

use Spatie\Ignition\ErrorPage\Renderer;
use Spatie\Ignition\Ignition;

class TraceContext
{
    protected int $noOfFiles = 5;

    public function __construct(
        protected array $trace,
    )
    {

    }

    public function getContext(): string
    {
        $this->filterStackTrace();

        return $this->stripEmptyLines(
            $this->compileContext()
        );
    }

    protected function filterStackTrace(): void
    {
        $this->filterClosures();
        $this->filterVendorFiles();
        $this->filterNumberOfFiles();
    }

    protected function compileContext(): string
    {
        return implode("\n\n", array_map(function ($details) {
            // Compile Formatted Args and Code
            $details['args_formatted'] = $this->formatArgs($details['args']);
            $details['code'] = $this->extractFunction(
                $details['function'],
                file_get_contents($details['file'])
            );

            $viewPath = Ignition::viewPath('aiContext');

            return (new Renderer())->renderAsString(
                ['details' => $details],
                $viewPath,
            );

        }, $this->trace));
    }

    protected function extractFunction($function_name, $file_content): string
    {
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

            $code = substr($file_content, $start, $end-$start+1);
            $code = preg_replace('/\/\*.*?\*\//s', '', $code);
            return "```php\n" . $code . "\n```";
        }

        return '';
    }

    protected function stripEmptyLines(string $context): string|array|null
    {
        return preg_replace('/\n{3,}/', "\n\n", $context);
    }

    protected function filterVendorFiles(): void
    {
        // TODO: add config for filtering vendor files
        if (true) {
            return;
        }
        $this->trace = array_filter(
            $this->trace,
            fn($i) => ! str_contains($i['file'], 'vendor')
        );
    }

    protected function filterClosures(): void
    {
        // TODO: Add closures to context
        $this->trace = array_filter(
            $this->trace,
            fn ($i) => isset($i['file'], $i['class'])
        );
    }

    protected function filterNumberOfFiles(): void
    {
        $this->trace = array_slice($this->trace, 0, $this->noOfFiles);
    }

    /**
     * @param $args
     *
     * @return string
     */
    function formatArgs($args): string
    {
        return implode(', ', array_map(function ($arg) {
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
            }, $args))."\n";
    }
}