<?php

namespace App\Service;

use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfGeneratorService
{
    public function __construct(
        private readonly DompdfFactoryInterface $factory,
        private readonly DompdfWrapperInterface $wrapper
    )
    {

    }

    public function getPdf(string $html):string
    {
        return $this->wrapper->getPdf($html);
    }

    public function getStreamResponse(string $html, string $filename):StreamedResponse
    {
    return $this->wrapper->getStreamResponse($html,$filename);
    }

    public function output(string $html): string
    {
        $dompdf = $this->factory->create();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}