<?php

declare(strict_types=1);

namespace App\Test\TestSuite;

use TCPDF;

/**
 * TCPDF keeping the texts written, to check a document without parsing it
 */
class RecordingPdf extends TCPDF
{
    /**
     * @var string[]
     */
    public array $texts = [];

    /**
     * MultiCell() and writeHTMLCell() call Cell(): only the outer call is kept
     */
    private int $depth = 0;

    public function __construct()
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName -- TCPDF method
    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M')
    {
        $this->record($txt);
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName -- TCPDF method
    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = null, $y = null, $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false)
    {
        $this->record($txt);
        $this->depth++;
        try {
            return parent::MultiCell($w, $h, $txt, $border, $align, $fill, $ln, $x, $y, $reseth, $stretch, $ishtml, $autopadding, $maxh, $valign, $fitcell);
        } finally {
            $this->depth--;
        }
    }

    public function writeHTMLCell($w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true)
    {
        $this->record($html);
        $this->depth++;
        try {
            parent::writeHTMLCell($w, $h, $x, $y, $html, $border, $ln, $fill, $reseth, $align, $autopadding);
        } finally {
            $this->depth--;
        }
    }

    private function record(mixed $text): void
    {
        if ($this->depth === 0) {
            $this->texts[] = (string)$text;
        }
    }

    public function allTexts(): string
    {
        return implode("\n", $this->texts);
    }
}
