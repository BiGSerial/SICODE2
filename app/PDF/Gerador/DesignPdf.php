<?php

namespace App\PDF\Gerador;

use TCPDF;

class DesignPdf extends TCPDF
{
    protected ?SicodePdf $fillcontent = null;

    protected $pdf;

    protected $version;

    public function __construct(SicodePdf $content)
    {
        $this->fillcontent = $content;
        $this->version     = (object) json_decode(file_get_contents(base_path('appver.json')));
    }

    public function Header()
    {
        $imageFile = public_path('img/edp-img/edp_documento.png');
        $this->pdf->Image($imageFile, 15, 13, '', 20, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }

    public function useEmpreiteira()
    {
        $this->pdf = new TCPDF();
        $this->pdf->setPageFormat('A4', 'P');
        $this->pdf->addPage();
        $this->pdf->SetHeaderData('', 0, 'Seu Cabeçalho', 'Sistema de PDF - ' . date('Y-m-d H:i:s'));
        $this->pdf->setHeader($this->Header());

        $this->pdf->SetLineWidth(0.8);
        $this->pdf->Line(10, 10, 200, 10);
        $this->pdf->Line(10, 10, 10, 287);
        $this->pdf->Line(10, 287, 200, 287);
        $this->pdf->Line(200, 10, 200, 287);

        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Line(10, 35, 200, 35);

        $this->pdf->setXY(75, 15);
        $this->pdf->SetFont('', 'B', 20);
        $this->pdf->Cell(115, 0, 'CHECKLIST - VIABILIDADE', 0, 2, 'C');

        $this->pdf->SetFont('', 'N', 10);
        $this->pdf->Cell(115, 0, "Sistema para Controle de Demandas (SICODE v{$this->version->appver})", 0, 2, 'C');

        // Label
        $this->pdf->setXY(10, 40);
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(20, 0, 'OV/NOTA:', 0, 1, 'R');
        // Text
        $this->pdf->setXY(32, 38);
        $this->pdf->SetFont('', 'B', 16);
        $this->pdf->Cell(40, 0, $this->fillcontent->getNote(), 1, 1, 'L');

        // Label
        $this->pdf->setXY(110, 40);
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(20, 0, 'OV/NOTA:', 0, 1, 'R');
        // Text
        $this->pdf->setXY(132, 38);
        $this->pdf->SetFont('', 'B', 12);
        if (is_array($this->fillcontent->getOrdem()) && count($this->fillcontent->getOrdem()) > 0) {
            foreach ($this->fillcontent->getOrdem() as $value) {
                $this->pdf->Cell(40, 1, $value, 1, 3, 'L');
            }

        }

        // Label
        $this->pdf->setXY(10, 48);
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(20, 0, 'CLIENTE:', 0, 1, 'R');
        // Text
        $this->pdf->setXY(32, 48);
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(80, 0, $this->fillcontent->getName_client(), 1, 1, 'L');

        $this->pdf->Output('teste.pdf', 'I');

    }
}
