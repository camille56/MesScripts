<?php

namespace App\Service;

use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;

class PdfGeneratorService
{
public function __construct(private readonly DompdfFactoryInterface $factory)
{

}

public function output(string $html): string{
    $dompdf = $this->factory->create();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    return $dompdf->output();
}
}