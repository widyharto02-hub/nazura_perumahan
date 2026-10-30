<?php

namespace App\Services;

use App\Models\LokasiKavling;
use App\Models\PengaturanProfil;
use Illuminate\Support\Str;
use TCPDF;

class SiteplanPrint
{
    public function download(string $svg, int $locationId, string $type, string $format)
    {
        $location = LokasiKavling::findOrFail($locationId);
        $company = PengaturanProfil::value('nama_perusahaan') ?: 'Siteplan';
        $title = 'Siteplan ' . $type . ' - ' . $location->nama_kavling;
        $filename = 'siteplan-' . Str::slug($type . '-' . $location->nama_kavling);
        [$svg, $width, $height] = $this->prepareSvg($svg);

        if ($format === 'jpg') {
            return response()->view('admin.siteplan.export', compact('svg', 'width', 'height', 'title', 'filename'));
        }

        $pdf = new TCPDF($width > $height ? 'L' : 'P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('Siteplan');
        $pdf->SetAuthor($company);
        $pdf->SetTitle($title);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, $company, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, $title, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, 'Tanggal cetak: ' . now()->translatedFormat('d F Y'), 0, 1, 'C');
        $top = $pdf->GetY() + 5;
        $scale = min(($pdf->getPageWidth() - 20) / $width, ($pdf->getPageHeight() - $top - 10) / $height);
        $printWidth = $width * $scale;
        $pdf->ImageSVG('@' . $this->preparePdfSvg($svg), ($pdf->getPageWidth() - $printWidth) / 2, $top, $printWidth, $height * $scale);

        return response($pdf->Output($filename . '.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function preparePdfSvg(string $svg): string
    {
        // TCPDF reads presentation attributes and inline styles, but ignores SVG stylesheets.
        $document = new \DOMDocument();
        $document->loadXML($svg, LIBXML_NONET);
        $rules = [];
        foreach ($document->getElementsByTagName('style') as $stylesheet) {
            $css = preg_replace('~/\*.*?\*/~s', '', $stylesheet->textContent);
            preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                foreach (explode(',', trim($match[1])) as $selector) {
                    $selector = trim($selector);
                    if (preg_match('/^\.([\w-]+)$/', $selector, $class)) {
                        $rules[] = [$class[1], $this->declarations($match[2])];
                    }
                }
            }
        }

        foreach ($document->getElementsByTagName('*') as $element) {
            $classes = preg_split('/\s+/', trim($element->getAttribute('class')));
            $properties = [];
            foreach ($rules as [$class, $declarations]) {
                if (in_array($class, $classes, true)) {
                    $properties = array_replace($properties, $declarations);
                }
            }
            $properties = array_replace($properties, $this->declarations($element->getAttribute('style')));
            if (isset($properties['font-family']) || in_array($element->localName, ['text', 'tspan'], true)) {
                // Use a built-in PDF font instead of unavailable Corel/desktop fonts.
                $properties['font-family'] = 'helvetica';
            }
            foreach ($properties as $property => $value) {
                $element->setAttribute($property, $value);
            }
            if ($properties) {
                $element->setAttribute('style', implode(';', array_map(
                    fn ($key, $value) => $key . ':' . $value,
                    array_keys($properties), array_values($properties)
                )));
            }
        }

        return $document->saveXML($document->documentElement);
    }

    private function declarations(string $style): array
    {
        $properties = [];
        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $properties[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $properties;
    }

    public function prepareSvg(string $svg): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $valid = $document->loadXML($svg, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        abort_unless($valid && $document->documentElement?->localName === 'svg', 422, 'Denah SVG tidak valid.');
        $root = $document->documentElement;
        $box = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox')));
        $width = isset($box[2]) ? (float) $box[2] : (float) $root->getAttribute('width');
        $height = isset($box[3]) ? (float) $box[3] : (float) $root->getAttribute('height');
        abort_unless($width > 0 && $height > 0 && is_finite($width) && is_finite($height), 422, 'Ukuran denah SVG tidak valid.');

        // Exports are self-contained: never execute scripts or load remote resources.
        foreach (iterator_to_array($document->getElementsByTagName('*')) as $node) {
            if (in_array($node->localName, ['script', 'foreignObject', 'image', 'use', 'style'], true)) {
                if ($node->localName !== 'style') {
                    $node->parentNode?->removeChild($node);
                    continue;
                }
                $node->nodeValue = preg_replace('/@import[^;]+;|url\([^)]*\)/i', '', $node->textContent);
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                if (str_starts_with(strtolower($attribute->name), 'on')
                    || in_array($attribute->localName, ['href', 'src'], true)
                    || preg_match('/url\(\s*[\'"]?(?!#)/i', $attribute->value)) {
                    $node->removeAttributeNode($attribute);
                }
            }
        }
        $root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $root->setAttribute('width', (string) $width);
        $root->setAttribute('height', (string) $height);

        return [$document->saveXML($root), $width, $height];
    }
}
