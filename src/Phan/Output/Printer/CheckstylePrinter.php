<?php declare(strict_types = 1);
namespace Phan\Output\Printer;

use Phan\IssueInstance;
use Phan\Output\BufferedPrinterInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CheckstylePrinter implements BufferedPrinterInterface
{

    /** @var OutputInterface */
    private $output;

    /** @var string[][] */
    private $files = [];

    /** @param IssueInstance $instance */
    public function print(IssueInstance $instance)
    {
        if (empty($this->files[$instance->getFile()])) {
            $this->files[$instance->getFile()] = [];
        }

        // Group issues by file
        $this->files[$instance->getFile()][] = [
            'line' => $instance->getLine(),
            'source' => $instance->getIssue()->getType(),
            'message' => $instance->getMessage(),
            'severity' => $instance->getIssue()->getSeverityName(),
        ];
    }

    /** flush printer buffer */
    public function flush()
    {
        $document = new \DomDocument('1.0', 'ISO-8859-15');

        $checkstyle = new \DOMElement('checkstyle');
        $document->appendChild($checkstyle);
        $checkstyle->appendChild(new \DOMAttr('version', '6.5'));

        // Write each file to the DOM
        foreach ($this->files as $file_name => $error_list) {
            $file = new \DOMElement('file');
            $checkstyle->appendChild($file);
            $file->appendChild(new \DOMAttr('name', $file_name));

            // Write each error to the file
            foreach ($error_list as $error_map) {
                $error = new \DOMElement('error');
                $file->appendChild($error);

                // Write each element of the error as an attribute
                // of the error
                foreach ($error_map as $key => $value) {
                    $error->appendChild(
                        new \DOMAttr($key, (string)$value)
                    );
                }
            }
        }

        $this->output->write($document->saveXML());
        $this->files = [];
    }

    /**
     * @param OutputInterface $output
     */
    public function configureOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
}
