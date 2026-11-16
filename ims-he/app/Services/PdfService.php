<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

/** Renders a Blade view to PDF bytes (Task B5). DejaVu Sans ships with dompdf and has the ₱ sign. */
class PdfService
{
    public function render(string $view, array $data = []): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view($view, $data)->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return $pdf->output();
    }
}
