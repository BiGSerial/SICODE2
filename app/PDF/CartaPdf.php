<?php

namespace App\PDF;

use TCPDF;

class CartaPdf extends TCPDF
{
    public function Header()
    {
        $imageFile = public_path('img/edp-img/edp_documento.png');
        $this->Image($imageFile, 20, 10, '', 20, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }

    public function Footer()
    {
        $version = (object) json_decode(file_get_contents(base_path('appver.json')));
        // Adicione uma linha azul
        $this->SetY(-35);
        $this->SetDrawColor(0, 0, 255); // Cor azul
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());

        // Adicione o texto no rodapé
        $this->SetY(-32);
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 2, 'EDP - ESPÍRITO SANTO DISTRIBUIÇÃO DE ENERGIA S.A.', 0, 1, 'C');
        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 2, 'Rua Florentino Faller Nº 80 - Enseada do Suá', 0, 1, 'C');
        $this->Cell(0, 2, 'Projeto de Redes - Ed. Maxxi I, 2º Piso - Tel: 3348 4226', 0, 1, 'C');
        $this->Cell(0, 2, 'CEP: 29.050 - 310 - Vitória-ES Brasil - Tel: 55 27 3348 4000', 0, 1, 'C');

        // Adicione mais uma linha para separar a informação seguinte
        $this->Ln(1);
        $this->SetFont('helvetica', '', 8);
        // Adicione o texto "Gerado via SICODE V." à esquerda
        $this->Cell(0, 2, 'Gerado via SICODE v.' . $version->appver, 0, 0, 'L');
    }

    public function AddSignatureBlock($posx, $posy)
    {
        // Caminho para a imagem do bloco de assinatura
        $imagePath = public_path('img/edp-img/assinatura_joao.png');

        // Posição e dimensões do bloco de assinatura
        $width  = ''; // Ajuste a largura conforme necessário
        $height = 20;

        $x = $posx;
        $y = $this->getPageHeight() - $posy;

        $this->Image($imagePath, $x, $y, $width, $height);

        // Adicione o texto após a imagem
        $this->SetFont('helvetica', '', 12); // Fonte normal
        $this->SetXY($x, $y + $height); // Defina a posição correta para o texto
        $this->Cell($width, 5, 'Atenciosamente,', 0, 0, 'L');
        $this->Ln(); // Quebra de linha
        $this->Cell($width, 5, 'Joao Paulo Mantovani De Farias', 0, 0, 'L');
        $this->Ln(); // Quebra de linha
        $this->Cell($width, 5, 'Gestor Operacional', 0, 0, 'L');
    }
}
