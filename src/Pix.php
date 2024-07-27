<?php

namespace Welves\LaravelPix;



use chillerlan\QRCode\QRCode;
use Welves\LaravelPix\Tools\Arr;
use Welves\LaravelPix\Tools\PixTools;

class Pix
{

    private $data;


    private $px;


    public function get($data)
    {


        return [
            'payload' => $this->payload($data),
            'qrCode' => $this->qrCode($data),
        ];
    }

    public function qrCode($data, $size = 200)
    {
        $this->format($data);

        $hash = (new QRCode)->render($this->mount());

        return '<img src="' . $hash . '" alt="QR Code" style="width:' . $size . 'px;height:' . $size . 'px;"/>';
    }

    public function payload($data)
    {
        $this->format($data);

        return $this->mount();
    }

    public function format($data)
    {

        $this->setDefaults();
        $this->validateKey($data);
        $this->setIsUnique($data);
        $this->setValue($data);
        $this->setBeneficiary($data);
        $this->setDescription($data);
        $this->setCity($data);
        $this->setIdentificador($data);
    }

    private function setDescription($data)
    {
        if (Arr::has($data, 'descricao')) {

            $description = $data['descricao'];

            if (strlen($description) >= 26) {
                throw new \Exception('Descrição do pagamento. Máximo: 25 caracteres.');
            }
            $this->px[62][04] = $description;
        }
    }


    private function setIdentificador($data)
    {
        if (Arr::has($data, 'identificador')) {

            $identificador = $data['identificador'];

            if (strlen($identificador) >= 26) {
                throw new \Exception('Identificador de transação. Máximo: 25 caracteres.');
            }
            $this->px[62][05] = $identificador;
        }

        $this->px[62][05] = "***";
    }




    private function setCity($data)
    {
        if (Arr::has($data, 'cidade')) {

            $city = $data['cidade'];

            if (strlen($city) >= 16) {
                throw new \Exception('Nome da cidade. Máximo: 15 caracteres.');
            }
            $this->px[60] = $city;
        }
    }



    private function setBeneficiary($data)
    {
        if (Arr::has($data, 'beneficiario')) {

            $beneficiary = $data['beneficiario'];

            if (strlen($beneficiary) >= 26) {
                throw new \Exception('Nome do beneficiário/recebedor. Máximo: 25 caracteres.');
            }
            $this->px[59] = $beneficiary;
        }
    }




    private function setValue($data)
    {
        if (Arr::has($data, 'valor')) {

            $value = $data['valor'];


            if (strlen($value) >= 13) {
                throw new \Exception('Valor máximo é de 13 caracteres');
            }
            $this->px[54] = $value;
        }
    }



    private function setIsUnique($data)
    {
        if (Arr::has($data, 'unico') && $data['unico'] == true) {
            $this->px[01] = "12";
        }
    }



    private function validateKey($data)
    {
        $key  = Arr::get($data, 'chave');
        $type = Arr::get($data, 'tipo_chave', 'aleatoria');

        if ($type == 'cpf') {

            $cpf = preg_replace('/[^0-9]/', '', $key);

            $this->px[26][01] = $cpf;

            if (strlen($cpf) != 11) {
                throw new \Exception('CPF inválido');
            }
        }


        if ($type == 'cnpj') {

            $cnpj = preg_replace('/[^0-9]/', '', $key);

            $this->px[26][01] = $cnpj;

            if (strlen($cnpj) != 14) {
                throw new \Exception('CNPJ inválido');
            }
        }

        if ($type == 'email') {

            $this->px[26][01] = $key;

            if (!filter_var($key, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('E-mail inválido');
            }
        }

        if ($type == 'telefone') {

            $telefone = preg_replace('/[^0-9]/', '', $key);

            $this->px[26][01] = '+55' . $telefone;

            if (strlen($telefone) < 10 || strlen($telefone) > 11) {
                throw new \Exception('Telefone inválido');
            }
        }

        if ($type == 'aleatoria') {

            $this->px[26][01] = $key;
        }
    }



    private function setDefaults()
    {
        $this->px[00] = "01";
        $this->px[26][00] = "BR.GOV.BCB.PIX"; //Indica arranjo específico; “00” (GUI) obrigatório e valor fixo: br.gov.bcb.pix
        $this->px[52] = "0000"; //Merchant Category Code “0000” ou MCC ISO18245
        $this->px[53] = "986"; //Moeda, “986” = BRL: real brasileiro - ISO4217
        $this->px[58] = "BR"; //“BR” – Código de país ISO3166-1 alpha 2

    }






    public function mount()
    {
        $pix = (new PixTools())->mount($this->px);
        $pix .= "6304";
        $pix .= (new PixTools())->crcChecksum($pix);
        return $pix;
    }
}
