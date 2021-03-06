<?php

namespace NFePHP\CTe\Factories;

use DateTime;
use NFePHP\Common\Keys;
use NFePHP\Common\Signer;
use NFePHP\Common\Strings;

class ContingencyCTe
{
    /**
     * Corrects CTe fields when in contingency mode
     * @param string $xml CTe xml content
     * @param Contingency $contingency
     * @return string
     */
    public static function adjust($xml, Contingency $contingency)
    {
        if ($contingency->type == '') {
            return $xml;
        }
        $xml = Signer::removeSignature($xml);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);

        $ide = $dom->getElementsByTagName('ide')->item(0);
        $cUF = $ide->getElementsByTagName('cUF')->item(0)->nodeValue;
        $cNF = $ide->getElementsByTagName('cCT')->item(0)->nodeValue;
        $nNF = $ide->getElementsByTagName('nCT')->item(0)->nodeValue;
        $serie = $ide->getElementsByTagName('serie')->item(0)->nodeValue;
        $mod = $ide->getElementsByTagName('mod')->item(0)->nodeValue;
        $dtEmi = new DateTime($ide->getElementsByTagName('dhEmi')->item(0)->nodeValue);
        $ano = $dtEmi->format('y');
        $mes = $dtEmi->format('m');
        $tpEmis = (string)$contingency->tpEmis;
        $emit = $dom->getElementsByTagName('emit')->item(0);
        $cnpj = $emit->getElementsByTagName('CNPJ')->item(0)->nodeValue;

        $motivo = trim(Strings::replaceUnacceptableCharacters($contingency->motive));
        $dt = new DateTime();
        $dt->setTimestamp($contingency->timestamp);
        $ide->getElementsByTagName('tpEmis')
            ->item(0)
            ->nodeValue = $contingency->tpEmis;

        //corrigir a chave
        $infCte = $dom->getElementsByTagName('infCte')->item(0);
        $chave = Keys::build(
            $cUF,
            $ano,
            $mes,
            $cnpj,
            $mod,
            $serie,
            $nNF,
            $tpEmis,
            $cNF
        );
        $ide->getElementsByTagName('cDV')->item(0)->nodeValue = substr($chave, -1);
        $infCte->setAttribute('Id', 'CTe' . $chave);

        $qrCode = $dom->getElementsByTagName('qrCodCTe')->item(0);

        // Altera a URL do qrcode
        $qrCode->textContent = self::replace_between($qrCode->textContent, 'chCTe=', '&', $chave);

        return Strings::clearXmlString($dom->saveXML(), true);
    }

    private static function replace_between($str, $needle_start, $needle_end, $replacement) {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);

        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;

        return substr_replace($str, $replacement, $start, $end - $start);
    }
}